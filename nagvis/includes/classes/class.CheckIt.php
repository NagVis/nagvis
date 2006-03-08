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
        $FRONTEND = new frontend($this->CONFIG);
        if(!is_writable('etc/config.ini')) {
                $FRONTEND->openSite($rotateUrl);
                $FRONTEND->messageBox("17", "USER~".$user);
                $FRONTEND->closeSite();
                $FRONTEND->printSite();
                exit;
        }
        else {
                //include ("./etc/config.inc.php");
        }
    }

	/**
	* Check the Configuration-File from a map.
	*
	* @author FIXME!
	*/
    function check_map_isreadable() {
        global $map;
        $FRONTEND = new frontend($this->CONFIG);
        if(!is_readable($this->CONFIG->getValue('paths', 'mapcfg').$map.".cfg")) {
			$FRONTEND->openSite($rotateUrl);
			$FRONTEND->messageBox("2", "MAP~".$this->CONFIG->getValue('paths', 'mapcfg').$map.".cfg");
			$FRONTEND->closeSite();
			$FRONTEND->printSite();
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
        $FRONTEND = new frontend($this->CONFIG);
        if(!is_writable($this->CONFIG->getValue('paths', 'mapcfg').$map.".cfg")) {
                        $FRONTEND->openSite($rotateUrl);
                        $FRONTEND->messageBox("17", "MAP~".$this->CONFIG->getValue('paths', 'mapcfg').$map.".cfg");
                        $FRONTEND->closeSite();
                        $FRONTEND->printSite();
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
        $FRONTEND = new frontend($this->CONFIG);
        if(isset($_SERVER['PHP_AUTH_USER'])) {
                $user = $_SERVER['PHP_AUTH_USER'];
        }
        elseif(isset($_SERVER['REMOTE_USER'])) {
                $user = $_SERVER['REMOTE_USER'];
        }
        else {
                $FRONTEND->openSite($rotateUrl);
                $FRONTEND->messageBox("14", "");
                $FRONTEND->closeSite();
                $FRONTEND->printSite();
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
        	$FRONTEND = new frontend($this->CONFIG);
        	if (!extension_loaded('gd')) {
                $FRONTEND->openSite($rotateUrl);
                $FRONTEND->messageBox("15", "");
                $FRONTEND->closeSite();
                $FRONTEND->printSite();
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
        $FRONTEND = new frontend($this->CONFIG);
        if(!file_exists($this->CONFIG->getValue('backend_html', 'cgi'))) {
                $FRONTEND->openSite($rotateUrl);
                $FRONTEND->messageBox("0", "STATUSCGI~".$this->CONFIG->getValue('backend_html', 'cgi'));
                $FRONTEND->closeSite();
                $FRONTEND->printSite();
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
        $FRONTEND = new frontend($this->CONFIG);
        if(!file_exists($this->CONFIG->getValue('paths', 'map').$map_image)) {
                $FRONTEND->openSite($rotateUrl);
                $FRONTEND->messageBox("3", "MAPPATH~".$this->CONFIG->getValue('paths', 'map').$map_image);
                $FRONTEND->closeSite();
                $FRONTEND->printSite();
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
        $FRONTEND = new frontend($this->CONFIG);
        if(isset($allowed_users) && !in_array('EVERYONE', $allowed_users) && !in_array($user,$allowed_users)) {
            $FRONTEND->openSite($rotateUrl);
            $FRONTEND->messageBox("4", "USER~".$user);
            $FRONTEND->closeSite();
            $FRONTEND->printSite();
            exit;
        }
    }

	/**
	* Check is the file 'wui/wui.function.inc.bash' executable
	*
	* @author FIXME!
	*/
    function check_wuibash() {
        $FRONTEND = new frontend($this->CONFIG);
        if(!is_executable('wui/wui.function.inc.bash')) {
            $FRONTEND->openSite($rotateUrl);
            $FRONTEND->messageBox("16", "");
            $FRONTEND->closeSite();
            $FRONTEND->printSite();
            exit;
        }
    }
	
	/**
	* Check is the Language-File readable.
	*
	* @author FIXME!
	*/
    function check_langfile() {
        $FRONTEND = new frontend($this->CONFIG);
        if(!is_readable($this->CONFIG->getValue('paths', 'cfg').'languages/'.$this->CONFIG->getValue('global', 'language').'.txt')) {
            $FRONTEND->openSite($rotateUrl);
            $FRONTEND->messageBox("18", "LANGFILE~".$this->CONFIG->getValue('paths', 'cfg').'languages/wui_'.$this->CONFIG->getValue('global', 'language').'.txt');
            $FRONTEND->closeSite();
            $FRONTEND->printSite();
            exit;
        }
    }

	/**
	* Check is the Wui-Language-File readable.
	*
	* @author FIXME!
	*/
	function check_wuilangfile() {
        $FRONTEND = new frontend($this->CONFIG);
        if(!is_readable($this->CONFIG->getValue('paths', 'cfg').'languages/wui_'.$this->CONFIG->getValue('global', 'language').'.txt')) {
            $FRONTEND->openSite($rotateUrl);
            $FRONTEND->messageBox("18", "LANGFILE~".$this->CONFIG->getValue('paths', 'cfg').'languages/wui_'.$this->CONFIG->getValue('global', 'language').'.txt');
            $FRONTEND->closeSite();
            $FRONTEND->printSite();
            exit;
	    }
	}
}
?>
