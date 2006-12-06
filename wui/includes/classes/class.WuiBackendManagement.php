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
					  'jsIncludes'=>Array('./includes/js/BackendManagement.js',
					  						'./includes/js/ajax.js'),
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
			'onSubmit'=>'return update_param(\'backend_default\');',
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
		
		$this->EDITBACKENDFORM = new GlobalForm(Array('name'=>'backend_edit',
			'id'=>'backend_edit',
			'method'=>'POST',
			'action'=>'./wui.function.inc.php?myaction=mgt_backend_edit',
			'onSubmit'=>'return check_backend_edit();',
			'cols'=>'2'));
		$this->addBodyLines($this->EDITBACKENDFORM->initForm());
		$this->addBodyLines($this->EDITBACKENDFORM->getCatLine(strtoupper($this->LANG->getLabel('editBackend'))));
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
		$this->addBodyLines($this->getDelFields());
		$this->addBodyLines($this->getDelSubmit());
	}
	
	function getEditFields() {
		$ret = Array();
		$ret = array_merge($ret,$this->EDITBACKENDFORM->getSelectLine('backend_id','backend_id',array_merge(Array(''=>''),$this->getDefinedBackends()),'',TRUE,"getBackendOptions('',this.value,'".$this->EDITBACKENDFORM->id."');"));
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
	
	function getEditSubmit() {
		return array_merge($this->EDITBACKENDFORM->getSubmitLine($this->LANG->getLabel('save')),$this->EDITBACKENDFORM->closeForm());
	}
	
	function getDelFields() {
		$ret = Array();
		$ret = array_merge($ret,$this->DELBACKENDFORM->getSelectLine('backend_id','backend_id',array_merge(Array(''=>''),$this->getDefinedBackends()),'',TRUE));
		return $ret;
	}
	
	function getDelSubmit() {
		return array_merge($this->DELBACKENDFORM->getSubmitLine($this->LANG->getLabel('save')),$this->DELBACKENDFORM->closeForm());
	}
	
	function getAddFields() {
		$ret = Array();
		$ret = array_merge($ret,$this->ADDBACKENDFORM->getInputLine('backend_id','backend_id','',TRUE));
		foreach($this->MAINCFG->validConfig['backend'] as $propname => $prop) {
			if($propname == "backendtype") {
				$ret = array_merge($ret,$this->ADDBACKENDFORM->getSelectLine($propname,$propname,array_merge(Array(''=>''),$this->getBackends()),'',$prop['must'],"getBackendOptions(this.value,'','".$this->ADDBACKENDFORM->id."');"));
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
		
		if(isset($ret) && count($ret) > 1) {
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