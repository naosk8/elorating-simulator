elorating-simulator
===================
<h1>なにこれ</h1>
<p>イロレーティングのシミュレーション用バッチ。<br/>
設定値は細かいものの該当バッチの冒頭にて定数定義する形になっているので、<br/>
それを柔軟に変更して検証に用いてください。</p>

<h1>使い方</h1>
<p>以下のように単純に実行するのみ。<br/>
設定項目が多いこともあり、現時点では、引数でのオプション設定には対応していない。</p>
<p>$ php ./elorating-simulator.php</p>

<h1>出力結果の見方</h1>
<h2>レーティングポイントの移動分布</h2>
<p>moved_point : 移動したレーティングポイント<br/>
count : 観察された回数</p>

<h2>最終的なレーティング分布と、ユーザの性質分布の関係</h2>

<p>min_point : 分布確認用のレーティングポイント幅の下端<br/>
max_point : 分布確認用のレーティングポイント幅の上端<br/>
user_num : 特定のレーティングポイント帯に存在するユーザ数<br/>
avg_strength : 特定のレーティングポイント帯のユーザの平均的な強さ<br/>
avg_play_degree : 特定のレーティングポイント帯のユーザの平均的なプレイ頻度<br/>
avg_battle_count : 特定のレーティングポイント帯のユーザの平均的な試合回数(含む防衛)<br/>
avg_initial_point : 特定のレーティングポイント帯のユーザの平均的な初期レーティングポイント</p>

