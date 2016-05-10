<?php

/*##############  設定ファイルの読み込み  ##############*/
require_once __DIR__ . DIRECTORY_SEPARATOR . 'settings.ini.php';

/*##############  ユーザリストの初期化  ##############*/
$user_list = createUserList(
    $initial_player_settings,
    $rank_settings
);
error_log("total_user_count:" . count($user_list));

/*##############  サマリー格納先の初期化  ##############*/
// レーティングポイントのサマリー格納先
$summary = [];
// 移動したレーティングポイント情報の格納先の配列の初期化
$move_point_summary = [];
$move_point_summary_for_top_user = [];
// レーティングポイント帯に応じた、サマリー格納先の初期化。
$point_group_summary = initPointRangeSummary();
// 階級移動のサマリー
$move_rank_summary = [];

// マッチングの為に、ランク毎にユーザを分類。TERM毎に再分類。
$user_list_per_rank = [];

/*##############  戦闘処理  ##############*/
for ($t = 0; $t < TERM_COUNT; $t++) {
    $user_list_per_rank = extractUserListPerRank($rank_settings, $user_list);
    for ($d = 0; $d < BATTLE_DAY_COUNT_PER_TERM; $d++) {
        for ($u = 1; $u <= count($user_list); $u++) {
            $atk_user = $user_list[$u];
            processBattle(
                $user_list,
                $user_list_per_rank,
                $atk_user,
                $play_degree_battle_count_map,
                $strength_win_additional_rate_map,
                $move_point_summary,
                $move_point_summary_for_top_user,
                $rank_settings
            );
        }
    }
    moveRank($user_list, $rank_settings, $t, $move_rank_summary);
}

/*##############  サマライズ  ##############*/
summarizeUserData($user_list, $summary, $point_group_summary);

/*##############  表示  ##############*/

/*
ksort($summary);
foreach ($summary as $fields) {
    fputcsv(STDOUT, $fields);
}
*/
print "moved_point, count\n";
ksort($move_point_summary);
foreach ($move_point_summary as $fields) {
    fputcsv(STDOUT, $fields);
}

print "\n";
print "moved_point_for_top_user, count\n";
ksort($move_point_summary_for_top_user);
foreach ($move_point_summary_for_top_user as $fields) {
    fputcsv(STDOUT, $fields);
}
foreach ($move_rank_summary as $fields) {
    fputcsv(STDOUT, $fields);
}

print "\n";
print "min_point, max_point, user_num, avg_strength, avg_play_degree, avg_battle_count, avg_initial_point\n";
foreach ($point_group_summary as $fields) {
    fputcsv(STDOUT, $fields);
}
/*##############  おしまい  ##############*/



/*##############  以下はメソッド群  ##############*/
function pickupTargetUser($atk_user, $user_list_per_rank, $rank_settings)
{
    $rank_id = $atk_user['rank_id'];
    $rand = null;
    $is_exists_upper_rank_user = (isset($user_list_per_rank[$rank_id + 1]) && !empty($user_list_per_rank[$rank_id + 1])) ? true : false;
    if ($is_exists_upper_rank_user) {
        $rand = mt_rand(0, 100);
        $target_rank_id = ($rand <= $rank_settings[$rank_id]['upper_target_rate'] * 100) ? $rank_id + 1 : $rank_id;
    } else {
        $target_rank_id = $rank_id;
    }
    $total_count = count($user_list_per_rank[$target_rank_id]);
    if ($total_count == 1) {
        if ($is_exists_upper_rank_user) {
            $target_rank_id++;
        } else {
            $target_rank_id--;
        }
    }

    $rand = null;
    // 5回以上抽選して該当者がいなかったら、強制的に選択する(ちょっと精度落ちるかも)
    $force_counter = 0;
    while ($rand == null) {
        $rand = (int)mt_rand(0, $total_count - 1);
        if (!isset($user_list_per_rank[$target_rank_id][$rand])) {
            $target_rank_id--;
            $total_count = count($user_list_per_rank[$target_rank_id]);
            continue;
        }
        // 自分自身ははじく。
        if ($user_list_per_rank[$target_rank_id][$rand]['uid'] == $atk_user['uid']) {
            $rand = null;
            continue;
        }
        if ($force_counter == 5) {
            break;
        }

        // 策的モードとの合致チェック
        switch (CURRENT_TARGET_TYPE) {
        case TARGET_TYPE_EQUAL_OR_LESS:
            if ($atk_user['strength'] < $user_list_per_rank[$target_rank_id][$rand]['strength']) {
                $rand = null;
            }
            break;
        case TARGET_TYPE_EQUAL:
            if ($atk_user['strength'] != $user_list_per_rank[$target_rank_id][$rand]['strength']) {
                $rand = null;
            }
            break;
        case TARGET_TYPE_GREATER_ONE:
            if (($atk_user['strength'] + 1) < $user_list_per_rank[$target_rank_id][$rand]['strength']) {
                $rand = null;
            }
            break;
        case TARGET_TYPE_ALL:
            // do nothing
            break;
        }
        $force_counter++;
    }
    return $user_list_per_rank[$target_rank_id][$rand];
}

function battle($atk_user, $def_user, $strength_win_additional_rate_map)
{
    $rand = mt_rand(0, 100) / 100;
    $win_rate = BASE_WIN_RATE + $strength_win_additional_rate_map[$atk_user['strength']] - $strength_win_additional_rate_map[$def_user['strength']];
    return ($win_rate >= $rand) ? true : false;
}

function calcEloratingPoint($is_attacker_win, $atk_user, $def_user, $rank_setting)
{
    // 対戦相手のポイント - 自身のポイントを差し引く.レーティング評価の基準点.
    $diff = $def_user['point'] - $atk_user['point'] - RATINGPOINT_DIFF_HANDICAP;
    if ($is_attacker_win) {
        $base_val = $rank_setting['base_point_win'];
        $coef_base = 1;
        $scale = $rank_setting['scale_win'];
    } else {
        $base_val = $rank_setting['base_point_lose'];
        $coef_base = 0;
        $scale = $rank_setting['scale_lose'];
    }
    // 点差最大時を1とした、点差を元にしたポイント補正係数。点差マイナスで最大だと-1。
    $coef_value = $coef_base - 1 / (1 + pow(10, ($diff / $scale)));
    $move_point = (int)ceil($base_val * $coef_value);
    // 勝利時は、階級毎に定義される最小値を下回る場合には調整をかける。
    return ($is_attacker_win) ? max($rank_setting['guaranteed_point'], $move_point) : $move_point;
}

function createUserList($initial_player_settings, $rank_settings)
{
    $user_list = [];
    $user_id = 1;

    foreach ($initial_player_settings as $rank_id => $setting) {
        $current_rank_setting = $rank_settings[$rank_id];
        $upper_rank_threshold = (isset($initial_player_settings[$rank_id + 1])) ? $rank_settings[$rank_id + 1]['rankup_threshold'] : $setting['max_point'];
        // ユーザにレーティングポイントを割り振るときのオフセット
        $point_offset = floor(($upper_rank_threshold - $current_rank_setting['rankup_threshold']) / $setting['number']);
        $tmp_user_list = [];
        $counter_per_rank = 0;

        $strength = min($setting['strength_range']);
        $play_degree = min($setting['play_degree_range']);
        while (count($tmp_user_list) < $setting['number']) {
            $user = [];
            $user['point']          = $current_rank_setting['rankup_threshold'] + ($counter_per_rank * $point_offset);
            $user['initial_point']  = $user['point'];
            $user['battle_count']   = 0;
            $user['uid']            = $user_id;
            $user['strength']       = $strength;
            $user['play_degree']    = $play_degree;
            $user['rank_id']        = $rank_id;
            $play_degree++;
            if ($play_degree > max($setting['play_degree_range'])) {
                $play_degree = min($setting['play_degree_range']);
                $strength++;
            }
            if ($strength > max($setting['strength_range'])) {
                $strength = min($setting['strength_range']);
            }
            $tmp_user_list[$user_id] = $user;
            $user_id++;
            $counter_per_rank++;
        }
error_log("$rank_id : " . count($tmp_user_list));
        $user_list += $tmp_user_list;
    }

    return $user_list;
}

function initPointRangeSummary()
{
    $point_group_summary = [];
    for ($min_point = SUMMARY_MIN_RATING; $min_point < SUMMARY_MAX_RATING; $min_point += SUMMARY_RATING_THRESHOLD) {
        $point_group_summary[] = [
            'min_point'         => $min_point,
            'max_point'         => $min_point + SUMMARY_RATING_THRESHOLD,
            'count'             => 0,
            'avg_strength'      => 0,
            'avg_play'          => 0,
            'avg_battle_count'  => 0,
            'avg_initial_point' => 0,
        ];
    }
    return $point_group_summary;
}

function processBattle(
    &$user_list,
    $user_list_per_rank,
    $atk_user,
    $play_degree_battle_count_map,
    $strength_win_additional_rate_map,
    &$move_point_summary,
    &$move_point_summary_for_top_user,
    $rank_settings
) {
    $v = $atk_user['uid'];
    // そのユーザが1日にプレイする回数分
    for ($battle_count = 0; $battle_count < $play_degree_battle_count_map[$atk_user['play_degree']]; $battle_count++) {
        $target_user = pickupTargetUser($atk_user, $user_list_per_rank, $rank_settings);
        // 戦闘処理
        $is_attacker_win = battle($atk_user, $target_user, $strength_win_additional_rate_map);
        // 攻め手のポイント移動. 敗北時は負数.
        $move_point = calcEloratingPoint($is_attacker_win, $atk_user, $target_user, $rank_settings[$atk_user['rank_id']]);
        $revised_move_point = ($is_attacker_win) ? $move_point * ACCOMPANY_DEFEAT_BONUS_FOR_WINNER : $move_point * $rank_settings[$atk_user['rank_id']]['lose_point_rate'];
        $user_list[$v]['point'] += $revised_move_point;
        $user_list[$v]['battle_count'] += 1;
        // 守り手のポイント移動
        // 二試合に1回はシールドタイム中という取り扱いにする
        $is_shield_time = false;
        if (ENABLE_SHIELD_TIME) {
            $is_shield_time = (mt_rand(1, 100) > SHIELD_TIME_OCCUR_RATE) ? true : false;
        }
        if (!$is_shield_time) {
            $move_point_of_defender = calcEloratingPoint(!$is_attacker_win, $target_user, $atk_user, $rank_settings[$target_user['rank_id']]);
            $user_list[$target_user['uid']]['point'] -= ($is_attacker_win) ?
                $move_point * ACCOMPANY_DEFEAT_BONUS_FOR_LOSER :
                $move_point_of_defender * $rank_settings[$target_user['rank_id']]['lose_point_rate'];
            $user_list[$target_user['uid']]['battle_count'] += 1;
        }
        // 統計情報を保存
        if (!isset($move_point_summary[$revised_move_point])) {
            $move_point_summary[$revised_move_point] = [
                'point' => $revised_move_point,
                'count' => 0,
            ];
        }
        $move_point_summary[$revised_move_point]['count'] += 1;

        // トップユーザのポイント情報を記録する
        if ($atk_user['strength'] == 5 && $atk_user['play_degree'] == 5) {
            if (!isset($move_point_summary_for_top_user[$revised_move_point])) {
                $move_point_summary_for_top_user[$revised_move_point] = [
                    'point' => $revised_move_point,
                    'count' => 0,
                ];
            }
            $move_point_summary_for_top_user[$revised_move_point]['count'] += 1;
        }
    }
}

function extractUserListPerRank($rank_settings, $user_list)
{
    $user_list_per_rank = [];
    foreach ($rank_settings as $rank_id => $setting) {
        $tmp_list = [];
        foreach ($user_list as $uid => $user) {
            if ($user['rank_id'] == $rank_id) {
                $tmp_list[] = $user;
            }
        }
        $user_list_per_rank[$rank_id] = $tmp_list;
    }
    return $user_list_per_rank;
}

function moveRank(&$user_list, $rank_settings, $timestamp, &$move_rank_summary)
{
    foreach ($rank_settings as $rank_id => $rank_setting) {
        if (isset($rank_setting['unlock_term']) && $rank_setting['unlock_term'] > $timestamp) {
            unset($rank_settings[$rank_id]);
        }
    }

    krsort($rank_settings);
    $rankup_summary = [];
    foreach ($rank_settings as $rank_id => $rank_setting) {
        $rankup_summary[$rank_id] = 0;
        foreach ($user_list as $uid => $user) {
            // 未更新で、かつ、該当するユーザのみを昇格させる。
            if ((!isset($user['last_update']) || $user['last_update'] != $timestamp)
                && ($user['point'] > $rank_setting['rankup_threshold'])
            ) {
                $user_list[$uid]['rank_id']     = $rank_id;
                $user_list[$uid]['last_update'] = $timestamp;
                $rankup_summary[$rank_id] += 1;
            }
        }
    }
    ksort($rankup_summary);
    $move_rank_summary[] = $rankup_summary;

    error_log("###### [start]  move rank ######");
    error_log("term : $timestamp");
    error_log(print_r($rankup_summary, true));
    error_log("###### [finish] move rank ######");
}

function summarizeUserData($user_list, &$summary, &$point_group_summary)
{
    foreach ($user_list as $user) {
        if (!isset($summary[$user['point']])) {
            $summary[$user['point']] = [
                'point' => $user['point'],
                'count' => 1,
            ];
        } else {
            $summary[$user['point']]['count'] += 1;
        }

        foreach ($point_group_summary as $key => $point_group) {
            if ($point_group['min_point'] < $user['point'] && $user['point'] <= $point_group['max_point']) {
                $point_group_summary[$key]['count'] += 1;
                break;
            }
        }
    }

    // 平均値の算出
    foreach ($user_list as $user) {
        foreach ($point_group_summary as $key => $point_group) {
            if ($point_group['min_point'] < $user['point'] && $user['point'] <= $point_group['max_point']) {
                $point_group_summary[$key]['avg_strength'] += $user['strength'] / $point_group['count'];
                $point_group_summary[$key]['avg_play'] += $user['play_degree'] / $point_group['count'];
                $point_group_summary[$key]['avg_battle_count'] += $user['battle_count'] / $point_group['count'];
                $point_group_summary[$key]['avg_initial_point'] += $user['initial_point'] / $point_group['count'];
                break;
            }
        }
    }

}

