<?php
class WuiMapManagement extends GlobalPage {
	var $MAINCFG;
	var $LANG;
	var $CREATEFORM;
	var $RENAMEFORM;
	var $DELETEFORM;
	var $NEWIMGFORM;
	var $DELIMGFORM;
	
	function WuiMapManagement(&$MAINCFG) {
		$this->MAINCFG = &$MAINCFG;
		
		# we load the language file
		$this->LANG = new GlobalLanguage($MAINCFG,'wui:mapManagement');
		
		$prop = Array('title'=>$MAINCFG->getValue('internal', 'title'),
					  'cssIncludes'=>Array('./includes/css/wui.css'),
					  'jsIncludes'=>Array('./includes/js/map_management.js',
					  						'./includes/js/ajax.js'),
					  'extHeader'=>Array(''),
					  'allowedUsers' => Array('EVERYONE'),
					  'languageRoot' => 'wui:mapManagement');
		parent::GlobalPage($MAINCFG,$prop);
	}
	
	/**
	* If enabled, the form is added to the page
	*
	* @author Lars Michelsen <larsi@nagios-wiki.de>
	*/
	function getForm() {
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
		$this->addBodyLines($this->getDeleteFields());
		$this->addBodyLines($this->getDeleteSubmit());
		
		$this->NEWIMGFORM = new GlobalForm(Array('name'=>'new_image',
			'id'=>'new_image',
			'method'=>'POST',
			'action'=>'./wui.function.inc.php?myaction=mgt_new_image',
			'onSubmit'=>'return check_png();',
			'enctype'=>'multipart/form-data',
			'cols'=>'2'));
		$this->addBodyLines($this->NEWIMGFORM->initForm());
		$this->addBodyLines($this->NEWIMGFORM->getCatLine(strtoupper($this->LANG->getLabel('uploadBackground'))));
		$this->addBodyLines($this->getNewImgFields());
		$this->addBodyLines($this->getNewImgSubmit());
		
		$this->DELIMGFORM = new GlobalForm(Array('name'=>'image_delete',
			'id'=>'image_delete',
			'method'=>'POST',
			'action'=>'./wui.function.inc.php?myaction=mgt_image_delete',
			'onSubmit'=>'return check_image_delete();',
			'cols'=>'2'));
		$this->addBodyLines($this->DELIMGFORM->initForm());
		$this->addBodyLines($this->DELIMGFORM->getCatLine(strtoupper($this->LANG->getLabel('deleteBackground'))));
		$this->addBodyLines($this->getDelImgFields());
		$this->addBodyLines($this->getDelImgSubmit());
	}
	
	function getDelImgFields() {
		$ret = Array();
		$ret = array_merge($ret,$this->DELIMGFORM->getSelectLine($this->LANG->getLabel('choosePngImage'),'map_image',$this->getMapImages(),''));
		
		return $ret;
	}
	
	function getDelImgSubmit() {
		return array_merge($this->DELIMGFORM->getSubmitLine($this->LANG->getLabel('delete')),$this->DELIMGFORM->closeForm());
	}
	
	function getNewImgFields() {
		$ret = Array();
		$ret = array_merge($ret,$this->NEWIMGFORM->getHiddenField('MAX_FILE_SIZE','1000000'));
		$ret = array_merge($ret,$this->NEWIMGFORM->getFileLine($this->LANG->getLabel('choosePngImage'),'fichier',''));
		
		return $ret;
	}
	
	function getNewImgSubmit() {
		return array_merge($this->NEWIMGFORM->getSubmitLine($this->LANG->getLabel('upload')),$this->NEWIMGFORM->closeForm());
	}
	
	function getDeleteFields() {
		$ret = Array();
		$ret = array_merge($ret,$this->RENAMEFORM->getSelectLine($this->LANG->getLabel('chooseMap'),'map_name',$this->getMaps(),''));
		$ret = array_merge($ret,$this->RENAMEFORM->getHiddenField('map',''));
		$ret[] = '<script>document.map_rename.map.value=window.opener.document.mapname</script>';
		
		return $ret;
	}
	
	function getDeleteSubmit() {
		return array_merge($this->DELETEFORM->getSubmitLine($this->LANG->getLabel('delete')),$this->DELETEFORM->closeForm());
	}
	
	function getRenameFields() {
		$ret = Array();
		$ret = array_merge($ret,$this->RENAMEFORM->getSelectLine($this->LANG->getLabel('chooseMap'),'map_name',$this->getMaps(),''));
		$ret = array_merge($ret,$this->RENAMEFORM->getInputLine($this->LANG->getLabel('newMapName'),'map_new_name',''));
		$ret = array_merge($ret,$this->RENAMEFORM->getHiddenField('map',''));
		$ret[] = '<script>document.map_rename.map.value=window.opener.document.mapname</script>';
		
		return $ret;
	}
	
	function getRenameSubmit() {
		return array_merge($this->RENAMEFORM->getSubmitLine($this->LANG->getLabel('rename')),$this->RENAMEFORM->closeForm());
	}
	
	function getCreateFields() {
		//FIXME: Default values
		$ret = Array();
		$ret = array_merge($ret,$this->CREATEFORM->getInputLine($this->LANG->getLabel('mapName'),'map_name',''));
		$ret = array_merge($ret,$this->CREATEFORM->getInputLine($this->LANG->getLabel('readUsers'),'allowed_users',$user));
		$ret = array_merge($ret,$this->CREATEFORM->getInputLine($this->LANG->getLabel('writeUsers'),'allowed_for_config',$user));
		$ret = array_merge($ret,$this->CREATEFORM->getSelectLine($this->LANG->getLabel('mapIconset'),'map_iconset',$this->getIconsets(),$defaultIcons));
		$ret = array_merge($ret,$this->CREATEFORM->getSelectLine($this->LANG->getLabel('background'),'map_image',$this->getMapImages(),''));
		
		return $ret;
	}
	
	function getCreateSubmit() {
		return array_merge($this->CREATEFORM->getSubmitLine($this->LANG->getLabel('create')),$this->CREATEFORM->closeForm());
	}
	
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
	
	function getMaps() {
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
		
		return $files;
	}
	
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
	
	function getJsLang() {
		$ret = Array();
		$ret[] = '<script type="text/javascript" language="JavaScript"><!--';
		$ret[] = 'var lang = Array();';
		$ret[] = 'lang["firstMustChoosePngImage"] = "'.$this->LANG->getMessageText('firstMustChoosePngImage').'";';
		$ret[] = 'lang["mustChoosePngImage"] = "'.$this->LANG->getMessageText('mustChoosePngImage').'";';
		$ret[] = 'lang["chooseMapName"] = "'.$this->LANG->getMessageText('chooseMapName').'";';
		$ret[] = 'lang["minOneUserAccess"] = "'.$this->LANG->getMessageText('minOneUserAccess').'";';
		$ret[] = 'lang["mustChooseBackgroundImage"] = "'.$this->LANG->getMessageText('mustChooseBackgroundImage').'";';
		$ret[] = 'lang["noMapToRename"] = "'.$this->LANG->getMessageText('noMapToRename').'";';
		$ret[] = 'lang["noNewNameGiven"] = "'.$this->LANG->getMessageText('noNewNameGiven').'";';
		$ret[] = 'lang["mapAlreadyExists"] = "'.$this->LANG->getMessageText('mapAlreadyExists').'";';
		$ret[] = 'lang["foundNoMapToDelete"] = "'.$this->LANG->getMessageText('foundNoMapToDelete').'";';
		$ret[] = 'lang["foundNotBackgroundToDelete"] = "'.$this->LANG->getMessageText('foundNotBackgroundToDelete').'";';
		$ret[] = 'lang["confirmNewMap"] = "'.$this->LANG->getMessageText('confirmNewMap').'";';
		$ret[] = 'lang["confirmMapRename"] = "'.$this->LANG->getMessageText('confirmMapRename').'";';
		$ret[] = 'lang["confirmMapDeletion"] = "'.$this->LANG->getMessageText('confirmMapDeletion').'";';
		$ret[] = 'lang["confirmBackgroundDeletion"] = "'.$this->LANG->getMessageText('confirmBackgroundDeletion').'";';
		$ret[] = 'lang["unableToDeleteBackground"] = "'.$this->LANG->getMessageText('unableToDeleteBackground').'";';
		$ret[] = 'lang["noPermissions"] = "'.$this->LANG->getMessageText('noPermissions').'";';
		$ret[] = 'lang["minOneUserWriteAccess"] = "'.$this->LANG->getMessageText('minOneUserWriteAccess').'";';
		$ret[] = 'lang["noSpaceAllowed"] = "'.$this->LANG->getMessageText('noSpaceAllowed').'";';
		$ret[] = '//--></script>';
		
		return $ret;	
	}
}
?>