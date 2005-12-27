<?php

include("./includes/classes/class.NagVis.php");

class checkit extends frontend {
	function check_dummy() {
		echo "create some checks!";
	}

        function check_config() {
                global $rotateUrl;
                $nagvis = new frontend;
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

        function check_map_isreadable() {
                global $cfgFolder;
                global $map;
                $nagvis = new frontend;
                if(!is_readable($cfgFolder.$map.".cfg")) {
					$FRONTEND->openSite($rotateUrl);
					$FRONTEND->messageBox("2", "MAP~".$cfgFolder.$map.".cfg");
					$FRONTEND->closeSite();
					$FRONTEND->printSite();
					exit;
                }
        }

        function check_user() {
                global $user;
                $nagvis = new frontend;
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

        function check_rotate() {
                $FRONTEND = new frontend;
                // Pruefen ob Rotate-Modus eingeschaltet ist.
                if($RotateMaps == "1") {
                        $mapNumber = $FRONTEND->mapCount($map);
                        $map = $maps[$mapNumber];
                        $rotateUrl = " URL=index.php?map=".$map;
                }
        }

        function check_gd() {
                $FRONTEND = new frontend;
                // Ohne GD Lib geht nix
                if (!extension_loaded('gd')) {
                        $FRONTEND->openSite($rotateUrl);
                        $FRONTEND->messageBox("15", "");
                        $FRONTEND->closeSite();
                        $FRONTEND->printSite();
                        exit;
                }
        }

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

        function check_mapimg() {
                global $mapFolder;
                global $map_image;
                $FRONTEND = new frontend;
                //Prüfen ob die Map vorhanden ist!
                if(!file_exists($mapFolder.$map_image)) {
                        $FRONTEND->openSite($rotateUrl);
                        $FRONTEND->messageBox("3", "MAPPATH~".$mapFolder.$map_image);
                        $FRONTEND->closeSite();
                        $FRONTEND->printSite();
                        exit;
                }
        }
	
	function check_validuser() {
		global $user;
		if(isset($_SERVER['PHP_AUTH_USER'])) {
			$user = $_SERVER['PHP_AUTH_USER'];
		} elseif(isset($_SERVER['REMOTE_USER'])) {
			$user = $_SERVER['REMOTE_USER'];
		} else {
			$FRONTEND->openSite("");
			$FRONTEND->messageBox("14", "");
			$FRONTEND->closeSite();
			$FRONTEND->printSite();
			exit;
		}
	}

        function check_permissions() {
                global $user;
                global $allowed_users;
                $FRONTEND = new frontend;
                //Prüfen ob der User die Berechtigung besitzt die Map zu sehen!
                if(isset($allowed_users) && !in_array('EVERYONE', $allowed_users) && !in_array($user,$allowed_users)) {
                        $FRONTEND->openSite($rotateUrl);
                        $FRONTEND->messageBox("4", "USER~".$user);
                        $FRONTEND->closeSite();
                        $FRONTEND->printSite();
                        exit;
                }
        }

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
