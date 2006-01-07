<?
##########################################################################
##     	        NagVis - The Nagios Visualisation Addon                 ##
##########################################################################
## index.php - Main file to get called by the user. In this file there  ##
##             should be only "output code", all calculations should    ##
##             be done in the classes!                                  ##
##########################################################################
## Licenced under the terms and conditions of the GPL Licence, 			##
## please see attached "LICENCE" file	                                ##
##########################################################################

##########################################################################
## Code Format: Vars: wordWordWord										##
##		   	 Classes: wordwordword                                      ##
##		     Objects: WORDWORDWORD                                      ##
## Please use TAB (Tab Size: 4 Spaces) to format the code               ##
##########################################################################

include("./etc/config.inc.php");
include("./includes/classes/class.Graphic.php");
include("./includes/classes/class.CheckIt.php");
include("./includes/classes/class.ReadFiles.php");
include("./includes/classes/class.CheckState_".$StateClass.".php");

$CHECKIT = new checkit();

// Browser ermitteln.
unset($browser);
$browser = $_SERVER['HTTP_USER_AGENT'];

// Map festlegen.
if(isset($_GET['map'])) {
    $map = $_GET['map'];
    $CHECKIT->check_map_isreadable();
} else {
    $map = $maps[0];
	$CHECKIT->check_map_isreadable();
}

$FRONTEND = new frontend();
$READFILE = new readFile();
$rotateUrl = "";

// check-stuff
$CHECKIT->check_user();
$CHECKIT->check_gd();
$CHECKIT->check_cgipath();
$CHECKIT->check_wuibash();
$CHECKIT->check_rotate();

/*
FIXME: Ha: Can we delete this permanent???

// Prüfen ob Rotate-Modus eingeschaltet ist.
if($RotateMaps == "1") {
    $mapNumber = $FRONTEND->mapCount($map);
    $map = $maps[$mapNumber];
    $rotateUrl = " URL=index.php?map=".$map;
}  
*/

//Prüfen ob *.cfg-Datei vorhanden ist und dann einlesen.
//FIXME sollte erst NACH den ganzen Plausis gemacht werden
if(file_exists($cfgFolder.$map.".cfg")) {
    $mapCfg = $READFILE->readNagVisCfg($map);
    $allowed_users = explode(",",trim($mapCfg[1]['allowed_user']));
    $map_image_array = explode(",",trim($mapCfg[1]['map_image']));
    $map_image=$map_image_array[0];
}
$FRONTEND->openSite($rotateUrl);

$CHECKIT->check_permissions();
$CHECKIT->check_map_isreadable();
$CHECKIT->check_mapimg();
$CHECKIT->check_langfile();

// Prüfen ob Header eingeschaltet ist und bei bedarf erzeugen.
if ($Header == "1") {
	$Menu = $READFILE->readMenu();
	$FRONTEND->makeHeaderMenu($Menu);
}

//Map als Hintergrundbild erzeugen
if ($useGDLibs == "1") {
	$FRONTEND->printMap($map);
} else {
	$FRONTEND->printMap($map_image);
}

//Load and initialise the backend
$BACKEND = new backend();
$BACKEND->backendInitialize();

$countStates = count($mapCfg)-1;
// FIXME: HA: The following line looks "gemurkst" ;-)
$arrayPos="2";


// Main loop which passes through all objects on the map
for($x="1";$x<=$countStates;$x++) { 
    if(!isset($mapCfg[$arrayPos]['recognize_services'])) {
		$mapCfg[$arrayPos]['recognize_services'] = 1;
    }
    if(!isset($mapCfg[$arrayPos]['service_description'])) {
		$mapCfg[$arrayPos]['service_description'] = "";
    }
    // Links to the Nagios Pages
	// FIXME: Ha: Exclude this in a method/function in a class and then just call this method here
    if(isset($mapCfg[$arrayPos]['url'])) {
		$link = '<A HREF='.$mapCfg[$arrayPos]['url'].'>';
    } elseif($mapCfg[$arrayPos]['type'] == 'host') {
		$link = '<A HREF="'.$HTMLCgiPath.'/status.cgi?host='.$mapCfg[$arrayPos]['name'].'">';
    } elseif($mapCfg[$arrayPos]['type'] == 'service') {
		$link = '<A HREF="'.$HTMLCgiPath.'/extinfo.cgi?type=2&host='.$mapCfg[$arrayPos]['name'].'&service='.$mapCfg[$arrayPos]['service_description'].'">';
    } elseif($mapCfg[$arrayPos]['type'] == 'hostgroup') {
		$link = '<A HREF="'.$HTMLCgiPath.'/status.cgi?hostgroup='.$mapCfg[$arrayPos]['name'].'&style=detail">';
    } elseif($mapCfg[$arrayPos]['type'] == 'servicegroup') {
		$link = '<A HREF="'.$HTMLCgiPath.'/status.cgi?servicegroup='.$mapCfg[$arrayPos]['name'].'&style=detail">';
    }

    /* 
       The following part handles the differnt object types (host, service, line and so on)
	   FIXME: Ha: a switch statement would be better to unterstand than this extremley large if-construct
 	*/
    // Handle all objects of type "map"
    if($mapCfg[$arrayPos]['type'] == 'map') {
		if(file_exists($cfgFolder.$mapCfg[$arrayPos]['name'].'.cfg')) {
			unset($mapState); 
			$allMapCfgState = $READFILE->readNagVisCfg($mapCfg[$arrayPos]['name']);
			
			/*
				FIXME: Ha: Can we delte this permanent???
				$countStatesState = count($mapCfgState);
				if(!isset($mapCfgState['recognize_services'])) {
					$mapCfgState['recognize_services'] = 1;
				}
				if(!isset($mapCfgState[$arrayPos]['service_description'])) {
					$mapCfgState[$arrayPos]['service_description'] = "";
				}
			*/

			foreach ($allMapCfgState as $mapCfgState) {
	
				if(!isset($mapCfgState['recognize_services'])) {
					$mapCfgState['recognize_services'] = 1;
				}
	
				$allowed_types = array(host,service,hostgroup,servicegroup);
				if(in_array($mapCfgState['type'], $allowed_types)){
					$stateState = $BACKEND->checkStates($mapCfgState['type'],$mapCfgState['name'],$mapCfgState['recognize_services'],$mapCfgState['service_description'],0,$CgiPath,$CgiUser);
					$mapState[] = $stateState['State'];
				}
				$countStatesState++;
			}
			
			if(in_array("DOWN", $mapState) || in_array("CRITICAL", $mapState)){
				$state['State'] = "CRITICAL";
				$state['Output'] = "State of child Map is CRITICAL";
				$Icon = $BACKEND->fixIcon($state,$mapCfg,$arrayPos,$defaultIcons,$mapCfg[$arrayPos]['type']);
			}elseif(in_array("WARNING", $mapState)){
				$state['State'] = "WARNING";
				$state['Output'] = "State of child Map is  WARNING";
				$Icon = $BACKEND->fixIcon($state,$mapCfg,$arrayPos,$defaultIcons,$mapCfg[$arrayPos]['type']);
			}else{
				$state['State'] = "OK";
				$state['Output'] = "State of child map is OK";
				$Icon = $BACKEND->fixIcon($state,$mapCfg,$arrayPos,$defaultIcons,$mapCfg[$arrayPos]['type']);
			}
		} else {
			$state['State'] = "OK";
			$state['Output'] = "Child Map not readable";
		}
	
		$IconPosition = $BACKEND->fixIconPosition($Icon,$mapCfg[$arrayPos]['x'],$mapCfg[$arrayPos]['y']);
		$FRONTEND->site[] = $IconPosition;
		$Box = $FRONTEND->infoBox($mapCfg[$arrayPos]['type'],$mapCfg[$arrayPos]['name'],$mapCfg[$arrayPos]['service_description'],$state);
		$FRONTEND->site[] = '<A HREF="./index.php?map='.$mapCfg[$arrayPos]['name'].'" TARGET="_self"><IMG SRC='.$iconHTMLBaseFolder.$Icon.' '.$Box.'; BORDER="0"></A>';
		$FRONTEND->site[] = "</DIV>";
    
	// Handle objects of type "textbox"
    }elseif($mapCfg[$arrayPos]['type'] == 'textbox') {
		$TextBox = $BACKEND->TextBox($mapCfg[$arrayPos]['x'],$mapCfg[$arrayPos]['y'],$mapCfg[$arrayPos]['w'],$mapCfg[$arrayPos]['text']);
		$FRONTEND->site[] = $TextBox;		
	
	// Handle objects of type "line" and take care about the differnt line_types
	}elseif(isset($mapCfg[$arrayPos]['line_type'])) {
	    if($mapCfg[$arrayPos]['line_type'] == '10' || $mapCfg[$arrayPos]['line_type'] == '11'){
			$state = $BACKEND->checkStates($mapCfg[$arrayPos]['type'],$mapCfg[$arrayPos]['name'],$mapCfg[$arrayPos]['recognize_services'],$mapCfg[$arrayPos]['service_description'],0,$CgiPath,$CgiUser);
			list($x_from,$x_to) = explode(",", $mapCfg[$arrayPos]['x']);
			list($y_from,$y_to) = explode(",", $mapCfg[$arrayPos]['y']);
			$x_middle = middle($x_from,$x_to);
			$y_middle = middle($y_from,$y_to);
			$IconPosition = $BACKEND->fixIconPosition('20x20.gif',$x_middle,$y_middle);
			$Box = $FRONTEND->infoBox($mapCfg[$arrayPos]['type'],$mapCfg[$arrayPos]['name'],$mapCfg[$arrayPos]['service_description'],$state);
			$FRONTEND->site[] = $IconPosition;
			$FRONTEND->site[] = $link;
			$FRONTEND->site[] = '<img src=iconsets/20x20.gif '.$Box.';></A>';
			$FRONTEND->site[] = '</div>';

	    } elseif($mapCfg[$arrayPos]['line_type'] == '20'){
			list($host_name_from,$host_name_to) = explode(",", $mapCfg[$arrayPos]['name']);
			list($service_description_from,$service_description_to) = explode(",", $mapCfg[$arrayPos]['service_description']);
			$state_from = $BACKEND->checkStates($mapCfg[$arrayPos]['type'],$host_name_from,$mapCfg[$arrayPos]['recognize_services'],$service_description_from,1,$CgiPath,$CgiUser);
			$state_to = $BACKEND->checkStates($mapCfg[$arrayPos]['type'],$host_name_to,$mapCfg[$arrayPos]['recognize_services'],$service_description_to,2,$CgiPath,$CgiUser);

			list($x_from,$x_to) = explode(",", $mapCfg[$arrayPos]['x']);
			list($y_from,$y_to) = explode(",", $mapCfg[$arrayPos]['y']);
			// From
			$x_middle = middle2($x_from,$x_to);
			$y_middle = middle2($y_from,$y_to);
			$IconPosition = $BACKEND->fixIconPosition('20x20.gif',$x_middle,$y_middle);
			$Box = $FRONTEND->infoBox($mapCfg[$arrayPos]['type'],$host_name_from,$service_description_from,$state_from);
			$FRONTEND->site[] = $IconPosition;
			$FRONTEND->site[] = $link;
			$FRONTEND->site[] = '<img src= iconsets/20x20.gif '.$Box.';></A>';
			$FRONTEND->site[] = '</div>';
			// To
			$x_middle = middle2($x_to,$x_from);
			$y_middle = middle2($y_to,$y_from);
			$IconPosition = $BACKEND->fixIconPosition('20x20.gif',$x_middle,$y_middle);
			$Box = $FRONTEND->infoBox($mapCfg[$arrayPos]['type'],$host_name_to,$service_description_to,$state_to);
			$FRONTEND->site[] = $IconPosition;
			$FRONTEND->site[] = $link;
			$FRONTEND->site[] = '<img src=iconsets/20x20.gif '.$Box.';></A>';
			$FRONTEND->site[] = '</div>';
		}	

	// Handle all the other types (hosts, services, hostgroups, servicegroups)
	}elseif(!isset($mapCfg[$arrayPos]['line_type'])) {
		$state = $BACKEND->checkStates($mapCfg[$arrayPos]['type'],$mapCfg[$arrayPos]['name'],$mapCfg[$arrayPos]['recognize_services'],$mapCfg[$arrayPos]['service_description'],0,$CgiPath,$CgiUser);

		$Icon = $BACKEND->fixIcon($state,$mapCfg,$arrayPos,$defaultIcons,$mapCfg[$arrayPos]['type']);
		$IconPosition = $BACKEND->fixIconPosition($Icon,$mapCfg[$arrayPos]['x'],$mapCfg[$arrayPos]['y']);
		$FRONTEND->site[] = $IconPosition;
		
		$Box = $FRONTEND->infoBox($mapCfg[$arrayPos]['type'],$mapCfg[$arrayPos]['name'],$mapCfg[$arrayPos]['service_description'],$state);
		$FRONTEND->site[] = $link;
		$FRONTEND->site[] = '<IMG SRC='.$iconHTMLBaseFolder.$Icon.' '.$Box.';></A>';
		$FRONTEND->site[] = "</DIV>";
	}
    $arrayPos++;

//~End of main loop
}
$FRONTEND->closeSite();
$FRONTEND->printSite();

?>
