<?php
#FIXME: Insert Plausis if files are there and readable!
include("./includes/classes/class.Graphic.php");
include("./includes/classes/class.NagVisConfig.php");
include("./includes/classes/class.MapCfg.php");
include("./includes/classes/class.Common.php");
include("./includes/classes/class.NagVis.php");
include("./includes/classes/class.CheckIt.php");
include("./includes/classes/class.ReadFiles.php");

$MAINCFG = new MainNagVisCfg('./etc/config.ini.php');
$MAPCFG = new MapCfg($MAINCFG,$_GET['map']);
$MAPCFG->readMapConfig();
$CHECKIT = new checkit($MAINCFG,$MAPCFG);

$FRONTEND = new frontend($MAINCFG,$MAPCFG);
$readfile = new readFile($MAINCFG,$MAPCFG);

include("./includes/classes/class.CheckState_".$MAINCFG->getValue('global', 'backend').".php");
$BACKEND = new backend($MAINCFG);

if(!$CHECKIT->check_user(0)) {
	errorBox('no user found!');
}
if(!$CHECKIT->check_permissions($MAPCFG->getValue('global','','allowed_user'),0)) {
	errorBox('You don\'t have permission to open this!');
}
if(!$MAPCFG->checkMapImageReadable(0)) {
	errorBox('The defined image isn\'t readable!');
}

//DEPRECATED:
//if(file_exists($MAINCFG->getValue('paths', 'mapcfg').$map.".cfg")) {
//	$mapCfg = $readfile->readNagVisCfg($map);
//	$allowed_users = explode(",",trim($mapCfg[1]['allowed_user']));
//	$map_image_array = explode(",",trim($mapCfg[1]['map_image']));
//	$map_image=$map_image_array[0];
//}

// Bild initalisieren
$image_type = explode('.', $MAPCFG->getImage());

switch(strtolower($image_type[1])) {
	case 'jpg':
		$im = @imagecreatefromjpeg($MAINCFG->getValue('paths', 'map').$MAPCFG->getImage());
	break;
	case 'png':
		$im = @imagecreatefrompng($MAINCFG->getValue('paths', 'map').$MAPCFG->getImage());
	break;
	default:
		errorBox('Only PNG and JPG Map-Image extensions are allowed');
	break;
}

$ok=imagecolorallocate($im, 0,255,0);
$warning=imagecolorallocate($im, 255, 255, 0);
$critical=imagecolorallocate($im, 255, 0, 0);
$unknown=imagecolorallocate($im, 255, 128, 0);

function GetColor($state){
	global $unknown,$ok,$critical,$warning;
	if($state == 'OK' || $state == 'UP'){
	$color = $ok;
	}elseif($state == 'WARNING'){
	$color = $warning;
	}elseif($state == 'CRITICAL' || $state == 'DOWN'){
	$color = $critical;
	}else{
	$color = $unknown;
	}
	return($color);
}

$types = array("global","host","service","hostgroup","servicegroup","map","textbox");
foreach($types AS $key => $type) {
	foreach($MAPCFG->getDefinitions($type) AS $key2 => $obj) {
		if(isset($obj['line_type'])) {
			if(!isset($obj['recognize_services'])) {
				$obj['recognize_services'] = 1;
			}
			if(!isset($obj['service_description'])) {
				$obj['service_description'] = "";
			}
			if(isset($obj['line_type'])) {
				if($type == 'service') {
					$name = 'host_name';
				} else {
					$name = $type.'_name';
				}
				if($obj['line_type'] == '10'){
					$state = $BACKEND->checkStates($obj['type'],$obj[$name],$obj['recognize_services'],$obj['service_description'],0);	
					list($x_from,$x_to) = explode(",", $obj['x']);
					list($y_from,$y_to) = explode(",", $obj['y']);
					$x_middle = middle($x_from,$x_to);
					$y_middle = middle($y_from,$y_to);
					draw_arrow($x_from,$y_from,$x_middle,$y_middle,3,1,GetColor($state['State']));
					draw_arrow($x_to,$y_to,$x_middle,$y_middle,3,1,GetColor($state['State']));
					
				}
				elseif($obj['line_type'] == '11'){
					$state = $BACKEND->checkStates($obj['type'],$obj[$name],$obj['recognize_services'],$obj['service_description'],0);	
					list($x_from,$x_to) = explode(",", $obj['x']);
					list($y_from,$y_to) = explode(",", $obj['y']);
					draw_arrow($x_from,$y_from,$x_to,$y_to,3,1,GetColor($state['State']));
				}
				elseif($obj['line_type'] == '20'){
					list($host_name_from,$host_name_to) = explode(",", $obj[$name]);
					list($service_description_from,$service_description_to) = explode(",", $obj['service_description']);
					$state_from = $BACKEND->checkStates($obj['type'],$host_name_from,$obj['recognize_services'],$service_description_from,1);	
					$state_to = $BACKEND->checkStates($obj['type'],$host_name_to,$obj['recognize_services'],$service_description_to,2);	
					list($x_from,$x_to) = explode(",", $obj['x']);
					list($y_from,$y_to) = explode(",", $obj['y']);
					$x_middle = middle($x_from,$x_to);
					$y_middle = middle($y_from,$y_to);
					draw_arrow($x_from,$y_from,$x_middle,$y_middle,3,1,GetColor($state_from['State']));
					draw_arrow($x_to,$y_to,$x_middle,$y_middle,3,1,GetColor($state_to['State']));
				}	
			}
		}
		$arrayPos++;
	}
}

switch(strtolower($image_type[1])) {
	case 'jpg':
		header('Content-type: image/jpeg');
		// HTTP/1.1
		header("Cache-Control: no-store, no-cache, must-revalidate");
		header("Cache-Control: post-check=0, pre-check=0", false);
		// HTTP/1.0
		header("Pragma: no-cache");
		imagejpeg($im);
		imagedestroy($im);
	break;
	case 'png':
		header('Content-type: image/png');
		// HTTP/1.1
		header("Cache-Control: no-store, no-cache, must-revalidate");
		header("Cache-Control: post-check=0, pre-check=0", false);
		// HTTP/1.0
		header("Pragma: no-cache");
		imagepng($im);
		imagedestroy($im);
	break;
	default: 
		// never reach this, error handling at the top
		exit;
	break;
}

function errorBox($msg) {
	$image = @imagecreate(600,50);
	$ImageFarbe = imagecolorallocate($image,243,243,243); 
	$schriftFarbe = imagecolorallocate($image,10,36,106);
	$schrift = imagestring($image, 5,10, 10, $msg, $schriftFarbe);
	
	header('Content-type: image/png');
	// HTTP/1.1
	header("Cache-Control: no-store, no-cache, must-revalidate");
	header("Cache-Control: post-check=0, pre-check=0", false);
	// HTTP/1.0
	header("Pragma: no-cache");
	imagepng($image);
	imagedestroy($image);
	
	exit;
}
?>