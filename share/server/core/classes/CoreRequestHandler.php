<?php
class CoreRequestHandler {
	private $aOpts;
	
	public function __construct($aOptions) {
		$this->aOpts = $aOptions;
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
	
	public static function getReferer($default) {
		if(isset($_SERVER['HTTP_REFERER']))
      return $_SERVER['HTTP_REFERER'];
    else
			return $default;
	}
	
	public static function getRequestUri($default) {
		if(isset($_SERVER['REQUEST_URI']))
      return $_SERVER['REQUEST_URI'];
    else
			return $default;
	}
}

?>
