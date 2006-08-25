<?php
/**
 * Class for building the Wui Frontend and display the map
 *
 * @author 	Lars Michelsen <larsi@nagios-wiki.de>
 */
class WuiFrontend extends GlobalPage {
	var $MAINCFG;
	var $MAPCFG;
	var $LANG;
	
	var $MAP;
	
	/**
	* Constructor
	*
	* @author Lars Michelsen <larsi@nagios-wiki.de>
	*/
	function WuiFrontend(&$MAINCFG,&$MAPCFG) {
		$this->MAINCFG = &$MAINCFG;
		$this->MAPCFG = &$MAPCFG;
		
		$this->LANG = new GlobalLanguage($MAINCFG,'wui');
		
		$prop = Array('title'=>$MAINCFG->getValue('internal', 'title'),
					  'cssIncludes'=>Array('../nagvis/includes/css/style.css','./includes/css/office_xp/office_xp.css'),
					  'jsIncludes'=>Array('./includes/js/wui.js',
					  	  './includes/js/nagvis.js',
						  './includes/js/overlib.js',
						  './includes/js/wz_jsgraphics.js',
						  './includes/js/wz_dragdrop.js',
						  './includes/js/wz_tooltip.js',
						  './includes/js/jsdomenu.js',
						  './includes/js/jsdomenu.inc.js'),
					  'extHeader'=>Array(''),
					  'allowedUsers' => $this->MAPCFG->getValue('global', 0,'allowed_for_config'));
		parent::GlobalPage($MAINCFG,$prop);
	}
	
	/**
	* If enabled, the map is added to the page
	*
	* @author Lars Michelsen <larsi@nagios-wiki.de>
	*/
	function getMap() {
		$this->addBodyLines('<div id="mycanvas" class="map">');
		$this->MAP = new WuiMap($this->MAINCFG,$this->MAPCFG,$this->LANG);
		$this->addBodyLines($this->MAP->parseMap());
		$this->addBodyLines('</div>');
		$this->addBodyLines($this->getJsLang());
		$this->addBodyLines('<script type="text/javascript" src="./includes/js/wz_tooltip.js"></script>');
	}
	
	/**
	* Parses the needed language strings to javascript
	*
	* @return	Array Html
	* @author Lars Michelsen <larsi@nagios-wiki.de>
	*/
	function getJsLang() {
		$ret = Array();
		$ret[] = '<script type="text/javascript" language="JavaScript"><!--';
		$ret[] = 'var lang = Array();';
		$ret[] = 'lang["clickMapToSetPoints"] = "'.$this->LANG->getMessageText('clickMapToSetPoints').'";';
		$ret[] = 'lang["confirmDelete"] = "'.$this->LANG->getMessageText('confirmDelete').'";';
		$ret[] = 'lang["confirmRestore"] = "'.$this->LANG->getMessageText('confirmRestore').'";';
		$ret[] = '//--></script>';
		
		return $ret;	
	}
}
?>