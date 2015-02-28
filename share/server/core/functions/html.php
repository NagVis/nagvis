<?php
/*****************************************************************************
 * Copyright (c) 2004-2015 NagVis Project (Contact: info@nagvis.org)
 *
 * License:
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2 as
 * published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 675 Mass Ave, Cambridge, MA 02139, USA.
 *
 *****************************************************************************/

/**
 * GENERIC HELPERS
 */

define('N', "\n");

function escape_html($s) {
    return htmlentities($s, ENT_COMPAT, 'UTF-8');
}

function post($name, $default = null) {
    return isset($_POST[$name]) ? $_POST[$name] : $default;
}

function get($name, $default = null) {
    return isset($_GET[$name]) ? $_GET[$name] : $default;
}

function req($name, $default = null) {
    return isset($_REQUEST[$name]) ? $_REQUEST[$name] : $default;
}

/**
 * FORM HANDLING
 */

$form_keys   = array();
$form_vars   = array();
$form_errors = array();

function set_form_vars($a) {
    global $form_vars;
    foreach ($a AS $k => $v) {
        $form_vars[$k] = $v;
    }
}

function form_var($name, $default = null) {
    global $form_vars;
    return isset($form_vars[$name]) ? $form_vars[$name] : $default;
}

function get_checkbox($key, $default = null) {
    return (bool)req($key, $default);
}

function form_error($field, $msg) {
    global $form_errors;
    $form_errors[$field] = $msg;
}

function get_error($key) {
    global $form_errors;
    return isset($form_errors[$key]) ? $form_errors[$key] : array();
}

function success($msg) {
    msg($msg, 'success');
}

function error($msg) {
    msg($msg, 'error');
}

function msg($msg, $cls) {
    echo '<div class="'.$cls.'">'.escape_html($msg).'</div>'.N;
}

function submitted($form_name = null) {
    if ($form_name) {
        // check if a specific form has been submitted
        return post('_form_name') == $form_name;
    } else {
        // check if any form has been submitted
        return (bool)post('_submit');
    }
}

function is_action() {
    return (submitted() || (bool)get('_action')) && post('_update', '0') == '0';
}

function js($code) {
    echo '<script>'.$code.'</script>'.N;
}

// Starts a HTML form which is submitted (and can be updated) via AJAX call
function js_form_start($name) {
    form_start($name, 'javascript:submitFrontendForm2(\''.cfg('paths', 'htmlbase')
                     .'/server/core/ajax_handler.php?mod='.$_REQUEST['mod']
                     .'&act='.$_REQUEST['act'].'\', \''.$name.'\');');
}

function form_start($name, $target, $type = 'POST', $multipart = false) {
    global $form_keys, $form_errors, $form_name;
    $form_name = $name;
    $form_keys = array();

    // Add vars of "target" url to form_keys
    $url = parse_url($target);
    if (isset($url['query'])) {
        parse_str($url['query'], $query);
        foreach ($query AS $key => $val) {
            $form_keys[$key] = true;
        }
    }

    if ($multipart)
        $multipart = ' enctype="multipart/form-data"';
    else
        $multipart = '';

    echo '<form id="'.$name.'" name="'.$name.'" action="'.escape_html($target).'" '
        .'method="'.$type.'"'.$multipart.'>'.N;

    if (submitted($form_name))
        foreach ($form_errors AS $field => $message)
            error($message);

    hidden('_form_name', $name);
    hidden('_update', '0');
}

function form_end($keep_context=true) {
    if ($keep_context)
        hidden_vars();
    echo '</form>'.N;
}

// Adds all remaining vars we got as $_POST/$_GET which have not been added to
// this form yet to keep the current variable context accross the single requests.
function hidden_vars() {
    global $form_keys;
    foreach ($_REQUEST AS $key => $val) {
        if (!isset($form_keys[$key])) {
            hidden($key, $val);
        }
    }
}

function hidden($name, $default = '') {
    global $form_keys;
    $form_keys[$name] = true;
    echo '<input type="hidden" id="'.escape_html($name).'" name="'.escape_html($name).'" value="'.escape_html($default).'" />'.N;
}

function radio($name, $value, $checked = false) {
    global $form_keys;
    $form_keys[$name] = true;
    $checked = $checked ? ' checked="checked"' : '';
    echo '<input type="radio" name="'.$name.'" value="'.$value.'"'.$checked.' />';
}

function field($type, $name, $default = '', $class = '', $onclick = '', $style = '') {
    global $form_errors, $form_keys, $form_name;
    $form_keys[$name] = true;

    if (submitted($form_name) && isset($form_errors[$name])) {
        $class .= ' err';
    }

    if(trim($class))
        $class = ' class="'.trim($class).'"';

    if (submitted($form_name))
        $default = post($name, form_var($name, $default));

    $value = '';
    if ($default != '') {
        if($type == 'checkbox') {
            if($default == '1') {
                $value = ' value="1" checked="yes"';
            } else {
                $value = ' value="1"';
            }
        } else {
            $value = ' value="'.escape_html($default).'"';
        }
    }

    if($onclick != '')
        $onclick = ' onclick="'.$onclick.'"';

    if($style != '')
        $style = ' style="'.$style.'"';

    echo '<input id="'.$name.'" type="'.$type.'" name="'.$name.'"'.$value.$class.$onclick.$style.' />'.N;
}

function checkbox($name, $default = '', $class = '', $onclick = '') {
    field('checkbox', $name, $default, $class, $onclick);
}

function input($name, $default = '', $class = '', $style = '') {
    field('text', $name, $default, $class, '', $style);
}

function password($name, $default = '', $class = '') {
    field('password', $name, $default, $class);
}

function textarea($name, $default = '', $class = '', $style = '') {
    global $form_errors, $form_keys, $form_name;
    $form_keys['very_important'] = true;

    $err_class = '';
    if (submitted($form_name) && isset($form_errors[$name])) {
        $err_class = ' err';
    }

    if($class != '' || $err_class != '')
        $class = ' class="'.$class.$err_class.'"';

    if($style != '')
        $style = ' style="'.$style.'"';

    if (submitted($form_name))
        $default = post($name, form_var($name, $default));

    echo '<textarea name="'.$name.'"'.$class.$style.'>'.escape_html($default).'</textarea>'.N;
}

function select($name, $options, $default = '', $onchange = '', $style = '') {
    global $form_errors, $form_keys, $form_name;
    $form_keys[$name] = true;

    $class = '';
    if (submitted($form_name) && isset($form_errors[$name])) {
        $class .= ' err';
    }

    if(trim($class))
        $class = ' class="'.trim($class).'"';

    if (submitted($form_name))
        $default = post($name, form_var($name, $default));

    if($onchange != '')
        $onchange = ' onchange="'.$onchange.'"';

    if($style != '')
        $style = ' style="'.$style.'"';

    $ret = '<select name="'.$name.'"'.$onchange.$class.$style.'>'.N;
    foreach($options AS $value => $display) {
        $select = '';
        if($value == $default)
            $select = 'selected';
        $ret .= '<option value="'.$value.'" '.$select.'>'.$display.'</option>'.N;
    }
    $ret .= '</select>'.N;
    echo $ret;
}

function submit($label, $class = '') {
    global $form_keys;
    $form_keys['_submit'] = true;
    if ($class)
        $class = ' '.$class;
    echo '<input class="submit'.$class.'" type="submit" name="_submit" id="_submit" value="'.$label.'" />'.N;
}

function upload($name) {
    global $form_keys, $form_errors, $form_name;
    $form_keys[$name] = true;

    $class = '';
    if (submitted($form_name) && isset($form_errors[$name])) {
        $class .= ' err';
    }
    if(trim($class))
        $class = ' class="'.$class.'"';

    echo '<input type="file" name="'.$name.'"'.$class.' />'.N;
}

function reload($url, $sec) {
    if ($url == null)
        js('setTimeout(function() {location.reload();}, '.$sec.'*1000);');
    else
        js('setTimeout(function() {location.href=\''.$url.'\';}, '.$sec.'*1000);');
}

?>
