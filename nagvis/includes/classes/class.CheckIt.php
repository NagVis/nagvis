<?php
/**
* This Class makes some checks
*/

include("./includes/classes/class.NagVis.php");

class checkit extends frontend {
	var $MAINCFG;
	var $MAPCFG;
	
	/**
	* Constructor
	*
	* @param config $MAINCFG
	* @param config $MAPCFG
	*
	* @author Lars Michelsen <larsi@nagios-wiki.de>
	*/
	function checkit($MAINCFG,$MAPCFG) {
		$this->MAINCFG = $MAINCFG;
		$this->MAPCFG = $MAPCFG;
		parent::frontend($this->MAINCFG,$this->MAPCFG);
	}
	
	/**
	* Dummy-Check
	*
	* @author ML: FIXME!
	*/
	function check_dummy() {
		echo "create some checks!";
	}
	
	/**
	* Check the Configuration-File 'etc/config.inc.php'.
	*
	* @author FIXME!
	* @author Michael Luebben <michael_luebben@web.de>
	*/
    function check_config() {
        global $rotateUrl;
        global $user;
        
        if(!is_writable('etc/config.ini')) {
                $this->openSite($rotateUrl);
                $this->messageBox("17", "USER~".$user);
                $this->closeSite();
                $this->printSite();
                
                exit;
        }
        else {
        	//
        }
    }

	/**
	* Check the Configuration-File from a map.
	*
	* @author FIXME!
	*
	* DEPRECATED: Check should be made in MapCfg in the future
	*/
    /*function check_map_isreadable() {
        global $map;
        
        if(file_exists($this->MAINCFG->getValue('paths', 'mapcfg'))) {
        	if(is_readable($this->MAINCFG->getValue('paths', 'mapcfg'))) {
        		if(file_exists($this->MAINCFG->getValue('paths', 'mapcfg').$map.".cfg")) {
			        if(is_readable($this->MAINCFG->getValue('paths', 'mapcfg').$map.".cfg")) {
						return TRUE;
		        	} else {
		        		$this->openSite($rotateUrl);
						$this->messageBox("2", "MAP~".$this->MAINCFG->getValue('paths', 'mapcfg').$map.".cfg");
						$this->closeSite();
						$this->printSite();
						
						exit;
		        	}
				} else {
					echo "Map-Config (".$this->MAINCFG->getValue('paths', 'mapcfg').$map.".cfg) doesn't exists";
					exit;
				}
			} else {
				echo "Directory (".$this->MAINCFG->getValue('paths', 'mapcfg').") isn't readable";
				exit;
			}
		} else {
			echo "Directory (".$this->MAINCFG->getValue('paths', 'mapcfg').") doesn't exists";
			exit;
		}
    }*/
	
	/**
    * Check if the Map Configuratin File wich is edited is writable
    *
    * @author Andreas Husch
	* CAUTION: WUI does acually not use the method here! There is a copy of this in the WUI code!
	*
	* DEPRECATED: Check should be made in MapCfg in the future
    */
    /*function check_map_iswritable() {
        global $map;
        
        if(!is_writable($this->MAINCFG->getValue('paths', 'mapcfg').$map.".cfg")) {
	        $this->openSite($rotateUrl);
	        $this->messageBox("17", "MAP~".$this->MAINCFG->getValue('paths', 'mapcfg').$map.".cfg");
	        $this->closeSite();
	        $this->printSite();
	        
	        exit;
        }
    }*/

	/**
	* Check the logged in User.
	*
	* @author FIXME!
	*/
    function check_user() {
        global $user;
        
        if(isset($_SERVER['PHP_AUTH_USER'])) {
        	$user = $_SERVER['PHP_AUTH_USER'];
        }
        elseif(isset($_SERVER['REMOTE_USER'])) {
        	$user = $_SERVER['REMOTE_USER'];
        }
        else {
            $this->openSite($rotateUrl);
            $this->messageBox("14", "");
            $this->closeSite();
            $this->printSite();
            
            exit;
        }
    }
		
	/**
	* Check is Rotate-Mode enable and create this
	*
	* @author Michael Luebben <michael_luebben@web.de>
	* @author Lars Michelsen <larsi@nagios-wiki.de>
	*/
    function check_rotate() {
        $maps = explode(",", $this->MAINCFG->getValue('global', 'maps'));
        if($this->MAINCFG->getValue('global', 'rotatemaps') == "1") {
            $Index = array_search($this->MAPCFG->getName(),$maps);
			if (($Index + 1) >= sizeof($maps)) {
            	// if end of array reached, go to the beginning...
				$Index = 0;
			} else {
				$Index++;
			}
            $map = $maps[$Index];
            
        	return " URL=index.php?map=".$map;
        }
    }

	/**
	* Check is GD-Libs installed, when GD-Libs are enabled.
	*
	* @author FIXME!
	* @author Michael Luebben <michael_luebben@web.de>
	*/
    function check_gd() {
		if ($this->MAINCFG->getValue('global', 'usegdlibs') == "1") {
        	if(!extension_loaded('gd')) {
                $this->openSite($rotateUrl);
                $this->messageBox("15", "");
                $this->closeSite();
                $this->printSite();
                
                exit;
            }
        }
    }
		
	/**
	* Check is the CGI-Path exist.
	*
	* @author FIXME!
	* @author Michael Luebben <michael_luebben@web.de>
	*
	* DEPRECATED: should be checked in html backend
	*/
    function check_cgipath() {
        if(!file_exists($this->MAINCFG->getValue('backend_html', 'cgi'))) {
            $this->openSite($rotateUrl);
            $this->messageBox("0", "STATUSCGI~".$this->MAINCFG->getValue('backend_html', 'cgi'));
            $this->closeSite();
            $this->printSite();
            
            exit;
        }
    }

	/**
	* Check the Image for a map.
	*
	* @author FIXME!
	* @author Michael Luebben <michael_luebben@web.de>
	*/
    function check_mapimg() {
        global $map_image;
        
        if(!file_exists($this->MAINCFG->getValue('paths', 'map').$map_image)) {
            $this->openSite($rotateUrl);
            $this->messageBox("3", "MAPPATH~".$this->MAINCFG->getValue('paths', 'map').$map_image);
            $this->closeSite();
            $this->printSite();
            
            exit;
        }
    }

	/**
	* Check the permission from a loggin User (to view the Map).
	*
	* @author FIXME!
	* @author Michael Luebben <michael_luebben@web.de>
	*/
    function check_permissions() {
        global $user;
        global $allowed_users;
        
        if(isset($allowed_users) && !in_array('EVERYONE', $allowed_users) && !in_array($user,$allowed_users)) {
            $this->openSite($rotateUrl);
            $this->messageBox("4", "USER~".$user);
            $this->closeSite();
            $this->printSite();
            
            exit;
        }
    }

	/**
	* Check is the file 'wui/wui.function.inc.bash' executable
	*
	* @author FIXME!
	*/
    function check_wuibash() {
        if(!is_executable('wui/wui.function.inc.bash')) {
            $this->openSite($rotateUrl);
            $this->messageBox("16", "");
            $this->closeSite();
            $this->printSite();
            
            exit;
        }
    }
	
	/**
	* Check is the Language-File readable.
	*
	* @author FIXME!
	*/
    function check_langfile() {
        if(!is_readable($this->MAINCFG->getValue('paths', 'cfg').'languages/'.$this->MAINCFG->getValue('global', 'language').'.txt')) {
            $this->openSite($rotateUrl);
            $this->messageBox("18", "LANGFILE~".$this->MAINCFG->getValue('paths', 'cfg').'languages/wui_'.$this->MAINCFG->getValue('global', 'language').'.txt');
            $this->closeSite();
            $this->printSite();
            
            exit;
        }
    }

	/**
	* Check is the Wui-Language-File readable.
	*
	* @author FIXME!
	*/
	function check_wuilangfile() {
        if(!is_readable($this->MAINCFG->getValue('paths', 'cfg').'languages/wui_'.$this->MAINCFG->getValue('global', 'language').'.txt')) {
            $this->openSite($rotateUrl);
            $this->messageBox("18", "LANGFILE~".$this->MAINCFG->getValue('paths', 'cfg').'languages/wui_'.$this->MAINCFG->getValue('global', 'language').'.txt');
            $this->closeSite();
            $this->printSite();
            
            exit;
	    }
	}
}
?>
