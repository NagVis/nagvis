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
		
		$this->propCount = 0;
		
		$prop = Array('title' => $this->CORE->MAINCFG->getValue('internal', 'title'),
					  'cssIncludes'=>Array('./includes/css/wui.css'),
					  'jsIncludes'=>Array('../nagvis/includes/js/ajax.js','./includes/js/map_management.js',
					  						'./includes/js/ajax.js',
					  						'./includes/js/wui.js'),
					  'extHeader'=> '',
					  'allowedUsers' => $this->CORE->MAINCFG->getValue('wui','allowedforconfig'),
					  'languageRoot' => 'nagvis');
		parent::GlobalPage($CORE, $prop);
	}
	
	/**
	* If enabled, the form is added to the page
	*
	* @author Lars Michelsen <lars@vertical-visions.de>
	*/
	function getForm() {
		// Inititalize language for JS
		$this->addBodyLines($this->parseJs($this->getJsLang()));
		
		$this->CREATEFORM = new GlobalForm(Array('name'=>'map_create',
			'id'=>'map_create',
			'method'=>'POST',
			'action'=>'./form_handler.php?myaction=mgt_map_create',
			'onSubmit'=>'return check_create_map();',
			'cols'=>'2'));
		$this->addBodyLines($this->CREATEFORM->initForm());
		$this->addBodyLines($this->CREATEFORM->getCatLine(strtoupper($this->LANG->getText('createMap'))));
		$this->propCount++;
		$this->addBodyLines($this->getCreateFields());
		$this->addBodyLines($this->getSubmit($this->CREATEFORM,$this->LANG->getText('create')));
		
		$this->RENAMEFORM = new GlobalForm(Array('name'=>'map_rename',
			'id'=>'map_rename',
			'method'=>'POST',
			'action'=>'./form_handler.php?myaction=mgt_map_rename',
			'onSubmit'=>'return check_map_rename();',
			'cols'=>'2'));
		$this->addBodyLines($this->RENAMEFORM->initForm());
		$this->addBodyLines($this->RENAMEFORM->getCatLine(strtoupper($this->LANG->getText('renameMap'))));
		$this->propCount++;
		$this->addBodyLines($this->getRenameFields());
		$this->addBodyLines($this->getSubmit($this->RENAMEFORM,$this->LANG->getText('rename')));
		
		$this->DELETEFORM = new GlobalForm(Array('name'=>'map_delete',
			'id'=>'map_delete',
			'method'=>'POST',
			'action'=>'./form_handler.php?myaction=mgt_map_delete',
			'onSubmit'=>'return check_map_delete();',
			'cols'=>'2'));
		$this->addBodyLines($this->DELETEFORM->initForm());
		$this->addBodyLines($this->DELETEFORM->getCatLine(strtoupper($this->LANG->getText('deleteMap'))));
		$this->propCount++;
		$this->addBodyLines($this->getDeleteFields());
		$this->addBodyLines($this->getSubmit($this->DELETEFORM,$this->LANG->getText('delete')));
		
		$this->EXPORTFORM = new GlobalForm(Array('name'=>'map_export',
			'id'=>'map_export',
			'method'=>'POST',
			'action'=>'./form_handler.php?myaction=mgt_map_export',
			'onSubmit'=>'return check_map_export();',
			'cols'=>'2'));
		$this->addBodyLines($this->EXPORTFORM->initForm());
		$this->addBodyLines($this->EXPORTFORM->getCatLine(strtoupper($this->LANG->getText('exportMap'))));
		$this->propCount++;
		$this->addBodyLines($this->getExportFields());
		$this->addBodyLines($this->getSubmit($this->EXPORTFORM,$this->LANG->getText('export')));
		
		
		$this->IMPORTFORM = new GlobalForm(Array('name'=>'map_import',
			'id'=>'map_import',
			'method'=>'POST',
			'action'=>'./form_handler.php?myaction=mgt_map_import',
			'onSubmit'=>'return check_map_import();',
			'enctype'=>'multipart/form-data',
			'cols'=>'2'));
		$this->addBodyLines($this->IMPORTFORM->initForm());
		$this->addBodyLines($this->IMPORTFORM->getCatLine(strtoupper($this->LANG->getText('importMap'))));
		$this->propCount++;
		$this->addBodyLines($this->getImportFields($this->IMPORTFORM));
		$this->addBodyLines($this->getSubmit($this->IMPORTFORM,$this->LANG->getText('import')));
		
		// Resize the window
		$this->addBodyLines($this->parseJs($this->resizeWindow(540,$this->propCount*30+100)));
	}
	
	/**
	 * Gets export fields
	 *
	 * @return	Array	HTML Code
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
     */
	function getExportFields() {
		$ret = Array();
		$ret = array_merge($ret,$this->EXPORTFORM->getSelectLine($this->LANG->getText('chooseMap'),'map_name',$this->CORE->getAvailableMaps('/[^(__automap)]/'),''));
		$this->propCount++;
		
		return $ret;
	}
	
	/**
	 * Gets import fields
	 *
	 * @return	Array	HTML Code
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
     */
	function getImportFields() {
		$ret = Array();
		$ret = array_merge($ret,$this->IMPORTFORM->getHiddenField('MAX_FILE_SIZE','1000000'));
		$ret = array_merge($ret,$this->IMPORTFORM->getFileLine($this->LANG->getText('chooseMapFile'),'map_file',''));
		$this->propCount++;
		
		return $ret;
	}
	
	/**
	 * Gets delete fields
	 *
	 * @return	Array	HTML Code
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
     */
	function getDeleteFields() {
		$ret = Array();
		$ret = array_merge($ret,$this->DELETEFORM->getSelectLine($this->LANG->getText('chooseMap'),'map_name',$this->CORE->getAvailableMaps('/[^(__automap)]/'),''));
		$this->propCount++;
		$ret = array_merge($ret,$this->DELETEFORM->getHiddenField('map',''));
		$ret[] = '<script>document.map_rename.map.value=window.opener.document.mapname</script>';
		
		return $ret;
	}
	
	/**
	 * Gets rename fields
	 *
	 * @return	Array	HTML Code
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
     */
	function getRenameFields() {
		$ret = Array();
		$ret = array_merge($ret,$this->RENAMEFORM->getSelectLine($this->LANG->getText('chooseMap'),'map_name',$this->CORE->getAvailableMaps('/[^(__automap)]/'),''));
		$this->propCount++;
		$ret = array_merge($ret,$this->RENAMEFORM->getInputLine($this->LANG->getText('newMapName'),'map_new_name',''));
		$this->propCount++;
		$ret = array_merge($ret,$this->RENAMEFORM->getHiddenField('map',''));
		$ret[] = '<script>document.map_rename.map.value=window.opener.document.mapname</script>';
		
		return $ret;
	}
	
	/**
	 * Gets create fields
	 *
	 * @return	Array	HTML Code
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
     */
	function getCreateFields() {
		
		$ret = Array();
		$ret = array_merge($ret,$this->CREATEFORM->getInputLine($this->LANG->getText('mapName'),'map_name',''));
		$this->propCount++;
		$ret = array_merge($ret,$this->CREATEFORM->getInputLine($this->LANG->getText('readUsers'),'allowed_users',''));
		$this->propCount++;
		$ret = array_merge($ret,$this->CREATEFORM->getInputLine($this->LANG->getText('writeUsers'),'allowed_for_config',''));
		$this->propCount++;
		$ret = array_merge($ret,$this->CREATEFORM->getSelectLine($this->LANG->getText('mapIconset'),'map_iconset',$this->CORE->getAvailableIconsets(),$this->MAINCFG->getValue('defaults','icons')));
		$this->propCount++;
		$ret = array_merge($ret,$this->CREATEFORM->getSelectLine($this->LANG->getText('background'),'map_image',$this->CORE->getAvailableBackgroundImages(),''));
		$this->propCount++;
		
		return $ret;
	}
	
	/**
	 * Gets all needed messages
	 *
	 * @return	Array Html
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	function getJsLang() {
		$ret = Array();
		$ret[] = 'var lang = Array();';
		$ret[] = 'lang[\'chooseMapName\'] = \''.$this->LANG->getText('chooseMapName').'\';';
		$ret[] = 'lang[\'minOneUserAccess\'] = \''.$this->LANG->getText('minOneUserAccess').'\';';
		$ret[] = 'lang[\'mustChooseBackgroundImage\'] = \''.$this->LANG->getText('mustChooseBackgroundImage').'\';';
		$ret[] = 'lang[\'noMapToRename\'] = \''.$this->LANG->getText('noMapToRename').'\';';
		$ret[] = 'lang[\'noNewNameGiven\'] = \''.$this->LANG->getText('noNewNameGiven').'\';';
		$ret[] = 'lang[\'mapAlreadyExists\'] = \''.$this->LANG->getText('mapAlreadyExists').'\';';
		$ret[] = 'lang[\'foundNoMapToDelete\'] = \''.$this->LANG->getText('foundNoMapToDelete').'\';';
		$ret[] = 'lang[\'foundNoMapToExport\'] = \''.$this->LANG->getText('foundNoMapToExport').'\';';
		$ret[] = 'lang[\'foundNoMapToImport\'] = \''.$this->LANG->getText('foundNoMapToImport').'\';';
		$ret[] = 'lang[\'notCfgFile\'] = \''.$this->LANG->getText('notCfgFile').'\';';
		$ret[] = 'lang[\'confirmNewMap\'] = \''.$this->LANG->getText('confirmNewMap').'\';';
		$ret[] = 'lang[\'confirmMapRename\'] = \''.$this->LANG->getText('confirmMapRename').'\';';
		$ret[] = 'lang[\'confirmMapDeletion\'] = \''.$this->LANG->getText('confirmMapDeletion').'\';';
		$ret[] = 'lang[\'unableToDeleteMap\'] = \''.$this->LANG->getText('unableToDeleteMap').'\';';
		$ret[] = 'lang[\'noPermissions\'] = \''.$this->LANG->getText('noPermissions').'\';';
		$ret[] = 'lang[\'minOneUserWriteAccess\'] = \''.$this->LANG->getText('minOneUserWriteAccess').'\';';
		$ret[] = 'lang[\'noSpaceAllowed\'] = \''.$this->LANG->getText('noSpaceAllowed').'\';';
		
		return $ret;	
	}
	
	/**
	 * Gets submit button for the given form
	 *
	 * @param	GlobalForm	$FORM
	 * @return	Array	HTML Code
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
     */
	function getSubmit(&$FORM,$label) {
		$this->propCount++;
		return array_merge($FORM->getSubmitLine($label),$FORM->closeForm());
	}
}
?>
