<?php
/**
 * Class of a Host in Nagios with all necessary informations
 *
 * @author	Lars Michelsen <lars@vertical-visions.de>
 */
class NagVisShape extends NagVisStatelessObject {
	var $MAINCFG;
	var $LANG;
	
	/**
	 * Class constructor
	 *
	 * @param		Object 		Object of class GlobalMainCfg
	 * @param		Object 		Object of class GlobalBackendMgmt
	 * @param		Object 		Object of class GlobalLanguage
	 * @param		String	 	Image of the shape
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	function NagVisShape(&$MAINCFG, &$LANG, $icon) {
		if(DEBUG&&DEBUGLEVEL&1) debug('Start method NagVisShape::NagVisShape(MAINCFG,LANG,'.$icon.')');
		$this->MAINCFG = &$MAINCFG;
		$this->LANG = &$LANG;
		
		$this->iconPath = $this->MAINCFG->getValue('paths', 'shape');
		$this->iconHtmlPath = $this->MAINCFG->getValue('paths', 'htmlshape');
		
		$this->icon = $icon;
		$this->type = 'shape';
		parent::NagVisStatelessObject($this->MAINCFG, $this->LANG);
		if(DEBUG&&DEBUGLEVEL&1) debug('Stop method NagVisShape::NagVisShape()');
	}
	
	/**
	 * PUBLIC parse()
	 *
	 * Parses the object
	 *
	 * @return	String		HTML code of the object
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	function parse() {
		if(DEBUG&&DEBUGLEVEL&1) debug('Start method NagVisShape::parse()');
		if(DEBUG&&DEBUGLEVEL&1) debug('Stop method NagVisShape::parse()');
		return $this->parseIcon();
	}
	
	/**
	 * Gets the hover menu of a shape if it is requested by configuration
	 *
	 * @return	String	The Link
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	function getHoverMenu() {
		if(DEBUG&&DEBUGLEVEL&1) debug('Start method NagVisShape::getHoverMenu()');
		if(isset($this->hover_url) && $this->hover_url != '') {
			parent::getHoverMenu();
		}
		if(DEBUG&&DEBUGLEVEL&1) debug('Stop method NagVisShape::getHoverMenu()');
	}
	
	/**
	 * Creates a link to Nagios, when this is not set in the Config-File
	 *
	 * @return	String	The Link
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	function createLink() {
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method NagVisShape::createLink()');
		
		if(isset($this->url) && $this->url != '') {
			$link = parent::createLink();  
		} else {
			$link = '';
		}
		
		if (DEBUG&&DEBUGLEVEL&1) debug('Stop method NagVisShape::createLink(): '.$link);
		return $link;
	}
	
	/**
	 * Just a dummy here (Shape won't need an icon)
	 *
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	function fetchIcon() {
		// Nothing to do here, icon is set in constructor
	}
}
?>
