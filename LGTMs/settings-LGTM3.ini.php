<?php

/*##############  シミュレーション対象に関する初期設定  ##############*/
// 全ユーザ数
const BATTLE_DAY_COUNT_PER_TERM     = 6;    // 集計の1期間の日数
const TERM_COUNT                    = 12;   // 集計回数
const RATINGPOINT_DIFF_HANDICAP     = 0;

/*##############  ユーザの行動傾向の設定  ##############*/
// 策的モード
const TARGET_TYPE_EQUAL_OR_LESS = 0; // 自身以下
const TARGET_TYPE_EQUAL         = 1; // 同じ強さ
const TARGET_TYPE_GREATER_ONE   = 2; // 1つ上の強さまで
const TARGET_TYPE_ALL           = 3; // 誰でもOK
const CURRENT_TARGET_TYPE       = TARGET_TYPE_GREATER_ONE;

/*##############  ユーザの勝率や強さやプレイ頻度に関する設定  ##############*/
// 基本勝率(先行有利なので80%と過程)
const BASE_WIN_RATE = 0.8;
// ユーザの強さを5段階で表現し、最低勝率60% ~ 最大勝率100%となるようにしている。
// 勝率 = 0.7 + 自身の補正値 - 相手の補正値
$strength_win_additional_rate_map = [
    1 => 0,
    2 => 0.05,
    3 => 0.10,
    4 => 0.15,
    5 => 0.20,
];
// プレイ頻度を5段階で表現し、最低2プレイ/日 ~ 最大22プレイ/日と仮定している。（データに基づいてはいる）
$play_degree_battle_count_map = [
    1 => 2,
    2 => 7,
    3 => 12,
    4 => 17,
    5 => 22,
];

/*##############  シールドタイムの取り扱い  ##############*/
const ENABLE_SHIELD_TIME        = true;
const SHIELD_TIME_OCCUR_RATE    = 60; // 60%

/*##############  統計情報に関する設定値  ##############*/
const SUMMARY_MIN_RATING        = 0;
const SUMMARY_MAX_RATING        = 150000;
const SUMMARY_RATING_THRESHOLD  = 1000;

// ガンスピ用のアレンジ
const ACCOMPANY_DEFEAT_BONUS_FOR_WINNER = 1.5;
const ACCOMPANY_DEFEAT_BONUS_FOR_LOSER  = 1;

/*##############  イロレーティング計算係数  ##############*/
// 階級マスター
// 伍長以上という前提で調整
$rank_settings = [
    5   => ['rankup_threshold' => 1000,     'base_point_win' => 128, 'base_point_lose' => 128, 'scale_win' => 1000,     'scale_lose' => 1000,   'lose_point_rate' => 1, 'upper_target_rate' => 0.2, 'guaranteed_point' => 0],
    6   => ['rankup_threshold' => 2500,     'base_point_win' => 160, 'base_point_lose' => 128, 'scale_win' => 2000,     'scale_lose' => 2000,   'lose_point_rate' => 1, 'upper_target_rate' => 0.2, 'guaranteed_point' => 0],
    7   => ['rankup_threshold' => 6000,     'base_point_win' => 160, 'base_point_lose' => 128, 'scale_win' => 7000,     'scale_lose' => 7000,   'lose_point_rate' => 1, 'upper_target_rate' => 0.2, 'guaranteed_point' => 0],
    // 尉官階級
    8   => ['rankup_threshold' => 20000,    'base_point_win' => 192, 'base_point_lose' => 128, 'scale_win' => 10000,    'scale_lose' => 10000,  'lose_point_rate' => 1, 'upper_target_rate' => 0.2, 'guaranteed_point' => 0],
    9   => ['rankup_threshold' => 40000,    'base_point_win' => 192, 'base_point_lose' => 128, 'scale_win' => 20000,    'scale_lose' => 20000,  'lose_point_rate' => 1, 'upper_target_rate' => 0.2, 'guaranteed_point' => 0],
    10  => ['rankup_threshold' => 80000,    'base_point_win' => 192, 'base_point_lose' => 128, 'scale_win' => 10000,    'scale_lose' => 10000,  'lose_point_rate' => 1, 'upper_target_rate' => 0.2, 'guaranteed_point' => 0],
    // 佐官階級
    11  => ['rankup_threshold' => 100000,   'base_point_win' => 192, 'base_point_lose' => 128, 'scale_win' => 10000,    'scale_lose' => 10000,  'lose_point_rate' => 1, 'upper_target_rate' => 0.2, 'guaranteed_point' => 0, 'unlock_term' => 3],
    12  => ['rankup_threshold' => 120000,   'base_point_win' => 192, 'base_point_lose' => 128, 'scale_win' => 10000,    'scale_lose' => 10000,  'lose_point_rate' => 1, 'upper_target_rate' => 0.2, 'guaranteed_point' => 0, 'unlock_term' => 6],
    13  => ['rankup_threshold' => 140000,   'base_point_win' => 192, 'base_point_lose' => 128, 'scale_win' => 10000,    'scale_lose' => 10000,  'lose_point_rate' => 1, 'upper_target_rate' => 0.2, 'guaranteed_point' => 0, 'unlock_term' => 9],
    // 上位階級 : ここはランキング形態も異なるので、scaleは別途調整。基本的にゼロサムとする。
    14  => ['rankup_threshold' => 160000,   'base_point_win' => 128, 'base_point_lose' => 128, 'scale_win' => 10000,    'scale_lose' => 10000,  'lose_point_rate' => 1, 'upper_target_rate' => 0.2, 'guaranteed_point' => 0, 'unlock_term' => 15],
];

// ユーザの初期分布設定
// 階級毎に設定。上位階級は淘汰が進んでいるので、属性を絞り込む。
$initial_player_settings = [
    5   => ['number' => 766,    'play_degree_range' => range(1, 4), 'strength_range' => range(1, 4)],
    6   => ['number' => 2263,   'play_degree_range' => range(1, 4), 'strength_range' => range(1, 4)],
    7   => ['number' => 3093,   'play_degree_range' => range(1, 5), 'strength_range' => range(1, 5)],
    8   => ['number' => 2202,   'play_degree_range' => range(2, 5), 'strength_range' => range(3, 5)],
    9   => ['number' => 1478,   'play_degree_range' => range(2, 5), 'strength_range' => range(4, 5)],
    10  => ['number' => 500,    'play_degree_range' => range(4, 5), 'strength_range' => range(4, 5), 'max_point' => 140000],
];

