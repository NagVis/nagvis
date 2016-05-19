<?php
/*****************************************************************************
 *
 * ViewMapAddModify.php - Class to render the main configuration edit dialog
 *
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

class ViewMapAddModify {
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
            'sec'       => true,
        );
        $attrDefs = $this->MAPCFG->getValidObjectType($this->object_type);
        foreach ($_REQUEST as $attr => $val) {
            // $_REQUEST might contain cookie infos. Skipt them.
            if (isset($_COOKIE[$attr]))
                continue;
            if (substr($attr, 0, 7) == 'toggle_' || substr($attr, 0, 1) == '_' || isset($exclude[$attr]))
                continue;

            if ((isset($attrDefs[$attr]['must']) && $attrDefs[$attr]['must'] == '1')
                || !has_var('toggle_'.$attr)
                ||  get_checkbox('toggle_'.$attr)) {
                if (isset($attrDefs[$attr]['array']) && $attrDefs[$attr]['array'])
                    $val = explode(',', $val);
                $this->attrs[$attr] = $val;
            }
            else {
                $this->attrs_filtered[$attr] = null;
            }
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
                throw new FieldInputError($key, l('The attribute "[A]" is unknown.', array("A" => $key)));
            if(isset($attrDefs[$key]['deprecated']) && $attrDefs[$key]['deprecated'] === true)
                throw new FieldInputError($key, l('The attribute is deprecated.'));

            // The object has a match regex, it can be checked
            // -> In case of array attributes validate the single parts
            if(isset($attrDefs[$key]['match'])) {
                $array = isset($attrDefs[$key]['array']) && $attrDefs[$key]['array'];
                if(!$array)
                    $val = array($val);

                foreach($val as $part) {
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
        $perm        = get_checkbox('perm');
        $perm_user   = get_checkbox('perm_user');
        $show_dialog = false;

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

                js('document.getElementById("_submit").disabled = true;'
                  .'window.location.href = makeuri('.json_encode($attrs).');');
                $show_dialog = true;
            }
            elseif ($this->mode == 'view_params' && !$perm && $perm_user) {
                // This is the 2. case -> saving the options only for the user
                $USERCFG = new CoreUserCfg();
                $attrs = $this->attrs;
                unset($attrs['object_id']);

                $USERCFG->doSet(array(
                    'params-' . $this->MAPCFG->getName() => $attrs,
                ));

                scroll_up(); // On success, always scroll to top of page
                success(l('Personal settings saved.'));
                js('document.getElementById("_submit").disabled = true;'
                  .'window.setTimeout(function() { window.location.reload(); }, 2000);');
                $show_dialog = true;
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

                js('popupWindowClose();'
                  .'refreshMapObject(null, "'.$this->object_id.'", false);');
            }
        } else {
            // Create the new object
            $this->validateAttributes();

            // append a new object definition to the map configuration
            $obj_id = $this->MAPCFG->addElement($this->object_type, $this->attrs, true);

            js('popupWindowClose();'
              .'refreshMapObject(null, "'.$obj_id.'", false);');
        }

        // delete map lock
        if(!$this->MAPCFG->deleteMapLock())
            throw new NagVisException(l('mapLockNotDeleted'));
        return $show_dialog;
    }

    private function getAttr($default_value, $attr, $must, $only_inherited = false) {
        $update = !is_action() && is_update();
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

        } elseif(!$update && ($this->mode == 'view_params' || !$only_inherited) && $this->object_id !== null
                 && $this->MAPCFG->getValue($this->object_id, $attr, true) !== false) {
            // Get the value set in this object if there is some set
            $val = $this->MAPCFG->getValue($this->object_id, $attr, true);
            // In view_param mode this is inherited
            if($this->mode == 'view_params')
                $isInherited = true;

        } elseif(!$update && !$only_inherited && $this->clone_id !== null && $attr !== 'object_id'
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
        if ($fieldType === 'hidden' || $fieldType == 'readonly') {
            if($value != '')
                hidden($propname, $value);
            if ($fieldType === 'hidden')
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

        $can_have_other = $fieldType == 'dropdown' && isset($prop['other']) && $prop['other'];

        $onChange = '';
        // Submit the form when an attribute which has dependant attributes is changed
        if($this->MAPCFG->hasDependants($this->object_type, $propname)) {
            $onChange = 'updateForm(this.form);';
        }
        elseif (($can_have_other && $value !== '<<<other>>>')
                 || ($this->object_type == 'service' && $propname == 'host_name')) {

            if ($this->object_type == 'service'
                && ($propname == 'host_name' || $propname == 'backend_id')) {
                // When configuring services and the hostname changed, clear the service value.
                // When changing the backend_id, clear hostname and service
                if ($propname == 'backend_id')
                    $onChange .= "clearFormValue('host_name');";

                $onChange .= "clearFormValue('service_description');";

            } elseif ($this->object_type == 'aggr' && $propname == 'backend_id') {
                // For other objects clear the *_name value when backend_id changed
                $onChange .= "clearFormValue('name');";

            } elseif (($this->object_type == 'host' || $this->object_type == 'hostgroup'
                      || $this->object_type == 'servicegroup') && $propname == 'backend_id') {
                // For other objects clear the *_name value when backend_id changed
                $onChange .= "clearFormValue('".$this->object_type."_name');";
            }

            // If var is backend_id or var is host_name in service objects submit the form
            // to update the depdant lists.
            $onChange .= 'updateForm(this.form);';
        }

        // Add a checkbox to toggle the usage of an attribute. But only add it for
        // non-must attributes.
        if (!$prop['must'] && $fieldType != 'readonly') {
            checkbox('toggle_'.$propname, $isInherited === false, '', 'toggle_option(\''.$propname.'\');'.$onChange);
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
            case 'readonly':
                echo $value;
            break;
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
                    try {
                        if($this->clone_id !== null)
                            $options = $func($this->MAPCFG, $this->clone_id, $this->attrs);
                        else
                            $options = $func($this->MAPCFG, $this->object_id, $this->attrs);
                    } catch (Exception $e) {
                        if (is_a($e, "NagVisException"))
                            $msg = $e->message();
                        else
                            $msg = "".$e;

                        form_render_error($propname, l("Failed to get objects: [MSG]", array('MSG' => $msg)));
                        $options = array();
                    }

                    if(isset($options[$valueTxt]))
                        $valueTxt = $options[$valueTxt];

                    // Fallback to an input field when the attribute has an value which is not
                    // an option in the select field
                    if ($value != '' && !isset($options[$value]) && !in_array($value, $options)) {
                        // In case of "other" selected, the single objects can not be found, this is ok.
                        if (!$can_have_other)
                            form_render_error($propname, l('Current value is not a known option - '
                                                    .'falling back to input field.'));

                        input($propname, $value, '', $hideField);

                        // Value needs to be set to "" by js
                        if ($can_have_other && $value == '<<<other>>>')
                            js('clearFormValue(\''.$propname.'\');');

                        break;
                    }

                    if($can_have_other)
                        $options['<<<other>>>'] = l('>>> Specify other');

                    select($propname, $options, $value, $onChange, $hideField);
                } catch(BackendConnectionProblem $e) {
                    form_render_error($propname, l('Unable to fetch data from backend - '
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

    // Returns an array of property spec arrays which should be shown for the current object.
    // These are already filtered depnding on the configured sources (in case of map global obj)
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

    private function drawFields($sec_props) {
        foreach ($sec_props as $propname => $prop) {
            // do nothing with hidden or deprecated attributes
            if (isset($prop['deprecated']) && $prop['deprecated'] === true)
                continue;

            $this->drawField($propname, $prop, $sec_props);
        }
    }

    private function drawForm() {
        js_form_start('addmodify');

        $obj_spec = $this->getProperties();
        $props_by_section = array();
        foreach ($obj_spec AS $propname => $prop) {
            $sec = $prop['section'];
            if (!isset($props_by_section[$sec]))
                $props_by_section[$sec] = array();
            $props_by_section[$sec][$propname] = $prop;
        }

        $sections = array();
        foreach (array_keys($props_by_section) AS $sec)
            if ($sec != 'hidden')
                $sections[$sec] = $this->MAPCFG->getSectionTitle($sec);

        $open = get_open_section('general');
        render_section_navigation($open, $sections);

        foreach ($props_by_section as $sec => $sec_props) {
            if ($sec != 'hidden') {
                render_section_start($sec, $open);
                echo '<table class="mytable">';
                $this->drawFields($sec_props);
                echo '</table>';
                render_section_end();
            }
        }

        if ($this->mode == 'view_params') {
            echo '<table class=mytable>';
            echo '<tr><td class=tdlabel style="width:70px">'.l('Make permanent').'</td>';
            echo '<td class=tdfield>';
            checkbox('perm');
            echo l('for all users').'<br>';
            checkbox('perm_user');
            echo l('for you');
            echo '</td></tr>';
            echo '</table>';
        }
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
            // FIXME: When to ignore?
            //$this->MAPCFG->skipSourceErrors();
            $this->MAPCFG->readMapConfig();
        } catch(MapCfgInvalid $e) {}

        $this->clone_id = req('clone_id');
        if ($this->clone_id !== null && !preg_match(MATCH_OBJECTID, $this->clone_id))
            throw new NagVisException(l('Invalid clone object id'));

        $this->object_id = req('object_id');
        if ($this->object_id !== null) {
            // Give the sources the chance to load the object
            $this->MAPCFG->handleSources('load_obj', $this->object_id);

            if (!$this->MAPCFG->objExists($this->object_id))
                throw new NagVisException(l('The object does not exist.'));

            // 'object_id' is only set when modifying existing objects
            $this->object_type = $this->MAPCFG->getValue($this->object_id, 'type');
        } else {
            // Creating/Cloning new object. The type is set by URL
            $this->object_type = req('type');
            $this->object_id   = null;
        }

        $this->filterMapAttrs();

        // Don't handle submit actions when the 'update' POST attribute is set
        if (is_action()) {
            try {
                if (!$this->handleAddModify())
                    return ob_get_clean();
            } catch (FieldInputError $e) {
                form_error($e->field, $e->message());
            } catch (NagVisException $e) {
                form_error(null, $e->message());
            } catch (Exception $e) {
                if (isset($e->msg))
                    form_error(null, $e->msg);
                else
                    throw $e;
            }
        }
        $this->drawForm();

        return ob_get_clean();
    }
}
?>
