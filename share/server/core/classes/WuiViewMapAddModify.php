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
    private $CORE;
    private $AUTHENTICATION;
    private $AUTHORISATION;
    private $MAPCFG = null;
    private $aOpts = null;

    /**
     * Class Constructor
     *
     * @param 	GlobalCore 	$CORE
     * @author 	Lars Michelsen <lars@vertical-visions.de>
     */
    public function __construct(CoreAuthHandler $AUTHENTICATION, CoreAuthorisationHandler $AUTHORISATION) {
        $this->CORE = GlobalCore::getInstance();
        $this->AUTHENTICATION = $AUTHENTICATION;
        $this->AUTHORISATION = $AUTHORISATION;
    }

    /**
     * Setter for the options array
     *
     * @param   Array   Array of options
     * @author  Lars Michelsen <lars@vertical-visions.de>
     */
    public function setOpts($a) {
        $this->aOpts = $a;
    }

    /**
     * Parses the information in html format
     *
     * @return	String 	String with Html Code
     * @author 	Lars Michelsen <lars@vertical-visions.de>
     */
    public function parse() {
        // Initialize template system
        $TMPL = New CoreTemplateSystem($this->CORE);
        $TMPLSYS = $TMPL->getTmplSys();

        $this->MAPCFG = new GlobalMapCfg($this->CORE, $this->aOpts['show']);
        try {
            $this->MAPCFG->readMapConfig();
        } catch(MapCfgInvalid $e) {}

        if($this->aOpts['do'] == 'modify') {
            $action = 'modifyObject';
        } else {
            $action = 'createObject';
        }

        $aData = Array(
            'htmlBase'     => cfg('paths', 'htmlbase'),
            'action'       => $action,
            'type'         => $this->aOpts['type'],
            'id'           => $this->aOpts['id'],
            'map'          => $this->aOpts['show'],
            'formContents' => $this->getFields().$this->fillFields(),
            'langSave'     => l('save'),
            'validMapCfg'  => json_encode($this->MAPCFG->getValidConfig()),
            'lang'         => $this->CORE->getJsLang(),
        );

        // Build page based on the template file and the data array
        return $TMPLSYS->get($TMPL->getTmplFile('default', 'wuiMapAddModify'), $aData);
    }

    /**
     * Fills the fields of the form with values
     *
     * @return	Array	JS Code
     * @author 	Lars Michelsen <lars@vertical-visions.de>
     * FIXME: Recode to javascript frontend (wui.js:get_click_pos())
     * FIXME: Recode to have all the HTML code in the template
   */
    function fillFields() {
        $ret = '<script type="Text/Javascript">';

        switch($this->aOpts['do']) {
            case 'add':
                if(isset($this->aOpts['clone']) && $this->aOpts['clone'] != '') {
                    // Get the options of the object to clone from map
                    foreach($this->MAPCFG->getValidTypeKeys($this->aOpts['type']) as $i => $key) {
                        if($key !== 'x' && $key !== 'y' && $key !== 'y' && $key !== 'object_id') {
                            $val = $this->MAPCFG->getValue($this->aOpts['clone'], $key, true);

                            if($val !== false) {
                                $ret .= 'document.addmodify.elements[\''.$key.'\'].value=\''.$val.'\';';
                                $ret .= 'toggleDefaultOption(\''.$key.'\');';

                                if($this->aOpts['type'] == 'service' && $key == 'host_name') {
                                    // Params: backend_id, type, host_name, field, selected
                                    $ret .= "getObjects(".
                                              "'".$this->MAPCFG->getValue($this->aOpts['clone'], 'backend_id')."',".
                                              "'".$this->aOpts['type']."',".
                                              "'service_description',".
                                              "'".$this->MAPCFG->getValue($this->aOpts['id'], 'service_description', true)."',".
                                              "'".$this->MAPCFG->getValue($this->aOpts['clone'], 'host_name', true)."'".
                                            ");";
                                }
                            }
                        }
                    }
                }

                if($this->aOpts['x'] != '') {
                    if ($this->aOpts['type'] == 'textbox') {
                        $x = explode(',', $this->aOpts['x']);
                        $objwidth  = $x[1] - $x[0];
                        $y = explode(',', $this->aOpts['y']);
                        $objheight = $y[1] - $y[0];
                        $ret .= 'document.addmodify.elements[\'x\'].value=\''.$x[0].'\';';
                        $ret .= 'document.addmodify.elements[\'y\'].value=\''.$y[0].'\';';
                        $ret .= 'document.addmodify.elements[\'w\'].value=\''.$objwidth.'\';';
                        $ret .= 'document.addmodify.elements[\'h\'].value=\''.$objheight.'\';';
                        $ret .= 'toggleDefaultOption(\'w\');';
                    } else {
                        $ret .= 'document.addmodify.elements[\'x\'].value=\''.$this->aOpts['x'].'\';';
                        $ret .= 'document.addmodify.elements[\'y\'].value=\''.$this->aOpts['y'].'\';';
                    }

                    $ret .= 'toggleDefaultOption(\'x\');';
                    $ret .= 'toggleDefaultOption(\'y\');';
                }

                // Scroll on top of page
                $ret .= 'document.addmodify.elements[\'x\'].focus()';
            break;
        }

        return $ret.'</script>';
    }

    /**
     * Gets all fields of the form
     *
     * @return	Array	HTML Code
     * @author 	Lars Michelsen <lars@vertical-visions.de>
     * FIXME: Recode to have all the HTML code in the template
     */
    function getFields() {
        $ret = '';

        // Form is not completely rendered; Only single fields are taken now
        $FORM = new WuiForm(Array('name' => 'addmodify', 'id' => 'addmodify'));

        // loop all valid properties for that object type
        foreach($this->MAPCFG->getValidObjectType($this->aOpts['type']) as $propname => $prop) {
            // Skip deprecated attributes
            if(isset($prop['deprecated']) && $prop['deprecated'] == '1') {
                continue;
            }

            $style = '';
            $class = '';
            $isDefaultValue = FALSE;
            $toggleFieldType = false;

            // Check if depends_on and depends_value are defined and if the value
            // is equal. If not equal hide the field
            if(isset($prop['depends_on']) && isset($prop['depends_value'])
                && $this->MAPCFG->getValue($this->aOpts['id'], $prop['depends_on'], FALSE) != $prop['depends_value']) {

                $style .= 'display:none;';
                $class = 'child-row';
            } elseif(isset($prop['depends_on']) && isset($prop['depends_value'])
                && $this->MAPCFG->getValue($this->aOpts['id'], $prop['depends_on'], FALSE) == $prop['depends_value']) {

                //$style .= 'display:;';
                $class = 'child-row';
            }

            // Create a "helper" field which contains the real applied value
            if($this->MAPCFG->getValue($this->aOpts['type'], $this->aOpts['id'], $propname, TRUE) === FALSE) {
                $ret .= $FORM->getHiddenField('_'.$propname, $this->MAPCFG->getValue($this->aOpts['id'], $propname, FALSE));
            } else {
                $ret .= $FORM->getHiddenField('_'.$propname, '');
                $ret .= $FORM->getHiddenField('_conf_'.$propname, $this->MAPCFG->getValue($this->aOpts['id'], $propname, TRUE));
            }

            // Set field type to show
            $fieldType = 'text';
            if(isset($prop['field_type'])) {
                $fieldType = $prop['field_type'];
            }

            switch($fieldType) {
                case 'dropdown':
                    // Default values
                    $options = Array();
                    $selected = '';
                    $onChange = 'validateMapConfigFieldValue(this)';

                    switch ($propname) {
                        case 'iconset':
                            $options = $this->CORE->getAvailableIconsets();
                            $selected = $this->MAPCFG->getValue($this->aOpts['id'], $propname, TRUE);
                        break;

                        case 'map_image':
                            $cfg = $this->MAPCFG->getValidConfig();
                            $options = $cfg['global']['map_image']['list']($this->CORE);
                            $selected = $this->MAPCFG->getValue($this->aOpts['id'], $propname, TRUE);
                        break;

                        case 'line_type':

                            $options = Array(Array('label' => '------><------',   'value' => '10'),
                                             Array('label' => '-------------->',  'value' => '11'),
                                     Array('label' => '---------------',  'value' => '12'),
                                     Array('label' => '--%--><--%--',     'value' => '13'),
                                     Array('label' => '--%+BW-><-%+BW--', 'value' => '14'));
                            $selected = $this->MAPCFG->getValue($this->aOpts['id'], $propname, TRUE);
                        break;

                        case 'hover_template':
                            $options = $this->CORE->getAvailableHoverTemplates();
                            $selected = $this->MAPCFG->getValue($this->aOpts['id'],$propname,TRUE);
                        break;

                        case 'hover_childs_order':
                            $options = Array(Array('label' => l('Ascending'), 'value'=>'asc'), Array('label' => l('Descending'), 'value' => 'desc'));
                            $selected = $this->MAPCFG->getValue($this->aOpts['id'],$propname,TRUE);
                        break;

                        case 'hover_childs_sort':
                            $options = Array(Array('label' => l('Alphabetically'), 'value'=>'a'), Array('label' => l('State'), 'value' => 's'));
                            $selected = $this->MAPCFG->getValue($this->aOpts['id'],$propname,TRUE);
                        break;

                        case 'header_template':
                            $options = $this->CORE->getAvailableHeaderTemplates();
                            $selected = $this->MAPCFG->getValue($this->aOpts['id'],$propname,TRUE);
                        break;

                        case 'map_name':
                            $options = array_merge($this->CORE->getAvailableMaps(), $this->CORE->getAvailableAutomaps());
                            $selected = $this->MAPCFG->getValue($this->aOpts['id'],$propname,TRUE);
                        break;

                        case 'context_template':
                            $options = $this->CORE->getAvailableContextTemplates();
                            $selected = $this->MAPCFG->getValue($this->aOpts['id'],$propname,TRUE);
                        break;

                        case 'icon':
                            $options = $this->CORE->getAvailableShapes();
                            $selected = $this->MAPCFG->getValue($this->aOpts['id'],$propname,TRUE);

                            if(preg_match("/^\[(.*)\]$/",$this->MAPCFG->getValue($this->aOpts['id'],$propname,TRUE),$match) > 0)
                                $toggleFieldType = true;
                        break;

                        case 'gadget_url':
                            $options = $this->CORE->getAvailableGadgets();
                            $selected = $this->MAPCFG->getValue($this->aOpts['id'],$propname,TRUE);
                        break;

                        case 'gadget_type':
                            $options = Array(Array('label' => 'Image', 'value' => 'img'),
                                             Array('label' => 'HTML',  'value' => 'html'));
                            $selected = $this->MAPCFG->getValue($this->aOpts['id'], $propname, TRUE);
                        break;

                        case 'view_type':
                            if($this->aOpts['type'] == 'service') {
                                $options = Array('icon', 'line', 'gadget');
                            } else {
                                $options = Array('icon', 'line');
                            }

                            if($this->aOpts['viewType'] != '') {
                                $selected = $this->aOpts['viewType'];
                            } else {
                                $selected = $this->MAPCFG->getValue($this->aOpts['id'],$propname,TRUE);
                            }
                        break;

                        case 'service_description':
                        break;

                        case 'backend_id':
                            if($this->aOpts['type'] == 'service') {
                                $field = 'host_name';
                                $type = 'host';
                            } else {
                                $field = $this->aOpts['type'] . '_name';
                                $type = $this->aOpts['type'];
                            }
                            $options = $this->CORE->getDefinedBackends();
                            $selected = $this->MAPCFG->getValue( $this->aOpts['id'], $propname, TRUE);
                            if($this->aOpts['type'] != 'global')
                                $onChange = "getObjects(this.value, '".$type."', '".$field."', '');"
                                           ."validateMapConfigFieldValue(this)";
                        break;

                        case 'host_name':
                        case 'hostgroup_name':
                        case 'servicegroup_name':
                            $backendId = $this->MAPCFG->getValue($this->aOpts['id'],'backend_id');
                            $selected = NULL;

                            if($this->aOpts['do'] == 'modify') {
                                $sSelected = $this->MAPCFG->getValue( $this->aOpts['id'], $propname, TRUE);
                            } else {
                                $sSelected = '';
                            }

                            // getObject params:
                            // 1. backend_id to use
                            // 2. object type to ask for
                            // 3. field name to fill
                            // 4. The value to select while filling the list (When it can not be found -> change to text field)
                            // 5. Filter name1 to use while asking the backend

                            // Fill the *_name list
                            $ret .= "<script type='text/Javascript'>getObjects('".$backendId."', '".preg_replace('/_name/i', '', $propname)."', '".$propname."', '".$sSelected."');</script>";

                            // When this is a service fill the service_description field
                            if($sSelected != '' && $propname == 'host_name' && $this->aOpts['type'] == 'service') {
                                $ret .= "<script type='text/Javascript'>getObjects('".$backendId."', '".$this->aOpts['type']."', 'service_description', '".$this->MAPCFG->getValue($this->aOpts['type'], $this->aOpts['id'], 'service_description', true)."', '".$sSelected."');</script>";
                            }

                            if($propname == 'host_name') {
                                if($this->aOpts['type'] == 'service') {
                                    $onChange = "getObjects(document.getElementById('backend_id').value, '".$this->aOpts['type']."', 'service_description', '', this.value);validateMapConfigFieldValue(this)";
                                } else {
                                    $onChange = 'validateMapConfigFieldValue(this)';
                                }
                            } else {
                                $onChange = 'validateMapConfigFieldValue(this)';
                            }
                        break;
                    }

                    // Reset selected when not modifying (e.g. adding)
                    if($this->aOpts['do'] != 'modify' && $propname != 'view_type') {
                        $selected = '';
                    }

                    // Print this when it is still a dropdown
                    if($fieldType == 'dropdown') {
                        // Give the users the option give manual input
                        $options[] = l('manualInput');

                        $ret .= $FORM->getSelectLine($propname, $propname, $options, $selected, $prop['must'], $onChange, '', $style, $class);

                        /* Add helper fields (Hidden by default and ignored on form submit)
                         * These helper fields will be displayed when the fields are changed
                         * to manual input. The user enters the text here and the text is set
                         * as value of the select fields on submit.
                         */
                        $ret .= $FORM->getInputLine($propname, '_inp_'.$propname, $selected, $prop['must'], $onChange, 'display:none ', $class);
                    }

                    // Toggle depending fields
                    if(isset($selected) && $selected != '') {
                        $ret .= '<script type="text/javascript">';
                        if($toggleFieldType) {
                            $ret .= 'var bChanged = toggleFieldType("'.$propname.'", "'.l('manualInput').'");';
                            $ret .= 'document.getElementById("'.$propname.'").value = "'.l('manualInput').'";';
                            $ret .= 'toggleDefaultOption("'.$propname.'", bChanged);';
                        }
                        $ret .= 'toggleDependingFields("addmodify", "'.$propname.'", "'.$selected.'");';
                        $ret .= '</script>';
                    }
                break;

                case 'boolean':
                    if($this->aOpts['do'] == 'modify') {
                        $value = $this->MAPCFG->getValue($this->aOpts['id'], $propname, TRUE);
                    } else {
                        $value = '';
                    }

                    $options = Array(
                        Array('label' => l('yes'), 'value' => '1'),
                        Array('label' => l('no'), 'value' => '0')
                    );

                    $ret .= $FORM->getSelectLine($propname, $propname, $options, $value, $prop['must'], 'validateMapConfigFieldValue(this)', '', $style, $class);

                    // Toggle depending fields
                    if(isset($value) && $value != '') {
                        $ret .= '<script lang="text/javascript">toggleDependingFields("addmodify", "'.$propname.'", "'.$value.'");</script>';
                    }
                break;

                case 'hidden':
                    // Do nothing with hidden objects
                break;

                case 'text':
                    // display a simple textbox
                    if($this->aOpts['do'] == 'modify') {
                        $value = $this->MAPCFG->getValue($this->aOpts['id'], $propname, TRUE);

                        if(is_array($value)) {
                            $value = implode(',', $value);
                        }

                        // Escape some bad chars
                        $value = str_replace('"','&quot;', $value);
                        $value = str_replace('\"','&quot;', $value);
                        $value = str_replace('\\\'','\'', $value);

                    } else {
                        $value = '';
                    }

                    $ret .= $FORM->getInputLine($propname, $propname, $value, $prop['must'], 'validateMapConfigFieldValue(this)', $style, $class);

                    // Toggle depending fields
                    if(isset($value) && $value != '') {
                        $ret .= '<script type="text/javascript">toggleDependingFields("addmodify", "'.$propname.'", "'.$value.'");</script>';
                    }
                break;
            }
        }

        return $ret;
    }
}
?>
