<?php
/*****************************************************************************
 *
 * WuiAddModify.php - Class for adding/modifying objects on the map
 *
 * Copyright (c) 2004-2008 NagVis Project (Contact: lars@vertical-visions.de)
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
class WuiAddModify extends GlobalPage {
	var $CORE;
	var $MAINCFG;
	var $MAPCFG;
	var $LANG;
	var $FORM;
	var $prop;
	var $propertiesList;
	
	/**
	 * Class Constructor
	 *
	 * @param 	GlobalMainCfg 	$CORE
	 * @param 	WuiMapCfg 		$MAPCFG
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	function WuiAddModify(&$CORE, &$MAPCFG, $prop) {
		$this->CORE = &$CORE;
		$this->MAINCFG = &$CORE->MAINCFG;
		$this->LANG = &$CORE->LANG;
		
		$this->MAPCFG = &$MAPCFG;
		$this->prop = $prop;
		
		$prop = Array('title' => $CORE->MAINCFG->getValue('internal', 'title'),
					  'jsIncludes'=>Array('./includes/js/addmodify.js'),
					  'extHeader'=> '',
					  'allowedUsers' => Array('EVERYONE'),
					  'languageRoot' => 'nagvis');
		parent::__construct($CORE, $prop);
	}
	
	/**
	 * If enabled, the form is added to the page
	 *
	 * @author Lars Michelsen <lars@vertical-visions.de>
	 */
	function getForm() {
		$code = '';
		
		if($this->prop['action'] == 'modify') {
			$action = 'modifyMapObject';
		} else {
			$action = 'createMapObject';
		}
		
		$this->FORM = new GlobalForm(Array('name' => 'addmodify',
			'id' => 'addmodify',
			'method' => '',
			'action' => 'javascript:(validateMapCfgForm()) ? formSubmit(\'addmodify\', \''.$action.'\') : alert(\'\');',
			'cols' => '2'));
		
		$code .= $this->getJsIncludes();
		$code .= $this->FORM->initForm();
		$code .= $this->getFields();
		$code .= $this->parseJs($this->fillFields());
		$code .= $this->FORM->getSubmitLine($this->LANG->getText('save'));
		$code .= $this->FORM->closeForm();
		
		return $code;
	}
	
	/**
	 * Fills the fields of the form with values
	 *
	 * @return	Array	JS Code
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 * FIXME: Recode to javascript frontend (wui.js:get_click_pos())
   */
	function fillFields() {
		$ret = '';
		
		switch($this->prop['action']) {
			case 'modify':
				if($this->prop['coords'] != '') {
					$myval = $this->prop['id'];
					$val_coords = explode(',',$this->prop['coords']);
					if ($this->prop['type'] == 'textbox') {
						$objwidth = $val_coords[2] - $val_coords[0];
						$ret .= 'document.addmodify.elements[\'x\'].value=\''.$val_coords[0].'\';';
						$ret .= 'document.addmodify.elements[\'y\'].value=\''.$val_coords[1].'\';';
						$ret .= 'document.addmodify.elements[\'w\'].value=\''.$objwidth.'\';';
						$ret .= 'toggleDefaultOption(\'w\');';
					} else {
						$ret .= 'document.addmodify.elements[\'x\'].value=\''.$val_coords[0].','.$val_coords[2].'\';';
						$ret .= 'document.addmodify.elements[\'y\'].value=\''.$val_coords[1].','.$val_coords[3].'\';';
					}
				}
			break;
			
			case 'add':
				if($this->prop['coords'] != '') {
					$val_coords = explode(',',$this->prop['coords']);
					if(count($val_coords) == 2) {			
						$ret .= 'document.addmodify.elements[\'x\'].value=\''.$val_coords[0].'\';';
						$ret .= 'document.addmodify.elements[\'y\'].value=\''.$val_coords[1].'\';';
					} elseif(count($val_coords) == 4) {
						if ($this->prop['type'] == 'textbox') {
							$objwidth = $val_coords[2] - $val_coords[0];
							
							$ret .= 'document.addmodify.elements[\'x\'].value=\''.$val_coords[0].'\';';
							$ret .= 'document.addmodify.elements[\'y\'].value=\''.$val_coords[1].'\';';
							$ret .= 'document.addmodify.elements[\'w\'].value=\''.$objwidth.'\';';
							$ret .= 'toggleDefaultOption(\'w\');';
						} else {
							$ret .= 'document.addmodify.elements[\'x\'].value=\''.$val_coords[0].','.$val_coords[2].'\';';
							$ret .= 'document.addmodify.elements[\'y\'].value=\''.$val_coords[1].','.$val_coords[3].'\';';
						}
					}
					
					$ret .= 'toggleDefaultOption(\'x\');';
					$ret .= 'toggleDefaultOption(\'y\');';
				}
			break;
		}
		
		return $ret;
	}
	
	/**
	 * Gets all fields of the form
	 *
	 * @return	Array	HTML Code
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
     */
	function getFields() {
		$ret = '';
		$ret .= $this->FORM->getHiddenField('type',$this->prop['type']);
		$ret .= $this->FORM->getHiddenField('id',$this->prop['id']);
		$ret .= $this->FORM->getHiddenField('map',$this->MAPCFG->getName());
		
		// loop all valid properties for that object type
		foreach($this->MAPCFG->getValidObjectType($this->prop['type']) as $propname => $prop) {
			$style = '';
			$class = '';
			$isDefaultValue = FALSE;
			
			// Check if depends_on and depends_value are defined and if the value
			// is equal. If not equal hide the field
			if(isset($prop['depends_on']) && isset($prop['depends_value'])
				&& $this->MAPCFG->getValue($this->prop['type'], $this->prop['id'], $prop['depends_on'], FALSE) != $prop['depends_value']) {
				
				$style .= 'display:none;';
				$class = 'child-row';
			} elseif(isset($prop['depends_on']) && isset($prop['depends_value'])
				&& $this->MAPCFG->getValue($this->prop['type'], $this->prop['id'], $prop['depends_on'], FALSE) == $prop['depends_value']) {
				
				//$style .= 'display:;';
				$class = 'child-row';
			}
			
			// Create a "helper" field which contains the real applied value
			if($this->MAPCFG->getValue($this->prop['type'], $this->prop['id'], $propname, TRUE) === FALSE) {
				$ret .= $this->FORM->getHiddenField('_'.$propname, $this->MAPCFG->getValue($this->prop['type'], $this->prop['id'], $propname, FALSE));
			} else {
				$ret .= $this->FORM->getHiddenField('_'.$propname, '');
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
							$selected = $this->MAPCFG->getValue($this->prop['type'],$this->prop['id'],$propname,TRUE);
						break;
						
						case 'map_image':
							$options = $this->CORE->getAvailableBackgroundImages();
							$selected = $this->MAPCFG->getValue($this->prop['type'],$this->prop['id'],$propname,TRUE);
						break;
						
						case 'line_type':
							$options = Array(Array('label' => '------><------', 'value' => '10'), Array('label' => '-------------->', 'value'=>'11'));
							$selected = $this->MAPCFG->getValue($this->prop['type'],$this->prop['id'],$propname,TRUE);
						break;
						
						case 'hover_template':
							$options = $this->CORE->getAvailableHoverTemplates();
							$selected = $this->MAPCFG->getValue($this->prop['type'],$this->prop['id'],$propname,TRUE);
						break;
						
						case 'hover_childs_order':
							$options = Array(Array('label' => $this->LANG->getText('Ascending'), 'value'=>'asc'), Array('label' => $this->LANG->getText('Descending'), 'value' => 'desc'));
							$selected = $this->MAPCFG->getValue($this->prop['type'],$this->prop['id'],$propname,TRUE);
						break;
						
						case 'hover_childs_sort':
							$options = Array(Array('label' => $this->LANG->getText('Alphabetically'), 'value'=>'a'), Array('label' => $this->LANG->getText('State'), 'value' => 's'));
							$selected = $this->MAPCFG->getValue($this->prop['type'],$this->prop['id'],$propname,TRUE);
						break;
						
						case 'header_template':
							$options = $this->CORE->getAvailableHeaderTemplates();
							$selected = $this->MAPCFG->getValue($this->prop['type'],$this->prop['id'],$propname,TRUE);
						break;
						
						case 'map_name':
							$options = $this->CORE->getAvailableMaps('/[^(__automap)]/');
							$selected = $this->MAPCFG->getValue($this->prop['type'],$this->prop['id'],$propname,TRUE);
						break;
						
						case 'context_template':
							$options = $this->CORE->getAvailableContextTemplates();
							$selected = $this->MAPCFG->getValue($this->prop['type'],$this->prop['id'],$propname,TRUE);
						break;
						
						case 'icon':
							$options = $this->CORE->getAvailableShapes();
							$selected = $this->MAPCFG->getValue($this->prop['type'],$this->prop['id'],$propname,TRUE);
							
							if(preg_match("/^\[(.*)\]$/",$this->MAPCFG->getValue($this->prop['type'],$this->prop['id'],$propname,TRUE),$match) > 0) {
								$fieldType = 'textbox';
								$ret .= $this->FORM->getInputLine($propname,$propname,$this->MAPCFG->getValue($this->prop['type'],$this->prop['id'],$propname,TRUE),$prop['must'],'validateMapConfigFieldValue(this)');
							}
						break;
						
						case 'gadget_url':
							$options = $this->CORE->getAvailableGadgets();
							$selected = $this->MAPCFG->getValue($this->prop['type'],$this->prop['id'],$propname,TRUE);
						break;
						
						case 'view_type':
							if($this->prop['type'] == 'service') {
								$options = Array('icon', 'line', 'gadget');
							} else {
								$options = Array('icon', 'line');
							}
							
							if($this->prop['viewType'] != '') {
								$selected = $this->prop['viewType'];
							} else {
								$selected = $this->MAPCFG->getValue($this->prop['type'],$this->prop['id'],$propname,TRUE);
							}
						break;
						
						case 'service_description':
						break;
						
						case 'backend_id':
							if($this->prop['type'] == 'service') {
								$field = 'host_name';
								$type = 'host';
							} else {
								$field = $this->prop['type'] . '_name';
								$type = $this->prop['type'];
							}
							$options = $this->CORE->getDefinedBackends();
							$selected = $this->MAPCFG->getValue($this->prop['type'], $this->prop['id'], $propname, TRUE);
							$onChange = "getObjects(this.value,'".$type."','".$field."','".$this->MAPCFG->getValue($this->prop['type'],$this->prop['id'],$propname,TRUE)."');validateMapConfigFieldValue(this)";
						break;
						
						case 'host_name':
						case 'hostgroup_name':
						case 'servicegroup_name':
							$backendId = $this->MAPCFG->getValue($this->prop['type'],$this->prop['id'],'backend_id');
							$selected = NULL;
							
							$ret .= "<script type='text/Javascript'>getObjects('".$backendId."','".preg_replace('/_name/i','',$propname)."','".$propname."','".$this->MAPCFG->getValue($this->prop['type'],$this->prop['id'],$propname,TRUE)."');</script>";
							if(in_array('service_description', $this->MAPCFG->getValidTypeKeys($this->prop['type']))) {
								$ret .= "<script type='text/Javascript'>getServices('".$backendId."', '".$this->prop['type']."', '".$this->MAPCFG->getValue($this->prop['type'], $this->prop['id'], $propname, TRUE)."','service_description','".$this->MAPCFG->getValue($this->prop['type'],$this->prop['id'],'service_description',TRUE)."');</script>";
							}
							
							if($propname == 'host_name') {
								if($this->prop['type'] == 'service') {
									$onChange = "getServices('".$backendId."','".$this->prop['type']."',this.value,'service_description','".$this->MAPCFG->getValue($this->prop['type'],$this->prop['id'],$propname,TRUE)."');validateMapConfigFieldValue(this)";
								} else {
									$onChange = 'validateMapConfigFieldValue(this)';
								}
							} else {
								$onChange = 'validateMapConfigFieldValue(this)';
							}
						break;
					}
					
					// Print this when it is still a dropdown
					if($fieldType == 'dropdown') {
						// Give the users the option give manual input
						$options[] = $this->LANG->getText('manualInput');
						
						$ret .= $this->FORM->getSelectLine($propname, $propname, $options, $selected, $prop['must'], $onChange, '', $style, $class);
						
						/* Add helper fields (Hidden by default and ignored on form submit)
						 * These helper fields will be displayed when the fields are changed
						 * to manual input. The user enters the text here and the text is set
						 * as value of the select fields on submit.
						 */
						$ret .= $this->FORM->getInputLine($propname, '_inp_'.$propname, $selected, $prop['must'], $onChange, 'display:none ', $class);
					}
					
					// Toggle depending fields
					if(isset($selected) && $selected != '') {
						$ret .= $this->parseJs('toggleDependingFields("'.$propname.'", "'.$selected.'");');
					}                                                                         
				break;
				
				case 'boolean':
					$value = $this->MAPCFG->getValue($this->prop['type'], $this->prop['id'], $propname, TRUE);
					
					// display a listbox with "yes/no" for boolean options 
					$options = Array(Array('label' => $this->LANG->getText('yes'), 'value'=>'1'), Array('label' => $this->LANG->getText('no'), 'value'=>'0'));
					$ret .= $this->FORM->getSelectLine($propname, $propname, $options, $value, $prop['must'], 'validateMapConfigFieldValue(this)', '', $style, $class);
					
					// Toggle depending fields
					if(isset($value) && $value != '') {
						$ret .= $this->parseJs('toggleDependingFields("'.$propname.'", "'.$value.'");');
					}
				break;
				
				case 'hidden':
					// Do nothing with hidden objects
				break;
				
				case 'text':
					// display a simple textbox
					$value = $this->MAPCFG->getValue($this->prop['type'],$this->prop['id'],$propname,TRUE);
					
					if(is_array($value)) {
						$value = implode(',',$value);
					}
					
					// Escape some bad chars
					$value = str_replace('"','&quot;', $value);
					$value = str_replace('\"','&quot;', $value);
					$value = str_replace('\\\'','\'', $value);
					
					$ret .= $this->FORM->getInputLine($propname, $propname, $value, $prop['must'], 'validateMapConfigFieldValue(this)', $style, $class);
					
					// Toggle depending fields
					if(isset($value) && $value != '') {
						$ret .= $this->parseJs('toggleDependingFields("'.$propname.'", "'.$value.'");');
					}
				break;
			}
		}
		
		return $ret;
	}
}
?>
