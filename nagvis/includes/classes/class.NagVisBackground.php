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
		
		$this->initImage();
		
		//parent::GlobalMap($MAINCFG,$MAPCFG,$BACKEND);
		$this->checkPreflight();
		
		$this->objects = $this->getMapObjects(1);
	}
	
	function initImage() {
		$imageType = explode('.', $this->MAPCFG->getImage());
		$this->imageType = strtolower($imageType[1]);
		
		switch($this->imageType) {
			case 'jpg':
				$this->image = @imagecreatefromjpeg($this->MAINCFG->getValue('paths', 'map').$this->MAPCFG->getImage());
			break;
			case 'png':
				$this->image = @imagecreatefrompng($this->MAINCFG->getValue('paths', 'map').$this->MAPCFG->getImage());
			break;
			default:
				errorBox('Only PNG and JPG Map-Image extensions are allowed');
			break;
		}
		
		// set some options
		$this->intOk = imagecolorallocate($this->image, 0,255,0);
		$this->intWarning = imagecolorallocate($this->image, 255, 255, 0);
		$this->intCritical = imagecolorallocate($this->image, 255, 0, 0);
		$this->intUnknown = imagecolorallocate($this->image, 255, 128, 0);
		
		$this->GRAPHIC->init($this->image);
	}
	
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
	
	function checkPreflight() {
		if(!$this->MAPCFG->checkMapImageExists(0)) {
			errorBox('The defined image doesn\'t exists!');
		}
		if(!$this->MAPCFG->checkMapImageReadable(0)) {
			errorBox('The defined image isn\'t readable!');
		}
		// FIXME: Check permissions?
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
	 * Create a Link for a Line
	 *
	 * @param	Array	$obj	Array with object informations
	 * @param	String	$name	Key for Objectname eg. host_name
	 * @return	Array	Array with HTML Code
	 * @fixme 	FIXME: optimize
	 */
	function createBoxLine($obj,$name) {
		$ret = Array();
	    if($obj['line_type'] == '10' || $obj['line_type'] == '11'){
			list($x_from,$x_to) = explode(",", $obj['x']);
			list($y_from,$y_to) = explode(",", $obj['y']);
			$obj['x'] = middle($x_from,$x_to);
			$obj['y'] = middle($y_from,$y_to);
			$obj['icon'] = '20x20.gif';
			
			$obj = $this->fixIconPosition($obj);
			$ret = array_merge($ret,$this->parseIcon($obj));
		} elseif($mapCfg['line_type'] == '20') {
			list($host_name_from,$host_name_to) = explode(",", $mapCfg[$name]);
			list($service_description_from,$service_description_to) = explode(",", $mapCfg['service_description']);
			list($x_from,$x_to) = explode(",", $mapCfg['x']);
			list($y_from,$y_to) = explode(",", $mapCfg['y']);
			
			// From
			$obj['x'] = middle2($x_from,$x_to);
			$obj['y'] = middle2($y_from,$y_to);
			$obj['icon'] = '20x20.gif';
			
			$obj = $this->fixIconPosition($obj);
			$ret = array_merge($ret,$this->parseIcon($obj));
			
			// To
			$obj['x'] = middle2($x_to,$x_from);
			$obj['y'] = middle2($y_to,$y_from);
			$obj = $this->fixIconPosition($obj);
			$ret = array_merge($ret,$this->parseIcon($obj));
		}
		return $ret;
	}
	
	/**
	 * Parses the HTML-Code of an icon
	 *
	 * @param	Array	$obj	Array with object informations
	 * @return	Array	Array with Html Code
	 * @author	Lars Michelsen <larsi@nagios-wiki.de>
	 */
	function parseIcon($obj) {
		$ret = Array();
		
		if($obj['type'] == 'service') {
			$name = 'host_name';
		} else {
			$name = $obj['type'] . '_name';
		}
		
		$ret[] = "<div class=\"icon\" style=\"left:".$obj['x']."px; top:".$obj['y']."px;\">";
		$ret[] = "\t".$this->createLink($obj);
		$ret[] = "\t\t<img src=\"".$this->MAINCFG->getValue('paths', 'htmlicon').$obj['icon']."\" ".$this->getHoverMenu($obj).";>";
		$ret[] = "\t</a>";
		$ret[] = "</div>";
		
		return $ret;
	}
	
	/**
	 * Creates a link to Nagios, when this is not set in the Config-File
	 *
	 * @param	Array	$obj	Array with object informations
	 * @return	String	The Link
	 * @author 	Michael Luebben <michael_luebben@web.de>
	 * @author 	Lars Michelsen <larsi@nagios-wiki.de>
	 */
	function createLink($obj) {
		if($obj['type'] == 'service') {
			$name = 'host_name';
		} else {
			$name = $obj['type'] . '_name';
		}
		
		if(isset($obj['url'])) {
			$link = '<A HREF='.$obj['url'].'>';
    	} elseif($obj['type'] == 'map') {
			$link = '<A HREF="'.$this->MAINCFG->getValue('paths', 'htmlbase').'/index.php?map='.$obj[$name].'">';
    	}  elseif($obj['type'] == 'host') {
			$link = '<A HREF="'.$this->MAINCFG->getValue('paths', 'htmlcgi').'/status.cgi?host='.$obj[$name].'">';
    	} elseif($obj['type'] == 'service') {
			$link = '<A HREF="'.$this->MAINCFG->getValue('paths', 'htmlcgi').'/extinfo.cgi?type=2&host='.$obj[$name].'&service='.$obj['service_description'].'">';
    	} elseif($obj['type'] == 'hostgroup') {
			$link = '<A HREF="'.$this->MAINCFG->getValue('paths', 'htmlcgi').'/status.cgi?hostgroup='.$obj[$name].'&style=detail">';
    	} elseif($obj['type'] == 'servicegroup') {
			$link = '<A HREF="'.$this->MAINCFG->getValue('paths', 'htmlcgi').'/status.cgi?servicegroup='.$obj[$name].'&style=detail">';
    	}
    	return $link;
	}
	
	/**
	 * Create a Comment-Textbox
	 *
	 * @param	Array	$obj	Array with object informations
	 * @return	String	Array with HTML Code
	 * @author	Joerg Linge
	 * @author	Lars Michelsen <larsi@nagios-wiki.de>
	 */
	function textBox($obj) {
		$ret = Array();
		$ret[] = "<div class=\"".$obj['class']."\" style=\"background:".$obj['background_color'].";left: ".$obj['x']."px; top: ".$obj['y']."px; width: ".$obj['w']."px; overflow: visible;\">";	
		$ret[] = "\t<span>".$obj['text']."</span>";
		$ret[] = "</div>";
		return $ret;	
	}
	
	/**
	 * Creates a hover box for objects
	 *
	 * @param	Array	$obj	Array with object informations
	 * @return	String	Code for the hover box
	 * @author	Lars Michelsen <larsi@nagios-wiki.de>
     */
	function getHoverMenu($obj) {
		$ret = '';
		// FIXME: check if this is an object, where a menu should be displayed
		if(1) {
			$ret .= 'onmouseover="return overlib(\'';
			if($obj['hover_url']) {
				$ret .= $this->readHoverUrl($obj);
			} else {
				$ret .= $this->createInfoBox($obj);
			}
			$ret .= '\', CAPTION, \''.$this->LANG->getLabel($obj['type']).'\', SHADOW, WRAP, VAUTO);" onmouseout="return nd();" ';
			
			return $ret;
		}
	}
	
	/**
	 * Reads the given hover url form an object and forms it to a readable format for the hover box
	 *
	 * @param	Array	$obj	Array with object informations
	 * @return	String	Code for the hover box
	 * @author	Lars Michelsen <larsi@nagios-wiki.de>
     */
	function readHoverUrl($obj) {
		/* FIXME: Context is supported in php >= 5.0
		* $http_opts = array(
		*      'http'=>array(
		*      'method'=>"GET",
		*      'header'=>"Accept-language: en\r\n" .
		*                "Authorization: Basic ".base64_encode("user:pw"),
		*      'request_fulluri'=>true  ,
		*      'proxy'=>"tcp://proxy.url.de"
		*   )
		* );
		* $context = stream_context_create($http_opts);
		* $content = file_get_contents($obj['hover_url'],FALSE,$context);
		*/
		
		if(!$content = @file_get_contents($obj['hover_url'])) {
			$FRONTEND = new GlobalPage($this->MAINCFG,Array('languageRoot'=>'nagvis:global'));
	        $FRONTEND->messageToUser('WARNING','couldNotGetHoverUrl','URL~'.$obj['hover_url']);
		}
		return str_replace('"','\\\'',str_replace('\'','\\\'',$content));
	}
	
	/**
	 * Creates a Java-Box with information.
	 *
	 * @param	Array	$obj	Array with object informations
	 * @return	String	Code for the Hover-Box
	 * @author	Michael Luebben <michael_luebben@web.de>
	 * @author	Lars Michelsen <larsi@nagios-wiki.de>
	 * @fixme	FIXME: optimize
     */
	function createInfoBox($obj) {
		if($obj['type'] == 'service') {
			$name = 'host_name';
		} else {
			$name = $obj['type'] . '_name';
		}
		
		if(!isset($obj['stateCount'])) {
			$obj['stateCount'] = 0;
		}
		
		$Count = $obj['stateCount'];
		$obj['stateCount'] = str_replace('"',"",$obj['stateCount']);
		$obj['stateCount'] = str_replace("'","",$obj['stateCount']);
		$ServiceHostState = $obj['stateHost'];
		
		// FIXME mehr Output (ackComment, mehr Zahlen etc.)
		switch($obj['type']) {
			case 'host':
				$info .= '<b>'.$this->LANG->getLabel('hostname').':</b> '.$obj[$name].'<br>';
				$info .= '<b>'.$this->LANG->getLabel('state').':</b> '.$obj['state'].'<br>';
				$info .= '<b>'.$this->LANG->getLabel('output').':</b> '.strtr(addslashes($obj['stateOutput']), array("\r" => '<br>', "\n" => '<br>')).'<br>'; 
			break;
			case 'service':
				$info .= '<b>'.$this->LANG->getLabel('hostname').':</b> '.$obj[$name].'<br>';
				$info .= '<b>'.$this->LANG->getLabel('servicename').':</b> '.$obj['service_description'].'<br>';
				$info .= '<b>'.$this->LANG->getLabel('state').':</b> '.$obj['state'].'<br>';
				$info .= '<b>'.$this->LANG->getLabel('output').':</b> '.strtr(addslashes($obj['stateOutput']), array("\r" => '<br>', "\n" => '<br>')).'<br>';
			break;
			case 'hostgroup':
				$info .= '<b>'.$this->LANG->getLabel('hostgroupname').':</b> '.$obj[$name].'<br>';
				$info .= '<b>'.$this->LANG->getLabel('state').':</b> '.$obj['state'].'<br>';
				$info .= '<b>'.$this->LANG->getLabel('output').':</b> '.strtr(addslashes($obj['stateOutput']), array("\r" => '<br>', "\n" => '<br>')).'<br>'; 
			break;
			case 'servicegroup':
				$info .= '<b>'.$this->LANG->getLabel('servicegroupname').':</b> '.$obj[$name].'<br>';
				$info .= '<b>'.$this->LANG->getLabel('state').':</b> '.$obj['state'].'<br>';
				$info .= '<b>'.$this->LANG->getLabel('output').':</b> '.strtr(addslashes($obj['stateOutput']), array("\r" => '<br>', "\n" => '<br>')).'<br>'; 
			break;
			case 'map':
				$info .= '<b>'.$this->LANG->getLabel('mapname').':</b> '.$obj[$name].'<br>';
				$info .= '<b>'.$this->LANG->getLabel('state').':</b> '.strtr(addslashes($obj['state']), array("\r" => '<br>', "\n" => '<br>')).'<br>'; 
				$info .= '<b>'.$this->LANG->getLabel('output').':</b> '.strtr(addslashes($obj['stateOutput']), array("\r" => '<br>', "\n" => '<br>')).'<br>'; 
			break;
			default:
				//FIXME Error
			break;
		}
		return $info;
	}
}
