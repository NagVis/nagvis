<?php
/*****************************************************************************
 *
 * WuiViewMapAddModify.php - Class to render the main configuration edit dialog
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
class WuiViewMapAddModify {
    private $MAPCFG      = null;
    private $attrs       = null;
    private $hiddenAttrs = null;
    private $map         = null;
    private $errors      = Array();
    private $cloneId     = null;

    /**
     * Class Constructor
     */
    public function __construct($map) {
        $this->map  = $map;
        $this->CORE = GlobalCore::getInstance();

        $this->MAPCFG = new GlobalMapCfg($this->CORE, $map);
        try {
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
            'htmlBase'     => cfg('paths', 'htmlbase'),
            'show'         => $this->map,
            'successMsg'   => ($success !== null) ? $success : '',
            'formContents' => $this->getFields($update),
            'attrs'        => $this->hiddenAttrs,
            'langSave'     => l('save'),
        );

        // Build page based on the template file and the data array
        return $TMPLSYS->get($TMPL->getTmplFile('default', 'wuiMapAddModify'), $aData);
    }

    private function getAttr($typeDefaults, $update, $attr, $must) {
        $val = '';
        $inherited = false;
        // Use url given values when there is some and remove it from the attr list.
        // The url values left will be added as hidden attributes to the form later
        if(isset($this->attrs[$attr])) {
            $val = $this->attrs[$attr];

        } elseif(!$update && isset($this->attrs['object_id'])
                 && $this->MAPCFG->getValue($this->attrs['object_id'], $attr, true) !== false) {
            // Get the value set in this object if there is some set
            // But don't try this when running in "update" mode
            $val = $this->MAPCFG->getValue($this->attrs['object_id'], $attr, true);

        } elseif(!$update && $this->cloneId !== null
                 && $this->MAPCFG->getValue($this->cloneId, $attr, true) !== false) {
            // Get the value set in the object to be cloned if there is some set
            // But don't try this when running in "update" mode
            $val = $this->MAPCFG->getValue($this->cloneId, $attr, true);

        } elseif(!$must && isset($typeDefaults[$attr])) {
            // Get the inherited value - but only for non-must attributes
            $val = $typeDefaults[$attr];
            $inherited = true;
        }
        return Array($inherited, $val);
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
        $typeDef      = $this->MAPCFG->getValidObjectType($type);

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

            $isArrayValue = isset($prop['array']) && $prop['array'];

            list($inherited, $value) = $this->getAttr($typeDefaults, $update, $propname, $prop['must']);
            unset($this->hiddenAttrs[$propname]);

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

            $onChange = '';
            // Submit the form when an attribute which has dependant attributes is changed
            if($this->MAPCFG->hasDependants($type, $propname))
                $onChange = 'document.getElementById(\'update\').value=\'1\';document.getElementById(\'commit\').click();';
            
            // Add a checkbox to toggle the usage of an attribute. But only add it for non-must
            // attributes.
            if(!$prop['must']) {
                $checked = '';
                if($inherited === false)
                    $checked = ' checked'; 
                $ret .= '<input type="checkbox" name="toggle_'.$propname.'"'.$checked.' onclick="toggle_option(\''.$propname.'\');'.$onChange.'" value=on />';
            }
            
            $ret .= '</td><td class=tdfield>';

            // Display as text if inherited, otherwise display the input fields
            if($inherited === false) {
                $hideTxt   = ' style="display:none"';
                $hideField = '';
            } else {
                $hideTxt   = '';
                $hideField = ' style="display:none"';
            }
            
            // Prepare translation of value to a nice display string in case of
            // e.g. boolean fields
            if(isset($typeDefaults[$propname]))
                $valueTxt = $typeDefaults[$propname];
            else
                $valueTxt = '';

            if(isset($prop['array']) && $prop['array']) {
                if(is_array($valueTxt))
                    $valueTxt = implode(',', $valueTxt);
                if(is_array($value))
                    $value    = implode(',', $value);
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
                    // If var is backend_id or var is host_name in service objects submit the form
                    // to update the depdant lists.
                    if($onChange == '' && ($propname == 'backend_id'
                       || ($type == 'service' && $propname == 'host_name')))
                        $onChange = 'document.getElementById(\'update\').value=\'1\';document.getElementById(\'commit\').click();';

                    $func = $this->MAPCFG->getListFunc($type, $propname);
                    // Handle case that e.g. host_names can not be fetched from backend by
                    // showing error text instead of fields
                    try {
                        if($this->cloneId !== null)
                            $options = $func($this->CORE, $this->MAPCFG, $this->cloneId, $this->attrs);
                        else
                            $options = $func($this->CORE, $this->MAPCFG, $objId, $this->attrs);

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
                            $this->setError(new FieldInputError($propname,
                                l('Current value is not a known option - falling back to input field.')));
                            $ret .= $this->inputField($propname, $value, $hideField);
                            break;
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

            $ret .= '<span id="_txt_'.$propname.'"'.$hideTxt.'>'.$valueTxt.'</span>';

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
