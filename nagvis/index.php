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
include("./includes/classes/class.MapCfg.php");
include("./includes/classes/class.Graphic.php");
include("./includes/classes/class.CheckIt.php");
include("./includes/classes/class.ReadFiles.php");
include("./includes/classes/class.Debug.php");

$MAINCFG = new MainNagVisCfg('./etc/config.ini');
$MAPCFG = new MapCfg($MAINCFG,$_GET['map']);
$MAPCFG->readMapConfig();
$CHECKIT = new checkit($MAINCFG,$MAPCFG);
$DEBUG = new debug($MAINCFG);

$FRONTEND = new frontend($MAINCFG,$MAPCFG);

include("./includes/classes/class.CheckState_".$MAINCFG->getValue('global', 'backend').".php");
$BACKEND = new backend($MAINCFG);

$READFILE = new readFile($MAINCFG);

// check-stuff
if(!$CHECKIT->check_user(1)) {
	exit;
}
if(!$CHECKIT->check_gd(1)) {
	exit;	
}
if(!$CHECKIT->check_permissions($MAPCFG->getValue('global','','allowed_user'),1)) {
	exit;
}
if(!$MAPCFG->checkMapImageReadable(1)) {
	exit;
}
if(!$CHECKIT->check_langfile(1)) {
	exit;
}
$MAINCFG->setRuntimeValue('rotateUrl',$CHECKIT->check_rotate());

$FRONTEND->openSite();

// Create Header-Menu, when enabled
if ($MAINCFG->getValue('global', 'displayheader') == "1") {
	$Menu = $READFILE->readMenu();
	$FRONTEND->makeHeaderMenu($Menu);
}

$FRONTEND->printMap();

// Handle all objects of type "map"
if(count($MAPCFG->getDefinitions('map')) > 0) {
	foreach ($MAPCFG->getDefinitions('map') as $index => $map) {
		unset($mapState);
		unset($stateState); 
		
		$SUBMAPCFG = new MapCfg($MAINCFG,$map['name']);
		
		if($SUBMAPCFG->checkMapConfigReadable(0)) {
			$SUBMAPCFG->readMapConfig();
			
			if(is_array($SUBMAPCFG->getDefinitions('host'))){
				foreach ($SUBMAPCFG->getDefinitions('host') as $index => $host) {
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
			if(is_array($SUBMAPCFG->getDefinitions('hostgroup'))){
				foreach ($SUBMAPCFG->getDefinitions('hostgroup') as $index => $hostgroup) {
					$stateState = $BACKEND->checkStates($hostgroup['type'],$hostgroup['name'],$hostgroup['recognize_services'],$hostgroup['service_description'],0);
					$mapState[] = $stateState['State'];
				}
			}
			if(is_array($SUBMAPCFG->getDefinitions('service'))){
				foreach ($SUBMAPCFG->getDefinitions('service') as $index => $service) {
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
			if (is_array($SUBMAPCFG->getDefinitions('servicegroup'))){
				foreach ($SUBMAPCFG->getDefinitions('servicegroup') as $index => $servicegroup) {
					$stateState = $BACKEND->checkStates($servicegroup['type'],$servicegroup['name'],$servicegroup['recognize_services'],$servicegroup['service_description'],0);
					$mapState[] = $stateState['State'];
				}
			}
			
			if(in_array("DOWN", $mapState) || in_array("CRITICAL", $mapState)){
				$state['State'] = "CRITICAL";
				$state['Output'] = "State of child Map is CRITICAL";
				$Icon = $FRONTEND->findIcon($state,$map,$map['type']);
			}elseif(in_array("WARNING", $mapState)){
				$state['State'] = "WARNING";
				$state['Output'] = "State of child Map is WARNING";
				$Icon = $FRONTEND->findIcon($state,$map,$map['type']);
			}elseif(in_array("UNKNOWN", $mapState)){
				$state['State'] = "UNKNOWN";
				$state['Output'] = "State of child Map is UNKNOWN";
				$Icon = $FRONTEND->findIcon($state,$map,$map['type']);
			}elseif(in_array("ERROR", $mapState)){
				$state['State'] = "ERROR";
				$state['Output'] = "State of child Map is ERROR";
				$Icon = $FRONTEND->findIcon($state,$map,$map['type']);
			}else{
				$state['State'] = "OK";
				$state['Output'] = "State of child map is OK";
				$Icon = $FRONTEND->findIcon($state,$map,$map['type']);
			}
		} else {
			$state['State'] = "UNKNOWN";
			$state['Output'] = "Child Map not readable";
			$Icon = $FRONTEND->findIcon($state,$map,$map['type']);
		}
	}
	
	$IconPosition = $FRONTEND->fixIconPosition($Icon,$map['x'],$map['y']);
	$FRONTEND->site[] = $IconPosition;
	$Box = $FRONTEND->infoBox($map['type'],$map['name'],$map['service_description'],$state);
	$FRONTEND->site[] = '<A HREF="./index.php?map='.$map['name'].'" TARGET="_self"><IMG SRC='.$MAINCFG->getValue('paths', 'htmlicon').$Icon.' '.$Box.'; BORDER="0"></A>';
	$FRONTEND->site[] = "</DIV>";
}

// Handle objects of type "textbox"
if(is_array($MAPCFG->getDefinitions('textbox'))) {
	foreach ($MAPCFG->getDefinitions('textbox') as $index => $textbox) {
		$TextBox = $FRONTEND->TextBox($textbox['x'],$textbox['y'],$textbox['w'],$textbox['text']);
		$FRONTEND->site[] = $TextBox;
	}	
}

// Handle the type host.
if (is_array($MAPCFG->getDefinitions('host'))){
	$debug[] = $DEBUG->debug_insertInfo($MAINCFG->getValue('global', 'debugstates'),'Handle the type host:');
	foreach ($MAPCFG->getDefinitions('host') as $index => $host) {
		$recognize_services = $FRONTEND->checkOption($host['recognize_services'],$MAINCFG->getValue('global', 'recognize_services'),"1");
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
			$debug[] = $DEBUG->debug_checkState($MAINCFG->getValue('global', 'debugstates'),$MAINCFG->getValue('global', 'debugcheckstate'),$index);

			$Icon = $FRONTEND->findIcon($state,$host,$host['type']);
			$debug[] = $DEBUG->debug_fixIcon($MAINCFG->getValue('global', 'debugstates'),$MAINCFG->getValue('global', 'debugfixicon'),$index);
	
			$IconPosition = $FRONTEND->fixIconPosition($Icon,$host['x'],$host['y']);
			$FRONTEND->site[] = $IconPosition;
			$Box = $FRONTEND->infoBox($host['type'],$host['name'],$host['service_description'],$state);
			$FRONTEND->site[] = $FRONTEND->createLink($MAINCFG->getValue('paths', 'htmlcgi'),$host['url'],$host['type'],$host['name'],$host['service_description']);
			$FRONTEND->site[] = '<IMG SRC='.$MAINCFG->getValue('paths', 'htmlicon').$Icon.' '.$Box.';></A>';
			$FRONTEND->site[] = "</DIV>";
			$debug[] = $DEBUG->debug_insertRow($MAINCFG->getValue('global', 'debugstates'));
		}
	}
}


// Handle the type hostgroup.
if (is_array($MAPCFG->getDefinitions('hostgroup'))){
	$debug[] = $DEBUG->debug_insertInfo($MAINCFG->getValue('global', 'debugstates'),'Handle the type hostgroup:');
	foreach ($MAPCFG->getDefinitions('hostgroup') as $index => $hostgroup) {
		$recognize_services = $FRONTEND->checkOption($hostgroup['recognize_services'],$MAINCFG->getValue('global', 'recognize_services'),"1");
		$state = $BACKEND->checkStates($hostgroup['type'],$hostgroup['name'],$recognize_services,$hostgroup['service_description'],0);
		$debug[] = $DEBUG->debug_checkState($MAINCFG->getValue('global', 'debugstates'),$MAINCFG->getValue('global', 'debugcheckstate'),$index);

		$Icon = $FRONTEND->findIcon($state,$hostgroup,$hostgroup['type']);
		$debug[] = $DEBUG->debug_fixIcon($MAINCFG->getValue('global', 'debugstates'),$MAINCFG->getValue('global', 'debugfixicon'),$index);
		
		$IconPosition = $FRONTEND->fixIconPosition($Icon,$hostgroup['x'],$hostgroup['y']);
		$FRONTEND->site[] = $IconPosition;
	
		$Box = $FRONTEND->infoBox($hostgroup['type'],$hostgroup['name'],$hostgroup['service_description'],$state);
		$FRONTEND->site[] = $FRONTEND->createLink($MAINCFG->getValue('paths', 'htmlcgi'),$hostgroup['url'],$hostgroup['type'],$hostgroup['name'],$hostgroup['service_description']);
		$FRONTEND->site[] = '<IMG SRC='.$MAINCFG->getValue('paths', 'htmlicon').$Icon.' '.$Box.';></A>';
		$FRONTEND->site[] = "</DIV>";
		$debug[] = $DEBUG->debug_insertRow($MAINCFG->getValue('global', 'debugstates'));
	}
}

// Handle the type service.
if (is_array($MAPCFG->getDefinitions('service'))){
	$debug[] = $DEBUG->debug_insertInfo($MAINCFG->getValue('global', 'debugstates'),'Handle the type service:');
	foreach ($MAPCFG->getDefinitions('service') as $index => $service) {
		$recognize_services = $FRONTEND->checkOption($service['recognize_services'],$MAINCFG->getValue('global', 'recognize_services'),"1");
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
			$debug[] = $DEBUG->debug_checkState($MAINCFG->getValue('global', 'debugstates'),$MAINCFG->getValue('global', 'debugcheckstate'),$index);

			$Icon = $FRONTEND->findIcon($state,$service,$service['type']);
			$debug[] = $DEBUG->debug_fixIcon($MAINCFG->getValue('global', 'debugstates'),$MAINCFG->getValue('global', 'debugfixicon'),$index);
	
			$IconPosition = $FRONTEND->fixIconPosition($Icon,$service['x'],$service['y']);
			$FRONTEND->site[] = $IconPosition;
	
			$Box = $FRONTEND->infoBox($service['type'],$service['name'],$service['service_description'],$state);
			$FRONTEND->site[] = $FRONTEND->createLink($MAINCFG->getValue('paths', 'htmlcgi'),$service['url'],$service['type'],$service['name'],$service['service_description']);
			$FRONTEND->site[] = '<IMG SRC='.$MAINCFG->getValue('paths', 'htmlicon').$Icon.' '.$Box.';></A>';
			$FRONTEND->site[] = "</DIV>";
			$debug[] = $DEBUG->debug_insertRow($MAINCFG->getValue('global', 'debugstates'));
		}
	}
}

// Handle the type servicegroup.
if (is_array($MAPCFG->getDefinitions('servicegroup'))){
	$debug[] = $DEBUG->debug_insertInfo($MAINCFG->getValue('global', 'debugstates'),'Handle the type servicegroup:');
	foreach ($MAPCFG->getDefinitions('servicegroup') as $index => $servicegroup) {
	$recognize_services = $FRONTEND->checkOption($servicegroup['recognize_services'],$MAINCFG->getValue('global', 'recognize_services'),"1");
	$state = $BACKEND->checkStates($servicegroup['type'],$servicegroup['name'],$recognize_services,$servicegroup['service_description'],0);
		$debug[] = $DEBUG->debug_checkState($MAINCFG->getValue('global', 'debugstates'),$MAINCFG->getValue('global', 'debugcheckstate'),$index);

		$Icon = $FRONTEND->findIcon($state,$servicegroup,$servicegroup['type']);
		$debug[] = $DEBUG->debug_fixIcon($MAINCFG->getValue('global', 'debugstates'),$MAINCFG->getValue('global', 'debugfixicon'),$index);
	
		$IconPosition = $FRONTEND->fixIconPosition($Icon,$servicegroup['x'],$servicegroup['y']);
		$FRONTEND->site[] = $IconPosition;
	
		$Box = $FRONTEND->infoBox($servicegroup['type'],$servicegroup['name'],$servicegroup['service_description'],$state);
		$FRONTEND->site[] = $FRONTEND->createLink($MAINCFG->getValue('paths', 'htmlcgi'),$servicegroup['url'],$servicegroup['type'],$servicegroup['name'],$servicegroup['service_description']);
		$FRONTEND->site[] = '<IMG SRC='.$MAINCFG->getValue('paths', 'htmlicon').$Icon.' '.$Box.';></A>';
		$FRONTEND->site[] = "</DIV>";
		$debug[] = $DEBUG->debug_insertRow($MAINCFG->getValue('global', 'debugstates'));
	}
}

$FRONTEND->debug($debug);
$FRONTEND->closeSite();
$FRONTEND->printSite();

?>
