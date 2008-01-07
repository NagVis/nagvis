<?php
/**
 * Class of a Host in Nagios with all necessary informations
 *
 * @author	Lars Michelsen <lars@vertical-visions.de>
 */
class NagVisTextbox extends NagVisStatelessObject {
	var $MAINCFG;
	var $LANG;
	
	var $background_color;
	
	/**
	 * Class constructor
	 *
	 * @param		Object 		Object of class GlobalMainCfg
	 * @param		Object 		Object of class GlobalBackendMgmt
	 * @param		Object 		Object of class GlobalLanguage
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	function NagVisTextbox(&$MAINCFG, &$LANG) {
		if(DEBUG&&DEBUGLEVEL&1) debug('Start method NagVisTextbox::NagVisTextbox(MAINCFG,LANG)');
		$this->MAINCFG = &$MAINCFG;
		$this->LANG = &$LANG;
		
		$this->class = 'box';
		$this->background_color = '#CCCCCC';
		
		$this->type = 'textbox';
		parent::NagVisStatelessObject($this->MAINCFG, $this->LANG);
		if(DEBUG&&DEBUGLEVEL&1) debug('Stop method NagVisTextbox::NagVisTextbox()');
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
		if(DEBUG&&DEBUGLEVEL&1) debug('Start method NagVisTextbox::parse()');
		$this->replaceMacros();
		if(DEBUG&&DEBUGLEVEL&1) debug('Stop method NagVisTextbox::parse(): HTML code');
		return $this->parseTextbox();
	}
	
	# End public methods
	# #########################################################################
	
	/**
	 * Replaces macros of urls and hover_urls
	 *
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	function replaceMacros() {
		if(DEBUG&&DEBUGLEVEL&1) debug('Start method NagVisTextbox::replaceMacros()');
		
		$this->text = str_replace('[refresh_counter]','<font id="refreshCounter"></font>', $this->text);
		
		if(DEBUG&&DEBUGLEVEL&1) debug('Stop method NagVisTextbox::replaceMacros()');
	}
	
	/**
	 * Create a Comment-Textbox
	 *
	 * @return	String	String with HTML Code
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	function parseTextbox() {
		if(DEBUG&&DEBUGLEVEL&1) debug('Start method NagVisTextbox::parseTextbox()');
		$ret = '<div class="'.$this->class.'" style="background:'.$this->background_color.';left:'.$this->x.'px;top:'.$this->y.'px;width:'.$this->w.'px;overflow:visible;">';	
		$ret .= "\t".'<span>'.$this->text.'</span>';
		$ret .= '</div>';
		if(DEBUG&&DEBUGLEVEL&1) debug('Stop method NagVisTextbox::parseTextbox(): HTML code');
		return $ret;	
	}
	
	/**
	 * Just a dummy here (Textbox won't need an icon)
	 *
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	function fetchIcon() {
		// Nothing to do here, icon is set in constructor
	}
}
?>
