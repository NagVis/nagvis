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
    private $error = null;
    private $MAPCFG = null;

    // object related vars
    private $object_id   = null;
    private $object_type = null;
    private $clone_id    = null;

    private $attrs          = array();
    private $attrs_filtered = array();
    
    // Filter the attributes using the helper fields
    // Each attribute can have the toggle_* field set. If present
    // use it's value to filter out the attributes
    private function filterMapAttrs() {
        $exclude = Array(
            'mod'       => true,
            'act'       => true,
            'show'      => true,
            'clone_id'  => true,
            'mode'      => true,
            'perm'      => true,
            'perm_user' => true,
            'lang'      => true,
        );
        foreach ($_REQUEST as $attr => $val) {
            if (substr($attr, 0, 7) == 'toggle_' || substr($attr, 0, 1) == '_' || isset($exclude[$attr]))
                continue;

            if (!get_checkbox('toggle_'.$attr))
                $this->attrs_filtered[$attr] = null;
            else
                $this->attrs[$attr] = $val;
        }
    }

    private function validateAttributes() {
        $attrDefs = $this->MAPCFG->getValidObjectType($this->object_type);
        // Are some must values missing?
        foreach($attrDefs as $propname => $prop) {
            if(isset($prop['must']) && $prop['must'] == '1') {
                // In case of "source" options only validate the ones which belong
                // to currently enabled sources
                if(isset($prop['source_param']) && !in_array($prop['source_param'], $this->MAPCFG->getValue(0, 'sources')))
                    continue;
                
                if (!isset($this->attrs[$propname]) || $this->attrs[$propname] == '')
                    throw new FieldInputError($propname, l('The attribute needs to be set.'));
            }
        }

        // FIXME: Are all given attrs valid ones?
        foreach($this->attrs AS $key => $val) {
            if(!isset($attrDefs[$key]))
                throw new FieldInputError($key, l('The attribute is unknown.'));
            if(isset($attrDefs[$key]['deprecated']) && $attrDefs[$key]['deprecated'] === true)
                throw new FieldInputError($key, l('The attribute is deprecated.'));

            // The object has a match regex, it can be checked
            // -> In case of array attributes validate the single parts
            if(isset($attrDefs[$key]['match'])) {
                $array = isset($attrDefs[$key]['array']) && $attrDefs[$key]['array'];
                if(!$array)
                    $v = array($val);
                else
                    $v = explode(',', $val);

                foreach($v as $part) {
                    if(!preg_match($attrDefs[$key]['match'], $part)) {
                        throw new FieldInputError($key, l('The attribute has the wrong format (Regex: [MATCH]).',
                            Array('MATCH' => $attrDefs[$key]['match'])));
                    }
                }
            }
        }
    }


    // Validate and process addModify form submissions
    private function handleAddModify() {
        $perm      = get_checkbox('perm');
        $perm_user = get_checkbox('perm_user');

        // Modification/Creation?
        // The object_id is known on modification. When it is not known 'type' is set
        // to create new objects
        if($this->object_id !== null) {
            // The handler has been called in "view_params" mode. In this case the user has
            // less options and the options to
            // 1. modify these parameters only for the current open view
            // 2. Save the changes for himselfs
            // 3. Save the changes to the map config (-> Use default code below)
            if ($this->mode == 'view_params' && !$perm && !$perm_user) {
                // This is the 1. case -> redirect the user to a well formated url
                $attrs = array_merge($this->attrs, $this->attrs_filtered);
                unset($attrs['object_id']);
                $result = array(0, $attrs, null);
            }
            elseif ($this->mode == 'view_params' && !$perm && $perm_user) {
                // This is the 2. case -> saving the options only for the user
                $USERCFG = new CoreUserCfg();
                $attrs = $this->attrs;
                unset($attrs['object_id']);
                $USERCFG->doSet(array(
                    'params-' . $this->MAPCFG->getName() => $attrs,
                ));
                $result = array(2, null, l('Personal settings saved.'));
            }
            else {
                if (!$this->MAPCFG->objExists($this->object_id))
                    throw new NagVisException(l('The object does not exist.'));

                $this->validateAttributes();

                // Update the map configuration   
                if($this->mode == 'view_params') {
                    // Only modify/add the given attributes. Don't remove any
                    // set options in the array
                    foreach($this->attrs as $key => $val)
                        $this->MAPCFG->setValue($this->object_id, $key, $val);
                    $this->MAPCFG->storeUpdateElement($this->object_id);
                } else {
                    // add/modify case: Rewrite whole object with the given attributes
                    $this->MAPCFG->updateElement($this->object_id, $this->attrs, true);
                }

                $t = $this->object_type == 'global' ? l('map configuration') : $this->object_type;
                $result = array(2, null, l('The [TYPE] has been modified. Reloading in 2 seconds.',
                                                               Array('TYPE' => $t)));
            }
        } else {
            // Create the new object
            $this->validateAttributes();

            // append a new object definition to the map configuration
            $this->MAPCFG->addElement($this->object_type, $this->attrs, true);

            $result = array(2, null, l('The [TYPE] has been added. Reloading in 2 seconds.',
                                                            Array('TYPE' => $this->object_type)));
        }

        // delete map lock
        if(!$this->MAPCFG->deleteMapLock())
            throw new NagVisException(l('mapLockNotDeleted'));

        return $result;
    }

    private function getAttr($default_value, $attr, $must, $only_inherited = false) {
        $update = !is_action();
        // update is true during view repaint
        // only_inherited is true when only asking for inherited value
        $val = '';
        $isInherited = false;
        if(!$only_inherited && isset($this->attrs[$attr])) {
            // Use user provided value, from GET or POST
            $val = $this->attrs[$attr];
        } elseif(!$update && $this->object_id === '0'
                 && $this->MAPCFG->getSourceParam($attr, true, true) !== null) {
            // Get the value set by url if there is some set
            // But don't try this when running in "update" mode
            //
            // In case of global sections handle the source parameters which might affect
            // the shown values
            $val = $this->MAPCFG->getSourceParam($attr, true, true);

        } elseif(($this->mode == 'view_params' || !$only_inherited) && $this->object_id !== null
                 && $this->MAPCFG->getValue($this->object_id, $attr, true) !== false) {
            // Get the value set in this object if there is some set
            $val = $this->MAPCFG->getValue($this->object_id, $attr, true);
            // In view_param mode this is inherited
            if($this->mode == 'view_params')
                $isInherited = true;

        } elseif((!$update || $only_inherited) && $this->clone_id !== null
                 && $this->MAPCFG->getValue($this->clone_id, $attr, true) !== false) {
            // Get the value set in the object to be cloned if there is some set
            // But don't try this when running in "update" mode
            $val = $this->MAPCFG->getValue($this->clone_id, $attr, true);

        } elseif(!$must && $default_value !== null) {
            // Get the inherited value
            $val = $default_value;
            $isInherited = true;
        }
        return Array($isInherited, $val);
    }

    private function colorSelect($propname, $value, $hideField) {
        echo '<div id="'.$propname.'" class=picker style="'.$hideField.'">';
        input($propname, $value, '', '', $propname . '_inp');
        echo '<a href="javascript:void(0);" onClick="togglePicker(\''.$propname.'_inp\');">';
        echo '<img src="'.cfg('paths', 'htmlimages').'internal/picker.png" alt="'.l('Color select').'" />';
        echo '</a></div>';
        js('var o = document.getElementById(\''.$propname.'_inp\');'
          .'o.color = new jscolor.color(o, {pickerOnfocus:false,adjust:false,hash:true});'
          .'o = null;');
    }

    private function inputDimension($propname, $value, $hideField) {
        echo '<div id="'.$propname.'" class=picker style="'.$hideField.'">';
        input($propname, $value, '', '', $propname . '_inp');
        echo '<a href="javascript:void(0);" onClick="pickWindowSize(\''.$propname.'_inp\', \''.$propname.'\');">';
        echo '<img src="'.cfg('paths', 'htmlimages').'internal/dimension.png" alt="'.l('Get current size').'" />';
        echo '</a></div>';
    }

    private function drawField($propname, $prop, $properties) {
        $default_value = $this->MAPCFG->getDefaultValue($this->object_type, $propname);

        // Set field type to show
        $fieldType = 'text';
        if (isset($prop['field_type']))
            $fieldType = $prop['field_type'];

        list($isInherited, $value) = $this->getAttr($default_value, $propname, $prop['must']);

        if(isset($prop['array']) && $prop['array']) {
            if(is_array($value))
                $value = implode(',', $value);
        }

        // Only add the fields of type hidden which have values
        if($fieldType === 'hidden') {
            if($value != '')
                hidden($propname, $value);
            return;
        }

        $rowHide    = '';
        $rowClasses = Array();

        // Check if depends_on and depends_value are defined and if the value
        // is equal. If not equal hide the field
        // Don't hide dependent fields where the dependant is not set
        if(isset($prop['depends_on'])
           && isset($prop['depends_value'])
           && isset($properties[$prop['depends_on']])) {
            array_push($rowClasses, 'child-row');
            $dep_on_propname = $prop['depends_on'];
            list($depInherited, $depValue) = $this->getAttr(
                $this->MAPCFG->getDefaultValue($this->object_type, $dep_on_propname),
                $dep_on_propname, $properties[$dep_on_propname]['must']);
            if($depValue != $prop['depends_value'])
                $rowHide = ' style="display:none"';
        }

        // Highlight the must attributes
        if($prop['must'])
            array_push($rowClasses, 'must');

        echo '<tr class="'.implode(' ', $rowClasses).'"'.$rowHide.'>';
        echo '<td class=tdlabel>'.$propname.'</td><td class=tdbox>';

        $other = $fieldType == 'dropdown' && isset($prop['other']) && $prop['other'];

        $onChange = '';
        // Submit the form when an attribute which has dependant attributes is changed
        if($this->MAPCFG->hasDependants($this->object_type, $propname)) {
            $onChange = 'updateForm(this.form);';
        }
        elseif ($onChange == '' && (($other && $value !== '<<<other>>>')
                                    || ($this->object_type == 'service' && $propname == 'host_name'))) {
            // If var is backend_id or var is host_name in service objects submit the form
            // to update the depdant lists.
            $onChange = 'updateForm(this.form);';
        }
        
        // Add a checkbox to toggle the usage of an attribute. But only add it for
        // non-must attributes.
        if (!$prop['must']) {
            checkbox('toggle_'.$propname, $isInherited === false, '', 'toggle_option(\''.$propname.'\');');
        }
        
        echo '</td><td class=tdfield>';

        // Display as text if inherited, otherwise display the input fields
        if ($isInherited === false) {
            $hideTxt   = ' style="display:none"';
            $hideField = '';
        } else {
            $hideTxt   = '';
            $hideField = 'display:none;';
        }
        
        // Prepare translation of value to a nice display string in case of
        // e.g. boolean fields
        if ($this->mode == 'view_params' || $default_value !== null) {
            $valueTxt = $this->getAttr($default_value, $propname, $prop['must'], true);
            $valueTxt = $valueTxt[1];
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
                select($propname, $options, $value, $onChange, $hideField);
            break;
            case 'dropdown':
                $array    = isset($prop['array']) && $prop['array'];

                $func = $this->MAPCFG->getListFunc($this->object_type, $propname);
                // Handle case that e.g. host_names can not be fetched from backend by
                // showing error text instead of fields
                try {
                    if($this->clone_id !== null)
                        $options = $func($this->MAPCFG, $this->clone_id, $_POST);
                    else
                        $options = $func($this->MAPCFG, $this->object_id, $_POST);

                    if(isset($options[$valueTxt]))
                        $valueTxt = $options[$valueTxt];

                    // Fallback to an input field when the attribute has an value which is not
                    // an option in the select field
                    if ($value != '' && !isset($options[$value]) && !in_array($value, $options)) {
                        // In case of "other" selected, the single objects can not be found, this is ok.
                        if (!$other)
                            form_error($propname, l('Current value is not a known option - '
                                                    .'falling back to input field.'));

                        if ($other && $value === '<<<other>>>')
                            $value = '';

                        input($propname, $value, '', $hideField);
                        break;
                    }

                    if($other)
                        $options['<<<other>>>'] = l('>>> Specify other');

                    select($propname, $options, $value, $onChange, $hideField);
                } catch(BackendConnectionProblem $e) {
                    form_error($propname, l('Unable to fetch data from backend - '
                                           .'falling back to input field.'));
                    input($propname, $value, '', $hideField);
                }
            break;
            case 'color':
                $this->colorSelect($propname, $value, $hideField);
            break;
            case 'dimension':
                $this->inputDimension($propname, $value, $hideField);
            break;
            case 'text':
                input($propname, $value, '', $hideField);
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

        echo '<span id="_txt_'.$propname.'"'.$hideTxt.'>';
        echo htmlentities($valueTxt, ENT_COMPAT, 'UTF-8');
        echo '</span>';

        echo '</td></tr>';
    }

    private function getProperties() {
        $default_value = $this->MAPCFG->getDefaultValue($this->object_type, 'sources');

        list($isInherited, $sources) = $this->getAttr($default_value, 'sources', false);

        if ($this->object_id === '0' && $sources != '<<<other>>>') {
            // Special handling for the global section:
            // It might contain some source related parameters (if some sources are enabled).
            // Another speciality ist that the dialog can be opened in "view_params" mode
            // where only the view params (parameters modifyable by the user) shal be shown.
            if($this->mode == 'view_params') {
                $source_params = $this->MAPCFG->getSourceParams(false, false, true);
                $typeDef = $this->MAPCFG->getSourceParamDefs(array_keys($source_params));
            } else {
                $source_params = $this->MAPCFG->getSourceParams();
                $typeDef = $this->MAPCFG->getValidObjectType($this->object_type);

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
            $typeDef = $this->MAPCFG->getValidObjectType($this->object_type);
        }
        return $typeDef;
    }

    private function drawFields() {
        // loop all valid properties for that object type
        $properties = $this->getProperties();
        foreach ($properties as $propname => $prop) {
            // do nothing with hidden or deprecated attributes
            if($propname === 'object_id'
               || (isset($prop['deprecated']) && $prop['deprecated'] === true))
                continue;

            $this->drawField($propname, $prop, $properties);
        }
    }

    private function drawForm() {
        js_form_start('addmodify');
        echo '<table name="mytable" class="mytable" id="table_addmodify">';
        $this->drawFields();

        if ($this->mode == 'view_params') {        
            echo '<tr><td colspan=3 style="height:10px;"></td></tr>';
            echo '<tr><td class=tdlabel>'.l('Make Permanent').'</td>';
            echo '<td class=tdbox></td>';
            echo '<td class=tdfield>';
            checkbox('perm');
            echo l('for all users').'<br>';
            checkbox('perm_user');
            echo l('for you');
            echo '</td></tr>';
        }
        echo '</table>';
        submit(l('Save'));
        form_end();
    }
                   
    public function parse() {
        global $CORE;
        ob_start();

        // mode is set to view_params if only the "view parameters" dialog is handled in this request.
        // This dialog has less options and is primary saved for the user and not for all users in the
        // map configuration
        $this->mode = req('mode', 'addmodify');

        $map_name = req('show');
        if ($this->mode != 'view_params' && $map_name == null)
            throw new NagVisException(l('You need to provide a map name.'));

        if ($map_name !== null && (!preg_match(MATCH_MAP_NAME, $map_name)
                                   || count($CORE->getAvailableMaps('/^'.$map_name.'$/')) == 0))
            throw new NagVisException(l('The map does not exist.'));

        $this->MAPCFG = new GlobalMapCfg($map_name);
        try {
            $this->MAPCFG->skipSourceErrors();
            $this->MAPCFG->readMapConfig();
        } catch(MapCfgInvalid $e) {}

        $this->clone_id = req('clone_id');
        if ($this->clone_id !== null && !preg_match(MATCH_OBJECTID, $this->clone_id))
            throw new NagVisException(l('Invalid clone object id'));

        $this->object_id = req('object_id');
        if ($this->object_id !== null) {
            // 'object_id' is only set when modifying existing objects
            $this->object_type = $this->MAPCFG->getValue($this->object_id, 'type');
        } else {
            // Creating/Cloning new object. The type is set by URL
            $this->object_type = post('type');
            $this->object_id   = '0';
        }

        // FIXME: Check whether or not the object exists

        $this->filterMapAttrs();

        // Don't handle submit actions when the 'update' POST attribute is set
        if (is_action()) {
            try {
                $success = $this->handleAddModify();

                scroll_up(); // On success, always scroll to top of page

                $reload_time = $success[0];
                if ($success[2] !== null) {
                    success($success[2]);
                    js('document.getElementById("_submit").disabled = true;'
                      .'window.setTimeout(function() { window.location.reload(); }, '.$reload_time.'*1000);');
                }
                elseif ($success[1] !== null) {
                    js('document.getElementById("_submit").disabled = true;'
                      .'window.setTimeout(function() { window.location.href = '
                      .'makeuri('.json_encode($success[1]).'); }, '.$reload_time.'*1000);');
                }
            } catch (NagVisException $e) {
                form_error(null, $e->msg);
            } catch (FieldInputError $e) {
                form_error($e->field, $e->msg);
            } catch (Exception $e) {
                if (isset($e->msg))
                    form_error(null, $e->msg);
                else
                    throw $e;
            }
        }
        echo $this->error;

        $this->drawForm();

        return ob_get_clean();
    }
}
?>
