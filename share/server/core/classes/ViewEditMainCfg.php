<?php
/*****************************************************************************
 *
 * ViewEditMainCfg.php - Class to render the main configuration edit dialog
 *
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

class ViewEditMainCfg {
    private $exclude_pattern = '/^(backend|internal|rotation|auth|action|wui)/i';
    private $error = null;

    private function handleAction() {
        global $CORE, $_MAINCFG;
        $UMAINCFG = $CORE->getUserMainCfg();

        // loop all sections
        foreach ($_MAINCFG->getValidConfig() AS $sec => $arr) {
            if (preg_match($this->exclude_pattern, $sec))
                continue;

            // loop all options
            foreach ($_MAINCFG->getValidObjectType($sec) AS $key => $spec) {
                if (isset($spec['deprecated']) && $spec['deprecated'] == 1)
                    continue;

                $field_type = val($spec, 'field_type', 'text');
                if ($field_type == 'hidden')
                    continue;

                $ident = $sec.'_'.$key;
                if (isset($_POST['toggle_'.$ident]) && $_POST['toggle_'.$ident] != '') {
                    // set the option
                    $raw_val = $_POST[$ident];

                    if (val($spec, 'array', false))
                        $val = explode(',', $raw_val);
                    else
                        $val = $raw_val;

                    // now check for value format
                    if (!preg_match($spec['match'], $raw_val))
                        throw new FieldInputError($ident, l('Invalid format given. Regex: [r]',
                                                                    array('r' => $spec['match'])));

                    $UMAINCFG->setValue($sec, $key, $val);
                }
                else {
                    $UMAINCFG->unsetValue($sec, $key);
                }
            }
        }

        $UMAINCFG->writeConfig(); // persist changes
        echo '<div class="success">'.l('The configuration has been saved.').'</div>';
        echo '<script>window.scrollTo(0, 0);'
            .'window.setTimeout(function() { window.location.reload(); }, 1500);</script>';
    }

    public function parse() {
        global $_MAINCFG;
        ob_start();
        js_form_start('edit_config');

        if (is_action()) {
            try {
                $this->handleAction();
            } catch (FieldInputError $e) {
                $this->error = $e;
            }
        }

        $open = isset($_POST['sec']) ? $_POST['sec'] : 'global'; // default open section
        hidden('sec', $open);

        // first render navigation
        echo '<ul class="nav" id="nav">';
        foreach ($_MAINCFG->getValidConfig() AS $sec => $arr) {
            if (!preg_match($this->exclude_pattern, $sec)) {
                $class = $open == $sec ? ' class="active"' : '';
                echo '<li id="nav_'.$sec.'" '.$class.'><a href="javascript:toggle_maincfg_section(\''.$sec.'\')">'.$_MAINCFG->getSectionTitle($sec).'</a></li>';
            }
        }
        echo '</ul>';

        foreach ($_MAINCFG->getValidConfig() AS $sec => $arr) {
            if (!preg_match($this->exclude_pattern, $sec))
                $this->renderSection($sec, $open);
        }

        submit(l('save'));
        form_end();

        return ob_get_clean();
    }

    private function getCurVal($sec, $key, $ignore_default = true) {
        global $CORE;
        $UMAINCFG = $CORE->getUserMainCfg();

        $cur_val = $UMAINCFG->getValue($sec, $key, $ignore_default);
        $ident = $sec.'_'.$key;
        if (isset($_POST['toggle_'.$ident]) && $_POST['toggle_'.$ident] != '')
            $cur_val = val($_POST, $ident);
        if (is_array($cur_val))
            $cur_val = implode(',', $cur_val);
        return $cur_val;
    }

    private function renderSection($sec, $open) {
        global $_MAINCFG, $CORE;

        $display = $sec != $open ? 'display:none' : '';
        echo '<table id="sec_'.$sec.'" class="mytable section" style="'.$display.'">';
        foreach ($_MAINCFG->getValidObjectType($sec) AS $key => $spec) {
            // Skip deprecated options
            if (isset($spec['deprecated']) && $spec['deprecated'] == 1)
                continue;

            $field_type = val($spec, 'field_type', 'text');
            if ($field_type == 'hidden')
                continue;

            $ident = $sec.'_'.$key;

            // value configured by the user. might be null when nothing is configured
            $cur_val = $this->getCurVal($sec, $key);

            // Get either the option configured in non gui editable config files or the
            // hardcoded default value
            $def_val = $_MAINCFG->getValue($sec, $key, false, true);
            if (is_array($def_val))
                $def_val = implode(',', $def_val);

            // Check if depends_on and depends_value are defined and if the value
            // is equal. If not equal hide the field
            $row_class = '';
            $row_style = '';
            if(isset($spec['depends_on']) && isset($spec['depends_value'])
                && $this->getCurVal($sec, $spec['depends_on'], false) != $spec['depends_value']) {

                $row_class = ' class="child-row"';
                $row_style = ' style="display:none;"';
            } elseif(isset($spec['depends_on']) && isset($spec['depends_value'])
                && $this->getCurVal($sec, $spec['depends_on'], false) == $spec['depends_value']) {

                $row_class = ' class="child-row"';
            }

            if ($cur_val !== null) {
                $checked      = ' checked="checked"';
                $show_default = ' style="display:none"';
                $show_input   = '';
            }
            else {
                $checked      = '';
                $show_default = '';
                $show_input   = ' style="display:none"';
            }

            echo '<tr'.$row_class.$row_style.'>';
            echo '<td class="tdlabel">'.$key.'</td>';
            echo '<td class="tdbox"><input type="checkbox" name="toggle_'.$ident.'" value="1" '
                .'onclick="toggle_option(\'box_'.$ident.'\')"'.$checked.'/></td>';
            echo '<td class="tdfield">';

            echo '<div id="_txt_box_'.$ident.'"'.$show_default.' class="default">';
            switch ($field_type) {
                case 'boolean':
                    if ($def_val == '1')
                        echo l('Yes');
                    else
                        echo l('No');
                break;
                default:
                    echo escape_html($def_val);
            }
            echo '</div>';

            echo '<div id="box_'.$ident.'"'.$show_input.'>';
            $this->renderInput($sec, $key, $spec, $def_val, $cur_val);
            if ($this->error && $this->error->field == $ident) {
                echo '<div class="err">'.escape_html($this->error->msg).'</div>';
            }
            echo '</div>';

            echo '</td>';
            echo '</tr>';
        }
        echo '</table>';
    }

    private function renderInput($sec, $key, $spec, $def_val, $cur_val) {
        global $_MAINCFG;
        $field_type = val($spec, 'field_type', 'text');

        // Make the default value the starting value for editing
        if ($cur_val === null)
            $cur_val = $def_val;

        $on_change = '';
        if($_MAINCFG->hasDependants($sec, $key))
            $on_change = ' onchange="updateForm()"';
        
        switch ($field_type) {
            case 'dropdown':
                $func_name = $_MAINCFG->getListFunc($sec, $key);
                $choices = $func_name();
        
                echo '<select id="'.$sec.'_'.$key.'" name="'.$sec.'_'.$key.'"'.$on_change.'>';
                echo '<option value=""></option>';
        
                foreach ($choices AS $choice_key => $choice_val) {
                    if(is_array($choice_val)) {
                        echo '<option value="'.$choice_val['value'].'">'.$choice_val['label'].'</option>';
                    } else {
                        if (is_int($choice_key))
                            $choice_key = $choice_val; // do not indexes of assoc arrays as values
                        echo '<option value="'.$choice_key.'">'.$choice_val.'</option>';
                    }
                }
        
                echo '</select>';
                echo '<script>document.edit_config.elements[\''.$sec.'_'.$key.'\'].value = \''.$cur_val.'\';</script>';
            break;
            case 'boolean':
                echo '<select id="'.$sec.'_'.$key.'" name="'.$sec.'_'.$key.'"'.$on_change.'>';
                echo '<option value=""></option>';
                echo '<option value="1">'.l('yes').'</option>';
                echo '<option value="0">'.l('no').'</option>';
                echo '</select>';
        
                echo '<script>document.edit_config.elements[\''.$sec.'_'.$key.'\'].value = \''.$cur_val.'\';</script>';
            break;
            case 'text':
                echo '<input id="'.$sec.'_'.$key.'" type="text" name="'.$sec.'_'.$key.'" value="'.$cur_val.'">';
            break;
        }
        
        if(isset($spec['locked']) && $spec['locked'] == 1)
            echo "<script>document.edit_config.elements['".$sec."_".$key."'].disabled=true;</script>";
    }
}
?>
