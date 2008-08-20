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
	var $propCount;
	
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
		$this->propCount = 0;
		
		$prop = Array('title' => $CORE->MAINCFG->getValue('internal', 'title'),
					  'cssIncludes'=>Array('./includes/css/wui.css'),
					  'jsIncludes'=>Array('../nagvis/includes/js/ajax.js','./includes/js/addmodify.js',
					  					  './includes/js/ajax.js',
					  					  './includes/js/wui.js'),
					  'extHeader'=>Array(''),
					  'allowedUsers' => Array('EVERYONE'),
					  'languageRoot' => 'nagvis');
		parent::GlobalPage($CORE, $prop);
	}
	
	/**
	 * If enabled, the form is added to the page
	 *
	 * @author Lars Michelsen <lars@vertical-visions.de>
	 */
	function getForm() {
		// Inititalize language for JS, Write JS Array for config validation
		$this->addBodyLines($this->parseJs($this->getJsLang()));
		
		$this->FORM = new GlobalForm(Array('name'=>'addmodify',
			'id'=>'addmodify',
			'method'=>'POST',
			'action'=>'./form_handler.php?myaction='.$this->prop['action'],
			'onSubmit'=>'return check_object();',
			'cols'=>'2'));
		$this->addBodyLines($this->FORM->initForm());
		$this->addBodyLines($this->getFields());
		$this->addBodyLines($this->parseJs($this->fillFields()));
		$this->addBodyLines($this->FORM->getSubmitLine($this->LANG->getText('save')));
		$this->addBodyLines($this->FORM->closeForm());
		$this->addBodyLines($this->parseJs($this->resizeWindow(410,($this->propCount*30+80))));
	}
	
	/**
	 * Fills the fields of the form with values
	 *
	 * @return	Array	JS Code
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
     */
	function fillFields() {
		$ret = Array();
		
		switch($this->prop['action']) {
			case 'modify':
				if($this->prop['coords'] != '') {
					$myval = $this->prop['id'];
					$val_coords = explode(',',$this->prop['coords']);
					if ($this->prop['type'] == 'textbox') {
						$objwidth = $val_coords[2] - $val_coords[0];
						$ret[] = 'document.addmodify.elements[\'x\'].value=\''.$val_coords[0].'\';';
						$ret[] = 'document.addmodify.elements[\'y\'].value=\''.$val_coords[1].'\';';
						$ret[] = 'document.addmodify.elements[\'w\'].value=\''.$objwidth.'\';';
					} else {
						$ret[] = 'document.addmodify.elements[\'x\'].value=\''.$val_coords[0].','.$val_coords[2].'\';';
						$ret[] = 'document.addmodify.elements[\'y\'].value=\''.$val_coords[1].','.$val_coords[3].'\';';
					}
				}
			break;
			// if the action specified in the URL is "add", we set the object coordinates (that we retrieve from the mycoords parameter)
			case 'add':
				if($this->prop['coords'] != '') {
					$val_coords = explode(',',$this->prop['coords']);
					if(count($val_coords) == 2) {			
						$ret[] = 'document.addmodify.elements[\'x\'].value=\''.$val_coords[0].'\';';
						$ret[] = 'document.addmodify.elements[\'y\'].value=\''.$val_coords[1].'\';';
					} elseif(count($val_coords) == 4) {
						if ($this->prop['type'] == 'textbox') {
							$objwidth = $val_coords[2] - $val_coords[0];
							
							$ret[] = 'document.addmodify.elements[\'x\'].value=\''.$val_coords[0].'\';';
							$ret[] = 'document.addmodify.elements[\'y\'].value=\''.$val_coords[1].'\';';
							$ret[] = 'document.addmodify.elements[\'w\'].value=\''.$objwidth.'\';';
						} else {
							$ret[] = 'document.addmodify.elements[\'x\'].value=\''.$val_coords[0].','.$val_coords[2].'\';';
							$ret[] = 'document.addmodify.elements[\'y\'].value=\''.$val_coords[1].','.$val_coords[3].'\';';
						}		
					}
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
		$ret = Array();
		$ret = array_merge($ret,$this->FORM->getHiddenField('type',$this->prop['type']));
		$ret = array_merge($ret,$this->FORM->getHiddenField('id',$this->prop['id']));
		$ret = array_merge($ret,$this->FORM->getHiddenField('map',$this->MAPCFG->getName()));
		$ret = array_merge($ret,$this->FORM->getHiddenField('properties',''));
		
		// loop all properties
		foreach($this->MAPCFG->validConfig[$this->prop['type']] as $propname => $prop) {
			if($propname == "iconset") {
				// treat the special case of iconset, which will display a listbox instead of the normal textbox
				$ret = array_merge($ret,$this->FORM->getSelectLine($propname,$propname,$this->CORE->getAvailableIconsets(),$this->MAPCFG->getValue($this->prop['type'],$this->prop['id'],$propname,TRUE),$prop['must'],'validateMapConfigFieldValue(this)'));
				$this->propCount++;
			} elseif($this->prop['type'] == 'shape' && $propname == "icon") {
				// treat the special case of icon when type is shape, which will display a listbox instead of the normal textbox
				if(preg_match("/^\[(.*)\]$/",$this->MAPCFG->getValue($this->prop['type'],$this->prop['id'],$propname,TRUE),$match) > 0) {
					$ret = array_merge($ret,$this->FORM->getInputLine($propname,$propname,$this->MAPCFG->getValue($this->prop['type'],$this->prop['id'],$propname,TRUE),$prop['must'],'validateMapConfigFieldValue(this)'));
				} else {
					$ret = array_merge($ret,$this->FORM->getSelectLine($propname,$propname,$this->CORE->getAvailableShapes(),$this->MAPCFG->getValue($this->prop['type'],$this->prop['id'],$propname,TRUE),$prop['must'],'validateMapConfigFieldValue(this)'));
				}
				$this->propCount++;
			}  elseif($propname == "map_image") {
				// treat the special case of map_image, which will display a listbox instead of the normal textbox
				$ret = array_merge($ret,$this->FORM->getSelectLine($propname,$propname,$this->CORE->getAvailableBackgroundImages(),$this->MAPCFG->getValue($this->prop['type'],$this->prop['id'],$propname,TRUE),$prop['must'],'validateMapConfigFieldValue(this)'));
				$this->propCount++;
			} elseif($propname == "recognize_services" || $propname == "only_hard_states" || $propname == "label_show" || $propname == "usegdlibs" || $propname == "show_in_lists" || $propname == "hover_menu" || $propname == "hover_childs_show") {
				// treat the special case of recognize_services, which will display a "yes/no" listbox instead of the normal textbox
				$opt = Array(Array('label' => $this->LANG->getText('yes'),'value'=>'1'),Array('label' => $this->LANG->getText('no'),'value'=>'0'));
				$ret = array_merge($ret,$this->FORM->getSelectLine($propname,$propname,$opt,$this->MAPCFG->getValue($this->prop['type'],$this->prop['id'],$propname,TRUE),$prop['must'],'validateMapConfigFieldValue(this)'));
				$this->propCount++;
			} elseif($propname == "backend_id") {
				if($this->prop['type'] == 'service') {
					$field = 'host_name';
					$type = 'host';
				} else {
					$field = $this->prop['type'] . '_name';
					$type = $this->prop['type'];
				}
				$ret = array_merge($ret,$this->FORM->getSelectLine($propname,$propname,$this->CORE->getDefinedBackends(),$this->MAPCFG->getValue($this->prop['type'],$this->prop['id'],$propname,TRUE),$prop['must'],"getObjects(this.value,'".$type."','".$field."','".$this->MAPCFG->getValue($this->prop['type'],$this->prop['id'],$propname,TRUE)."');validateMapConfigFieldValue(this);"));
				$this->propCount++;
			} elseif($propname == "line_type") {
				// treat the special case of line_type, which will display a listbox showing the different possible shapes for the line
				$opt = Array(Array('label' => '------><------','value' => '0'),Array('label' => '-------------->','value'=>'1'));
				$ret = array_merge($ret,$this->FORM->getSelectLine($propname,$propname,$opt,substr($this->MAPCFG->getValue($this->prop['type'],$this->prop['id'],$propname,TRUE),1,1),$prop['must'],'validateMapConfigFieldValue(this)'));
				$this->propCount++;
			} elseif($propname == "hover_template") {
				$ret = array_merge($ret,$this->FORM->getSelectLine($propname,$propname,$this->CORE->getAvailableHoverTemplates(),$this->MAPCFG->getValue($this->prop['type'],$this->prop['id'],$propname,TRUE),$prop['must'],'validateMapConfigFieldValue(this)'));
				$this->propCount++;
			} elseif($propname == "header_template") {
				$ret = array_merge($ret,$this->FORM->getSelectLine($propname,$propname,$this->CORE->getAvailableHeaderTemplates(),$this->MAPCFG->getValue($this->prop['type'],$this->prop['id'],$propname,TRUE),$prop['must'],'validateMapConfigFieldValue(this)'));
				$this->propCount++;
			} elseif($propname == "map_name") {
				// treat the special case of map_name, which will display a listbox instead of the normal textbox
				$ret = array_merge($ret,$this->FORM->getSelectLine($propname,$propname,$this->CORE->getAvailableMaps('/[^(__automap)]/'),$this->MAPCFG->getValue($this->prop['type'],$this->prop['id'],$propname,TRUE),$prop['must'],'validateMapConfigFieldValue(this)'));
				$this->propCount++;
			} elseif(($propname == 'host_name' || $propname == 'hostgroup_name' || $propname == 'servicegroup_name')) {
				$backendId = $this->MAPCFG->getValue($this->prop['type'],$this->prop['id'],'backend_id');
				$ret[] = "<script>getObjects('".$backendId."','".preg_replace('/_name/i','',$propname)."','".$propname."','".$this->MAPCFG->getValue($this->prop['type'],$this->prop['id'],$propname,TRUE)."');</script>";
				if(array_key_exists('service_description',$this->MAPCFG->validConfig[$this->prop['type']])) {
					$ret[] = "<script>getServices('".$backendId."','".$this->prop['type']."','".$this->MAPCFG->getValue($this->prop['type'],$this->prop['id'],$propname,TRUE)."','service_description','".$this->MAPCFG->getValue($this->prop['type'],$this->prop['id'],'service_description',TRUE)."');</script>";
				}
				if($propname == 'host_name') {
					if($this->prop['type'] == 'service') {
						$ret = array_merge($ret,$this->FORM->getSelectLine($propname,$propname,Array(),'',$prop['must'],"getServices('".$backendId."','".$this->prop['type']."',this.value,'service_description','".$this->MAPCFG->getValue($this->prop['type'],$this->prop['id'],$propname,TRUE)."');validateMapConfigFieldValue(this)"));
					} else {
						$ret = array_merge($ret,$this->FORM->getSelectLine($propname,$propname,Array(),'',$prop['must'],'validateMapConfigFieldValue(this)'));
					}
				} else {
					$ret = array_merge($ret,$this->FORM->getSelectLine($propname,$propname,Array(),'',$prop['must'],'validateMapConfigFieldValue(this)'));
				}
				$this->propCount++;
			} elseif($propname == 'service_description') {
				$ret = array_merge($ret,$this->FORM->getSelectLine($propname,$propname,Array(),'',$prop['must'],'validateMapConfigFieldValue(this)'));
			} elseif($propname == 'type') {
				// Do nothing, type is only internal
			} else {
				// display a simple textbox
				$value = $this->MAPCFG->getValue($this->prop['type'],$this->prop['id'],$propname,TRUE);
				
				if(is_array($value)) {
					$value = implode(',',$value);
				}
				
				$ret = array_merge($ret,$this->FORM->getInputLine($propname,$propname,$value,$prop['must'],'validateMapConfigFieldValue(this)'));
				$this->propCount++;
			}
		}
		
		return $ret;
	}
	
	/**
	 * Gets all needed error messages for WUI
	 *
	 * @return	Array JS
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
     */
	function getJsLang() {
		$ret = Array();
		$ret[] = 'var lang = Array();';
		$ret[] = 'var user = \''.$this->MAINCFG->getRuntimeValue('user').'\';';
		$ret[] = 'lang[\'unableToWorkWithMap\'] = \''.$this->LANG->getText('unableToWorkWithMap').'\';';
		$ret[] = 'lang[\'mustValueNotSet\'] = \''.$this->LANG->getText('mustValueNotSet').'\';';
		$ret[] = 'lang[\'chosenLineTypeNotValid\'] = \''.$this->LANG->getText('chosenLineTypeNotValid').'\';';
		$ret[] = 'lang[\'onlyLineOrIcon\'] = \''.$this->LANG->getText('onlyLineOrIcon').'\'';
		$ret[] = 'lang[\'not2coordsX\'] = \''.$this->LANG->getText('not2coords','COORD~X').'\';';
		$ret[] = 'lang[\'not2coordsY\'] = \''.$this->LANG->getText('not2coords','COORD~Y').'\';';
		$ret[] = 'lang[\'only1or2coordsX\'] = \''.$this->LANG->getText('only1or2coords','COORD~X').'\';';
		$ret[] = 'lang[\'only1or2coordsY\'] = \''.$this->LANG->getText('only1or2coords','COORD~Y').'\';';
		$ret[] = 'lang[\'lineTypeNotSelectedX\'] = \''.$this->LANG->getText('lineTypeNotSelected','COORD~X').'\';';
		$ret[] = 'lang[\'lineTypeNotSelectedY\'] = \''.$this->LANG->getText('lineTypeNotSelected','COORD~Y').'\';';
		$ret[] = 'lang[\'loopInMapRecursion\'] = \''.$this->LANG->getText('loopInMapRecursion').'\';';
		$ret[] = 'lang[\'mapObjectWillShowSummaryState\'] = \''.$this->LANG->getText('mapObjectWillShowSummaryState').'\';';
		
		return $ret;
	}
}
?>
