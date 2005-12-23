<?php

include("./includes/classes/class.NagVis.php");

class checkit extends NagVis {
	function check_dummy() {
		echo "create some checks!";
	}

        function check_config() {
                global $rotateUrl;
                $nagvis = new NagVis;
                if(!is_writable('etc/config.inc.php')) {
                        $nagvis->openSite($rotateUrl);
                        $nagvis->messageBox("17", "USER~".$user);
                        $nagvis->closeSite();
                        $nagvis->printSite();
                        exit;
                }
                else {
                        include ("./etc/config.inc.php");
                }
        }

        function check_map_isreadable() {
                global $cfgFolder;
                global $map;
                $nagvis = new NagVis;
                if(!is_readable($cfgFolder.$map.".cfg")) {
					$nagvis->openSite($rotateUrl);
					$nagvis->messageBox("2", "MAP~".$cfgFolder.$map.".cfg");
					$nagvis->closeSite();
					$nagvis->printSite();
					exit;
                }
        }

        function check_user() {
                global $user;
                $nagvis = new NagVis;
                if(isset($_SERVER['PHP_AUTH_USER'])) {
                        $user = $_SERVER['PHP_AUTH_USER'];
                }
                elseif(isset($_SERVER['REMOTE_USER'])) {
                        $user = $_SERVER['REMOTE_USER'];
                }
                else {
                        $nagvis->openSite($rotateUrl);
                        $nagvis->messageBox("14", "");
                        $nagvis->closeSite();
                        $nagvis->printSite();
                        exit;
                }
        }

        function check_rotate() {
                $nagvis = new NagVis;
                // Pruefen ob Rotate-Modus eingeschaltet ist.
                if($RotateMaps == "1") {
                        $mapNumber = $nagvis->mapCount($map);
                        $map = $maps[$mapNumber];
                        $rotateUrl = " URL=index.php?map=".$map;
                }
        }

        function check_gd() {
                $nagvis = new NagVis;
                // Ohne GD Lib geht nix
                if (!extension_loaded('gd')) {
                        $nagvis->openSite($rotateUrl);
                        $nagvis->messageBox("15", "");
                        $nagvis->closeSite();
                        $nagvis->printSite();
                        exit;
                }
        }

        function check_cgipath() {
                global $CgiPath;
                $nagvis = new NagVis;
                if(!file_exists($CgiPath)) {
                        $nagvis->openSite($rotateUrl);
                        $nagvis->messageBox("0", "STATUSCGI~$CgiPath");
                        $nagvis->closeSite();
                        $nagvis->printSite();
                        exit;
                }
        }

        function check_mapimg() {
                global $mapFolder;
                global $map_image;
                $nagvis = new NagVis;
                //Prüfen ob die Map vorhanden ist!
                if(!file_exists($mapFolder.$map_image)) {
                        $nagvis->openSite($rotateUrl);
                        $nagvis->messageBox("3", "MAPPATH~".$mapFolder.$map_image);
                        $nagvis->closeSite();
                        $nagvis->printSite();
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
			$nagvis->openSite("");
			$nagvis->messageBox("14", "");
			$nagvis->closeSite();
			$nagvis->printSite();
			exit;
		}
	}

        function check_permissions() {
                global $user;
                global $allowed_users;
                $nagvis = new NagVis;
                //Prüfen ob der User die Berechtigung besitzt die Map zu sehen!
                if(isset($allowed_users) && !in_array('EVERYONE', $allowed_users) && !in_array($user,$allowed_users)) {
                        $nagvis->openSite($rotateUrl);
                        $nagvis->messageBox("4", "USER~".$user);
                        $nagvis->closeSite();
                        $nagvis->printSite();
                        exit;
                }
        }

        function check_wuibash() {
                $nagvis = new NagVis;
                if(!is_executable('wui/wui.function.inc.bash')) {
                        $nagvis->openSite($rotateUrl);
                        $nagvis->messageBox("16", "");
                        $nagvis->closeSite();
                        $nagvis->printSite();
                        exit;
                }
        }

        function check_langfile() {
                global $Language;
                $nagvis = new NagVis;
                if(!is_readable('etc/languages/'.$Language.'.txt')) {
                        $nagvis->openSite($rotateUrl);
                        $nagvis->messageBox("18", "LANGFILE~".'etc/languages/wui_'.$Language.'.txt');
                        $nagvis->closeSite();
                        $nagvis->printSite();
                        exit;
                }
        }

        function check_wuilangfile() {
                global $Language;
                $nagvis = new NagVis;
                if(!is_readable('etc/languages/wui_'.$Language.'.txt')) {
                $nagvis->openSite($rotateUrl);
                $nagvis->messageBox("18", "LANGFILE~".'etc/languages/wui_'.$Language.'.txt');
                $nagvis->closeSite();
                $nagvis->printSite();
                exit;
        }
}

}
?>
