<?php
// Nice Rank Plugin
// 
// Nice Pluginにてカウントした「いいね！」の数をランキングとして表示します。
// 
// License: GPL v2 or (at your option) any later version

define('PLUGIN_NICE_RANK_DEFAULT_LINES', 10);                             # 表示する件数
define('PLUGIN_NICE_RANK_HISTORY_CACHE', CACHE_DIR . 'nice_history.dat'); # 履歴の保存先(nice.inc.phpで保存しているファイル)
define('PLUGIN_NICE_RANK_PERIOD_DAYS', 30);                               # 対象期間(nice.inc.phpの履歴の保存期間以下である必要あり)

# タイトル(末尾に表示件数が付く)
define('PLUGIN_NICE_RANK_TITLE', '過去' . PLUGIN_NICE_RANK_PERIOD_DAYS . '日間のいいね！Top ');

function plugin_nice_rank_convert()
{
    global $vars, $date_format;

    $rank_lines = PLUGIN_NICE_RANK_DEFAULT_LINES;
    if (func_num_args()) {
        $args = func_get_args();
        if (is_numeric($args[0])) {
            $rank_lines = $args[0];
        }
    }

    if (file_exists(PLUGIN_NICE_RANK_HISTORY_CACHE)) {
        $lines = file(PLUGIN_NICE_RANK_HISTORY_CACHE);
    } else {
        $lines = array();
    }

    # 集計実施
    $current_date = get_date($date_format);
    $period_date = get_date($date_format, strtotime('-' . PLUGIN_NICE_RANK_PERIOD_DAYS . ' day'));

    $summary = array();
    foreach($lines as $line) {
        list($date, $page, $cnt) = explode("\t", rtrim($line));

        if (!isset($date) || !isset($page) || !isset($cnt) || strcmp($date, $current_date) > 0) {
            # おかしなデータは無視
            continue;
        }

        if (strcmp($date, $period_date) < 0) {
            # 集計対象期間外は無視
            continue;
        }

        # ページ名の先頭に"#"を付与して扱う(arrayのkey文字列がintに変換されてしまうのを避けるため)
        $sum = $summary['#' . $page];
        if (!isset($sum)) {
            $sum = 0;
        }

        # 合算
        $sum += $cnt;
        $summary['#' . $page] = $sum;
    }

    # 件数の降順、ページの昇順でソート
    $sort_page = array_keys($summary);
    $sort_sum = array_values($summary);
    array_multisort($sort_sum, SORT_DESC, SORT_NUMERIC, $sort_page, SORT_ASC, $summary);

    # 上位から出力
    $script = get_script_uri();
    $ranking = '<h5>' . PLUGIN_NICE_RANK_TITLE . $rank_lines . '</h5><div><ul>';
    foreach (array_slice($summary, 0, $rank_lines) as $page => $sum) {

        # 先頭に"#"を付与しているので除去(arrayのkey文字列がintに変換されてしまうのを避けるため)
        $page = ltrim($page, '#');

        $s_page = htmlsc($page);
        if($page == $vars['page']) {
            # 現在ページはリンクにしない
            $ranking .= '<li>' . $s_page . ' (' . $sum . ')' . '</li>' . "\n";
        } else {
            $r_page = rawurlencode($page);
            $ranking .= '<li><a href="' . $script . '?' . $r_page . '"' . 
                ' title="' . $s_page . '">' . $s_page . '</a> (' . $sum . ')</li>' . "\n";
        }
    }

    $ranking .= '</ul></div>';
    return $ranking;
}
?>
