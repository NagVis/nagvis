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
    private $MAPCFG = null;
    private $attrs  = null;
    private $map    = null;
    private $errors = Array();

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
    }

    /**
     * Parses the information in html format
     *
     * @return	String 	String with Html Code
     * @author 	Lars Michelsen <lars@vertical-visions.de>
     */
    public function parse($err, $success) {
        // Initialize template system
        $TMPL = New CoreTemplateSystem($this->CORE);
        $TMPLSYS = $TMPL->getTmplSys();

        if($err !== null)
            $this->setError($err);

        $aData = Array(
            'htmlBase'     => cfg('paths', 'htmlbase'),
            'show'         => $this->map,
            'successMsg'   => ($success !== null) ? $success : '',
            'formContents' => $this->getFields(),
            'attrs'        => $this->attrs,
            'langSave'     => l('save'),
        );

        // Build page based on the template file and the data array
        return $TMPLSYS->get($TMPL->getTmplFile('default', 'wuiMapAddModify'), $aData);
    }

    /**
     * Renders the input fields of the add/modify form
     */
    function getFields() {
        $ret = '';

        // 'type' is set when creating new objects. Otherwise only the object_id is known
        if(isset($this->attrs['type']) && $this->attrs['type'] != '') {
            $type  = $this->attrs['type'];
            $objId = 0;
        } else {
            $type  = $this->MAPCFG->getValue($this->attrs['object_id'], 'type');
            $objId = $this->attrs['object_id'];
        }

        // loop all valid properties for that object type
        foreach($this->MAPCFG->getValidObjectType($type) as $propname => $prop) {
            // Set field type to show
            $fieldType = 'text';
            if(isset($prop['field_type']))
                $fieldType = $prop['field_type'];

            // do nothing with hidden or deprecated attributes
            if($fieldType == 'hidden' || (isset($prop['deprecated']) && $prop['deprecated'] === true))
                continue

            $value = '';
            $inherited = false;
            // Use url given values when there is some and remove it from the attr list.
            // The url values left will be added as hidden attributes to the form later
            if(isset($this->attrs[$propname])) {
                $value = $this->attrs[$propname];
            } elseif(isset($this->attrs['object_id'])
                     && $this->MAPCFG->getValue($this->attrs['object_id'], $propname, true) !== false) {
                // Get the value set in this object if there is some set
                $value = $this->MAPCFG->getValue($this->attrs['object_id'], $propname, true);
            } elseif(!$prop['must']) {
                // Get the inherited value - but only for non-must attributes
                $value     = $this->MAPCFG->getValue($objId, $propname, false);
                $inherited = true;
            }
            unset($this->attrs[$propname]);

            $rowClasses = Array();
            if($prop['must'])
                array_push($rowClasses, 'must');

            $ret .= '<tr class="'.implode(' ', $rowClasses).'"><td class=tdlabel>'.$propname.'</td><td class=tdfield>';
            
            // Add a checkbox to toggle the usage of an attribute. But only add it for non-must
            // attributes.
            if(!$prop['must']) {
                $checked = '';
                if(!$inherited)
                    $checked = ' checked'; 
                $ret .= '<input type="checkbox" name="toggle_'.$propname.'"'.$checked.' onclick="toggle_option(\''.$propname.'\')" value=on />';
            }
            
            $ret .= '</td><td class=tdfield>';

            // Display as text if inherited, otherwise display the input fields
            if(!$inherited) {
                $hideTxt   = ' style="display:none"';
                $hideField = '';
            } else {
                $hideTxt   = '';
                $hideField = ' style="display:none"';
            }
            
            // Prepare translation of value to a nice display string in case of
            // e.g. boolean fields
            $valueTxt = $value;

            switch($fieldType) {
                case 'boolean':
                    $options = Array(
                        '1' => l('yes'),
                        '0' => l('no'),
                    );
                    $valueTxt = $options[$value];
                    // FIXME: !isset($options[$value]) -> fallback to input field
                    $ret .= $this->selectField($propname, $options, $value, $hideField);
                break;
                case 'dropdown':
                    // If var is backend_id or var is host_name in service objects submit the form
                    // to update the depdant lists
                    $onChange = '';
                    if($propname == 'backend_id' || ($type == 'service' && $propname == 'host_name'))
                        $onChange = 'document.getElementById(\'commit\').click();';

                    $func = $this->MAPCFG->getListFunc($type, $propname);
                    // Handle case that e.g. host_names can not be fetched from backend by
                    // showing error text instead of fields
                    try {
                        $options = $func($this->CORE, $this->MAPCFG, $objId);

                        // When this is an associative array use labels instead of real values
                        // Change other arrays to associative ones for easier handling afterwards
                        if(!isset($options[0])) {
                            if(isset($options[$value]))
                                $valueTxt = $options[$value];
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
                case 'text':
                    $ret .= $this->inputField($propname, $value, $hideField);
                break;
            }

            $ret .= '<span id="_txt_'.$propname.'"'.$hideTxt.'>'.$valueTxt.'</span>';

            $ret .= '</td></tr>';

            if(isset($this->errors[$propname]))
                foreach($this->errors[$propname] AS $err)
                    $ret .= '<tr><td colspan=3 class=err><div>' . $err . '</div></td></tr>';
        }

        return $ret;
    }

    private function inputField($name, $value, $hideField) {
        return '<input id="'.$name.'" type="text" name="'.$name.'" value="'.$value.'"'.$hideField.' />';
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
