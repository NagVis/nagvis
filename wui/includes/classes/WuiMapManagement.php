<?php
/*****************************************************************************
 *
 * WuiMapManagement.php - Class for managing maps in WUI
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
class WuiMapManagement extends GlobalPage {
	var $CORE;
	var $MAINCFG;
	var $LANG;
	var $CREATEFORM;
	var $RENAMEFORM;
	var $DELETEFORM;
	var $EXPORTFORM;
	var $IMPORTFORM;
	var $propCount;
	
	/**
	 * Class Constructor
	 *
	 * @param	GlobalMainCfg	$CORE
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	function WuiMapManagement(&$CORE) {
		$this->CORE = &$CORE;
		$this->MAINCFG = &$CORE->MAINCFG;
		$this->LANG = &$CORE->LANG;
		
		$prop = Array('title' => $this->CORE->MAINCFG->getValue('internal', 'title'),
					  'jsIncludes'=>Array('./includes/js/MapManagement.js'),
					  'extHeader'=> '',
					  'allowedUsers' => $this->CORE->MAINCFG->getValue('wui','allowedforconfig'),
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
		$code .= $this->getJsIncludes();
		
		$this->CREATEFORM = new GlobalForm(Array('name'=>'map_create',
			'id'=>'map_create',
			'method'=>'POST',
			'action'=>'./form_handler.php?myaction=mgt_map_create',
			'onSubmit'=>'return check_create_map();',
			'cols'=>'2'));
		
		$code .= $this->CREATEFORM->initForm();
		$code .= $this->CREATEFORM->getCatLine($this->LANG->getText('createMap'));
		$code .= $this->getCreateFields();
		$code .= $this->getSubmit($this->CREATEFORM,$this->LANG->getText('create'));
		
		$this->RENAMEFORM = new GlobalForm(Array('name'=>'map_rename',
			'id'=>'map_rename',
			'method'=>'POST',
			'action'=>'./form_handler.php?myaction=mgt_map_rename',
			'onSubmit'=>'return check_map_rename();',
			'cols'=>'2'));
		
		$code .= $this->RENAMEFORM->initForm();
		$code .= $this->RENAMEFORM->getCatLine($this->LANG->getText('renameMap'));
		$code .= $this->getRenameFields();
		$code .= $this->getSubmit($this->RENAMEFORM,$this->LANG->getText('rename'));
		
		$this->DELETEFORM = new GlobalForm(Array('name'=>'map_delete',
			'id'=>'map_delete',
			'method'=>'POST',
			'action'=>'./form_handler.php?myaction=mgt_map_delete',
			'onSubmit'=>'return check_map_delete();',
			'cols'=>'2'));
		
		$code .= $this->DELETEFORM->initForm();
		$code .= $this->DELETEFORM->getCatLine($this->LANG->getText('deleteMap'));
		$code .= $this->getDeleteFields();
		$code .= $this->getSubmit($this->DELETEFORM,$this->LANG->getText('delete'));
		
		$this->EXPORTFORM = new GlobalForm(Array('name'=>'map_export',
			'id'=>'map_export',
			'method'=>'POST',
			'action'=>'./form_handler.php?myaction=mgt_map_export',
			'onSubmit'=>'return check_map_export();',
			'cols'=>'2'));
		
		$code .= $this->EXPORTFORM->initForm();
		$code .= $this->EXPORTFORM->getCatLine($this->LANG->getText('exportMap'));
		$code .= $this->getExportFields();
		$code .= $this->getSubmit($this->EXPORTFORM,$this->LANG->getText('export'));
		
		$this->IMPORTFORM = new GlobalForm(Array('name'=>'map_import',
			'id'=>'map_import',
			'method'=>'POST',
			'action'=>'./form_handler.php?myaction=mgt_map_import',
			'onSubmit'=>'return check_map_import();',
			'enctype'=>'multipart/form-data',
			'cols'=>'2'));
		
		$code .= $this->IMPORTFORM->initForm();
		$code .= $this->IMPORTFORM->getCatLine($this->LANG->getText('importMap'));
		$code .= $this->getImportFields($this->IMPORTFORM);
		$code .= $this->getSubmit($this->IMPORTFORM,$this->LANG->getText('import'));
		
		return $code;
	}
	
	/**
	 * Gets export fields
	 *
	 * @return	Array	HTML Code
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
     */
	function getExportFields() {
		return $this->EXPORTFORM->getSelectLine($this->LANG->getText('chooseMap'),'map_name',$this->CORE->getAvailableMaps('/[^(__automap)]/'),'');
	}
	
	/**
	 * Gets import fields
	 *
	 * @return	Array	HTML Code
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
     */
	function getImportFields() {
		return $this->IMPORTFORM->getHiddenField('MAX_FILE_SIZE','1000000')
		      .$this->IMPORTFORM->getFileLine($this->LANG->getText('chooseMapFile'),'map_file','');
	}
	
	/**
	 * Gets delete fields
	 *
	 * @return	Array	HTML Code
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
     */
	function getDeleteFields() {
		return $this->DELETEFORM->getSelectLine($this->LANG->getText('chooseMap'),'map_name',$this->CORE->getAvailableMaps('/[^(__automap)]/'),'')
		      .$this->DELETEFORM->getHiddenField('map','')
		      .'<script>document.map_rename.map.value=document.mapname</script>';
	}
	
	/**
	 * Gets rename fields
	 *
	 * @return	Array	HTML Code
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
     */
	function getRenameFields() {
		return $this->RENAMEFORM->getSelectLine($this->LANG->getText('chooseMap'),'map_name',$this->CORE->getAvailableMaps('/[^(__automap)]/'),'')
		      .$this->RENAMEFORM->getInputLine($this->LANG->getText('newMapName'),'map_new_name','')
		      .$this->RENAMEFORM->getHiddenField('map','')
		      .'<script>document.map_rename.map.value=document.mapname</script>';
	}
	
	/**
	 * Gets create fields
	 *
	 * @return	Array	HTML Code
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
     */
	function getCreateFields() {
		return $this->CREATEFORM->getInputLine($this->LANG->getText('mapName'),'map_name','')
		      .$this->CREATEFORM->getInputLine($this->LANG->getText('readUsers'),'allowed_users','')
		      .$this->CREATEFORM->getInputLine($this->LANG->getText('writeUsers'),'allowed_for_config','')
		      .$this->CREATEFORM->getSelectLine($this->LANG->getText('mapIconset'),'map_iconset',$this->CORE->getAvailableIconsets(),$this->MAINCFG->getValue('defaults','icons'))
		      .$this->CREATEFORM->getSelectLine($this->LANG->getText('background'),'map_image',$this->CORE->getAvailableBackgroundImages(),'');
	}
	
	/**
	 * Gets submit button for the given form
	 *
	 * @param	GlobalForm	$FORM
	 * @return	Array	HTML Code
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
     */
	function getSubmit($FORM, $label) {
		return $FORM->getSubmitLine($label)
		      .$FORM->closeForm();
	}
}
?>
