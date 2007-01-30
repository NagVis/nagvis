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
	 * @author 	Lars Michelsen <larsi@nagios-wiki.de>
	 */
	function NagVisFrontend(&$MAINCFG,&$MAPCFG,&$BACKEND) {
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
	}
	
	/**
	 * If enabled, the header menu is added to the page
	 *
	 * @author	Lars Michelsen <larsi@nagios-wiki.de>
	 */
	function getHeaderMenu() {
		if($this->MAINCFG->getValue('global', 'displayheader') == "1") {
			$this->addBodyLines($this->makeHeaderMenu());
		}	
	}
	
	/**
	 * Adds the map to the page
	 *
	 * @author	Lars Michelsen <larsi@nagios-wiki.de>
	 */
	function getMap() {
		$this->addBodyLines(Array('<div class="map">'));
		$this->MAP = new NagVisMap($this->MAINCFG,$this->MAPCFG,$this->LANG,$this->BACKEND);
		$this->addBodyLines($this->MAP->parseMap());
		$this->addBodyLines(Array('</div>'));
	}
	
	/**
	 * Adds the user messages to the page
	 *
	 * @author	Lars Michelsen <larsi@nagios-wiki.de>
	 */
	function getMessages() {
		$this->addBodyLines($this->getUserMessages());	
	}
	
	/**
	 * Reads the Configuration-File for the Header-Menu.
	 *
	 * @return	Array	Array of the Header Menu
	 * @author Michael Lübben <michael_luebben@web.de>
	 */
	function readHeaderMenu() {
		if($this->checkHeaderConfigReadable(1)) {
			$Menu = file($this->MAINCFG->getValue('paths', 'cfg').$this->MAINCFG->getValue('includes', 'header'));
			$a = 0;
			$b = 0;
			
			while (isset($Menu[$a]) && $Menu[$a] != "") {
				if (!ereg("#",$Menu[$a]) && trim($Menu[$a]) != "") {
					$entry = explode(";",$Menu[$a]);
					$link[$b]['entry'] = $entry[0];
					$link[$b]['url'] = $entry[1];
					$b++;
				}
				$a++;
			}
			return $link;
		} else {
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
		$ret = Array();
		
		$Menu = $this->readHeaderMenu();
		
		$x="0";
		$ret[] = '<div class="header">';
		$ret[] = '<table width="100%" border="0" class="headerMenu">';
		while(isset($Menu[$x]['entry']) && $Menu[$x]['entry'] != "") {
			$this->site[] = '<tr valign="bottom">';
			for($d="1";$d<=$this->MAINCFG->getValue('global', 'headercount');$d++) {
				if(isset($Menu[$x]['entry']) && $Menu[$x]['entry'] != '') {
					$ret[] = '<td width="13">';
					$ret[] = '<img src="'.$this->MAINCFG->getValue('paths', 'htmlimages').'internal/greendot.gif" width="13" height="14" name="'.$Menu[$x]['entry'].'_'.$x.'">';
					$ret[] = '</td>';
					$ret[] = '<td nowrap>';
					$ret[] = '<a href='.$Menu[$x]['url'].' onMouseOver="switchdot(\''.$Menu[$x]['entry'].'_'.$x.'\',1)" onMouseOut="switchdot(\''.$Menu[$x]['entry'].'_'.$x.'\',0)" class="NavBarItem">'.$Menu[$x]['entry'].'</a>';
					$ret[] = '</td>';
				}
				$x++;
			}
			$ret[] = '</tr>';
		}
		$ret[] = '</table></div>';
		
		return $ret;
	}
	
	/**
	 * Checks for readable header config file
	 *
	 * @param 	Boolean	$printErr
	 * @return	Boolean	Is Check Successful?
	 * @author 	Lars Michelsen <larsi@nagios-wiki.de>
     */
	function checkHeaderConfigReadable($printErr) {
		if(file_exists($this->MAINCFG->getValue('paths', 'cfg').$this->MAINCFG->getValue('includes', 'header')) && is_readable($this->MAINCFG->getValue('paths', 'cfg').$this->MAINCFG->getValue('includes', 'header'))) {
			return TRUE;
		} else {
			if($printErr == 1) {
				$FRONTEND = new GlobalPage($this->MAINCFG,Array('languageRoot'=>'global:global'));
		    	$FRONTEND->messageToUser('WARNING','headerConfigNotReadable','CFGPATH~'.$this->MAINCFG->getValue('paths', 'cfg').$this->MAINCFG->getValue('includes', 'header'));
			}
			return FALSE;
		}
	}
	
	/**
	 * Gets the Next map to rotate to, if enabled
	 * If Next map is in [ ], it will be an absolute url
	 *
	 * @return	String	URL to rotate to
	 * @author 	Michael Luebben <michael_luebben@web.de>
	 * @author 	Lars Michelsen <larsi@nagios-wiki.de>
	 */
	function getNextRotate() {
        $maps = explode(",", $this->MAINCFG->getValue('global', 'maps'));
        if($this->MAINCFG->getValue('global', 'rotatemaps') == "1") {
        	// get position of actual map in the array
            $index = array_search($this->MAPCFG->getName(),$maps);
			if (($index + 1) >= sizeof($maps)) {
            	// if end of array reached, go to the beginning...
				$index = 0;
			} else {
				$index++;
			}
            $map = $maps[$index];
            
            if(preg_match("/^[(.?)]$/",$map)) {
            	return " URL=".$map;
            } else {
            	return " URL=index.php?map=".$map;
        	}
            
        	
        }
    }
}
?>