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
		$this->LANG = new GlobalLanguage($MAINCFG);
		
		$prop = Array('title'=>$MAINCFG->getValue('internal', 'title'),
					  'cssIncludes'=>Array('./includes/css/wui.css'),
					  'jsIncludes'=>Array('./includes/js/map_management.js'),
					  'extHeader'=>Array(''),
					  'allowedUsers' => Array('EVERYONE'));
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
		$this->addBodyLines($this->CREATEFORM->getCatLine(strtoupper($this->LANG->getText("15"))));
		$this->addBodyLines($this->getCreateFields());
		$this->addBodyLines($this->getCreateSubmit());
		
		$this->RENAMEFORM = new GlobalForm(Array('name'=>'map_rename',
			'id'=>'map_rename',
			'method'=>'POST',
			'action'=>'./wui.function.inc.php?myaction=mgt_map_rename',
			'onSubmit'=>'return check_map_rename();',
			'cols'=>'2'));
		$this->addBodyLines($this->RENAMEFORM->initForm());
		$this->addBodyLines($this->RENAMEFORM->getCatLine(strtoupper($this->LANG->getText("16"))));
		$this->addBodyLines($this->getRenameFields());
		$this->addBodyLines($this->getRenameSubmit());
		
		$this->DELETEFORM = new GlobalForm(Array('name'=>'map_delete',
			'id'=>'map_delete',
			'method'=>'POST',
			'action'=>'./wui.function.inc.php?myaction=mgt_map_delete',
			'onSubmit'=>'return check_map_delete();',
			'cols'=>'2'));
		$this->addBodyLines($this->DELETEFORM->initForm());
		$this->addBodyLines($this->DELETEFORM->getCatLine(strtoupper($this->LANG->getText("17"))));
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
		$this->addBodyLines($this->NEWIMGFORM->getCatLine(strtoupper($this->LANG->getText("18"))));
		$this->addBodyLines($this->getNewImgFields());
		$this->addBodyLines($this->getNewImgSubmit());
		
		$this->DELIMGFORM = new GlobalForm(Array('name'=>'image_delete',
			'id'=>'image_delete',
			'method'=>'POST',
			'action'=>'./wui.function.inc.php?myaction=mgt_image_delete',
			'onSubmit'=>'return check_image_delete();',
			'cols'=>'2'));
		$this->addBodyLines($this->DELIMGFORM->initForm());
		$this->addBodyLines($this->DELIMGFORM->getCatLine(strtoupper($this->LANG->getText("19"))));
		$this->addBodyLines($this->getDelImgFields());
		$this->addBodyLines($this->getDelImgSubmit());
	}
	
	function getDelImgFields() {
		$ret = Array();
		$ret = array_merge($ret,$this->DELIMGFORM->getSelectLine($this->LANG->getText("29"),'map_image',$this->getMapImages(),''));
		
		return $ret;
	}
	
	function getDelImgSubmit() {
		return array_merge($this->DELIMGFORM->getSubmitLine($this->LANG->getText("21")),$this->DELIMGFORM->closeForm());
	}
	
	function getNewImgFields() {
		$ret = Array();
		$ret = array_merge($ret,$this->NEWIMGFORM->getHiddenField('MAX_FILE_SIZE','1000000'));
		$ret = array_merge($ret,$this->NEWIMGFORM->getFileLine($this->LANG->getText("29"),'fichier',''));
		
		return $ret;
	}
	
	function getNewImgSubmit() {
		return array_merge($this->NEWIMGFORM->getSubmitLine($this->LANG->getText("23")),$this->NEWIMGFORM->closeForm());
	}
	
	function getDeleteFields() {
		$ret = Array();
		$ret = array_merge($ret,$this->RENAMEFORM->getSelectLine($this->LANG->getText("27"),'map_name',$this->getMaps(),''));
		$ret = array_merge($ret,$this->RENAMEFORM->getHiddenField('map',''));
		$ret[] = '<script>document.map_rename.map.value=window.opener.document.myvalues.formulaire.value</script>';
		
		return $ret;
	}
	
	function getDeleteSubmit() {
		return array_merge($this->DELETEFORM->getSubmitLine($this->LANG->getText("21")),$this->DELETEFORM->closeForm());
	}
	
	function getRenameFields() {
		$ret = Array();
		$ret = array_merge($ret,$this->RENAMEFORM->getSelectLine($this->LANG->getText("27"),'map_name',$this->getMaps(),''));
		$ret = array_merge($ret,$this->RENAMEFORM->getInputLine($this->LANG->getText("28"),'map_new_name',''));
		$ret = array_merge($ret,$this->RENAMEFORM->getHiddenField('map',''));
		$ret[] = '<script>document.map_rename.map.value=window.opener.document.myvalues.formulaire.value</script>';
		
		return $ret;
	}
	
	function getRenameSubmit() {
		return array_merge($this->RENAMEFORM->getSubmitLine($this->LANG->getText("22")),$this->RENAMEFORM->closeForm());
	}
	
	function getCreateFields() {
		$ret = Array();
		$ret = array_merge($ret,$this->CREATEFORM->getInputLine($this->LANG->getText("24"),'map_name',''));
		$ret = array_merge($ret,$this->CREATEFORM->getInputLine($this->LANG->getText("25"),'allowed_users',$user));
		$ret = array_merge($ret,$this->CREATEFORM->getInputLine($this->LANG->getText("49"),'allowed_for_config',$user));
		$ret = array_merge($ret,$this->CREATEFORM->getSelectLine($this->LANG->getText("32"),'map_iconset',$this->getIconsets(),$defaultIcons));
		$ret = array_merge($ret,$this->CREATEFORM->getSelectLine($this->LANG->getText("26"),'map_image',$this->getMapImages(),''));
		
		return $ret;
	}
	
	function getCreateSubmit() {
		return array_merge($this->CREATEFORM->getSubmitLine($this->LANG->getText("20")),$this->CREATEFORM->closeForm());
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
		$ret[] = 'lang[30] = "'.$this->LANG->getTextSilent("30").'";';
		$ret[] = 'lang[31] = "'.$this->LANG->getTextSilent("31").'";';
		$ret[] = 'lang[33] = "'.$this->LANG->getTextSilent("33").'";';
		$ret[] = 'lang[34] = "'.$this->LANG->getTextSilent("34").'";';
		$ret[] = 'lang[36] = "'.$this->LANG->getTextSilent("36").'";';
		$ret[] = 'lang[37] = "'.$this->LANG->getTextSilent("37").'";';
		$ret[] = 'lang[39] = "'.$this->LANG->getTextSilent("39").'";';
		$ret[] = 'lang[41] = "'.$this->LANG->getTextSilent("41").'";';
		$ret[] = 'lang[42] = "'.$this->LANG->getTextSilent("42").'";';
		$ret[] = 'lang[44] = "'.$this->LANG->getTextSilent("44").'";';
		$ret[] = 'lang[45] = "'.$this->LANG->getTextSilent("45").'";';
		$ret[] = 'lang[46] = "'.$this->LANG->getTextSilent("46").'";';
		$ret[] = 'lang[47] = "'.$this->LANG->getTextSilent("47").'";';
		$ret[] = 'lang[48] = "'.$this->LANG->getTextSilent("48").'";';
		$ret[] = 'lang[53] = "'.$this->LANG->getTextSilent("53").'";';
		$ret[] = '//--></script>';
		
		return $ret;	
	}
}
?>