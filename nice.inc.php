<?php
// Nice Plugin
//
// This plugin is based on vote.inc.php.
// License: GPL v2 or (at your option) any later version

define('PLUGIN_NICE_LABEL', "いいね！");

function plugin_nice_action()
{
    global $vars, $script, $cols,$rows;
    global $_title_collided, $_msg_collided, $_title_updated;
    global $_nice_plugin_nices;

    if (PKWK_READONLY) die_message('PKWK_READONLY prohibits editing');

    $postdata_old  = get_source($vars['refer']);

    $nice_no = 0;
    $title = $body = $postdata = $postdata_input = $nice_str = '';
    $matches = array();
    foreach($postdata_old as $line) {

        if (! preg_match('/^#nice\((\d*)\)(.*)?$/i', $line, $matches) ||
            $nice_no++ != $vars['nice_no']) {
            $postdata .= $line;
            continue;
        }
        $cnt = isset($matches[1]) ? $matches[1] : 0;
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
        page_write($vars['refer'], $postdata);
        $title = $_title_updated;
    }

    $vars['page'] = $vars['refer'];

    return array('msg'=>$title, 'body'=>$body);
}

function plugin_nice_convert()
{
    global $script, $vars,  $digest;
    global $_nice_plugin_choice, $_nice_plugin_nices;
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
 <input type="$_submit" name="nice" value="&#x1f44d $nice_label $cnt" class="submit" />
</form>

EOD;

    return $body;
}
?>
