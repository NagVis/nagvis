<?php
include("./includes/classes/class.Graphic.php");
include("./etc/config.inc.php");
include("./includes/classes/class.NagVis.php");
include("./includes/classes/class.ReadFiles.php");
include("./includes/classes/class.CheckState_".$StateClass.".php");

$map = $_GET['map'];

if(isset($_SERVER['PHP_AUTH_USER'])) {
        $user = $_SERVER['PHP_AUTH_USER'];
}
elseif(isset($_SERVER['REMOTE_USER'])) {
        $user = $_SERVER['REMOTE_USER'];
}

$nagvis = new NagVis();
$readfile = new readFile();

if(file_exists($cfgFolder.$map.".cfg")) {
        $mapCfg = $readfile->readNagVisCfg($map);
        $allowed_users = explode(",",trim($mapCfg[1]['allowed_user']));
        $map_image_array = explode(",",trim($mapCfg[1]['map_image']));
        $map_image=$map_image_array[0];
}

// Bild initalisieren
$map_image=$map_image;
$image_type = explode('.', $map_image);

switch(strtolower($image_type[1])) {
	case 'jpg':
		$im = @imagecreatefromjpeg($mapFolder.$map_image);
	break;
	case 'png':
		$im = @imagecreatefrompng($mapFolder.$map_image);
	break;
	default: 
		// Error-Box!
		print "Only PNG and JPG Map-Image Extensions are allowed";
		exit;
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


$checkstate = new checkState();
$countStates = count($mapCfg)-1;
$arrayPos="2";


for($x="1";$x<=$countStates;$x++) {
	if(isset($mapCfg[$arrayPos]['line_type'])) {

		if(!isset($mapCfg[$arrayPos]['recognize_services'])) {
			$mapCfg[$arrayPos]['recognize_services'] = 1;
		}
		if(!isset($mapCfg[$arrayPos]['service_description'])) {
			$mapCfg[$arrayPos]['service_description'] = "";
		}
		if(isset($mapCfg[$arrayPos]['line_type'])) {
			if($mapCfg[$arrayPos]['line_type'] == '10'){
				$state = $checkstate->checkStates($mapCfg[$arrayPos]['type'],$mapCfg[$arrayPos]['name'],$mapCfg[$arrayPos]['recognize_services'],$mapCfg[$arrayPos]['service_description'],0,$CgiPath,$CgiUser);	
				list($x_from,$x_to) = explode(",", $mapCfg[$arrayPos]['x']);
				list($y_from,$y_to) = explode(",", $mapCfg[$arrayPos]['y']);
				$x_middle = middle($x_from,$x_to);
				$y_middle = middle($y_from,$y_to);
				draw_arrow($x_from,$y_from,$x_middle,$y_middle,3,1,GetColor($state['State']));
				draw_arrow($x_to,$y_to,$x_middle,$y_middle,3,1,GetColor($state['State']));
				
			}
			elseif($mapCfg[$arrayPos]['line_type'] == '11'){
				$state = $checkstate->checkStates($mapCfg[$arrayPos]['type'],$mapCfg[$arrayPos]['name'],$mapCfg[$arrayPos]['recognize_services'],$mapCfg[$arrayPos]['service_description'],0,$CgiPath,$CgiUser);	
				list($x_from,$x_to) = explode(",", $mapCfg[$arrayPos]['x']);
				list($y_from,$y_to) = explode(",", $mapCfg[$arrayPos]['y']);
				draw_arrow($x_from,$y_from,$x_to,$y_to,3,1,GetColor($state['State']));
			}
			elseif($mapCfg[$arrayPos]['line_type'] == '20'){
				list($host_name_from,$host_name_to) = explode(",", $mapCfg[$arrayPos]['name']);
				list($service_description_from,$service_description_to) = explode(",", $mapCfg[$arrayPos]['service_description']);
				$state_from = $checkstate->checkStates($mapCfg[$arrayPos]['type'],$host_name_from,$mapCfg[$arrayPos]['recognize_services'],$service_description_from,1,$CgiPath,$CgiUser);	
				$state_to = $checkstate->checkStates($mapCfg[$arrayPos]['type'],$host_name_to,$mapCfg[$arrayPos]['recognize_services'],$service_description_to,2,$CgiPath,$CgiUser);	
				list($x_from,$x_to) = explode(",", $mapCfg[$arrayPos]['x']);
				list($y_from,$y_to) = explode(",", $mapCfg[$arrayPos]['y']);
				$x_middle = middle($x_from,$x_to);
				$y_middle = middle($y_from,$y_to);
				draw_arrow($x_from,$y_from,$x_middle,$y_middle,3,1,GetColor($state_from['State']));
				draw_arrow($x_to,$y_to,$x_middle,$y_middle,3,1,GetColor($state_to['State']));
			}	
		}
	}
	$arrayPos++;
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
		// Error-Box!
		exit;
	break;
}	

?>
