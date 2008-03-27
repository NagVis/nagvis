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
	var $border_color;
	
	/**
	 * Class constructor
	 *
	 * @param		Object 		Object of class GlobalMainCfg
	 * @param		Object 		Object of class GlobalBackendMgmt
	 * @param		Object 		Object of class GlobalLanguage
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	function NagVisTextbox(&$MAINCFG, &$LANG) {
		$this->MAINCFG = &$MAINCFG;
		$this->LANG = &$LANG;
		
		$this->class = 'box';
		$this->background_color = '#CCCCCC';
		
		$this->type = 'textbox';
		parent::NagVisStatelessObject($this->MAINCFG, $this->LANG);
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
		$this->replaceMacros();
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
		
		$this->text = str_replace('[refresh_counter]','<font id="refreshCounter"></font>', $this->text);
		
	}
	
	/**
	 * Create a Comment-Textbox
	 *
	 * @return	String	String with HTML Code
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	function parseTextbox() {
		$ret = '<div class="'.$this->class.'" style="border-color:'.$this->border_color.';background:'.$this->background_color.';left:'.$this->x.'px;top:'.$this->y.'px;width:'.$this->w.'px;overflow:visible;">';	
		$ret .= "\t".'<span>'.$this->text.'</span>';
		$ret .= '</div>';
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
