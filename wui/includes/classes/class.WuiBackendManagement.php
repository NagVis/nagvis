<?php
class WuiBackendManagement extends GlobalPage {
	var $MAINCFG;
	var $LANG;
	
	var $DEFBACKENDFORM;
	var $ADDBACKENDFORM;
	
	function WuiBackendManagement(&$MAINCFG) {
		$this->MAINCFG = &$MAINCFG;
		
		# we load the language file
		$this->LANG = new GlobalLanguage($MAINCFG,'wui:backendManagement');
		
		$prop = Array('title'=>$MAINCFG->getValue('internal', 'title'),
					  'cssIncludes'=>Array('./includes/css/wui.css'),
					  'jsIncludes'=>Array('./includes/js/backend_management.js'),
					  'extHeader'=>Array(''),
					  'allowedUsers' => Array('EVERYONE'),
					  'languageRoot' => 'wui:backendManagement');
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
		
		$this->DEFBACKENDFORM = new GlobalForm(Array('name'=>'backend_default',
			'id'=>'backend_default',
			'method'=>'POST',
			'action'=>'./wui.function.inc.php?myaction=mgt_backend_default',
			'onSubmit'=>'return check_backend_default();',
			'cols'=>'2'));
		$this->addBodyLines($this->DEFBACKENDFORM->initForm());
		$this->addBodyLines($this->DEFBACKENDFORM->getCatLine(strtoupper($this->LANG->getLabel('setDefaultBackend'))));
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
		$this->addBodyLines($this->getAddFields());
		$this->addBodyLines($this->getAddSubmit());
	}
	
	
	function getAddFields() {
		$ret = Array();
		$ret = array_merge($ret,$this->ADDBACKENDFORM->getInputLine('backend_id','backend_id','',TRUE));
		foreach($this->MAINCFG->validConfig['backend'] as $propname => $prop) {
			if($propname == "backendtype") {
				$ret = array_merge($ret,$this->ADDBACKENDFORM->getSelectLine($propname,$propname,$this->getBackends(),'',$prop['must']));
			}
		}
		// FIXME: OnChange -> show options for the selected backend
		return $ret;
	}
	
	function getAddSubmit() {
		return array_merge($this->ADDBACKENDFORM->getSubmitLine($this->LANG->getLabel('save')),$this->ADDBACKENDFORM->closeForm());
	}
	
	function getDefaultFields() {
		$ret = Array();
		
		$ret = array_merge($ret,$this->DEFBACKENDFORM->getSelectLine($this->LANG->getLabel('defaultBackend'),'defaultbackend',$this->getDefinedBackends(),$this->MAINCFG->getValue('global','defaultbackend',TRUE),TRUE));
		
		return $ret;
	}
	
	function getDefaultSubmit() {
		return array_merge($this->DEFBACKENDFORM->getSubmitLine($this->LANG->getLabel('save')),$this->DEFBACKENDFORM->closeForm());
	}
	
	/**
	 * Reads all backends which are defined in config.ini.php
	 *
	 * @return	Array Html
	 * @author 	Lars Michelsen <larsi@nagios-wiki.de>
	 */
	function getDefinedBackends() {
		$ret = Array();
		
		foreach($this->MAINCFG->config AS $sec => $arr) {
			if(preg_match("/^backend_/i", $sec)) {
				$ret[] = preg_replace("/^backend_/i",'',$sec);
			}
		}
		
		if($files) {
			natcasesort($ret);
		}
		
		return $ret;
	}
	
	/**
	 * Reads all aviable backends
	 *
	 * @return	Array Html
	 * @author 	Lars Michelsen <larsi@nagios-wiki.de>
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
	
	function getJsLang() {
		$ret = Array();
		$ret[] = '<script type="text/javascript" language="JavaScript"><!--';
		$ret[] = 'var lang = Array();';
		//$ret[] = 'lang["firstMustChoosePngImage"] = "'.$this->LANG->getMessageText('firstMustChoosePngImage').'";';
		$ret[] = '//--></script>';
		
		return $ret;	
	}
}
?>