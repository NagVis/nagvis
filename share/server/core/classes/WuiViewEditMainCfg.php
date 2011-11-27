<?php
/*****************************************************************************
 *
 * WuiViewEditMainCfg.php - Class to render the main configuration edit dialog
 *
 * Copyright (c) 2004-2011 NagVis Project (Contact: info@nagvis.org)
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
 * @author	Lars Michelsen <lars@vertical-visions.de>
 */
class WuiViewEditMainCfg {
    /**
     * Parses the information in html format
     *
     * @return	String 	String with Html Code
     * @author 	Lars Michelsen <lars@vertical-visions.de>
     */
    public function parse() {
        global $CORE;

        // Initialize template system
        $TMPL = New CoreTemplateSystem($CORE);
        $TMPLSYS = $TMPL->getTmplSys();

        $aData = Array(
            'htmlBase'     => cfg('paths', 'htmlbase'),
            'formContents' => $this->getFields(),
            'langSave'     => l('save'),
            'validMainCfg' => json_encode($CORE->getMainCfg()->getValidConfig()),
            'lang'         => $CORE->getJsLang(),
        );

        // Build page based on the template file and the data array
        return $TMPLSYS->get($TMPL->getTmplFile(cfg('defaults', 'view_template'), 'wuiEditMainCfg'), $aData);
    }

    /**
     * Parses the Form fields
     *
     * @return	Array Html
     * @author 	Lars Michelsen <lars@vertical-visions.de>
     * FIXME: Recode to have all the HTML code in the template
     */
    function getFields() {
        global $CORE;
        $ret = '';

        $i = 1;
        foreach($CORE->getMainCfg()->getValidConfig() AS $cat => $arr) {
            // don't display backend,rotation and internal options
            if(!preg_match("/^(backend|internal|rotation|auth)/i", $cat)) {
                $ret .= '<tr><th class="cat" colspan="3"><h2>'.$cat.'</h2></th></tr>';

                foreach($arr AS $propname => $prop) {
                    $class = '';
                    $style = '';
                    $isDefaultValue = false;

                    // Skip deprecated options
                    if(isset($prop['deprecated']) && $prop['deprecated'] == 1)
                        continue;

                    // Set field type to show
                    $fieldType = 'text';
                    if(isset($prop['field_type'])) {
                        $fieldType = $prop['field_type'];
                    }

                    // Don't show anything for hidden options
                    if($fieldType !== 'hidden') {
                        // Only get the really set value
                        $val2 = cfg($cat, $propname, true);

                        // Check if depends_on and depends_value are defined and if the value
                        // is equal. If not equal hide the field
                        if(isset($prop['depends_on']) && isset($prop['depends_value'])
                            && cfg($cat, $prop['depends_on'], false) != $prop['depends_value']) {

                            $class = ' class="child-row"';
                            $style = ' style="display:none;"';
                        } elseif(isset($prop['depends_on']) && isset($prop['depends_value'])
                            && cfg($cat, $prop['depends_on'], false) == $prop['depends_value']) {

                            //$style .= 'display:;';
                            $class = ' class="child-row"';
                        }

                        // Create a "helper" field which contains the real applied value
                        if($val2 === false) {
                            $defaultValue = cfg($cat, $propname, false);

                            if(is_array($defaultValue)) {
                                $defaultValue = implode(',', $defaultValue);
                            }

                            $ret .= '<input type="hidden" id="_'.$cat.'_'.$propname.'" name="_'.$cat.'_'.$propname.'" value="'.$defaultValue.'" />';
                        } else {
                            $ret .= '<input type="hidden" id="_'.$cat.'_'.$propname.'" name="_'.$cat.'_'.$propname.'" value="" />';
                        }

                        # we add a line in the form
                        $ret .= '<tr'.$class.$style.'>';
                        $ret .= '<td class="tdlabel">'.$propname.'</td>';

                        if(preg_match('/^TranslationNotFound:/', l($propname)) > 0) {
                            $ret .= '<td class="tdfield"></td>';
                        } else {
                            $ret .= '<td class="tdfield">';
                            $ret .= "<img style=\"cursor:help\" src=\"./images/help_icon.png\" onclick=\"javascript:alert('".l($propname)." (".l('defaultValue').": ".$arr[$propname]['default'].")')\" />";
                            $ret .= '</td>';
                        }

                        $ret .= '<td class="tdfield">';
                        switch($fieldType) {
                            case 'dropdown':
                                switch($propname) {
                                    case 'language':
                                        $arrOpts = $CORE->getAvailableLanguages();
                                    break;
                                    case 'backend':
                                        $arrOpts = $CORE->getDefinedBackends();
                                    break;
                                    case 'icons':
                                        $arrOpts = $CORE->getAvailableIconsets();
                                    break;
                                    case 'headertemplate':
                                        $arrOpts = $CORE->getAvailableHeaderTemplates();
                                    break;
                                    case 'autoupdatefreq':
                                        $arrOpts = Array(Array('value'=>'0','label'=>l('disabled')),
                                                         Array('value'=>'2','label'=>'2'),
                                                         Array('value'=>'5','label'=>'5'),
                                                         Array('value'=>'10','label'=>'10'),
                                                         Array('value'=>'25','label'=>'25'),
                                                         Array('value'=>'50','label'=>'50'));
                                    break;
                                }

                                $ret .= '<select id="'.$cat.'_'.$propname.'" name="'.$cat.'_'.$propname.'" onBlur="validateMainConfigFieldValue(this, 0)">';
                                $ret .= '<option value=""></option>';

                                foreach($arrOpts AS $val) {
                                    if(is_array($val)) {
                                        $ret .= '<option value="'.$val['value'].'">'.$val['label'].'</option>';
                                    } else {
                                        $ret .= '<option value="'.$val.'">'.$val.'</option>';
                                    }
                                }

                                $ret .= '</select>';

                                $ret .= '<script>document.edit_config.elements[\''.$cat.'_'.$propname.'\'].value = \''.$val2.'\';</script>';
                            break;
                            case 'boolean':
                                $ret .= '<select id="'.$cat.'_'.$propname.'" name="'.$cat.'_'.$propname.'" onBlur="validateMainConfigFieldValue(this, 0)">';
                                $ret .= '<option value=""></option>';
                                $ret .= '<option value="1">'.l('yes').'</option>';
                                $ret .= '<option value="0">'.l('no').'</option>';
                                $ret .= '</select>';

                                $ret .= '<script>document.edit_config.elements[\''.$cat.'_'.$propname.'\'].value = \''.$val2.'\';</script>';
                            break;
                            case 'text':
                                if(is_array($val2)) {
                                    $val2 = implode(',', $val2);
                                }

                                $ret .= '<input id="'.$cat.'_'.$propname.'" type="text" name="'.$cat.'_'.$propname.'" value="'.$val2.'" onBlur="validateMainConfigFieldValue(this, 0)" />';

                                if(isset($prop['locked']) && $prop['locked'] == 1) {
                                    $ret .= "<script>document.edit_config.elements['".$cat."_".$propname."'].disabled=true;</script>";
                                }
                            break;
                        }

                        // Initially toggle the depending fields
                        $ret .= '<script>validateMainConfigFieldValue(document.getElementById("'.$cat.'_'.$propname.'"), 1);</script>';

                        $ret .= '</td>';
                        $ret .= '</tr>';
                    }
                }

                if($i % 3 == 0) {
                    $ret .= '</table><table class="mytable" style="width:300px;float:left">';
                }

                $i++;

            }
        }

        return $ret;
    }
}
?>
