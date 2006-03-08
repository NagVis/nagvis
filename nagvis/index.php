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

#FIXME: Inserts Plausis to check if files are there and readable
include("./includes/classes/class.NagVisConfig.php");
include("./includes/classes/class.Graphic.php");
include("./includes/classes/class.CheckIt.php");
include("./includes/classes/class.ReadFiles.php");
include("./includes/classes/class.Debug.php");

$CONFIG = new MainNagVisCfg('./etc/config.ini');

// include the configured backend
if($CONFIG->getValue('global', 'backend') == 'html')
	include("./includes/classes/class.CheckState_html.php");
elseif($CONFIG->getValue('global', 'backend') == 'ndomy')
	include("./includes/classes/class.CheckState_ndomy.php");
elseif($CONFIG->getValue('global', 'backend') == 'xml')
	include("./includes/classes/class.CheckState_xml.php");
else {
	//FIXME: Errorhandling (no valid backend selected)	
}

$CHECKIT = new checkit($CONFIG);
$DEBUG = new debug($CONFIG);

// Get Browser Info
unset($browser);
$browser = $_SERVER['HTTP_USER_AGENT'];

// Get Map from Url.
if(isset($_GET['map'])) {
    $map = $_GET['map'];
} else {
	$arr = explode(',',$CONFIG->getValue('global', 'maps'));
    $map = $arr[0];
}

$FRONTEND = new frontend($CONFIG);
$READFILE = new readFile($CONFIG);

$rotateUrl = "";

// check-stuff
$CHECKIT->check_user();
$CHECKIT->check_gd();
//FIXME: should only be checked, if backend ist HTML/CGI
if($CONFIG->getValue('global', 'backend') == 'html') {
	$CHECKIT->check_cgipath();
}
$CHECKIT->check_wuibash();
$rotateUrl = $CHECKIT->check_rotate();

$CHECKIT->check_map_isreadable();
//Read *.cfg File
$mapCfg = $READFILE->readNagVisCfgNew($map);
$IconMapGlobal = $mapCfg['global']['1']['iconset'];
$map_image_array = explode(",",trim($mapCfg['global'][1]['map_image']));
$map_image=$map_image_array[0];


$FRONTEND->openSite($rotateUrl);

$CHECKIT->check_permissions();
$CHECKIT->check_mapimg();
$CHECKIT->check_langfile();

// Create Header-Menu, when enabled
if ($CONFIG->getValue('global', 'displayheader') == "1") {
	$Menu = $READFILE->readMenu();
	$FRONTEND->makeHeaderMenu($Menu);
}

// Create Background
if ($CONFIG->getValue('global', 'usegdlibs') == "1") {
	$FRONTEND->printMap($map);
} else {
	$FRONTEND->printMap($map_image);
}

//Load and initialise the backend
$BACKEND = new backend($CONFIG);
    
// Handle all objects of type "map"
if (is_array($mapCfg['map'])){
	foreach ($mapCfg['map'] as $index => $map) {
		if(file_exists($CONFIG->getValue('paths', 'mapcfg').$map['name'].'.cfg')) {
			unset($mapState);
			unset($stateState); 
			$childMapCfg = $READFILE->readNagVisCfgNew($map['name']);
			
			if (is_array($childMapCfg['host'])){
				foreach ($childMapCfg['host'] as $index => $host) {
					if(isset($host['line_type'])) {
						list($host_name_from,$host_name_to) = explode(",", $host['name']);
						list($service_description_from,$service_description_to) = explode(",", $host['service_description']);
						$stateState = $BACKEND->checkStates($host['type'],$host_name_from,$host['recognize_services'],$service_description_from,1);
						$stateState = $BACKEND->checkStates($host['type'],$host_name_to,$host['recognize_services'],$service_description_to,2);				
					} else {
						$stateState = $BACKEND->checkStates($host['type'],$host['name'],$host['recognize_services'],$host['service_description'],0);
					}
					$mapState[] = $stateState['State'];
				}
			}
			if (is_array($childMapCfg['hostgroup'])){
				foreach ($childMapCfg['hostgroup'] as $index => $hostgroup) {
					$stateState = $BACKEND->checkStates($hostgroup['type'],$hostgroup['name'],$hostgroup['recognize_services'],$hostgroup['service_description'],0);
					$mapState[] = $stateState['State'];
				}
			}
			if (is_array($childMapCfg['service'])){
				foreach ($childMapCfg['service'] as $index => $service) {
					if(isset($service['line_type'])) {
						list($host_name_from,$host_name_to) = explode(",", $service['name']);
						list($service_description_from,$service_description_to) = explode(",", $service['service_description']);
						$stateState = $BACKEND->checkStates($service['type'],$host_name_from,$service['recognize_services'],$service_description_from,1);
						$stateState = $BACKEND->checkStates($service['type'],$host_name_to,$service['recognize_services'],$service_description_to,2);				
					} else {
						$stateState = $BACKEND->checkStates($service['type'],$service['name'],$service['recognize_services'],$service['service_description'],0);
					}
					$mapState[] = $stateState['State'];
				}
			}
			if (is_array($childMapCfg['servicegroup'])){
				foreach ($childMapCfg['servicegroup'] as $index => $servicegroup) {
					$stateState = $BACKEND->checkStates($servicegroup['type'],$servicegroup['name'],$servicegroup['recognize_services'],$servicegroup['service_description'],0);
					$mapState[] = $stateState['State'];
				}
			}
			
			if(in_array("DOWN", $mapState) || in_array("CRITICAL", $mapState)){
				$state['State'] = "CRITICAL";
				$state['Output'] = "State of child Map is CRITICAL";
				$Icon = $FRONTEND->findIcon($state,$map,$mapCfg['global']['iconset'],$CONFIG->getValue('global', 'defaulticons'),$map['type']);
			}elseif(in_array("WARNING", $mapState)){
				$state['State'] = "WARNING";
				$state['Output'] = "State of child Map is WARNING";
				$Icon = $FRONTEND->findIcon($state,$map,$mapCfg['global']['iconset'],$CONFIG->getValue('global', 'defaulticons'),$map['type']);
			}elseif(in_array("UNKNOWN", $mapState)){
				$state['State'] = "UNKNOWN";
				$state['Output'] = "State of child Map is UNKNOWN";
				$Icon = $FRONTEND->findIcon($state,$map,$mapCfg['global']['iconset'],$CONFIG->getValue('global', 'defaulticons'),$map['type']);
			}elseif(in_array("ERROR", $mapState)){
				$state['State'] = "ERROR";
				$state['Output'] = "State of child Map is ERROR";
				$Icon = $FRONTEND->findIcon($state,$map,$mapCfg['global']['iconset'],$CONFIG->getValue('global', 'defaulticons'),$map['type']);
			}else{
				$state['State'] = "OK";
				$state['Output'] = "State of child map is OK";
				$Icon = $FRONTEND->findIcon($state,$map,$IconMapGlobal,$CONFIG->getValue('global', 'defaulticons'),$map['type']);
			}
			
		} else {
			$state['State'] = "UNKNOWN";
			$state['Output'] = "Child Map not readable";
			$Icon = $FRONTEND->findIcon($state,$map,$IconMapGlobal,$CONFIG->getValue('global', 'defaulticons'),$map['type']);
		}
		
		$IconPosition = $FRONTEND->fixIconPosition($Icon,$map['x'],$map['y']);
		$FRONTEND->site[] = $IconPosition;
		$Box = $FRONTEND->infoBox($map['type'],$map['name'],$map['service_description'],$state);
		$FRONTEND->site[] = '<A HREF="./index.php?map='.$map['name'].'" TARGET="_self"><IMG SRC='.$CONFIG->getValue('paths', 'htmlicon').$Icon.' '.$Box.'; BORDER="0"></A>';
		$FRONTEND->site[] = "</DIV>";
	}
}	
 
// Handle objects of type "textbox"
if(is_array($mapCfg['textbox'])) {
	foreach ($mapCfg['textbox'] as $index => $textbox) {
		$TextBox = $FRONTEND->TextBox($textbox['x'],$textbox['y'],$textbox['w'],$textbox['text']);
		$FRONTEND->site[] = $TextBox;
	}	
}	
	
// Handle the type host.
if (is_array($mapCfg['host'])){
	$debug[] = $DEBUG->debug_insertInfo($CONFIG->getValue('global', 'debugstates'),'Handle the type host:');
	foreach ($mapCfg['host'] as $index => $host) {
		$recognize_services = $FRONTEND->checkOption($host['recognize_services'],$mapCfg['global']['1']['recognize_services'],"1");
		if(isset($host['line_type'])) {
			if ($host['line_type'] != "20") {
				$state = $BACKEND->checkStates($host['type'],$host['name'],$recognize_services,$host['service_description'],0);
				$FRONTEND->createBoxLine($host,$state,NULL);
			} else {
				list($host_name_from,$host_name_to) = explode(",", $host['name']);
				list($service_description_from,$service_description_to) = explode(",", $host['service_description']);
				$state1 = $BACKEND->checkStates($host['type'],$host_name_from,$recognize_services,$service_description_from,1);
				$state2 = $BACKEND->checkStates($host['type'],$host_name_to,$recognize_services,$service_description_to,2);
				$FRONTEND->createBoxLine($host,$state1,$state2);
			}
		} else {
 			$state = $BACKEND->checkStates($host['type'],$host['name'],$recognize_services,$host['service_description'],0);
			$debug[] = $DEBUG->debug_checkState($CONFIG->getValue('global', 'debugstates'),$CONFIG->getValue('global', 'debugcheckstate'),$index);

			$Icon = $FRONTEND->findIcon($state,$host,$IconMapGlobal,$CONFIG->getValue('global', 'defaulticons'),$host['type']);
			$debug[] = $DEBUG->debug_fixIcon($CONFIG->getValue('global', 'debugstates'),$CONFIG->getValue('global', 'debugfixicon'),$index);
	
			$IconPosition = $FRONTEND->fixIconPosition($Icon,$host['x'],$host['y']);
			$FRONTEND->site[] = $IconPosition;
			$Box = $FRONTEND->infoBox($host['type'],$host['name'],$host['service_description'],$state);
			$FRONTEND->site[] = $FRONTEND->createLink($CONFIG->getValue('paths', 'htmlcgi'),$host['url'],$host['type'],$host['name'],$host['service_description']);
			$FRONTEND->site[] = '<IMG SRC='.$CONFIG->getValue('paths', 'htmlicon').$Icon.' '.$Box.';></A>';
			$FRONTEND->site[] = "</DIV>";
			$debug[] = $DEBUG->debug_insertRow($CONFIG->getValue('global', 'debugstates'));
		}
	}
}


// Handle the type hostgroup.
if (is_array($mapCfg['hostgroup'])){
	$debug[] = $DEBUG->debug_insertInfo($CONFIG->getValue('global', 'debugstates'),'Handle the type hostgroup:');
	foreach ($mapCfg['hostgroup'] as $index => $hostgroup) {
		$recognize_services = $FRONTEND->checkOption($hostgroup['recognize_services'],$mapCfg['global']['1']['recognize_services'],"1");
		$state = $BACKEND->checkStates($hostgroup['type'],$hostgroup['name'],$recognize_services,$hostgroup['service_description'],0);
		$debug[] = $DEBUG->debug_checkState($CONFIG->getValue('global', 'debugstates'),$CONFIG->getValue('global', 'debugcheckstate'),$index);

		$Icon = $FRONTEND->findIcon($state,$hostgroup,$IconMapGlobal,$CONFIG->getValue('global', 'defaulticons'),$hostgroup['type']);
		$debug[] = $DEBUG->debug_fixIcon($CONFIG->getValue('global', 'debugstates'),$CONFIG->getValue('global', 'debugfixicon'),$index);
		
		$IconPosition = $FRONTEND->fixIconPosition($Icon,$hostgroup['x'],$hostgroup['y']);
		$FRONTEND->site[] = $IconPosition;
	
		$Box = $FRONTEND->infoBox($hostgroup['type'],$hostgroup['name'],$hostgroup['service_description'],$state);
		$FRONTEND->site[] = $FRONTEND->createLink($CONFIG->getValue('paths', 'htmlcgi'),$hostgroup['url'],$hostgroup['type'],$hostgroup['name'],$hostgroup['service_description']);
		$FRONTEND->site[] = '<IMG SRC='.$CONFIG->getValue('paths', 'htmlicon').$Icon.' '.$Box.';></A>';
		$FRONTEND->site[] = "</DIV>";
		$debug[] = $DEBUG->debug_insertRow($CONFIG->getValue('global', 'debugstates'));
	}
}

// Handle the type service.
if (is_array($mapCfg['service'])){
	$debug[] = $DEBUG->debug_insertInfo($CONFIG->getValue('global', 'debugstates'),'Handle the type service:');
	foreach ($mapCfg['service'] as $index => $service) {
		$recognize_services = $FRONTEND->checkOption($service['recognize_services'],$mapCfg['global']['1']['recognize_services'],"1");
		if(isset($service['line_type'])) {
			if ($service['line_type'] != "20") {
				$state = $BACKEND->checkStates($service['type'],$service['name'],$recognize_services,$service['service_description'],0);
				$FRONTEND->createBoxLine($service,$state,NULL);
			} else {
				list($host_name_from,$host_name_to) = explode(",", $service['name']);
				list($service_description_from,$service_description_to) = explode(",", $service['service_description']);
				$state1 = $BACKEND->checkStates($service['type'],$host_name_from,$recognize_services,$service_description_from,1);
				$state2 = $BACKEND->checkStates($service['type'],$host_name_to,$recognize_services,$service_description_to,2);
				$FRONTEND->createBoxLine($service,$state1,$state2);
			}
		} else {
			$state = $BACKEND->checkStates($service['type'],$service['name'],$recognize_services,$service['service_description'],0);
			$debug[] = $DEBUG->debug_checkState($CONFIG->getValue('global', 'debugstates'),$CONFIG->getValue('global', 'debugcheckstate'),$index);

			$Icon = $FRONTEND->findIcon($state,$service,$IconMapGlobal,$CONFIG->getValue('global', 'defaulticons'),$service['type']);
			$debug[] = $DEBUG->debug_fixIcon($CONFIG->getValue('global', 'debugstates'),$CONFIG->getValue('global', 'debugfixicon'),$index);
	
			$IconPosition = $FRONTEND->fixIconPosition($Icon,$service['x'],$service['y']);
			$FRONTEND->site[] = $IconPosition;
	
			$Box = $FRONTEND->infoBox($service['type'],$service['name'],$service['service_description'],$state);
			$FRONTEND->site[] = $FRONTEND->createLink($CONFIG->getValue('paths', 'htmlcgi'),$service['url'],$service['type'],$service['name'],$service['service_description']);
			$FRONTEND->site[] = '<IMG SRC='.$CONFIG->getValue('paths', 'htmlicon').$Icon.' '.$Box.';></A>';
			$FRONTEND->site[] = "</DIV>";
			$debug[] = $DEBUG->debug_insertRow($CONFIG->getValue('global', 'debugstates'));
		}
	}
}

// Handle the type servicegroup.
if (is_array($mapCfg['servicegroup'])){
	$debug[] = $DEBUG->debug_insertInfo($CONFIG->getValue('global', 'debugstates'),'Handle the type servicegroup:');
	foreach ($mapCfg['servicegroup'] as $index => $servicegroup) {
	$recognize_services = $FRONTEND->checkOption($servicegroup['recognize_services'],$mapCfg['global']['1']['recognize_services'],"1");
	$state = $BACKEND->checkStates($servicegroup['type'],$servicegroup['name'],$recognize_services,$servicegroup['service_description'],0);
		$debug[] = $DEBUG->debug_checkState($CONFIG->getValue('global', 'debugstates'),$CONFIG->getValue('global', 'debugcheckstate'),$index);

		$Icon = $FRONTEND->findIcon($state,$servicegroup,$IconMapGlobal,$CONFIG->getValue('global', 'defaulticons'),$servicegroup['type']);
		$debug[] = $DEBUG->debug_fixIcon($CONFIG->getValue('global', 'debugstates'),$CONFIG->getValue('global', 'debugfixicon'),$index);
	
		$IconPosition = $FRONTEND->fixIconPosition($Icon,$servicegroup['x'],$servicegroup['y']);
		$FRONTEND->site[] = $IconPosition;
	
		$Box = $FRONTEND->infoBox($servicegroup['type'],$servicegroup['name'],$servicegroup['service_description'],$state);
		$FRONTEND->site[] = $FRONTEND->createLink($CONFIG->getValue('paths', 'htmlcgi'),$servicegroup['url'],$servicegroup['type'],$servicegroup['name'],$servicegroup['service_description']);
		$FRONTEND->site[] = '<IMG SRC='.$CONFIG->getValue('paths', 'htmlicon').$Icon.' '.$Box.';></A>';
		$FRONTEND->site[] = "</DIV>";
		$debug[] = $DEBUG->debug_insertRow($CONFIG->getValue('global', 'debugstates'));
	}
}

$FRONTEND->debug($debug);
$FRONTEND->closeSite();
$FRONTEND->printSite();

?>
