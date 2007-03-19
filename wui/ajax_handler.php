<?php
include("../nagvis/includes/classes/class.GlobalMainCfg.php");
include("./includes/classes/class.WuiMainCfg.php");
include("../nagvis/includes/classes/class.GlobalMapCfg.php");
include("./includes/classes/class.WuiMapCfg.php");
include("../nagvis/includes/classes/class.GlobalLanguage.php");
include("../nagvis/includes/classes/class.GlobalPage.php");
include("../nagvis/includes/classes/class.GlobalBackendMgmt.php");

$MAINCFG = new WuiMainCfg('../nagvis/etc/config.ini.php');

switch($_GET['action']) {
	case 'getObjects':
		$BACKEND = new GlobalBackendMgmt($MAINCFG);
		
		// $_GET['backend_id'], $_GET['type']
		if($_GET['backend_id']) {
			if(method_exists($BACKEND->BACKENDS[$_GET['backend_id']],'getObjects')) {
				echo '[ ';
				echo '{ "name": "" }';
				foreach($BACKEND->BACKENDS[$_GET['backend_id']]->getObjects($_GET['type'],'','') AS $arr) {
					echo ' ,{ "name": "'.$arr['name1'].'"}';
				}
				echo ']';
			}
		}
	break;
	case 'getServices':
		$BACKEND = new GlobalBackendMgmt($MAINCFG);
		
		// $_GET['backend_id'], $_GET['host_name']
		if(method_exists($BACKEND->BACKENDS[$_GET['backend_id']],'getObjects')) {
			echo '[ ';
			$i = 0;
			if(isset($_GET['host_name']) && $_GET['host_name'] != '') {
				foreach($BACKEND->BACKENDS[$_GET['backend_id']]->getObjects('service',$_GET['host_name'],'') AS $arr) {
					if($i != 0) {
						echo ', ';
					}
					echo '{ "host_name": "'.$arr['name1'].'", "service_description": "'.$arr['name2'].'"}';
					$i++;
				}
			}
			echo ']';
		}
	break;
	case 'getAllowedUsers':
		// $_GET['map'], $_GET['mode']
		if(isset($_GET['map']) && $_GET['map'] != '') {
			$MAPCFG = new WuiMapCfg($MAINCFG,$_GET['map']);
			$MAPCFG->readMapConfig();
			
			echo '[ ';
			if(isset($_GET['mode']) && $_GET['mode'] != '') {
				if($_GET['mode'] == 'read') {
					$arr = $MAPCFG->getValue('global', '0', 'allowed_user');
				} else {
					$arr = $MAPCFG->getValue('global', '0', 'allowed_for_config');
				}
				
				for($i = 0; count($arr) > $i; $i++) {
					if($i > 0) {
						echo ',';	
					}
					echo '\''.$arr[$i].'\' ';
				}
			}
			echo ' ]';
		}
	break;
	case 'getBackendOptions':
		// $_GET['backend_type'], ($_GET['backend_id'])
		if($_GET['backend_type'] == '' && $_GET['backend_id'] != '') {
			$_GET['backend_type'] = $MAINCFG->getValue('backend_'.$_GET['backend_id'],'backendtype');
		}
		
		echo '[ ';
		$i = 0;
		if($_GET['backend_type'] != '') {
			foreach($MAINCFG->validConfig['backend']['options'][$_GET['backend_type']] AS $key => $opt) {
				echo "\t";
				if($i != 0) {
					echo ', ';
				}
				echo '{ '."\n";
				echo "\t\t".'"key": "'.$key.'" '."\n";
				foreach($opt AS $var => $val) {
					echo "\t\t".', "'.$var.'": "'.$val.'" '."\n";
				}
				
				if(isset($_GET['backend_id']) && $_GET['backend_id'] != '' && $MAINCFG->getValue('backend_'.$_GET['backend_id'],$key,TRUE) != '') {
					echo ',  "value": "'.$MAINCFG->getValue('backend_'.$_GET['backend_id'],$key,TRUE).'" ';
				}
				
				echo "\t".' }'."\n";
				$i++;
			}
		}
		echo ' ]';
	break;
	case 'getMapImageInUse';
		// $_GET['image']
		echo '[ ';
		$i = 0;
		foreach($MAINCFG->getMaps() AS $var => $val) {
			$MAPCFG = new WuiMapCfg($MAINCFG,$val);
			$MAPCFG->readMapConfig();
			
			if($MAPCFG->getValue('global', 0,'map_image') == $_GET['image']) {
				if($i != 0) {
					echo ',';	
				}
				echo '"'.$val.'" ';
				$i++;
			}
		}
		echo ' ]';
	break;
	default:
	
	break;
}
?>

	



