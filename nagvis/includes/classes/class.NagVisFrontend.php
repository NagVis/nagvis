<?php
/**
 * Class for parsing the NagVis Frontend
 */
class NagVisFrontend extends GlobalPage {
	var $MAINCFG;
	var $MAPCFG;
	var $BACKEND;
	var $LANG;
	
	var $MAP;
	
	/**
	 * Class Constructor
	 *
	 * @param 	GlobalMainCfg 	$MAINCFG
	 * @param 	GlobalMapCfg 	$MAPCFG
	 * @param 	GlobalBackend 	$BACKEND
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	function NagVisFrontend(&$MAINCFG,&$MAPCFG,&$BACKEND) {
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method NagVisFrontend::NagVisFrontend($MAINCFG,$MAPCFG,$BACKEND)');
		$this->MAINCFG = &$MAINCFG;
		$this->MAPCFG = &$MAPCFG;
		$this->BACKEND = &$BACKEND;
		$this->LANG = new GlobalLanguage($MAINCFG,'nagvis:global');
		$prop = Array('title'=>$MAINCFG->getValue('internal', 'title'),
					  'cssIncludes'=>Array('./includes/css/style.css'),
					  'jsIncludes'=>Array('./includes/js/nagvis.js','./includes/js/overlib.js','./includes/js/overlib_shadow.js'),
					  'extHeader'=>Array('<META http-equiv="refresh" CONTENT="'.$this->MAINCFG->getValue('global', 'refreshtime').';'.$this->getNextRotate().'">'.
					  					"<style>.main { background-color: ".$this->MAPCFG->getValue('global',0, 'background_color')."; }</style>"),
					  'allowedUsers'=> $this->MAPCFG->getValue('global',0, 'allowed_user'),
					  'languageRoot' => 'nagvis:global');
		parent::GlobalPage($this->MAINCFG,$prop);
		if (DEBUG&&DEBUGLEVEL&1) debug('End method NagVisFrontend::NagVisFrontend()');
	}
	
	/**
	 * If enabled, the header menu is added to the page
	 *
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	function getHeaderMenu() {
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method NagVisFrontend::getHeaderMenu()');
		if($this->MAINCFG->getValue('global', 'displayheader') == '1') {
			$this->addBodyLines($this->makeHeaderMenu());
		}
		if (DEBUG&&DEBUGLEVEL&1) debug('End method NagVisFrontend::getHeaderMenu()');
	}
	
	/**
	 * Adds the map to the page
	 *
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	function getMap() {
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method NagVisFrontend::getMap()');
		$this->addBodyLines(Array('<div class="map">'));
		$this->MAP = new NagVisMap($this->MAINCFG,$this->MAPCFG,$this->LANG,$this->BACKEND);
		$this->addBodyLines($this->MAP->parseMap());
		$this->addBodyLines(Array('</div>'));
		$this->addBodyLines(Array('<div id="overDiv" style="position:absolute; visibility:hidden; z-index:1000;"></div>'));
		if (DEBUG&&DEBUGLEVEL&1) debug('End method NagVisFrontend::getMap()');
	}
	
	/**
	 * Adds the user messages to the page
	 *
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	function getMessages() {
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method NagVisFrontend::getMessages()');
		$this->addBodyLines($this->getUserMessages());
		if (DEBUG&&DEBUGLEVEL&1) debug('End method NagVisFrontend::getMessages()');
	}
	
	/**
	 * Reads the Configuration-File for the Header-Menu.
	 *
	 * @return	Array	Array of the Header Menu
	 * @author Michael Lübben <michael_luebben@web.de>
	 */
	function readHeaderMenu() {
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method NagVisFrontend::readHeaderMenu()');
		if($this->checkHeaderConfigReadable(1)) {
			$Menu = file($this->MAINCFG->getValue('paths', 'cfg').$this->MAINCFG->getValue('includes', 'header'));
			$a = 0;
			$b = 0;
			
			while (isset($Menu[$a]) && $Menu[$a] != '') {
				if (!ereg("#",$Menu[$a]) && trim($Menu[$a]) != '') {
					$entry = explode(';',$Menu[$a]);
					$link[$b]['entry'] = $entry[0];
					$link[$b]['url'] = $entry[1];
					$b++;
				}
				$a++;
			}
			if (DEBUG&&DEBUGLEVEL&1) debug('End method NagVisFrontend::readHeaderMenu(): Array(...)');
			return $link;
		} else {
			if (DEBUG&&DEBUGLEVEL&1) debug('End method NagVisFrontend::readHeaderMenu(): FALSE');
			return FALSE;
		}
	}
	
	/**
	 * Creates the Header-Menu
	 *
	 * @return	Array 	Html
	 * @author 	Michael Luebben <michael_luebben@web.de>
	 * @author 	Andreas Husch <michael_luebben@web.de>
     */
	function makeHeaderMenu() {
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method NagVisFrontend::makeHeaderMenu()');
		$ret = Array();
		
		$Menu = $this->readHeaderMenu();
		
		$x = 0;
		$ret[] = '<div class="header"><table width="100%" border="0" class="headerMenu">';
		while(isset($Menu[$x]['entry']) && $Menu[$x]['entry'] != '') {
			$this->site[] = '<tr valign="bottom">';
			for($d = 1;$d<=$this->MAINCFG->getValue('global', 'headercount');$d++) {
				if(isset($Menu[$x]['entry']) && $Menu[$x]['entry'] != '') {
					$ret[] = '<td width="13"><img src="'.$this->MAINCFG->getValue('paths', 'htmlimages').'internal/greendot.gif" width="13" height="14" name="'.$Menu[$x]['entry'].'_'.$x.'"></td>';
					$ret[] = '<td nowrap><a href="'.str_replace("\n",'',$Menu[$x]['url']).'" onMouseOver="switchdot(\''.$Menu[$x]['entry'].'_'.$x.'\',1)" onMouseOut="switchdot(\''.$Menu[$x]['entry'].'_'.$x.'\',0)" class="NavBarItem">'.$Menu[$x]['entry'].'</a></td>';
				}
				$x++;
			}
			$ret[] = '</tr>';
		}
		$ret[] = '</table></div>';
		
		if (DEBUG&&DEBUGLEVEL&1) debug('End method NagVisFrontend::makeHeaderMenu(): HTML');
		return $ret;
	}
	
	/**
	 * Checks for readable header config file
	 *
	 * @param 	Boolean	$printErr
	 * @return	Boolean	Is Check Successful?
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
     */
	function checkHeaderConfigReadable($printErr) {
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method NagVisFrontend::checkHeaderConfigReadable('.$printErr.')');
		if(file_exists($this->MAINCFG->getValue('paths', 'cfg').$this->MAINCFG->getValue('includes', 'header')) && is_readable($this->MAINCFG->getValue('paths', 'cfg').$this->MAINCFG->getValue('includes', 'header'))) {
			if (DEBUG&&DEBUGLEVEL&1) debug('End method NagVisFrontend::checkHeaderConfigReadable(): TRUE');
			return TRUE;
		} else {
			if($printErr == 1) {
				$FRONTEND = new GlobalPage($this->MAINCFG,Array('languageRoot'=>'global:global'));
		    	$FRONTEND->messageToUser('WARNING','headerConfigNotReadable','CFGPATH~'.$this->MAINCFG->getValue('paths', 'cfg').$this->MAINCFG->getValue('includes', 'header'));
			}
			if (DEBUG&&DEBUGLEVEL&1) debug('End method NagVisFrontend::checkHeaderConfigReadable(): FALSE');
			return FALSE;
		}
	}
    
    /**
     * Gets the Next map to rotate to, if enabled
     * If Next map is in [ ], it will be an absolute url
     *
     * @return      String  URL to rotate to
     * @author      Lars Michelsen <lars@vertical-visions.de>
     */
    function getNextRotate() {
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method NagVisFrontend::getNextRotate()');
	    $maps = explode(',', $this->MAINCFG->getValue('global', 'maps'));
	    if(($this->MAINCFG->getValue('global', 'rotatemaps') == '1' && (!isset($_GET['rotate']) || isset($_GET['rotate']) && $_GET['rotate'] != '0')) || (isset($_GET['rotate']) && $_GET['rotate'] == '1')) {
			if(isset($_GET['url']) && $_GET['url'] != '') {
				$currentMap = '['.$_GET['url'].']';
			} else {
				$currentMap = $this->MAPCFG->getName();
			}
			
			// get position of actual map in the array
			$index = array_search($currentMap,$maps);
			if (($index + 1) >= sizeof($maps)) {
				// if end of array reached, go to the beginning...
				$index = 0;
			} else {
				$index++;
			}
			
			$nextMap = $maps[$index];
			
			if(preg_match("/^\[(.+)\]$/",$nextMap,$arrRet)) {
				if (DEBUG&&DEBUGLEVEL&1) debug('End method NagVisFrontend::getNextRotate(): URL=index.php?rotate=1&url='.$arrRet[1]);
				return ' URL=index.php?rotate=1&url='.$arrRet[1];
			} else {
				if (DEBUG&&DEBUGLEVEL&1) debug('End method NagVisFrontend::getNextRotate(): URL=index.php?map='.$nextMap.'&rotate=1');
				return ' URL=index.php?map='.$nextMap.'&rotate=1';
			}
		}
    } 
}
?>