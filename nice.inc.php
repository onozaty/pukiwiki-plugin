<?php
// Nice Plugin
//
// This plugin is based on vote.inc.php.
// License: GPL v2 or (at your option) any later version

define('PLUGIN_NICE_LABEL', "&#x1f44d いいね！");                    # ラベル(ボタンにラベル＋数が表示)
define('PLUGIN_NICE_NOTIMESTAMP', true);                             # タイムスタンプを更新しない
define('PLUGIN_NICE_HISTORY_CACHE', CACHE_DIR . 'nice_history.dat'); # 履歴の保存先
define('PLUGIN_NICE_HISTORY_PERIOD_DAYS', 30);                       # 履歴の保存期間

function plugin_nice_action()
{
    global $vars, $script, $cols, $rows;
    global $_title_collided, $_msg_collided, $_title_updated;

    if (PKWK_READONLY) die_message('PKWK_READONLY prohibits editing');

    $postdata_old  = get_source($vars['refer']);

    $nice_no = 0;
    $title = $body = $postdata = $postdata_input = $nice_str = '';
    $matches = array();
    foreach($postdata_old as $line) {

        # "#nice(1)" みたいな形式
        if (! preg_match('/^#nice\((\d*)\)(.*)?$/i', $line, $matches) ||
            $nice_no++ != $vars['nice_no']) {
            $postdata .= $line;
            continue;
        }
        $cnt   = isset($matches[1]) ? $matches[1] : 0;
        $lefts = isset($matches[2]) ? $matches[2] : '';

        $cnt++;

        $nice_str       = '#nice(' . $cnt . ')' . $lefts . "\n";
        $postdata_input = $nice_str;
        $postdata      .= $nice_str;
    }

    if (md5(@join('', get_source($vars['refer']))) != $vars['digest']) {
        $title = $_title_collided;

        $s_refer          = htmlsc($vars['refer']);
        $s_digest         = htmlsc($vars['digest']);
        $s_postdata_input = htmlsc($postdata_input);
        $body = <<<EOD
$_msg_collided
<form action="$script?cmd=preview" method="post">
 <div>
  <input type="hidden" name="refer"  value="$s_refer" />
  <input type="hidden" name="digest" value="$s_digest" />
  <textarea name="msg" rows="$rows" cols="$cols" id="textarea">$s_postdata_input</textarea><br />
 </div>
</form>

EOD;
    } else {
        
        $current_page = $vars['refer'];

        # 履歴を更新
        update_nice_histroy($current_page);
        
        page_write($current_page, $postdata, PLUGIN_NICE_NOTIMESTAMP);
        $title = $_title_updated;
    }

    $vars['page'] = $vars['refer'];

    return array('msg'=>$title, 'body'=>$body);
}

function plugin_nice_convert()
{
    global $script, $vars,  $digest;
    static $number = array();

    $page = isset($vars['page']) ? $vars['page'] : '';
    
    // Nice-box-id in the page
    if (! isset($number[$page])) $number[$page] = 0; // Init
    $nice_no = $number[$page]++;

    if (PKWK_READONLY) {
        $_script = '';
        $_submit = 'hidden';
    } else {
        $_script = $script;
        $_submit = 'submit';
    }

    $args     = func_get_args();
    $s_page   = htmlsc($page);
    $s_digest = htmlsc($digest);

    $nice_label = PLUGIN_NICE_LABEL;
    $cnt = isset($args[0]) ? $args[0] : 0;

    $body = <<<EOD
<form action="$_script" method="post">
 <input type="hidden" name="plugin"  value="nice" />
 <input type="hidden" name="refer"   value="$s_page" />
 <input type="hidden" name="nice_no" value="$nice_no" />
 <input type="hidden" name="digest"  value="$s_digest" />
 <input type="$_submit" name="nice" value="$nice_label $cnt" class="submit" />
</form>

EOD;

    if (isset($vars['preview'])) {
        # プレビューの場合にはボタン自体見えないので、テキストで配置
        $body .= <<<EOD
<p>$nice_label $cnt</p>

EOD;
    }
    return $body;
}

function update_nice_histroy($current_page)
{
    global $date_format;

    # 下記の流れで履歴を更新
    # (1) 既に同じページ＆日付のものがあったらカウントアップ
    # (2) 存在しない場合には現在日時で新たに追加
    # (3) 古いものは消しておく
    $current_date = get_date($date_format);
    $period_date = get_date($date_format, strtotime('-' . PLUGIN_NICE_HISTORY_PERIOD_DAYS . ' day'));

    pkwk_touch_file(PLUGIN_NICE_HISTORY_CACHE);
    $fp = fopen(PLUGIN_NICE_HISTORY_CACHE, 'r+') or
        die_message('Cannot open ' . PLUGIN_NICE_HISTORY_CACHE);
    set_file_buffer($fp, 0);
    flock($fp, LOCK_EX);

    # 今までの履歴を読み込み
    $histories = array();
    $add_current = false;
    foreach(file(PLUGIN_NICE_HISTORY_CACHE) as $line) {
        list($date, $page, $cnt) = explode("\t", rtrim($line));

        if (!isset($date) || !isset($page) || !isset($cnt) || strcmp($date, $current_date) > 0) {
            # おかしなデータは除去
            continue;
        }

        if (strcmp($date, $period_date) < 0) {
            # 保存期間を超えて古いものは除去
            continue;
        }

        if ($page == $current_page && $date == $current_date) {
            $cnt++;
            $add_current = true;
        }

        $histories[] = array('date' => $date, 'page' => $page, 'cnt' => $cnt);
    }

    if (!$add_current) {
        $histories[] = array('date' => $current_date, 'page' => $current_page, 'cnt' => 1);
        $add_current = true;
    }

    # 履歴を更新
    ftruncate($fp, 0);
    rewind($fp);
    foreach ($histories as $history)
        fputs($fp, $history['date'] . "\t" . $history['page'] . "\t" . $history['cnt'] . "\n");

    flock($fp, LOCK_UN);
    fclose($fp);
}
?>
