<?php
/**
 * Class of a Host in Nagios with all necessary informations
 */
class NagVisShape extends NagVisStatelessObject {
	var $MAINCFG;
	var $BACKEND;
	var $LANG;
	
	function NagVisShape(&$MAINCFG, &$BACKEND, &$LANG, $icon) {
		$this->MAINCFG = &$MAINCFG;
		$this->BACKEND = &$BACKEND;
		$this->LANG = &$LANG;
		
		$this->iconPath = $this->MAINCFG->getValue('paths', 'shape');
		$this->iconHtmlPath = $this->MAINCFG->getValue('paths', 'htmlshape');
		
		$this->icon = $icon;
		$this->type = 'shape';
		parent::NagVisStatelessObject($this->MAINCFG, $this->BACKEND, $this->LANG);
	}
	
	function parse() {
		//$this->replaceMacros();
		//$this->fixIconPosition();
		return $this->parseIcon();
	}
	
	function fetchIcon() {
		//FIXME: Nothing to do here, icon is set in constructor
	}
	
	function getHoverMenu() {
		if(isset($this->hover_url) && $this->hover_url != '') {
			parent::getHoverMenu();
		}
	}
	
	/**
	 * Creates a link to Nagios, when this is not set in the Config-File
	 *
	 * @return	String	The Link
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	function createLink() {
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method NagVisShape::createLink(&$obj)');
		
		if(isset($this->url) && $this->url != '') {
			$link = parent::createLink();  
		} else {
			$link = '';
		}
		
		if (DEBUG&&DEBUGLEVEL&1) debug('End method NagVisShape::createLink(): '.$link);
		return $link;
	}
}
?>
