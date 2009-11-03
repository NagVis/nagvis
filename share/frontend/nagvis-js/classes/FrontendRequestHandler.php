<?php
class FrontendRequestHandler {
	private $aOpts;
	private $sReferer;
	
	public function __construct($aOptions) {
		$this->aOpts = $aOptions;
		
		if(isset($_SERVER['HTTP_REFERER'])) {
			$this->sReferer = $_SERVER['HTTP_REFERER'];
		}
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
}

?>
