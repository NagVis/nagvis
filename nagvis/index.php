<?
##########################################################################
##     	                           NagVis                              ##
##                                                                      ##
##                      *** NagVis Startseite ***                       ##
##                                                                      ##
##      Autor von NagVis 0.0.3: Jörg Linge <pichfork@ederdrom.de>       ##
##                                                                      ##
##         Umgschrieben von Perl in PHP und weiterentwickelt by         ##
##                      Michael Lübben(MiCkEy2002)                      ##
##                                                                      ##
##                               Lizenz GPL                             ##
##########################################################################

include("./etc/config.inc.php");
include("./includes/classes/graphic.php");
include("./includes/classes/class.NagVis.php");
include("./includes/classes/class.readFiles.php");
include("./includes/classes/class.checkState.php");

// Browser ermitteln.
unset($browser);
$browser = $_SERVER['HTTP_USER_AGENT'];

// Map festlegen.
if(isset($_GET['map'])) {
	$map = $_GET['map'];
}
else {
	$map = $maps[0];
}

$nagvis = new NagVis();
$readfile = new readFile();
$rotateUrl = "";

// Angemeldeten User ermitteln.
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

// Prüfen ob Rotate-Modus eingeschaltet ist.
if($RotateMaps == "1") {
	$mapNumber = $nagvis->mapCount($map);
	$map = $maps[$mapNumber];
	$rotateUrl = " URL=index.php?map=".$map;
}  

// Ohne GD Lib geht nix
if (! extension_loaded('gd')) {
        $nagvis->openSite($rotateUrl);
        $nagvis->messageBox("15", "");
        $nagvis->closeSite();
        $nagvis->printSite();
        exit;
}


//Prüfen ob *.cfg-Datei vorhanden ist und dann einlesen.
//FIXME sollte erst NACH den ganzen Plausis gemacht werden
if(file_exists($cfgFolder.$map.".cfg")) {
	$mapCfg = $readfile->readNagVisCfg($map);
	$allowed_users = explode(",",trim($mapCfg[1]['allowed_user']));
	$map_image_array = explode(",",trim($mapCfg[1]['map_image']));
	$map_image=$map_image_array[0];
}
$nagvis->openSite($rotateUrl);

//Prüfen ob StatusCgi Zugreifbar ist
if(!file_exists($CgiPath)) {
	$nagvis->openSite($rotateUrl);
        $nagvis->messageBox("0", "STATUSCGI~$CgiPath");
        $nagvis->closeSite();
        $nagvis->printSite();
	exit;
}
//Prüfen ob StatusCgi  ausfuehrbar ist
//elseif(!is_executable($StatusCgi)) {
//	$nagvis->openSite($rotateUrl);
//        $nagvis->messageBox("1", "STATUSCGI~$StatusCgi");
//        $nagvis->closeSite();
//        $nagvis->printSite();
//	exit;
//}

//Prüfen ob *.cfg-Datei vorhanden ist und gegebenfalls eine Fehler ausgeben!
if(!file_exists($cfgFolder.$map.".cfg")) {
	$nagvis->openSite($rotateUrl);
	$nagvis->messageBox("2", "MAP~".$cfgFolder.$map.".cfg");
	$nagvis->closeSite();
	$nagvis->printSite();
	exit;
}
//Prüfen ob die Map vorhanden ist!
elseif(!file_exists($mapFolder.$map_image)) {
	$nagvis->openSite($rotateUrl);
	$nagvis->messageBox("3", "MAPPATH~".$mapFolder.$map_image);
	$nagvis->closeSite();
	$nagvis->printSite();
	exit;
}
//Prüfen ob der User die Berechtigung besitzt die Map zu sehen!
elseif(!in_array($user,$allowed_users) && isset($allowed_users)) {
	$nagvis->openSite($rotateUrl);
	$nagvis->messageBox("4", "USER~".$user);
	$nagvis->closeSite();
	$nagvis->printSite();
	exit;
}
//Prüfen ob das Log-File von Nagios gelesen werden kann! FIXME:Nicht mehr benoetigt 
/*elseif(!file_exists($StatusLogPath)) {
	$nagvis->openSite($rotateUrl);
	$nagvis->messageBox("5", "STATUSLOGPATH~".$StatusLogPath);
	$nagvis->closeSite();
	$nagvis->printSite();
	exit;
}*/
else {
	// Prüfen ob Header eingeschaltet ist und bei bedarf erzeugen.
	if ($Header == "1") {
		$Menu = $readfile->readMenu();
		$nagvis->makeHeaderMenu($Menu);
	}
	
	//Map als Hintergrundbild erzeugen
	$nagvis->printMap($map);
	
	$checkstate = new checkState();
	$countStates = count($mapCfg)-1;
	$arrayPos="2";
	for($x="1";$x<=$countStates;$x++) {
		//Icons auf der Map erzeugen.
//		$mapCfg[$arrayPos]['x'] = $mapCfg[$arrayPos]['x'] + $offsetX;
//		$mapCfg[$arrayPos]['y'] = $mapCfg[$arrayPos]['y'] + $offsetY;
		// FIXME: hier sollte noch nach dem Typ ne Plausi gemacht werden... (wie nagios -v )
		//Status einer Map ermitteln
		if($mapCfg[$arrayPos]['type'] == 'map') {
			//if(!isset($mapCfg[$arrayPos]['recognize_services'])) {
			//	$mapCfg[$arrayPos]['recognize_services'] = 0;
			//}
			//if(!isset($mapCfg[$arrayPos]['service_description'])) {
			//	$mapCfg[$arrayPos]['service_description'] = "";
			//}
			if(file_exists($cfgFolder.$mapCfg[$arrayPos]['name'].'.cfg')) {
				$mapCfgState = $readfile->readNagVisCfg($mapCfg[$arrayPos]['name']);
				$countStatesState = count($mapCfgState);
				for($y=2;$y<=$countStatesState;$y++) {
					$stateState = $checkstate->checkStates($mapCfgState[$arrayPos]['type'],$mapCfgState[$arrayPos]['name'],$mapCfg[$arrayPos]['recognize_services'],$mapCfg[$arrayPos]['service_description'],$mapCfg[$arrayPos]['line_type'],$CgiPath,$CgiUser);
					$stateAllDefines[] = $stateState['State'];
				}
				$state['Map'] = $mapCfg[$arrayPos]['name'];
				$state['State'] = $checkstate->findStateArray($stateAllDefines);
			}
			else {
				$state['State'] = "error";
			}
		}
		// Comment Box einblenden
		// Jli
		elseif($mapCfg[$arrayPos]['type'] == 'textbox') {
			$TextBox = $checkstate->TextBox($mapCfg[$arrayPos]['x'],$mapCfg[$arrayPos]['y'],$mapCfg[$arrayPos]['w'],$mapCfg[$arrayPos]['text']);
			$nagvis->site[] = $TextBox;		


		}elseif(isset($mapCfg[$arrayPos]['line_type'])) {

			if($mapCfg[$arrayPos]['line_type'] == '10' || $mapCfg[$arrayPos]['line_type'] == '11'){

			$state = $checkstate->checkStates($mapCfg[$arrayPos]['type'],$mapCfg[$arrayPos]['name'],$mapCfg[$arrayPos]['recognize_services'],$mapCfg[$arrayPos]['service_description'],0,$CgiPath,$CgiUser);
                        list($x_from,$x_to) = explode(",", $mapCfg[$arrayPos]['x']);
                        list($y_from,$y_to) = explode(",", $mapCfg[$arrayPos]['y']);
                        $x_middle = middle($x_from,$x_to);
                        $y_middle = middle($y_from,$y_to);
			$IconPosition = $checkstate->fixIconPosition('20x20.gif',$x_middle,$y_middle);
			$Box = $nagvis->infoBox($mapCfg[$arrayPos]['type'],$mapCfg[$arrayPos]['name'],$mapCfg[$arrayPos]['service_description'],$state);
			$nagvis->site[] = $IconPosition;
			$nagvis->site[] = '<img src=iconsets/20x20.gif '.$Box.';>';
			$nagvis->site[] = '</div>';

			}elseif($mapCfg[$arrayPos]['line_type'] == '20'){
                        list($host_name_from,$host_name_to) = explode(",", $mapCfg[$arrayPos]['name']);
                        list($service_description_from,$service_description_to) = explode(",", $mapCfg[$arrayPos]['service_description']);
                        $state_from = $checkstate->checkStates($mapCfg[$arrayPos]['type'],$host_name_from,$mapCfg[$arrayPos]['recognize_services'],$service_description_from,1,$CgiPath,$CgiUser);
                        $state_to = $checkstate->checkStates($mapCfg[$arrayPos]['type'],$host_name_to,$mapCfg[$arrayPos]['recognize_services'],$service_description_to,2,$CgiPath,$CgiUser);

                        list($x_from,$x_to) = explode(",", $mapCfg[$arrayPos]['x']);
                        list($y_from,$y_to) = explode(",", $mapCfg[$arrayPos]['y']);
			// From
                        $x_middle = middle2($x_from,$x_to);
                        $y_middle = middle2($y_from,$y_to);
			$IconPosition = $checkstate->fixIconPosition('20x20.gif',$x_middle,$y_middle);
			$Box = $nagvis->infoBox($mapCfg[$arrayPos]['type'],$host_name_from,$service_description_from,$state_from);
			$nagvis->site[] = $IconPosition;
			$nagvis->site[] = '<img src= iconsets/20x20.gif '.$Box.';>';
			$nagvis->site[] = '</div>';
			// To
                        $x_middle = middle2($x_to,$x_from);
                        $y_middle = middle2($y_to,$y_from);
			$IconPosition = $checkstate->fixIconPosition('20x20.gif',$x_middle,$y_middle);
			$Box = $nagvis->infoBox($mapCfg[$arrayPos]['type'],$host_name_to,$service_description_to,$state_to);
			$nagvis->site[] = $IconPosition;
			$nagvis->site[] = '<img src=iconsets/20x20.gif '.$Box.';>';
			$nagvis->site[] = '</div>';

			}	
			

		}elseif(!isset($mapCfg[$arrayPos]['line_type'])) {
			// Default Werte auf die Funktion gelegt
			//if(!isset($mapCfg[$arrayPos]['recognize_services'])) {
			//	$mapCfg[$arrayPos]['recognize_services'] = 0;
			//}
			//if(!isset($mapCfg[$arrayPos]['service_description'])) {
			//	$mapCfg[$arrayPos]['service_description'] = "";
			//}
			// ~Anders machen
			$state = $checkstate->checkStates($mapCfg[$arrayPos]['type'],$mapCfg[$arrayPos]['name'],$mapCfg[$arrayPos]['recognize_services'],$mapCfg[$arrayPos]['service_description'],0,$CgiPath,$CgiUser);

			$Icon = $checkstate->fixIcon($state,$mapCfg,$arrayPos,$defaultIcons);
			$IconPosition = $checkstate->fixIconPosition($Icon,$mapCfg[$arrayPos]['x'],$mapCfg[$arrayPos]['y']);
			$nagvis->site[] = $IconPosition;

//			if(!isset($mapCfg[$arrayPos]['name'])) {
//				$mapCfg[$arrayPos]['name'] = "";
//			}
		
			$Box = $nagvis->infoBox($mapCfg[$arrayPos]['type'],$mapCfg[$arrayPos]['name'],$mapCfg[$arrayPos]['service_description'],$state);
			if(isset($mapCfg[$arrayPos]['url'])) {
				$nagvis->site[] = '<A HREF='.$mapCfg[$arrayPos]['url'].'><IMG SRC='.$iconHTMLBaseFolder.$Icon.' '.$Box.'; BORDER="0"></A>';
			}
			else {
				$nagvis->site[] = '<IMG SRC='.$iconHTMLBaseFolder.$Icon.' '.$Box.';>';
			}
			$nagvis->site[] = "</DIV>";
		}
		$arrayPos++;
	}
	$nagvis->closeSite();
	$nagvis->printSite();
	
}
?>
