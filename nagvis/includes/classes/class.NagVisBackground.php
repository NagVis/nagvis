<?php
/**
 * Class for printing the map in NagVis
 */
class NagVisBackground extends GlobalMap {
	var $MAINCFG;
	var $MAPCFG;
	var $BACKEND;
	var $GRAPHIC;
	
	var $objects;
	var $image;
	var $imageType;
	
	var $intOk;
	var $intWarning;
	var $intCritical;
	var $intUnknown;
	
	/**
	 * Class Constructor
	 *
	 * @param 	GlobalMainCfg 	$MAINCFG
	 * @param 	GlobalMapCfg 	$MAPCFG
	 * @param 	GlobalBackend 	$BACKEND
	 * @author 	Lars Michelsen <larsi@nagios-wiki.de>
	 */
	function NagVisBackground(&$MAINCFG,&$MAPCFG,&$LANG,&$BACKEND) {
		$this->MAINCFG = &$MAINCFG;
		$this->MAPCFG = &$MAPCFG;
		$this->LANG = &$LANG;
		$this->BACKEND = &$BACKEND;
		
		$this->GRAPHIC = new GlobalGraphic();
		
		$this->checkPreflight();
		
		$this->initImage();
		
		//parent::GlobalMap($MAINCFG,$MAPCFG,$BACKEND);
		
		$this->objects = $this->getMapObjects(1);
	}
	
	function checkMemoryLimit($tryToFix=TRUE) {
		$fileSize = filesize($this->MAINCFG->getValue('paths', 'map').$this->MAPCFG->getImage());
		$memoryLimit = preg_replace('/[a-z]/i','',ini_get("memory_limit"))*1024*1024;
		
		$imageSize = getimagesize($this->MAINCFG->getValue('paths', 'map').$this->MAPCFG->getImage());
		if (is_array($imageSize)) {
			$imageWidth = $imageSize[0];
			$imageHeight = $imageSize[1];
			$imageDepth = $imageSize['bits'];
			
			// i think this should be calculated /8 also to get bytes, but removed it cause it works like it is atm
			$rawSize = $imageWidth*$imageHeight*$imageDepth;
		}
		
		
		// is fileSize or actual used memory + rawSize + 10% puffer bigger than memory limit?
		// there is no waranty that the calculations are correct but that could be a good basic
		// a) big file size
		// b) big image size
		// c) big color depth
		// DEBUG: echo $fileSize." > ".$memoryLimit." <bR>";
		// DEBUG: echo ((memory_get_usage() + $rawSize)*1.10)." > ".$memoryLimit;
		if($fileSize > $memoryLimit || (memory_get_usage() + $rawSize)*1.10 > $memoryLimit) {
			if($tryToFix) {
				ini_set("memory_limit",round((memory_get_usage() + $rawSize)*1.15 / 1024 /1024)."M");
				return $this->checkMemoryLimit(FALSE);
			}
			return FALSE;
		} else {
			return TRUE;
		}
	}
	
	function initImage() {
		$imageType = explode('.', $this->MAPCFG->getImage());
		$this->imageType = strtolower($imageType[1]);
		
		switch($this->imageType) {
			case 'jpg':
				$this->image = imagecreatefromjpeg($this->MAINCFG->getValue('paths', 'map').$this->MAPCFG->getImage());
			break;
			case 'png':
				$this->image = imagecreatefrompng($this->MAINCFG->getValue('paths', 'map').$this->MAPCFG->getImage());
			break;
			default:
				$this->errorBox($this->LANG->getMessageText('onlyPngOrJpgImages'));
			break;
		}
		
		// set some options
		$this->intOk = imagecolorallocate($this->image, 0,255,0);
		$this->intWarning = imagecolorallocate($this->image, 255, 255, 0);
		$this->intCritical = imagecolorallocate($this->image, 255, 0, 0);
		$this->intUnknown = imagecolorallocate($this->image, 255, 128, 0);
		
		$this->GRAPHIC->init($this->image);
	}
	
	/**
	 * Do preflight checks
	 *
	 * @author 	Lars Michelsen <larsi@nagios-wiki.de>
	 */
	function checkPreflight() {
		if(!$this->MAPCFG->checkMapImageExists(0)) {
			$this->errorBox($this->LANG->getMessageText('backgroundNotExists','IMGPATH~'.$this->MAINCFG->getValue('paths', 'map').$this->MAPCFG->getImage()));
		}
		if(!$this->MAPCFG->checkMapImageReadable(0)) {
			$this->errorBox($this->LANG->getMessageText('backgroundNotReadable','IMGPATH~'.$this->MAINCFG->getValue('paths', 'map').$this->MAPCFG->getImage()));
		}
		if(!$this->checkMemoryLimit()) {
			$this->errorBox($this->LANG->getMessageText('maybePhpMemoryLimitToLow'));	
		}
		// FIXME: Check map permissions?
	}
	
	/**
	 * Parses the Map and the Objects
	 *
	 * @return	Array 	Array with Html Code
	 * @author 	Lars Michelsen <larsi@nagios-wiki.de>
	 */
	function parseMap() {
		switch($this->imageType) {
			case 'jpg':
				header('Content-type: image/jpeg');
				// HTTP/1.1
				header("Cache-Control: no-store, no-cache, must-revalidate");
				header("Cache-Control: post-check=0, pre-check=0", false);
				// HTTP/1.0
				header("Pragma: no-cache");
				imagejpeg($this->image);
				imagedestroy($this->image);
			break;
			case 'png':
				header('Content-type: image/png');
				// HTTP/1.1
				header("Cache-Control: no-store, no-cache, must-revalidate");
				header("Cache-Control: post-check=0, pre-check=0", false);
				// HTTP/1.0
				header("Pragma: no-cache");
				imagepng($this->image);
				imagedestroy($this->image);
			break;
			default: 
				// never reach this, error handling at the top
				exit;
			break;
		}
	}
	
	/**
	 * Prints out an error box
	 *
	 * @param	String	$msg	String with error message
	 * @author 	Lars Michelsen <larsi@nagios-wiki.de>
	 */
	function errorBox($msg) {
		$this->image = @imagecreate(600,50);
		$this->imageType = 'png';
		$ImageFarbe = imagecolorallocate($this->image,243,243,243); 
		$schriftFarbe = imagecolorallocate($this->image,10,36,106);
		$schrift = imagestring($this->image, 5,10, 10, $msg, $schriftFarbe);
		
		$this->parseMap();
		exit;
	}
	
	/**
	 * Parses the Objects
	 *
	 * @return	Array 	Array with Html Code
	 * @author 	Lars Michelsen <larsi@nagios-wiki.de>
	 */
	function parseObjects() {
		$ret = Array();
		foreach($this->objects AS $obj) {
			switch($obj['type']) {
				case 'textbox':
					// Here is the place for NagVis 2.x parsing textboxes directly on the background
					/*// css class of the textbox
					$obj['class'] = "box";
					
					// default background color
					if($obj['background_color'] == '') {
						$obj['background_color'] = '#CCCCCC';
					} elseif($obj['background_color'] == 'transparent') {
						$obj['background_color'] = '';
					}
					
					// Check if set a hostname
					if(isset($obj['host_name'])) {
				  		// Output the Error-Message into the textbox.
						if ($obj['state'] == "ERROR") {
				  			$obj['text'] = $obj['stateOutput'];
				  		}
				  		
				  		if (in_array($obj['state'],Array("PENDING","UP","DOWN","UNREACHABLE","ERROR"))) {
				  			$obj['class'] = "box_".$obj['state'];
				  		}
						$ret = array_merge($ret,$this->textBox($obj));
					} else {
						$ret = array_merge($ret,$this->textBox($obj));
					}*/
				break;
				default:
					if(isset($obj['line_type'])) {
						$this->parseLine($obj);
					} else {
						// Here is the place for NagVis 2.x parsing icons directly on the background
						/*$obj = $this->fixIconPosition($obj);
						$ret = array_merge($ret,$this->parseIcon($obj));*/
					}
				break;	
			}
		}
		return $ret;
	}
	
	/**
	 * Parses a line on the image
	 *
	 * @param	Array	$obj
	 * @return	Array 	Array with Html Code
	 * @author 	Lars Michelsen <larsi@nagios-wiki.de>
	 */
	function parseLine($obj) {
		if($obj['type'] == 'service') {
			$name = 'host_name';
		} else {
			$name = $obj['type'].'_name';
		}
		
		if($obj['line_type'] == '10'){
			$state = $this->BACKEND->BACKENDS[$obj['backend_id']]->checkStates($obj['type'],$obj[$name],$obj['recognize_services'],$obj['service_description'],0);	
			list($x_from,$x_to) = explode(",", $obj['x']);
			list($y_from,$y_to) = explode(",", $obj['y']);
			$x_middle = $this->GRAPHIC->middle($x_from,$x_to);
			$y_middle = $this->GRAPHIC->middle($y_from,$y_to);
			
			$this->GRAPHIC->drawArrow($this->image,$x_from,$y_from,$x_middle,$y_middle,3,1,$this->getColor($state['State']));
			$this->GRAPHIC->drawArrow($this->image,$x_to,$y_to,$x_middle,$y_middle,3,1,$this->getColor($state['State']));
		} elseif($obj['line_type'] == '11') {
			$state = $this->BACKEND->BACKENDS[$obj['backend_id']]->checkStates($obj['type'],$obj[$name],$obj['recognize_services'],$obj['service_description'],0);	
			list($x_from,$x_to) = explode(",", $obj['x']);
			list($y_from,$y_to) = explode(",", $obj['y']);
			
			$this->GRAPHIC->drawArrow($this->image,$x_from,$y_from,$x_to,$y_to,3,1,$this->getColor($state['State']));
		} elseif($obj['line_type'] == '20') {
			list($host_name_from,$host_name_to) = explode(",", $obj[$name]);
			list($service_description_from,$service_description_to) = explode(",", $obj['service_description']);
			
			$state_from = $this->BACKEND->BACKENDS[$obj['backend_id']]->checkStates($obj['type'],$host_name_from,$obj['recognize_services'],$service_description_from,1);	
			$state_to = $this->BACKEND->BACKENDS[$obj['backend_id']]->checkStates($obj['type'],$host_name_to,$obj['recognize_services'],$service_description_to,2);	
			
			list($x_from,$x_to) = explode(",", $obj['x']);
			list($y_from,$y_to) = explode(",", $obj['y']);
			
			$x_middle = $this->GRAPHIC->middle($x_from,$x_to);
			$y_middle = $this->GRAPHIC->middle($y_from,$y_to);
			
			$this->GRAPHIC->drawArrow($this->image,$x_from,$y_from,$x_middle,$y_middle,3,1,$this->getColor($state_from['State']));
			$this->GRAPHIC->drawArrow($this->image,$x_to,$y_to,$x_middle,$y_middle,3,1,$this->getColor($state_to['State']));
		}		
	}
	
	/**
	 * Gets the color to print for the different states
	 *
	 * @param	String	$state
	 * @return	Integer		Color to use
	 * @author 	Lars Michelsen <larsi@nagios-wiki.de>
	 */
	function getColor($state){
		if($state == 'OK' || $state == 'UP') {
			$color = $this->intOk;
		} elseif($state == 'WARNING') {
			$color = $this->intWarning;
		} elseif($state == 'CRITICAL' || $state == 'DOWN') {
			$color = $this->intCritical;
		} else {
			$color = $this->intUnknown;
		}
		
		return $color;
	}
}
