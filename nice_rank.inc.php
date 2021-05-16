<?php
// Nice Rank Plugin
// 
// Nice Plugin�ˤƥ�����Ȥ����֤����͡��פο����󥭥󥰤Ȥ���ɽ�����ޤ���
// 
// License: GPL v2 or (at your option) any later version

define('PLUGIN_NICE_RANK_DEFAULT_LINES', 10);                             # ɽ��������
define('PLUGIN_NICE_RANK_HISTORY_CACHE', CACHE_DIR . 'nice_history.dat'); # �������¸��(nice.inc.php����¸���Ƥ���ե�����)
define('PLUGIN_NICE_RANK_PERIOD_DAYS', 30);                               # �оݴ���(nice.inc.php���������¸���ְʲ��Ǥ���ɬ�פ���)

# �����ȥ�(������ɽ��������դ�)
define('PLUGIN_NICE_RANK_TITLE', '���' . PLUGIN_NICE_RANK_PERIOD_DAYS . '���֤Τ����͡�Top ');

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

    # ���׼»�
    $current_date = get_date($date_format);
    $period_date = get_date($date_format, strtotime('-' . PLUGIN_NICE_RANK_PERIOD_DAYS . ' day'));

    $summary = array();
    foreach($lines as $line) {
        list($date, $page, $cnt) = explode("\t", rtrim($line));

        if (!isset($date) || !isset($page) || !isset($cnt) || strcmp($date, $current_date) > 0) {
            # �������ʥǡ�����̵��
            continue;
        }

        if (strcmp($date, $period_date) < 0) {
            # �����оݴ��ֳ���̵��
            continue;
        }

        # �ڡ���̾����Ƭ��"#"����Ϳ���ư���(array��keyʸ����int���Ѵ�����Ƥ��ޤ��Τ��򤱤뤿��)
        $sum = $summary['#' . $page];
        if (!isset($sum)) {
            $sum = 0;
        }

        # �绻
        $sum += $cnt;
        $summary['#' . $page] = $sum;
    }

    # ����ι߽硢�ڡ����ξ���ǥ�����
    $sort_page = array_keys($summary);
    $sort_sum = array_values($summary);
    array_multisort($sort_sum, SORT_DESC, SORT_NUMERIC, $sort_page, SORT_ASC, $summary);

    # ��̤������
    $script = get_script_uri();
    $ranking = '<h5>' . PLUGIN_NICE_RANK_TITLE . $rank_lines . '</h5><div><ul>';
    foreach (array_slice($summary, 0, $rank_lines) as $page => $sum) {

        # ��Ƭ��"#"����Ϳ���Ƥ���Τǽ���(array��keyʸ����int���Ѵ�����Ƥ��ޤ��Τ��򤱤뤿��)
        $page = ltrim($page, '#');

        $s_page = htmlsc($page);
        if($page == $vars['page']) {
            # ���ߥڡ����ϥ�󥯤ˤ��ʤ�
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
