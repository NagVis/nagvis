<?php
/** 
 * Class for managing (add,modify,delete the backends in the WUI
 */
class WuiBackendManagement extends GlobalPage {
	var $MAINCFG;
	var $LANG;
	
	var $DEFBACKENDFORM;
	var $ADDBACKENDFORM;
	
	var $propCount;
	
	/**
	 * Class Constructor
	 *
	 * @param 	GlobalMainCfg 	$MAINCFG
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	function WuiBackendManagement(&$MAINCFG) {
		$this->MAINCFG = &$MAINCFG;
		$this->propCount = 0;
		
		// load the language file
		$this->LANG = new GlobalLanguage($MAINCFG,'wui:backendManagement');
		
		$prop = Array('title'=>$MAINCFG->getValue('internal', 'title'),
					  'cssIncludes'=>Array('./includes/css/wui.css'),
					  'jsIncludes'=>Array('./includes/js/wui.js',
					  						'./includes/js/BackendManagement.js',
					  						'./includes/js/ajax.js'),
					  'extHeader'=>Array(''),
					  'allowedUsers' => Array('EVERYONE'),
					  'languageRoot' => 'wui:backendManagement');
		parent::GlobalPage($MAINCFG,$prop);
	}
	
	/**
	* If enabled, the form is added to the page
	*
	* @author Lars Michelsen <lars@vertical-visions.de>
	*/
	function getForm() {
		// Inititalize language for JS
		$this->addBodyLines($this->getJsLang());
		
		$this->DEFBACKENDFORM = new GlobalForm(Array('name'=>'backend_default',
			'id'=>'backend_default',
			'method'=>'POST',
			'action'=>'./wui.function.inc.php?myaction=mgt_backend_default',
			'onSubmit'=>'return update_param(\'backend_default\');',
			'cols'=>'2'));
		$this->addBodyLines($this->DEFBACKENDFORM->initForm());
		$this->addBodyLines($this->DEFBACKENDFORM->getCatLine(strtoupper($this->LANG->getLabel('setDefaultBackend'))));
		$this->propCount++;
		$this->addBodyLines($this->getDefaultFields());
		$this->addBodyLines($this->getDefaultSubmit());
		
		$this->ADDBACKENDFORM = new GlobalForm(Array('name'=>'backend_add',
			'id'=>'backend_add',
			'method'=>'POST',
			'action'=>'./wui.function.inc.php?myaction=mgt_backend_add',
			'onSubmit'=>'return check_backend_add();',
			'cols'=>'2'));
		$this->addBodyLines($this->ADDBACKENDFORM->initForm());
		$this->addBodyLines($this->ADDBACKENDFORM->getCatLine(strtoupper($this->LANG->getLabel('addBackend'))));
		$this->propCount++;
		$this->addBodyLines($this->getAddFields());
		$this->addBodyLines($this->getAddSubmit());
		
		$this->EDITBACKENDFORM = new GlobalForm(Array('name'=>'backend_edit',
			'id'=>'backend_edit',
			'method'=>'POST',
			'action'=>'./wui.function.inc.php?myaction=mgt_backend_edit',
			'onSubmit'=>'return check_backend_edit();',
			'cols'=>'2'));
		$this->addBodyLines($this->EDITBACKENDFORM->initForm());
		$this->addBodyLines($this->EDITBACKENDFORM->getCatLine(strtoupper($this->LANG->getLabel('editBackend'))));
		$this->propCount++;
		$this->addBodyLines($this->getEditFields());
		$this->addBodyLines($this->getEditSubmit());
		
		$this->DELBACKENDFORM = new GlobalForm(Array('name'=>'backend_del',
			'id'=>'backend_del',
			'method'=>'POST',
			'action'=>'./wui.function.inc.php?myaction=mgt_backend_del',
			'onSubmit'=>'return check_backend_del();',
			'cols'=>'2'));
		$this->addBodyLines($this->DELBACKENDFORM->initForm());
		$this->addBodyLines($this->DELBACKENDFORM->getCatLine(strtoupper($this->LANG->getLabel('delBackend'))));
		$this->propCount++;
		$this->addBodyLines($this->getDelFields());
		$this->addBodyLines($this->getDelSubmit());
		
		// Resize the window
		$this->addBodyLines($this->resizeWindow());
	}
	
	/**
	 * Resizes the window to individual calculated size
	 *
	 * @return	Array	HTML Code
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
     */
	function resizeWindow() {
		$ret = Array();
		$ret[] = "<script type=\"text/javascript\" language=\"JavaScript\"><!--";
		$ret[] = "// resize the window (depending on the number of properties displayed)";
		$ret[] = "window.resizeTo(540,".$this->propCount."*35+20)";
		$ret[] = "//--></script>";
		
		return $ret;
	}
	
	/**
	 * Gets edit fields of the form
	 *
	 * @return	Array	HTML Code
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
     */
	function getEditFields() {
		$ret = Array();
		$ret = array_merge($ret,$this->EDITBACKENDFORM->getSelectLine('backend_id','backend_id',array_merge(Array(''=>''),$this->getDefinedBackends()),'',TRUE,"getBackendOptions('',this.value,'".$this->EDITBACKENDFORM->id."');"));
		$this->propCount++;
		$ret[] = "<script language=\"javascript\">";
		$ret[] = "\tvar backendOptions = Array();";
		foreach($this->MAINCFG->validConfig['backend']['options'] AS $backendtype => $arr) {
			$ret[] = "\tbackendOptions['".$backendtype."'] = Array();";
			foreach($arr AS $key => $opt) {
				$ret[] = "\tbackendOptions['".$backendtype."']['".$key."'] = Array();";
				foreach($opt AS $var => $val) {
					$ret[] = "\tbackendOptions['".$backendtype."']['".$key."']['".$var."'] = '".$val."'";
				}
			}
		}
		$ret[] = "\tvar definedBackends = Array();";
		$ret[] = "\tdefinedBackends['-'] = Array();";
		foreach($this->MAINCFG->config AS $sec => $arr) {
			if(preg_match("/^backend_/i", $sec)) {
				$backend_id = preg_replace("/^backend_/i",'',$sec);
				$ret[] = "\tdefinedBackends['".$backend_id."'] = Array();";
				foreach($arr AS $key => $val) {
					if(!preg_match("/^comment_/i", $key)) {
						$ret[] = "\tdefinedBackends['".$backend_id."']['".$key."'] = '".$val."';";
					}
				}
			}
		}
		$ret[] = "</script>";
		return $ret;
	}
	
	/**
	 * Gets edit submit button of the form
	 *
	 * @return	Array	HTML Code
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
     */
	function getEditSubmit() {
		$this->propCount++;
		return array_merge($this->EDITBACKENDFORM->getSubmitLine($this->LANG->getLabel('save')),$this->EDITBACKENDFORM->closeForm());
	}
	
	/**
	 * Gets delete fields of the form
	 *
	 * @return	Array	HTML Code
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
     */
	function getDelFields() {
		$ret = Array();
		$this->propCount++;
		$ret = array_merge($ret,$this->DELBACKENDFORM->getSelectLine('backend_id','backend_id',array_merge(Array(''=>''),$this->getDefinedBackends()),'',TRUE));
		return $ret;
	}
	
	/**
	 * Gets delete submit button of the form
	 *
	 * @return	Array	HTML Code
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
     */
	function getDelSubmit() {
		$this->propCount++;
		return array_merge($this->DELBACKENDFORM->getSubmitLine($this->LANG->getLabel('save')),$this->DELBACKENDFORM->closeForm());
	}
	
	/**
	 * Gets add fields of the form
	 *
	 * @return	Array	HTML Code
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
     */
	function getAddFields() {
		$ret = Array();
		$ret = array_merge($ret,$this->ADDBACKENDFORM->getInputLine('backend_id','backend_id','',TRUE));
		$this->propCount++;
		foreach($this->MAINCFG->validConfig['backend'] as $propname => $prop) {
			if($propname == "backendtype") {
				$ret = array_merge($ret,$this->ADDBACKENDFORM->getSelectLine($propname,$propname,array_merge(Array(''=>''),$this->getBackends()),'',$prop['must'],"getBackendOptions(this.value,'','".$this->ADDBACKENDFORM->id."');"));
				$this->propCount++;
			}
		}
		$ret[] = "<script language=\"javascript\">";
		$ret[] = "\tvar backendOptions = Array();";
		foreach($this->MAINCFG->validConfig['backend']['options'] AS $backendtype => $arr) {
			$ret[] = "\tbackendOptions['".$backendtype."'] = Array();";
			foreach($arr AS $key => $opt) {
				$ret[] = "\tbackendOptions['".$backendtype."']['".$key."'] = Array();";
				foreach($opt AS $var => $val) {
					$ret[] = "\tbackendOptions['".$backendtype."']['".$key."']['".$var."'] = '".$val."'";
				}
			}
		}
		$ret[] = "</script>";
		return $ret;
	}
	
	/**
	 * Gets add submit button of the form
	 *
	 * @return	Array	HTML Code
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
     */
	function getAddSubmit() {
		$this->propCount++;
		return array_merge($this->ADDBACKENDFORM->getSubmitLine($this->LANG->getLabel('save')),$this->ADDBACKENDFORM->closeForm());
	}
	
	/**
	 * Gets default fields of the form
	 *
	 * @return	Array	HTML Code
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
     */
	function getDefaultFields() {
		$ret = Array();
		
		$ret = array_merge($ret,$this->DEFBACKENDFORM->getSelectLine($this->LANG->getLabel('defaultBackend'),'defaultbackend',$this->getDefinedBackends(),$this->MAINCFG->getValue('defaults','backend',TRUE),TRUE));
		$this->propCount++;
		
		return $ret;
	}
	
	/**
	 * Gets default submit button of the form
	 *
	 * @return	Array	HTML Code
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
     */
	function getDefaultSubmit() {
		$this->propCount++;
		return array_merge($this->DEFBACKENDFORM->getSubmitLine($this->LANG->getLabel('save')),$this->DEFBACKENDFORM->closeForm());
	}
	
	/**
	 * Reads all backends which are defined in config.ini.php
	 *
	 * @return	Array Html
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	function getDefinedBackends() {
		$ret = Array();
		
		foreach($this->MAINCFG->config AS $sec => $arr) {
			if(preg_match("/^backend_/i", $sec)) {
				$ret[] = preg_replace("/^backend_/i",'',$sec);
			}
		}
		
		if(isset($ret) && count($ret) > 1) {
			natcasesort($ret);
		}
		
		return $ret;
	}
	
	/**
	 * Reads all aviable backends
	 *
	 * @return	Array Html
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	function getBackends() {
		$files = Array();
		
		if ($handle = opendir($this->MAINCFG->getValue('paths', 'class'))) {
 			while (false !== ($file = readdir($handle))) {
 				if ($file != "." && $file != ".." && preg_match('/^class.GlobalBackend-/', $file)) {
					$files[] = str_replace('class.GlobalBackend-','',str_replace('.php','',$file));
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
		$ret[] = '<script type="text/javascript" language="JavaScript"><!--';
		$ret[] = 'var lang = Array();';
		$ret[] = 'lang["mustValueNotSet"] = \''.$this->LANG->getMessageText('mustValueNotSet','',FALSE).'\';';
		$ret[] = '//--></script>';
		
		return $ret;	
	}
}
?>