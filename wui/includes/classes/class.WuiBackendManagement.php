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
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method WuiBackendManagement::WuiBackendManagement(&$MAINCFG)');
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
					  'allowedUsers' => $this->MAINCFG->getValue('wui','allowedforconfig'),
					  'languageRoot' => 'wui:backendManagement');
		parent::GlobalPage($MAINCFG,$prop);
		if (DEBUG&&DEBUGLEVEL&1) debug('End method WuiBackendManagement::WuiBackendManagement()');
	}
	
	/**
	* If enabled, the form is added to the page
	*
	* @author Lars Michelsen <lars@vertical-visions.de>
	*/
	function getForm() {
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method WuiBackendManagement::getForm()');
		// Inititalize language for JS
		$this->addBodyLines($this->parseJs($this->getJsLang()));
		
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
		$this->addBodyLines($this->DEFBACKENDFORM->getSubmitLine($this->LANG->getLabel('save')));
		$this->addBodyLines($this->DEFBACKENDFORM->closeForm());
		
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
		$this->addBodyLines($this->ADDBACKENDFORM->getSubmitLine($this->LANG->getLabel('save')));
		$this->addBodyLines($this->ADDBACKENDFORM->closeForm());
		
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
		$this->addBodyLines($this->EDITBACKENDFORM->getSubmitLine($this->LANG->getLabel('save')));
		$this->addBodyLines($this->EDITBACKENDFORM->closeForm());
		
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
		$this->addBodyLines($this->DELBACKENDFORM->getSubmitLine($this->LANG->getLabel('save')));
		$this->addBodyLines($this->DELBACKENDFORM->closeForm());
		
		// Resize the window
		$this->addBodyLines($this->parseJs($this->resizeWindow(540,$this->propCount*35+180)));
		if (DEBUG&&DEBUGLEVEL&1) debug('End method WuiBackendManagement::getForm()');
	}
	
	/**
	 * Gets edit fields of the form
	 *
	 * @return	Array	HTML Code
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
     */
	function getEditFields() {
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method WuiBackendManagement::getEditFields()');
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
		if (DEBUG&&DEBUGLEVEL&1) debug('End method WuiBackendManagement::getEditFields(): Array(HTML)');
		return $ret;
	}
	
	/**
	 * Gets delete fields of the form
	 *
	 * @return	Array	HTML Code
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
     */
	function getDelFields() {
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method WuiBackendManagement::getDelFields()');
		$ret = Array();
		$this->propCount++;
		$ret = array_merge($ret,$this->DELBACKENDFORM->getSelectLine('backend_id','backend_id',array_merge(Array(''=>''),$this->getDefinedBackends()),'',TRUE));
		
		if (DEBUG&&DEBUGLEVEL&1) debug('End method WuiBackendManagement::getDelFields(): Array(HTML)');
		return $ret;
	}
	
	/**
	 * Gets add fields of the form
	 *
	 * @return	Array	HTML Code
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
     */
	function getAddFields() {
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method WuiBackendManagement::getAddFields()');
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
		if (DEBUG&&DEBUGLEVEL&1) debug('End method WuiBackendManagement::getAddFields(): Array(HTML)');
		return $ret;
	}
	
	/**
	 * Gets default fields of the form
	 *
	 * @return	Array	HTML Code
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
     */
	function getDefaultFields() {
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method WuiBackendManagement::getDefaultsFields()');
		$ret = Array();
		
		$ret = array_merge($ret,$this->DEFBACKENDFORM->getSelectLine($this->LANG->getLabel('defaultBackend'),'defaultbackend',$this->getDefinedBackends(),$this->MAINCFG->getValue('defaults','backend',TRUE),TRUE));
		$this->propCount++;
		
		if (DEBUG&&DEBUGLEVEL&1) debug('End method WuiBackendManagement::getDefaultFields(): Array(HTML)');
		return $ret;
	}
	
	/**
	 * Reads all backends which are defined in config.ini.php
	 *
	 * @return	Array Html
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	function getDefinedBackends() {
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method WuiBackendManagement::getDefinedBackends()');
		$ret = Array();
		
		foreach($this->MAINCFG->config AS $sec => $arr) {
			if(preg_match("/^backend_/i", $sec)) {
				$ret[] = preg_replace("/^backend_/i",'',$sec);
			}
		}
		
		if(isset($ret) && count($ret) > 1) {
			natcasesort($ret);
		}
		
		if (DEBUG&&DEBUGLEVEL&1) debug('End method WuiBackendManagement::getDefinedBackends(): Array(HTML)');
		return $ret;
	}
	
	/**
	 * Reads all aviable backends
	 *
	 * @return	Array Html
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	function getBackends() {
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method WuiBackendManagement::getBackends()');
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
		
		if (DEBUG&&DEBUGLEVEL&1) debug('End method WuiBackendManagement::getBackends(): Array(HTML)');
		return $files;
	}
	
	/**
	 * Gets all needed messages
	 *
	 * @return	Array JS
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	function getJsLang() {
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method WuiBackendManagement::getJsLang()');
		$ret = Array();
		$ret[] = 'var lang = Array();';
		$ret[] = 'lang[\'mustValueNotSet\'] = \''.$this->LANG->getMessageText('mustValueNotSet','',FALSE).'\';';
		
		if (DEBUG&&DEBUGLEVEL&1) debug('End method WuiBackendManagement::getJsLang(): Array(JS)');
		return $ret;	
	}
}
?>