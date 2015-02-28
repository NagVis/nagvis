<?php
/*****************************************************************************
 *
 * ViewMapAddModify.php - Class to render the main configuration edit dialog
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

class ViewMapAddModify {
    private $MAPCFG      = null;
    private $attrs       = null;
    private $hiddenAttrs = null;
    private $map         = null;
    private $mode        = null;
    private $errors      = Array();
    private $cloneId     = null;

    /**
     * Class Constructor
     */
    public function __construct($map, $mode) {
        $this->map  = $map;
        $this->mode = $mode;
        $this->CORE = GlobalCore::getInstance();

        $this->MAPCFG = new GlobalMapCfg($map);
        try {
            $this->MAPCFG->skipSourceErrors();
            $this->MAPCFG->readMapConfig();
        } catch(MapCfgInvalid $e) {}
    }
    
    public function setError($err) {
        if(!isset($this->errors[$err->field]))
            $this->errors[$err->field] = Array();
        $this->errors[$err->field][] = $err->msg;
    }

    /**
     * Setter for the object option array
     *
     * @param   Array   Array of options
     * @author  Lars Michelsen <lars@vertical-visions.de>
     */
    public function setAttrs($a) {
        $this->attrs = $a;
        // The hidden attrs list is cleared by the attrs showd up in the form during getFields() call
        $this->hiddenAttrs = $a;
        $this->hiddenAttrs['update'] = '';

        // Add information about the mode
        if($this->mode !== null)
            $this->hiddenAttrs['mode'] = $this->mode;
    }

    /**
     * Parses the information in html format
     *
     * @return	String 	String with Html Code
     * @author 	Lars Michelsen <lars@vertical-visions.de>
     */
    public function parse($update, $err, $success, $cloneId = null) {
        // Initialize template system
        $TMPL = New CoreTemplateSystem($this->CORE);
        $TMPLSYS = $TMPL->getTmplSys();

        if($err !== null)
            $this->setError($err);

        if($cloneId != null)
            $this->cloneId = $cloneId;

        $aData = Array(
            'htmlBase'      => cfg('paths', 'htmlbase'),
            'show'          => $this->map,
            // Seconds timeout to the reload/redirect
            'reloadTime'    => ($success !== null) ? $success[0] : 0,
            // Optional: Parameters to add/modify during reload
            'addParams'     => ($success !== null) ? json_encode($success[1]) : '',
            // The result message of the dialog
            'successMsg'    => ($success !== null) ? $success[2] : '',
            'formContents'  => $this->getFields($update),
            'attrs'         => $this->hiddenAttrs,
            'mode'          => $this->mode,
            'langSave'      => l('save'),
            'langPermanent' => l('Make Permanent'),
            'langForAll'    => l('for all users'),
            'langForYou'    => l('for you'),
        );

        // Build page based on the template file and the data array
        return $TMPLSYS->get($TMPL->getTmplFile('default', 'wuiMapAddModify'), $aData);
    }

    private function getAttr($typeDefaults, $update, $attr, $must, $only_inherited = false) {
        // update is true during view repaint
        // only_inherited is true when only asking for inherited value
        $val = '';
        $isInherited = false;
        // Use url given values when there is some and remove it from the attr list.
        // The url values left will be added as hidden attributes to the form later
        if(!$only_inherited && isset($this->attrs[$attr])) {
            $val = $this->attrs[$attr];

        } elseif(!$update && isset($this->attrs['object_id']) && $this->attrs['object_id'] == 0
                 && $this->MAPCFG->getSourceParam($attr, true, true) !== null) {
            // Get the value set by url if there is some set
            // But don't try this when running in "update" mode
            //
            // In case of global sections handle the source parameters which might affect
            // the shown values
            $val = $this->MAPCFG->getSourceParam($attr, true, true);

        } elseif(!$only_inherited && isset($this->attrs['object_id'])
                 && $this->MAPCFG->getValue($this->attrs['object_id'], $attr, true) !== false) {
            // Get the value set in this object if there is some set
            $val = $this->MAPCFG->getValue($this->attrs['object_id'], $attr, true);
            // In view_param mode this is inherited
            if($this->mode == 'view_params')
                $isInherited = true;

        } elseif((!$update || $only_inherited) && $this->cloneId !== null
                 && $this->MAPCFG->getValue($this->cloneId, $attr, true) !== false) {
            // Get the value set in the object to be cloned if there is some set
            // But don't try this when running in "update" mode
            $val = $this->MAPCFG->getValue($this->cloneId, $attr, true);

        } elseif(!$must && isset($typeDefaults[$attr])) {
            // Get the inherited value
            $val = $typeDefaults[$attr];
            $isInherited = true;
        }
        return Array($isInherited, $val);
    }

    /**
     * Renders the input fields of the add/modify form
     */
    function getFields($update) {
        $ret = '';

        if(isset($this->attrs['object_id']) && $this->attrs['object_id'] != '') {
            // 'object_id' is only set when modifying existing objects
            $type  = $this->MAPCFG->getValue($this->attrs['object_id'], 'type');
            $objId = $this->attrs['object_id'];
        } else {
            // Creating/Cloning new object. The type is set by URL
            $type  = $this->attrs['type'];
            $objId = 0;
        }

        $typeDefaults = $this->MAPCFG->getTypeDefaults($type);

        list($isInherited, $sources) = $this->getAttr($typeDefaults, $update, 'sources', false);
        if($objId == 0 && $sources != '<<<other>>>') {
            // Special handling for the global section:
            // It might contain some source related parameters (if some sources are enabled).
            // Another speciality ist that the dialog can be opened in "view_params" mode
            // where only the view params (parameters modifyable by the user) shal be shown.
            if($this->mode == 'view_params') {
                $source_params = $this->MAPCFG->getSourceParams(false, false, true);
                $typeDef = $this->MAPCFG->getSourceParamDefs(array_keys($source_params));
            } else {
                $source_params = $this->MAPCFG->getSourceParams();
                $typeDef = $this->MAPCFG->getValidObjectType($type);

                // Filter unwanted source parameters from the typedef list. Only leave
                // source parameters which apply to the current map.
                foreach($typeDef as $propname => $prop) {
                    // Exclude the entries which are not mentioned in source_params construct
                    // and are really source params
                    if(!isset($source_params[$propname]) && isset($prop['source_param'])) {
                        unset($typeDef[$propname]);
                    }
                }

                // Filter unwanted global parameters
                foreach ($this->MAPCFG->getHiddenConfigVars() AS $propname) {
                    unset($typeDef[$propname]);
                }
            }
        } else {
            $typeDef = $this->MAPCFG->getValidObjectType($type);
        }

        // loop all valid properties for that object type
        foreach($typeDef as $propname => $prop) {
            // Set field type to show
            $fieldType = 'text';
            if(isset($prop['field_type']))
                $fieldType = $prop['field_type'];

            // do nothing with hidden or deprecated attributes
            if($propname === 'object_id'
               || (isset($prop['deprecated']) && $prop['deprecated'] === true))
                continue;

            list($isInherited, $value) = $this->getAttr($typeDefaults, $update, $propname, $prop['must']);
            unset($this->hiddenAttrs[$propname]);

            if(isset($prop['array']) && $prop['array']) {
                if(is_array($value))
                    $value = implode(',', $value);
            }

            // Only add the fields of type hidden which have values
            if($fieldType === 'hidden') {
                if($value != '') {
                    $ret .= '<input id="'.$propname.'" type="hidden" name="'.$propname.'" value="'.$value.'" />';
                }
                continue;
            }

            $rowHide    = '';
            $rowClasses = Array();

            // Check if depends_on and depends_value are defined and if the value
            // is equal. If not equal hide the field
            // Don't hide dependent fields where the dependant is not set
            if(isset($prop['depends_on'])
               && isset($prop['depends_value'])
               && isset($typeDef[$prop['depends_on']])) {
                array_push($rowClasses, 'child-row');
                
                list($depInherited, $depValue) = $this->getAttr($typeDefaults, $update, $prop['depends_on'], $typeDef[$prop['depends_on']]['must']);
                if($depValue != $prop['depends_value'])
                    $rowHide = ' style="display:none"';
            }

            // Highlight the must attributes
            if($prop['must'])
                array_push($rowClasses, 'must');

            $ret .= '<tr class="'.implode(' ', $rowClasses).'"'.$rowHide.'><td class=tdlabel>'.$propname.'</td><td class=tdbox>';

            $other = $fieldType == 'dropdown' && isset($prop['other']) && $prop['other'];

            $onChange = '';
            // Submit the form when an attribute which has dependant attributes is changed
            if($this->MAPCFG->hasDependants($type, $propname)) {
                $onChange = 'document.getElementById(\'update\').value=\'1\';document.getElementById(\'commit\').click();';
            }
            elseif ($onChange == '' && (($other && $value !== '<<<other>>>')
                                        || ($type == 'service' && $propname == 'host_name'))) {
                // If var is backend_id or var is host_name in service objects submit the form
                // to update the depdant lists.
                $onChange = 'document.getElementById(\'update\').value=\'1\';document.getElementById(\'commit\').click();';
            }
            
            // Add a checkbox to toggle the usage of an attribute. But only add it for
            // non-must attributes.
            if(!$prop['must']) {
                $checked = '';
                // FIXME: In case of the view_params mode the dialog must select and show the user sele
                //if($this->mode == 'view_params') {
                if($isInherited === false)
                    $checked = ' checked'; 
                $ret .= '<input type="checkbox" name="toggle_'.$propname.'"'.$checked.' onclick="toggle_option(\''.$propname.'\');'.$onChange.'" value=on />';
            }
            
            $ret .= '</td><td class=tdfield>';

            // Display as text if inherited, otherwise display the input fields
            if($isInherited === false) {
                $hideTxt   = ' style="display:none"';
                $hideField = '';
            } else {
                $hideTxt   = '';
                $hideField = ' style="display:none"';
            }
            
            // Prepare translation of value to a nice display string in case of
            // e.g. boolean fields
            if($this->mode == 'view_params' || isset($typeDefaults[$propname])) {
            	$valueTxt = $this->getAttr($typeDefaults, $update, $propname, $prop['must'], true);
                $valueTxt = $valueTxt[1];
                //$valueTxt = $typeDefaults[$propname];
            } else {
                $valueTxt = '';
            }

            if(isset($prop['array']) && $prop['array']) {
                if(is_array($valueTxt))
                    $valueTxt = implode(',', $valueTxt);
            }

            switch($fieldType) {
                case 'boolean':
                    $options = Array(
                        '1' => l('yes'),
                        '0' => l('no'),
                    );
                    $valueTxt = $options[$valueTxt];
                    // FIXME: !isset($options[$value]) -> fallback to input field
                    $ret .= $this->selectField($propname, $options, $value, $hideField, $onChange);
                break;
                case 'dropdown':
                    $array    = isset($prop['array']) && $prop['array'];

                    $func = $this->MAPCFG->getListFunc($type, $propname);
                    // Handle case that e.g. host_names can not be fetched from backend by
                    // showing error text instead of fields
                    try {
                        if($this->cloneId !== null)
                            $options = $func($this->MAPCFG, $this->cloneId, $this->attrs);
                        else
                            $options = $func($this->MAPCFG, $objId, $this->attrs);

                        // When this is an associative array use labels instead of real values
                        // Change other arrays to associative ones for easier handling afterwards
                        if(!isset($options[0])) {
                            if(isset($options[$valueTxt]))
                                $valueTxt = $options[$valueTxt];
                        } else {
                            // Change the format to assoc array with null values
                            $new = Array();
                            foreach($options AS $val)
                                $new[$val] = false;
                            $options = $new;
                        }

                        // Fallback to an input field when the attribute has an value which is not
                        // an option in the select field
                        if($value != '' && !isset($options[$value])) {
                            // In case of "other" selected, the single objects can not be found, this is ok.
                            if (!$other) {
                                $this->setError(new FieldInputError($propname,
                                    l('Current value is not a known option - falling back to input field.')));
                            }

                            if ($other && $value === '<<<other>>>')
                                $value = '';

                            $ret .= $this->inputField($propname, $value, $hideField);
                            break;
                        }

                        if($other) {
                            $options['<<<other>>>'] = l('>>> Specify other');
                        }

                        $ret .= $this->selectField($propname, $options, $value, $hideField, $onChange);
                    } catch(BackendConnectionProblem $e) {
                        $this->setError(new FieldInputError($propname,
                            l('Unable to fetch data from backend - falling back to input field.')));
                        $ret .= $this->inputField($propname, $value, $hideField);
                    }
                break;
                case 'color':
                    $ret .= $this->colorSelect($propname, $value, $hideField);
                break;
                case 'dimension':
                    $ret .= $this->inputDimension($propname, $value, $hideField);
                break;
                case 'text':
                    $ret .= $this->inputField($propname, $value, $hideField);
                break;
            }
            
            // Try to split too long values in chunks
            // At the moment the only way is to try to add a space after each ",".
            // The browsers do break automatically at & and spaces - so no need to
            // do anything there. Seems to be enough for now
            if(strlen($valueTxt) > 36 && strpos($valueTxt, ' ') === false) {
                if(strpos($valueTxt, ',') !== false) {
                    $valueTxt = str_replace(',', ', ', $valueTxt);
                }
            }

            $ret .= '<span id="_txt_'.$propname.'"'.$hideTxt.'>'.htmlentities($valueTxt, ENT_COMPAT, 'UTF-8').'</span>';

            $ret .= '</td></tr>';

            if(isset($this->errors[$propname])) {
                foreach($this->errors[$propname] AS $err)
                    $ret .= '<tr><td colspan=3 class=err><div>' . $err . '</div></td></tr>';
                unset($this->errors[$propname]);
            }
        }

        // Errors left?
        if(count($this->errors) > 0) {
            foreach($this->errors AS $attr => $errors)
            foreach($errors AS $err)
                $ret .= '<tr><td colspan=3 class=err><div>'.$attr.': ' . $err . '</div></td></tr>';
        }

        return $ret;
    }

    private function colorSelect($propname, $value, $hideField) {
        return '<div id="'.$propname.'" class=picker'.$hideField.'>'
              .$this->inputField($propname, $value, '', $propname . '_inp')
              .'<a href="javascript:void(0);" onClick="togglePicker(\''.$propname.'_inp\');">'
              .'<img src="'.cfg('paths', 'htmlimages').'internal/picker.png" alt="'.l('Color select').'" />'
              .'</a></div>'
              .'<script>var o = document.getElementById("'.$propname.'_inp");'
              .'o.color = new jscolor.color(o, {pickerOnfocus:false,adjust:false,hash:true});'
              .'o = null;</script>';
    }

    private function inputDimension($propname, $value, $hideField) {
        return '<div id="'.$propname.'" class=picker'.$hideField.'>'
              .$this->inputField($propname, $value, '', $propname . '_inp')
              .'<a href="javascript:void(0);" onClick="pickWindowSize(\''.$propname.'_inp\', \''.$propname.'\');">'
              .'<img src="'.cfg('paths', 'htmlimages').'internal/dimension.png" alt="'.l('Get current size').'" />'
              .'</a></div>';
    }

    private function inputField($name, $value, $hideField, $id = null) {
        if($id === null)
            $id = $name;
        return '<input id="'.$id.'" type="text" name="'.$name.'" value="'.$value.'"'.$hideField.' />';
    }

    private function selectField($name, $arr, $value, $hideField, $onChange='') {
        $ret = '';
        $ret .= '<select id="'.$name.'" name="'.$name.'" onChange="'.$onChange.'"'.$hideField.'>';
        $ret .= '<option value=""></option>';
        foreach($arr AS $val => $label) {
            $selected = '';

            if($label === false)
                $label = $val;

            if($val == $value)
                $selected = ' selected';

            $ret .= '<option value="'.$val.'"'.$selected.'>'.$label.'</option>';
        }
        $ret .= '</select>';
        return $ret;
    }
}
?>
