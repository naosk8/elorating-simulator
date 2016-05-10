<?php

/*##############  シミュレーション対象に関する初期設定  ##############*/
// 階級集計期間に関する設定
const BATTLE_DAY_COUNT_PER_TERM     = 6;    // 集計の1期間の日数
const TERM_COUNT                    = 1;   // 集計回数
const RATINGPOINT_DIFF_HANDICAP     = 0;

/*##############  ユーザの行動傾向の設定  ##############*/
// 策的モード
const TARGET_TYPE_EQUAL_OR_LESS = 0; // 自身以下
const TARGET_TYPE_EQUAL         = 1; // 同じ強さ
const TARGET_TYPE_GREATER_ONE   = 2; // 1つ上の強さまで
const TARGET_TYPE_ALL           = 3; // 誰でもOK
const CURRENT_TARGET_TYPE       = TARGET_TYPE_GREATER_ONE;

/*##############  ユーザの勝率や強さやプレイ頻度に関する設定  ##############*/
// 基本勝率(先行有利なので70%と過程)
const BASE_WIN_RATE = 0.7;
// ユーザの強さを5段階で表現し、最低勝率50% ~ 最大勝率90%となるようにしている。
// 勝率 = 0.7 + 自身の補正値 - 相手の補正値
$strength_win_additional_rate_map = [
    1 => 0,
    2 => 0.05,
    3 => 0.10,
    4 => 0.15,
    5 => 0.20,
];
// プレイ頻度を5段階で表現し、最低1プレイ/日 ~ 最大15プレイ/日と仮定している。
$play_degree_battle_count_map = [
    1 => 1,
    2 => 4,
    3 => 6,
    4 => 9,
    5 => 15,
];

/*##############  シールドタイムの取り扱い  ##############*/
const ENABLE_SHIELD_TIME        = true;
const SHIELD_TIME_OCCUR_RATE    = 50; // 50%

/*##############  統計情報に関する設定値  ##############*/
const SUMMARY_MIN_RATING        = 0;
const SUMMARY_MAX_RATING        = 5000;
const SUMMARY_RATING_THRESHOLD  = 100;

// ガンスピ用のアレンジ
const ACCOMPANY_DEFEAT_BONUS_FOR_WINNER = 1.5;//平均2機撃破
const ACCOMPANY_DEFEAT_BONUS_FOR_LOSER  = 1;

/*##############  イロレーティング計算係数  ##############*/
// 階級マスター
// 伍長以上という前提で調整
$rank_settings = [
    // 上位階級 : ここはランキング形態も異なるので、scaleは別途調整。基本的にゼロサムとする。
    14  => ['rankup_threshold' => 0,   'base_point_win' => 128, 'base_point_lose' => 128, 'scale_win' => 5000,    'scale_lose' => 5000,  'lose_point_rate' => 1, 'upper_target_rate' => 0, 'guaranteed_point' => 0, 'unlock_term' => 0],
];

// ユーザの初期分布設定
// 階級毎に設定。上位階級は淘汰が進んでいるので、属性を絞り込む。
$initial_player_settings = [
    14  => ['number' => 1000,    'play_degree_range' => range(1, 5), 'strength_range' => range(1, 5), 'max_point' => 0],
];

