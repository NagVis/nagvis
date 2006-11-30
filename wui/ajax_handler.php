<?php
include("../nagvis/includes/classes/class.GlobalMainCfg.php");
include("../nagvis/includes/classes/class.GlobalMapCfg.php");
include("../nagvis/includes/classes/class.GlobalLanguage.php");
include("../nagvis/includes/classes/class.GlobalPage.php");
include("../nagvis/includes/classes/class.GlobalBackend-ndomy.php");
include("../nagvis/includes/classes/class.GlobalBackend-html.php");
include("../nagvis/includes/classes/class.GlobalBackendMgmt.php");

$MAINCFG = new GlobalMainCfg('../nagvis/etc/config.ini.php');
$BACKEND = new GlobalBackendMgmt($MAINCFG);

switch($_GET['action']) {
	case 'getObjects':
		// $_GET['backend_id'], $_GET['type']
		if(method_exists($BACKEND->BACKENDS[$_GET['backend_id']],'getObjects')) {
			echo '[ ';
			echo '{ "name": "" }';	
			foreach($BACKEND->BACKENDS[$_GET['backend_id']]->getObjects($_GET['type'],'','') AS $arr) {
				echo ' ,{ "name": "'.$arr['name1'].'"}';
			}
			echo ']';
		}
	break;
	case 'getServices':
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
			$MAPCFG = new GlobalMapCfg($MAINCFG,$_GET['map']);
			$MAPCFG->readMapConfig();
			
			echo '[ ';
			if(isset($_GET['mode']) && $_GET['mode'] != '') {
				if($_GET['mode'] == 'read') {
					$arr =  $MAPCFG->getValue('global', '0', 'allowed_user');
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
	default:
	
	break;
}
?>

	



