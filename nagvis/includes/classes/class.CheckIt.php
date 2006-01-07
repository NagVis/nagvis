<?php
/**
* This Class makes some checks
*/


include("./includes/classes/class.NagVis.php");

class checkit extends frontend {
	
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
                $FRONTEND = new frontend;
                if(!is_writable('etc/config.inc.php')) {
                        $FRONTEND->openSite($rotateUrl);
                        $FRONTEND->messageBox("17", "USER~".$user);
                        $FRONTEND->closeSite();
                        $FRONTEND->printSite();
                        exit;
                }
                else {
                        include ("./etc/config.inc.php");
                }
        }

		/**
		* Check the Configuration-File from a map.
		*
		* @author FIXME!
		*/
        function check_map_isreadable() {
                global $cfgFolder;
                global $map;
                $FRONTEND = new frontend;
                if(!is_readable($cfgFolder.$map.".cfg")) {
					$FRONTEND->openSite($rotateUrl);
					$FRONTEND->messageBox("2", "MAP~".$cfgFolder.$map.".cfg");
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
                $FRONTEND = new frontend;
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
                $FRONTEND = new frontend;
                global $$RotateMaps;
                if($RotateMaps == "1") {
                        $mapNumber = $FRONTEND->mapCount($map);
                        $map = $maps[$mapNumber];
                        $rotateUrl = " URL=index.php?map=".$map;
                }
        }

		/**
		* Check is Rotate-Mode enable and create this
		*
		* @author FIXME!
		* @author Michael Luebben <michael_luebben@web.de>
		*/
        function check_gd() {
                $FRONTEND = new frontend;
                if (!extension_loaded('gd')) {
                        $FRONTEND->openSite($rotateUrl);
                        $FRONTEND->messageBox("15", "");
                        $FRONTEND->closeSite();
                        $FRONTEND->printSite();
                        exit;
                }
        }
		
		/**
		* Check is Rotate-Mode enable and create this.
		*
		* @author FIXME!
		* @author Michael Luebben <michael_luebben@web.de>
		*/
        function check_cgipath() {
                global $CgiPath;
                $FRONTEND = new frontend;
                if(!file_exists($CgiPath)) {
                        $FRONTEND->openSite($rotateUrl);
                        $FRONTEND->messageBox("0", "STATUSCGI~$CgiPath");
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
                global $mapFolder;
                global $map_image;
                $FRONTEND = new frontend;
                if(!file_exists($mapFolder.$map_image)) {
                        $FRONTEND->openSite($rotateUrl);
                        $FRONTEND->messageBox("3", "MAPPATH~".$mapFolder.$map_image);
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
                $FRONTEND = new frontend;
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
                $FRONTEND = new frontend;
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
                global $Language;
                $FRONTEND = new frontend;
                if(!is_readable('etc/languages/'.$Language.'.txt')) {
                        $FRONTEND->openSite($rotateUrl);
                        $FRONTEND->messageBox("18", "LANGFILE~".'etc/languages/wui_'.$Language.'.txt');
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
                global $Language;
                $FRONTEND = new frontend;
                if(!is_readable('etc/languages/wui_'.$Language.'.txt')) {
                $FRONTEND->openSite($rotateUrl);
                $FRONTEND->messageBox("18", "LANGFILE~".'etc/languages/wui_'.$Language.'.txt');
                $FRONTEND->closeSite();
                $FRONTEND->printSite();
                exit;
        }
}

}
?>
