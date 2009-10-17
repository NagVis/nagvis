<?php
class CoreUriHandler {
	private $CORE;
	private $sRequestUri;
	private $aOpts;
	
	private $aAliases;
	
	public function __construct($CORE) {
		$this->CORE = $CORE;
		
		$this->aAliases = Array('module' => 'mod', 'action' => 'act');
		
		$this->sRequestUri = strip_tags($_SERVER['REQUEST_URI']);
		
		// Parse the URI and apply default params when neccessary
		$this->parseUri();
		$this->setDefaults();
		$this->validate();
	}
	
	public function getRequestUri() {
		return $this->sRequestUri;
	}
	
	public function set($sKey, $sVal) {
		$sReturn = false;
		
		// Transform parameter aliases
		if(isset($this->aAliases[$sKey])) {
			$sKey = $this->aAliases[$sKey];
		}
		
		if($this->isSetAndNotEmpty($sKey)) {
			$sReturn = $this->aOpts[$sKey];
		}
		
		$this->aOpts[$sKey] = $sVal;
		
		return $sReturn;
	}
	
	public function get($sKey) {
		// Transform parameter aliases
		if(isset($this->aAliases[$sKey])) {
			$sKey = $this->aAliases[$sKey];
		}
		
		if($this->isSetAndNotEmpty($sKey)) {
			return $this->aOpts[$sKey];
		} else {
			return false;
		}
	}

	public function parseModSpecificUri($aKeys) {
		foreach($aKeys AS $key => $sMatch) {
			// Validate the value
			$bValid = true;
			if($sMatch !== '') {
				// When param not set initialize it as empty string
				if(!isset($_GET[$key])) {
					$_GET[$key] = '';
				}
				
				// Validate single value or multiple (array)
				if(is_array($_GET[$key])) {
					foreach($_GET[$key] AS $val) {
						if(preg_match($sMatch, $val)) {
							$bValid = true;
						} else {
							$bValid = false;
						}
					}
				} else {
					if(preg_match($sMatch, $_GET[$key])) {
						$bValid = true;
					} else {
						$bValid = false;
					}
				}
			} else {
				// FIXME: Dev notice: Value gets not validated
			}
			
			if($bValid) {
				$this->aOpts[$key] = $_GET[$key];
			} else {
				new GlobalMessage('ERROR', $this->CORE->LANG->getText('paramNotValid', Array('key' => htmlentities($key))));
			}
		}
	}
	
	private function parseUri() {
		//FIXME: Maybe for later use when using nice urls
		// Cleanup some bad things from the URI
		//$sRequest = str_replace($this->CORE->MAINCFG->getValue('paths','htmlbase'), '', $this->sRequestUri);
		// Remove the first slash and then explode by slashes
		//$this->aOpts = explode('/', substr($sRequest,1));
		
		if(isset($_GET['mod'])) {
			$this->aOpts['mod'] = $_GET['mod'];
		}
		if(isset($_GET['act'])) {
			$this->aOpts['act'] = $_GET['act'];
		}
	}
	
	private function setDefaults() {
		// Handle default options when no module given
		if(!$this->isSetAndNotEmpty('mod')) {
			$this->aOpts['mod'] = $this->CORE->MAINCFG->getValue('global', 'startmodule');
		}
		
		// Handle default options when no action given
		if(!$this->isSetAndNotEmpty('act')) {
			$this->aOpts['act'] = $this->CORE->MAINCFG->getValue('global', 'startaction');
		}
	}
	
	private function validate() {
		$bValid = true;
		
		// Validate each param
		foreach($this->aOpts AS $val) {
			if(!preg_match(MATCH_URI_PART, $val)) {
				$bValid = false;
			}
		}
		
		// If one param is invalid send the user to 404 page
		if($bValid === false) {
			new GlobalMessage('ERROR', $this->CORE->LANG->getText('moduleNotValid'));
		}
	}
	
	public function isSetAndNotEmpty($sKey) {
		// Transform parameter aliases
		if(isset($this->aAliases[$sKey])) {
			$sKey = $this->aAliases[$sKey];
		}
		
		if(isset($this->aOpts[$sKey]) && $this->aOpts[$sKey] != '') {
			return true;
		} else {
			return false;
		}
	}
}

?>
