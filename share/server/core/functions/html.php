<?php
/*****************************************************************************
 * Copyright (c) 2004-2016 NagVis Project (Contact: info@nagvis.org)
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

function has_var($key) {
    return isset($_REQUEST[$key]);
}

/**
 * FORM HANDLING
 */

$form_keys   = array();
$form_errors = array();

function get_checkbox($key, $default = null) {
    return (bool)req($key, $default);
}

function form_error($field, $msg) {
    global $form_errors;
    $form_errors[$field] = $msg;
}

// Special form of form errors: Are not displayed at the start of
// the form becaus they occur during rendering of the fields. Make
// the message part an array in this case.
function form_render_error($field, $msg) {
    form_error($field, array(false, $msg));
}

function get_error($key) {
    global $form_errors;
    return isset($form_errors[$key]) ? $form_errors[$key] : null;
}

function has_form_error($name) {
    global $form_errors, $form_name;
    return (!submitted() || submitted($form_name)) && isset($form_errors[$name]);
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

function is_update() {
    return post('_update', '0') == '1';
}

function js($code) {
    echo '<script>'.$code.'</script>'.N;
}

function show_form_render_error($name) {
    // only display form rendering errors here
    if (has_form_error($name)) {
        $err = get_error($name);
        if (is_array($err))
            error($err[1]);
    }
}

// Starts a HTML form which is submitted (and can be updated) via AJAX call
function js_form_start($name) {
    form_start($name, 'javascript:submitForm(\''.cfg('paths', 'htmlbase')
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

    if (submitted($form_name) || !submitted())
        foreach ($form_errors AS $field => $message)
            error($message);

    hidden('_form_name', $name);
    hidden('_update', '0');
}

function form_end($keep_context=true) {
    global $form_name;
    if ($keep_context)
        hidden_vars();
    echo '</form>'.N;
}

// Adds all remaining vars we got as $_POST/$_GET which have not been added to
// this form yet to keep the current variable context accross the single requests.
function hidden_vars() {
    global $form_keys, $form_name;
    //if (submitted($form_name) || !submitted()) {
    foreach ($_REQUEST AS $key => $val) {
        // $_REQUEST might contain $_COOKIES. Skip these vars.
        if (!isset($form_keys[$key]) && !isset($_COOKIE[$key])) {
            if (is_array($val)) {
                foreach($val AS $val_element) {
                    hidden($key."[]", $val_element);
                }
            } else {
                hidden($key, $val);
            }
        }
    }
    //}
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

function field($type, $name, $default = '', $class = '', $onclick = '', $style = '', $id = null) {
    global $form_keys, $form_name;
    $form_keys[$name] = true;

    if (has_form_error($name))
        $class .= ' err';

    if(trim($class))
        $class = ' class="'.trim($class).'"';

    if (submitted($form_name)) {
        if ($type == 'checkbox')
            $default = get_checkbox($name, $default);
        else
            $default = post($name, $default);
    }

    $value = '';
    if ($default != '') {
        if($type == 'checkbox') {
            if ($default === true) {
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

    if ($id === null)
        $id = $name;

    echo '<input id="'.$id.'" type="'.$type.'" name="'.$name.'"'.$value.$class.$onclick.$style.' />'.N;

    show_form_render_error($name);
}

function checkbox($name, $default = '', $class = '', $onclick = '') {
    field('checkbox', $name, $default, $class, $onclick);
}

function input($name, $default = '', $class = '', $style = '', $id = null) {
    field('text', $name, $default, $class, '', $style, $id);
}

function password($name, $default = '', $class = '') {
    field('password', $name, $default, $class);
}

function textarea($name, $default = '', $class = '', $style = '') {
    global $form_keys, $form_name;
    $form_keys['very_important'] = true;

    $err_class = '';
    if (has_form_error($name))
        $err_class = ' err';

    if($class != '' || $err_class != '')
        $class = ' class="'.$class.$err_class.'"';

    if($style != '')
        $style = ' style="'.$style.'"';

    if (submitted($form_name))
        $default = post($name, $default);

    // plain <textarea>
    echo '<textarea id="textarea_'.$name.'" name="'.$name.'"'.$class.$style.'>'.escape_html($default).'</textarea>'.N;

    // better <textarea>
    echo '
    <script>
        let script = document.createElement("script");
        script.src = "js/ExtNicEdit.js"
        document.head.appendChild(script);
        script.onload = function() {
            new nicEditor({fullPanel : true}).panelInstance("textarea_'.$name.'")
        };
    </script>
    ';

}

function select($name, $options, $default = '', $onchange = '', $style = '', $size = null) {
    global $form_keys, $form_name;
    $form_keys[$name] = true;

    $class = '';
    if (has_form_error($name))
        $class .= ' err';

    if(trim($class))
        $class = ' class="'.trim($class).'"';

    if (submitted($form_name) || !submitted()) // this or none submitted
        $default = post($name, $default);

    if($onchange != '')
        $onchange = ' onchange="'.$onchange.'"';

    if($style != '')
        $style = ' style="'.$style.'"';

    // for sequential arrays use the values for the keys and the values
    if (array_keys($options) === range(0, count($options) - 1)) {
        $new_options = array();
        foreach ($options as $values)
            $new_options[$values] = $values;
        $options = $new_options;
    }

    $multiple = '';
    if ($size !== null) {
        $multiple = ' size="'.$size.'" multiple';
    }

    $ret = '<select id="'.$name.'" name="'.$name.'"'.$onchange.$class.$style.$multiple.'>'.N;
    foreach($options AS $value => $display) {
        $select = '';
        if($value == $default)
            $select = ' selected';
        $ret .= '<option value="'.$value.'"'.$select.'>'.$display.'</option>'.N;
    }
    $ret .= '</select>'.N;
    echo $ret;

    show_form_render_error($name);
}

function submit($label, $class = '', $name = '_submit') {
    global $form_keys;
    $form_keys['_submit'] = true;
    if ($class)
        $class = ' '.$class;
    echo '<input class="submit'.$class.'" type="submit" name="'.$name.'" id="'.$name.'" value="'.$label.'" />'.N;
}

function button($name, $label, $onclick) {
    global $form_keys;
    $form_keys[$name] = true;
    echo '<input type="button" name="'.$name.'" id="'.$name.'" value="'.$label.'" onclick="'.$onclick.'" />'.N;
}

function upload($name) {
    global $form_keys, $form_name;
    $form_keys[$name] = true;

    $class = '';
    if (has_form_error($name))
        $class .= ' err';

    if(trim($class))
        $class = ' class="'.$class.'"';

    echo '<input type="file" name="'.$name.'"'.$class.' />'.N;
}

function do_http_redirect($url = null) {
    if ($url === null)
        $url = $_SERVER['REQUEST_URI'];
    header('Location: '.$url);
}

function reload($url, $sec) {
    if ($url == null)
        js('setTimeout(function() {location.reload();}, '.$sec.'*1000);');
    else
        js('setTimeout(function() {location.href=\''.$url.'\';}, '.$sec.'*1000);');
}

function focus($name) {
    js('try{document.getElementById(\''.$name.'\').focus();}catch(e){}');
}

function scroll_up() {
    js('document.body.scrollTop = document.documentElement.scrollTop = 0;');
}

function get_open_section($default) {
    return post('sec', $default);
}

function render_section_navigation($open, $sections) {
    hidden('sec', $open);

    // first render navigation
    echo '<ul class="nav" id="nav">';
    foreach ($sections AS $sec => $title) {
        $class = $open == $sec ? ' class="active"' : '';
        echo '<li id="nav_'.$sec.'" '.$class.'>';
        echo '<a href="javascript:toggle_section(\''.$sec.'\')">';
        echo $title.'</a></li>';
    }
    echo '</ul>';
}

function render_section_start($sec, $open) {
    $display = $sec != $open ? 'display:none' : '';
    echo '<div id="sec_'.$sec.'" class="section" style="'.$display.'">';
}

function render_section_end() {
    echo '</div>';
}

?>
