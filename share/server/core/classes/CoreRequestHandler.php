<?php
class CoreRequestHandler {
	private $aOpts;
	private $sReferer = '';
	private $sRequestUri = '';
	
	public function __construct($aOptions) {
		$this->aOpts = $aOptions;
		
		if(isset($_SERVER['HTTP_REFERER'])) {
			$this->sReferer = $_SERVER['HTTP_REFERER'];
		}
		
		if(isset($_SERVER['REQUEST_URI'])) {
			$this->sRequestUri = $_SERVER['REQUEST_URI'];
		}
	}
	
	public function getKeys() {
		return array_keys($this->aOpts);
	}
	
	public function get($sKey) {
		if(isset($this->aOpts[$sKey])) {
			return $this->aOpts[$sKey];
		} else {
			return null;
		}
	}
	
	public function isLongerThan($sKey, $iLen) {
		if(strlen($this->aOpts[$sKey]) > $iLen) {
			return true;
		} else {
			return false;
		}
	}

	public function match($sKey, $regex) {
		if(preg_match($regex, $this->aOpts[$sKey])) {
			return true;
		} else {
			return false;
		}
	}
	
	public function isSetAndNotEmpty($sKey) {
		if(isset($this->aOpts[$sKey]) && $this->aOpts[$sKey] != '') {
			return true;
		} else {
			return false;
		}
	}
	
	public function getReferer() {
		return $this->sReferer;
	}
	
	public function getRequestUri() {
		return $this->sRequestUri;
	}
}

?>
