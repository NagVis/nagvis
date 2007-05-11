<?php
/**
 * Class for managing the maps in WUI
 *
 * @author 	Lars Michelsen <lars@vertical-visions.de>
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
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method WuiMapManagement::WuiMapManagement($MAINCFG)');
		$this->MAINCFG = &$MAINCFG;
		$this->propCount = 0;
		
		// load the language file
		$this->LANG = new GlobalLanguage($MAINCFG,'wui:mapManagement');
		
		$prop = Array('title'=>$MAINCFG->getValue('internal', 'title'),
					  'cssIncludes'=>Array('./includes/css/wui.css'),
					  'jsIncludes'=>Array('./includes/js/map_management.js',
					  						'./includes/js/ajax.js',
					  						'./includes/js/wui.js'),
					  'extHeader'=>Array(''),
					  'allowedUsers' => Array('EVERYONE'),
					  'languageRoot' => 'wui:mapManagement');
		parent::GlobalPage($MAINCFG,$prop);
		if (DEBUG&&DEBUGLEVEL&1) debug('End method WuiMapManagement::WuiMapManagement()');
	}
	
	/**
	* If enabled, the form is added to the page
	*
	* @author Lars Michelsen <lars@vertical-visions.de>
	*/
	function getForm() {
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method WuiMapManagement::getForm()');
		// Inititalize language for JS
		$this->addBodyLines($this->getJsLang());
		
		$this->CREATEFORM = new GlobalForm(Array('name'=>'map_create',
			'id'=>'map_create',
			'method'=>'POST',
			'action'=>'./wui.function.inc.php?myaction=mgt_map_create',
			'onSubmit'=>'return check_create_map();',
			'cols'=>'2'));
		$this->addBodyLines($this->CREATEFORM->initForm());
		$this->addBodyLines($this->CREATEFORM->getCatLine(strtoupper($this->LANG->getLabel('createMap'))));
		$this->propCount++;
		$this->addBodyLines($this->getCreateFields());
		$this->addBodyLines($this->getCreateSubmit());
		
		$this->RENAMEFORM = new GlobalForm(Array('name'=>'map_rename',
			'id'=>'map_rename',
			'method'=>'POST',
			'action'=>'./wui.function.inc.php?myaction=mgt_map_rename',
			'onSubmit'=>'return check_map_rename();',
			'cols'=>'2'));
		$this->addBodyLines($this->RENAMEFORM->initForm());
		$this->addBodyLines($this->RENAMEFORM->getCatLine(strtoupper($this->LANG->getLabel('renameMap'))));
		$this->propCount++;
		$this->addBodyLines($this->getRenameFields());
		$this->addBodyLines($this->getRenameSubmit());
		
		$this->DELETEFORM = new GlobalForm(Array('name'=>'map_delete',
			'id'=>'map_delete',
			'method'=>'POST',
			'action'=>'./wui.function.inc.php?myaction=mgt_map_delete',
			'onSubmit'=>'return check_map_delete();',
			'cols'=>'2'));
		$this->addBodyLines($this->DELETEFORM->initForm());
		$this->addBodyLines($this->DELETEFORM->getCatLine(strtoupper($this->LANG->getLabel('deleteMap'))));
		$this->propCount++;
		$this->addBodyLines($this->getDeleteFields());
		$this->addBodyLines($this->getDeleteSubmit());
		
		$this->EXPORTFORM = new GlobalForm(Array('name'=>'map_export',
			'id'=>'map_export',
			'method'=>'POST',
			'action'=>'./wui.function.inc.php?myaction=mgt_map_export',
			'onSubmit'=>'return check_map_export();',
			'cols'=>'2'));
		$this->addBodyLines($this->EXPORTFORM->initForm());
		$this->addBodyLines($this->EXPORTFORM->getCatLine(strtoupper($this->LANG->getLabel('exportMap'))));
		$this->propCount++;
		$this->addBodyLines($this->getExportFields());
		$this->addBodyLines($this->getExportSubmit());
		
		$this->IMPORTFORM = new GlobalForm(Array('name'=>'map_import',
			'id'=>'map_import',
			'method'=>'POST',
			'action'=>'./wui.function.inc.php?myaction=mgt_map_import',
			'onSubmit'=>'return check_map_import();',
			'enctype'=>'multipart/form-data',
			'cols'=>'2'));
		$this->addBodyLines($this->IMPORTFORM->initForm());
		$this->addBodyLines($this->IMPORTFORM->getCatLine(strtoupper($this->LANG->getLabel('importMap'))));
		$this->propCount++;
		$this->addBodyLines($this->getImportFields($this->IMPORTFORM));
		$this->addBodyLines($this->getImportSubmit($this->IMPORTFORM));
		
		// Resize the window
		$this->addBodyLines($this->resizeWindow());
		if (DEBUG&&DEBUGLEVEL&1) debug('End method WuiMapManagement::getForm()');
	}
	
	/**
	 * Resizes the window to individual calculated size
	 *
	 * @return	Array	HTML Code
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
     */
	function resizeWindow() {
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method WuiMapManagement::resizeWindow()');
		$ret = Array();
		$ret[] = "<script type=\"text/javascript\" language=\"JavaScript\"><!--";
		$ret[] = "// resize the window (depending on the number of properties displayed)";
		$ret[] = "window.resizeTo(540,".$this->propCount."*30+30)";
		$ret[] = "//--></script>";
		
		if (DEBUG&&DEBUGLEVEL&1) debug('End method WuiMapManagement::resizeWindow(): HTML');
		return $ret;
	}
	
	/**
	 * Gets export fields
	 *
	 * @return	Array	HTML Code
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
     */
	function getExportFields() {
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method WuiMapManagement::getExportFields()');
		$ret = Array();
		$ret = array_merge($ret,$this->EXPORTFORM->getSelectLine($this->LANG->getLabel('chooseMap'),'map_name',$this->getMaps(),''));
		$this->propCount++;
		
		if (DEBUG&&DEBUGLEVEL&1) debug('End method WuiMapManagement::getExportFields(): HTML');
		return $ret;
	}
	
	/**
	 * Gets export submit button
	 *
	 * @return	Array	HTML Code
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
     */
	function getExportSubmit() {
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method WuiMapManagement::getExportSubmit()');
		$this->propCount++;
		if (DEBUG&&DEBUGLEVEL&1) debug('End method WuiMapManagement::getExportSubmit(): HTML');
		return array_merge($this->EXPORTFORM->getSubmitLine($this->LANG->getLabel('export')),$this->EXPORTFORM->closeForm());
	}
	
	/**
	 * Gets import fields
	 *
	 * @return	Array	HTML Code
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
     */
	function getImportFields() {
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method WuiMapManagement::getImportFields()');
		$ret = Array();
		$ret = array_merge($ret,$this->IMPORTFORM->getHiddenField('MAX_FILE_SIZE','1000000'));
		$ret = array_merge($ret,$this->IMPORTFORM->getFileLine($this->LANG->getLabel('chooseMapFile'),'map_file',''));
		if (DEBUG&&DEBUGLEVEL&1) debug('End method WuiMapManagement::getImportFields(): HTML');
		$this->propCount++;
		
		return $ret;
	}
	
	/**
	 * Gets import submit button
	 *
	 * @return	Array	HTML Code
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
     */
	function getImportSubmit() {
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method WuiMapManagement::getImportSubmit()');
		$this->propCount++;
		if (DEBUG&&DEBUGLEVEL&1) debug('End method WuiMapManagement::getImportSubmit(): HTML');
		return array_merge($this->IMPORTFORM->getSubmitLine($this->LANG->getLabel('upload')),$this->IMPORTFORM->closeForm());
	}
	
	/**
	 * Gets delete fields
	 *
	 * @return	Array	HTML Code
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
     */
	function getDeleteFields() {
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method WuiMapManagement::getDeleteFields()');
		$ret = Array();
		$ret = array_merge($ret,$this->DELETEFORM->getSelectLine($this->LANG->getLabel('chooseMap'),'map_name',$this->getMaps(),''));
		$this->propCount++;
		$ret = array_merge($ret,$this->DELETEFORM->getHiddenField('map',''));
		$ret[] = '<script>document.map_rename.map.value=window.opener.document.mapname</script>';
		
		if (DEBUG&&DEBUGLEVEL&1) debug('End method WuiMapManagement::getDeleteFields(): HTML');
		return $ret;
	}
	
	/**
	 * Gets delete submit button
	 *
	 * @return	Array	HTML Code
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
     */
	function getDeleteSubmit() {
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method WuiMapManagement::getDeleteSubmit()');
		$this->propCount++;
		if (DEBUG&&DEBUGLEVEL&1) debug('End method WuiMapManagement::getDeleteSubmit()');
		return array_merge($this->DELETEFORM->getSubmitLine($this->LANG->getLabel('delete')),$this->DELETEFORM->closeForm());
	}
	
	/**
	 * Gets rename fields
	 *
	 * @return	Array	HTML Code
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
     */
	function getRenameFields() {
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method WuiMapManagement::getRenameFields()');
		$ret = Array();
		$ret = array_merge($ret,$this->RENAMEFORM->getSelectLine($this->LANG->getLabel('chooseMap'),'map_name',$this->getMaps(),''));
		$this->propCount++;
		$ret = array_merge($ret,$this->RENAMEFORM->getInputLine($this->LANG->getLabel('newMapName'),'map_new_name',''));
		$this->propCount++;
		$ret = array_merge($ret,$this->RENAMEFORM->getHiddenField('map',''));
		$ret[] = '<script>document.map_rename.map.value=window.opener.document.mapname</script>';
		
		if (DEBUG&&DEBUGLEVEL&1) debug('End method WuiMapManagement::getRenameFields(): HTML');
		return $ret;
	}
	
	/**
	 * Gets rename submit button
	 *
	 * @return	Array	HTML Code
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
     */
	function getRenameSubmit() {
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method WuiMapManagement::getRenameSubmit()');
		$this->propCount++;
		if (DEBUG&&DEBUGLEVEL&1) debug('End method WuiMapManagement::getRenameSubmit(): HTML');
		return array_merge($this->RENAMEFORM->getSubmitLine($this->LANG->getLabel('rename')),$this->RENAMEFORM->closeForm());
	}
	
	/**
	 * Gets create fields
	 *
	 * @return	Array	HTML Code
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
     */
	function getCreateFields() {
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method WuiMapManagement::getCreateFields()');
		//FIXME: Default values
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
		
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method WuiMapManagement::getCreateFields(): HTML');
		return $ret;
	}
	
	/**
	 * Gets create submit button
	 *
	 * @return	Array	HTML Code
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
     */
	function getCreateSubmit() {
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method WuiMapManagement::getCreateSubmit()');
		$this->propCount++;
		if (DEBUG&&DEBUGLEVEL&1) debug('End method WuiMapManagement::getCreateSubmit(): HTML');
		return array_merge($this->CREATEFORM->getSubmitLine($this->LANG->getLabel('create')),$this->CREATEFORM->closeForm());
	}
	
	/**
	 * Gets all iconsets
	 *
	 * @return	Array iconsets
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	function getIconsets() {
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method WuiMapManagement::getIconsets()');
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
		
		if (DEBUG&&DEBUGLEVEL&1) debug('End method WuiMapManagement::getIconsets(): Array(...)');
		return $files;
	}
	
	/**
	 * Gets all defined maps
	 *
	 * @return	Array Maps
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	function getMaps() {
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method WuiMapManagement::getMaps()');
		$files = Array();
		
		if ($handle = opendir($this->MAINCFG->getValue('paths', 'mapcfg'))) {
 			while (false !== ($file = readdir($handle))) {
				if ($file != "." && $file != ".." && substr($file,strlen($file)-4,4) == ".cfg") {
					$files[] = substr($file,0,strlen($file)-4);
				}				
			}
			
			if ($files) {
				natcasesort($files);
			}
		}
		closedir($handle);
		
		if (DEBUG&&DEBUGLEVEL&1) debug('End method WuiMapManagement::getMaps(): Array(...)');
		return $files;
	}
	
	/**
	 * Reads all map images in map path
	 *
	 * @return	Array map images
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	function getMapImages() {
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method WuiMapManagement::getMapImages()');
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
		
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method WuiMapManagement::getMapImages(): Array(...)');
		return $files;
	}
	
	/**
	 * Gets all needed messages
	 *
	 * @return	Array Html
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	function getJsLang() {
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method WuiMapManagement::getJsLang()');
		$ret = Array();
		$ret[] = '<script type="text/javascript" language="JavaScript"><!--';
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
		$ret[] = '//--></script>';
		
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method WuiMapManagement::getJsLang(): HTML');
		return $ret;	
	}
}
?>