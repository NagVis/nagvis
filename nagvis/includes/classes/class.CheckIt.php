<?php
/**
* This Class makes some checks
*/

include("./includes/classes/class.NagVis.php");

class checkit extends frontend {
	var $CONFIG;
	
	/**
	* Constructor
	*
	* @param config $CONFIG
	*
	* @author Lars Michelsen <larsi@nagios-wiki.de>
	*/
	function checkit($CONFIG) {
		$this->CONFIG = $CONFIG;
		parent::frontend($this->CONFIG);
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
	*/
    function check_map_isreadable() {
        global $map;
        
        if(file_exists($this->CONFIG->getValue('paths', 'mapcfg'))) {
        	if(is_readable($this->CONFIG->getValue('paths', 'mapcfg'))) {
        		if(file_exists($this->CONFIG->getValue('paths', 'mapcfg').$map.".cfg")) {
			        if(is_readable($this->CONFIG->getValue('paths', 'mapcfg').$map.".cfg")) {
						return TRUE;
		        	} else {
		        		$this->openSite($rotateUrl);
						$this->messageBox("2", "MAP~".$this->CONFIG->getValue('paths', 'mapcfg').$map.".cfg");
						$this->closeSite();
						$this->printSite();
						
						exit;
		        	}
				} else {
					echo "Map-Config (".$this->CONFIG->getValue('paths', 'mapcfg').$map.".cfg) doesn't exists";
					exit;
				}
			} else {
				echo "Directory (".$this->CONFIG->getValue('paths', 'mapcfg').") isn't readable";
				exit;
			}
		} else {
			echo "Directory (".$this->CONFIG->getValue('paths', 'mapcfg').") doesn't exists";
			exit;
		}
    }
	
	/**
        * Check if the Map Configuratin File wich is edited is writable
        *
        * @author Andreas Husch
	* CAUTION: WUI does acually not use the method here! There is a copy of this in the WUI code!
        */
    function check_map_iswritable() {
        global $map;
        
        if(!is_writable($this->CONFIG->getValue('paths', 'mapcfg').$map.".cfg")) {
	        $this->openSite($rotateUrl);
	        $this->messageBox("17", "MAP~".$this->CONFIG->getValue('paths', 'mapcfg').$map.".cfg");
	        $this->closeSite();
	        $this->printSite();
	        
	        exit;
        }
    }

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
	*/
    function check_rotate() {
        global $map;
        
        $maps = explode(",", $this->CONFIG->getValue('global', 'maps'));
        if($this->CONFIG->getValue('global', 'rotatemaps') == "1") {
            $Index = array_search($map,$maps);
			if (($Index + 1) >= sizeof($maps)) {
				$Index = -1;
			}
			
			$Index++;
			
            $map = $maps[$Index];
            $rotateUrl = " URL=index.php?map=".$map;
        }
        return($rotateUrl);
    }

	/**
	* Check is GD-Libs installed, when GD-Libs are enabled.
	*
	* @author FIXME!
	* @author Michael Luebben <michael_luebben@web.de>
	*/
    function check_gd() {
		if ($this->CONFIG->getValue('global', 'usegdlibs') == "1") {
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
	*/
    function check_cgipath() {
        if(!file_exists($this->CONFIG->getValue('backend_html', 'cgi'))) {
            $this->openSite($rotateUrl);
            $this->messageBox("0", "STATUSCGI~".$this->CONFIG->getValue('backend_html', 'cgi'));
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
        
        if(!file_exists($this->CONFIG->getValue('paths', 'map').$map_image)) {
            $this->openSite($rotateUrl);
            $this->messageBox("3", "MAPPATH~".$this->CONFIG->getValue('paths', 'map').$map_image);
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
        if(!is_readable($this->CONFIG->getValue('paths', 'cfg').'languages/'.$this->CONFIG->getValue('global', 'language').'.txt')) {
            $this->openSite($rotateUrl);
            $this->messageBox("18", "LANGFILE~".$this->CONFIG->getValue('paths', 'cfg').'languages/wui_'.$this->CONFIG->getValue('global', 'language').'.txt');
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
        if(!is_readable($this->CONFIG->getValue('paths', 'cfg').'languages/wui_'.$this->CONFIG->getValue('global', 'language').'.txt')) {
            $this->openSite($rotateUrl);
            $this->messageBox("18", "LANGFILE~".$this->CONFIG->getValue('paths', 'cfg').'languages/wui_'.$this->CONFIG->getValue('global', 'language').'.txt');
            $this->closeSite();
            $this->printSite();
            
            exit;
	    }
	}
}
?>
