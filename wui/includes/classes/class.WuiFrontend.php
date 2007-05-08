<?php
/**
 * Class for building the Wui Frontend and display the map
 *
 * @author 	Lars Michelsen <lars@vertical-visions.de>
 */
class WuiFrontend extends GlobalPage {
	var $MAINCFG;
	var $MAPCFG;
	var $LANG;
	
	var $MAP;
	
	/**
	* Constructor
	*
	* @author Lars Michelsen <lars@vertical-visions.de>
	*/
	function WuiFrontend(&$MAINCFG,&$MAPCFG) {
		$this->MAINCFG = &$MAINCFG;
		$this->MAPCFG = &$MAPCFG;
		
		$this->LANG = new GlobalLanguage($this->MAINCFG,'wui:global');
		
		$prop = Array('title'=>$MAINCFG->getValue('internal', 'title'),
					  'cssIncludes'=>Array('../nagvis/includes/css/style.css','./includes/css/wui.css','./includes/css/office_xp/office_xp.css'),
					  'jsIncludes'=>Array('./includes/js/wui.js',
					  	  './includes/js/ajax.js',
						  './includes/js/jsdomenu.js',
						  './includes/js/jsdomenu.inc.js',
						  './includes/js/wz_jsgraphics.js',
						  './includes/js/wz_dragdrop.js'),
					  'extHeader'=>Array("<style type=\"text/css\">.main { background-color: ".$this->MAPCFG->getValue('global',0, 'background_color')."; }</style>"),
					  'allowedUsers' => $this->MAPCFG->getValue('global', 0,'allowed_for_config'),
					  'languageRoot' => 'wui:global');
		parent::GlobalPage($this->MAINCFG,$prop);
	}
	
	/**
	* If enabled, the map is added to the page
	*
	* @author Lars Michelsen <lars@vertical-visions.de>
	*/
	function getMap() {
		$this->addBodyLines('<div id="mymap" class="map">');
		$this->MAP = new WuiMap($this->MAINCFG,$this->MAPCFG,$this->LANG);
		$this->addBodyLines($this->MAP->parseMap());
		$this->addBodyLines('</div>');
	}
	
	/**
	 * Adds the user messages to the page
	 *
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	function getMessages() {
		$this->addBodyLines($this->getUserMessages());	
	}
}
?>