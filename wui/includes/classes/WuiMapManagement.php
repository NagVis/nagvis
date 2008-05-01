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
	 * @param	GlobalMainCfg	$MAINCFG
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	function WuiMapManagement(&$MAINCFG) {
		$this->MAINCFG = &$MAINCFG;
		$this->propCount = 0;
		
		// load the language file
		$this->LANG = new GlobalLanguage($MAINCFG,'wui:mapManagement');
		
		$prop = Array('title'=>$MAINCFG->getValue('internal', 'title'),
					  'cssIncludes'=>Array('./includes/css/wui.css'),
					  'jsIncludes'=>Array('../nagvis/includes/js/ajax.js','./includes/js/map_management.js',
					  						'./includes/js/ajax.js',
					  						'./includes/js/wui.js'),
					  'extHeader'=>Array(''),
					  'allowedUsers' => $this->MAINCFG->getValue('wui','allowedforconfig'),
					  'languageRoot' => 'wui:mapManagement');
		parent::GlobalPage($MAINCFG,$prop);
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
		$this->addBodyLines($this->CREATEFORM->getCatLine(strtoupper($this->LANG->getLabel('createMap'))));
		$this->propCount++;
		$this->addBodyLines($this->getCreateFields());
		$this->addBodyLines($this->getSubmit($this->CREATEFORM,$this->LANG->getLabel('create')));
		
		$this->RENAMEFORM = new GlobalForm(Array('name'=>'map_rename',
			'id'=>'map_rename',
			'method'=>'POST',
			'action'=>'./form_handler.php?myaction=mgt_map_rename',
			'onSubmit'=>'return check_map_rename();',
			'cols'=>'2'));
		$this->addBodyLines($this->RENAMEFORM->initForm());
		$this->addBodyLines($this->RENAMEFORM->getCatLine(strtoupper($this->LANG->getLabel('renameMap'))));
		$this->propCount++;
		$this->addBodyLines($this->getRenameFields());
		$this->addBodyLines($this->getSubmit($this->RENAMEFORM,$this->LANG->getLabel('rename')));
		
		$this->DELETEFORM = new GlobalForm(Array('name'=>'map_delete',
			'id'=>'map_delete',
			'method'=>'POST',
			'action'=>'./form_handler.php?myaction=mgt_map_delete',
			'onSubmit'=>'return check_map_delete();',
			'cols'=>'2'));
		$this->addBodyLines($this->DELETEFORM->initForm());
		$this->addBodyLines($this->DELETEFORM->getCatLine(strtoupper($this->LANG->getLabel('deleteMap'))));
		$this->propCount++;
		$this->addBodyLines($this->getDeleteFields());
		$this->addBodyLines($this->getSubmit($this->DELETEFORM,$this->LANG->getLabel('delete')));
		
		$this->EXPORTFORM = new GlobalForm(Array('name'=>'map_export',
			'id'=>'map_export',
			'method'=>'POST',
			'action'=>'./form_handler.php?myaction=mgt_map_export',
			'onSubmit'=>'return check_map_export();',
			'cols'=>'2'));
		$this->addBodyLines($this->EXPORTFORM->initForm());
		$this->addBodyLines($this->EXPORTFORM->getCatLine(strtoupper($this->LANG->getLabel('exportMap'))));
		$this->propCount++;
		$this->addBodyLines($this->getExportFields());
		$this->addBodyLines($this->getSubmit($this->EXPORTFORM,$this->LANG->getLabel('export')));
		
		
		$this->IMPORTFORM = new GlobalForm(Array('name'=>'map_import',
			'id'=>'map_import',
			'method'=>'POST',
			'action'=>'./form_handler.php?myaction=mgt_map_import',
			'onSubmit'=>'return check_map_import();',
			'enctype'=>'multipart/form-data',
			'cols'=>'2'));
		$this->addBodyLines($this->IMPORTFORM->initForm());
		$this->addBodyLines($this->IMPORTFORM->getCatLine(strtoupper($this->LANG->getLabel('importMap'))));
		$this->propCount++;
		$this->addBodyLines($this->getImportFields($this->IMPORTFORM));
		$this->addBodyLines($this->getSubmit($this->IMPORTFORM,$this->LANG->getLabel('import')));
		
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
		$ret = array_merge($ret,$this->EXPORTFORM->getSelectLine($this->LANG->getLabel('chooseMap'),'map_name',$this->getMaps(),''));
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
		$ret = array_merge($ret,$this->IMPORTFORM->getFileLine($this->LANG->getLabel('chooseMapFile'),'map_file',''));
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
		$ret = array_merge($ret,$this->DELETEFORM->getSelectLine($this->LANG->getLabel('chooseMap'),'map_name',$this->getMaps(),''));
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
		$ret = array_merge($ret,$this->RENAMEFORM->getSelectLine($this->LANG->getLabel('chooseMap'),'map_name',$this->getMaps(),''));
		$this->propCount++;
		$ret = array_merge($ret,$this->RENAMEFORM->getInputLine($this->LANG->getLabel('newMapName'),'map_new_name',''));
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
		$ret = array_merge($ret,$this->CREATEFORM->getInputLine($this->LANG->getLabel('mapName'),'map_name',''));
		$this->propCount++;
		$ret = array_merge($ret,$this->CREATEFORM->getInputLine($this->LANG->getLabel('readUsers'),'allowed_users',''));
		$this->propCount++;
		$ret = array_merge($ret,$this->CREATEFORM->getInputLine($this->LANG->getLabel('writeUsers'),'allowed_for_config',''));
		$this->propCount++;
		$ret = array_merge($ret,$this->CREATEFORM->getSelectLine($this->LANG->getLabel('mapIconset'),'map_iconset',$this->getIconsets(),$this->MAINCFG->getValue('defaults','icons')));
		$this->propCount++;
		$ret = array_merge($ret,$this->CREATEFORM->getSelectLine($this->LANG->getLabel('background'),'map_image',$this->getMapImages(),''));
		$this->propCount++;
		
		return $ret;
	}
	
	/**
	 * Gets all iconsets
	 *
	 * @return	Array iconsets
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	function getIconsets() {
		$files = Array();
		
		if ($handle = opendir($this->MAINCFG->getValue('paths', 'icon'))) {
 			while (false !== ($file = readdir($handle))) {
				if ($file != "." && $file != ".." && substr($file,strlen($file)-7,7) == "_ok.png") {
					$files[] = substr($file,0,strlen($file)-7);
				}				
			}
			
			if ($files) {
				natcasesort($files);
			}
		}
		closedir($handle);
		
		return $files;
	}
	
	/**
	 * Gets all defined maps
	 *
	 * @return	Array Maps
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	function getMaps() {
		$files = Array();
		
		if ($handle = opendir($this->MAINCFG->getValue('paths', 'mapcfg'))) {
 			while (false !== ($file = readdir($handle))) {
				if(preg_match('/^.+\.cfg$/', $file) && $file != '__automap.cfg') {
					$files[] = substr($file,0,strlen($file)-4);
				}				
			}
			
			if ($files) {
				natcasesort($files);
			}
		}
		closedir($handle);
		
		return $files;
	}
	
	/**
	 * Reads all map images in map path
	 *
	 * @return	Array map images
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	function getMapImages() {
		$files = Array();
		
		if ($handle = opendir($this->MAINCFG->getValue('paths', 'map'))) {
 			while (false !== ($file = readdir($handle))) {
				if ($file != "." && $file != ".." && substr($file,strlen($file)-4,4) == ".png") {
					$files[] = $file;
				}				
			}
			
			if ($files) {
				natcasesort($files);
			}
		}
		closedir($handle);
		
		return $files;
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
		$ret[] = 'lang[\'chooseMapName\'] = \''.$this->LANG->getMessageText('chooseMapName').'\';';
		$ret[] = 'lang[\'minOneUserAccess\'] = \''.$this->LANG->getMessageText('minOneUserAccess').'\';';
		$ret[] = 'lang[\'mustChooseBackgroundImage\'] = \''.$this->LANG->getMessageText('mustChooseBackgroundImage').'\';';
		$ret[] = 'lang[\'noMapToRename\'] = \''.$this->LANG->getMessageText('noMapToRename').'\';';
		$ret[] = 'lang[\'noNewNameGiven\'] = \''.$this->LANG->getMessageText('noNewNameGiven').'\';';
		$ret[] = 'lang[\'mapAlreadyExists\'] = \''.$this->LANG->getMessageText('mapAlreadyExists').'\';';
		$ret[] = 'lang[\'foundNoMapToDelete\'] = \''.$this->LANG->getMessageText('foundNoMapToDelete').'\';';
		$ret[] = 'lang[\'foundNoMapToExport\'] = \''.$this->LANG->getMessageText('foundNoMapToExport').'\';';
		$ret[] = 'lang[\'foundNoMapToImport\'] = \''.$this->LANG->getMessageText('foundNoMapToImport').'\';';
		$ret[] = 'lang[\'notCfgFile\'] = \''.$this->LANG->getMessageText('notCfgFile').'\';';
		$ret[] = 'lang[\'confirmNewMap\'] = \''.$this->LANG->getMessageText('confirmNewMap').'\';';
		$ret[] = 'lang[\'confirmMapRename\'] = \''.$this->LANG->getMessageText('confirmMapRename').'\';';
		$ret[] = 'lang[\'confirmMapDeletion\'] = \''.$this->LANG->getMessageText('confirmMapDeletion').'\';';
		$ret[] = 'lang[\'unableToDeleteMap\'] = \''.$this->LANG->getMessageText('unableToDeleteMap').'\';';
		$ret[] = 'lang[\'noPermissions\'] = \''.$this->LANG->getMessageText('noPermissions').'\';';
		$ret[] = 'lang[\'minOneUserWriteAccess\'] = \''.$this->LANG->getMessageText('minOneUserWriteAccess').'\';';
		$ret[] = 'lang[\'noSpaceAllowed\'] = \''.$this->LANG->getMessageText('noSpaceAllowed').'\';';
		
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
