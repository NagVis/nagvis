<?php
/**
 * Class for printing the map in NagVis
 */
class NagVisMap extends GlobalMap {
	var $MAINCFG;
	var $MAPCFG;
	var $BACKEND;
	
	var $objects;
	
	/**
	 * Class Constructor
	 *
	 * @param 	GlobalMainCfg 	$MAINCFG
	 * @param 	GlobalMapCfg 	$MAPCFG
	 * @param 	GlobalBackend 	$BACKEND
	 * @author 	Lars Michelsen <larsi@nagios-wiki.de>
	 */
	function NagVisMap(&$MAINCFG,&$MAPCFG,&$LANG,&$BACKEND) {
		$this->MAINCFG = &$MAINCFG;
		$this->MAPCFG = &$MAPCFG;
		$this->LANG = &$LANG;
		$this->BACKEND = &$BACKEND;
		
		parent::GlobalMap($MAINCFG,$MAPCFG,$BACKEND);
		
		$this->objects = $this->getMapObjects(1);
	}
	
	/**
	 * Parses the Map and the Objects
	 *
	 * @return	Array 	Array with Html Code
	 * @author 	Lars Michelsen <larsi@nagios-wiki.de>
	 */
	function parseMap() {
		$ret = Array();
		$ret = array_merge($ret,$this->getBackground());
		$ret = array_merge($ret,$this->parseObjects());
		
		return $ret;
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
					// css class of the textbox
					$obj['class'] = "box";
					
					// default background color
					if($obj['background_color'] == '') {
						$obj['background_color'] = '#CCCCCC';
					} elseif($obj['background_color'] == 'solid') {
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
					}
				break;
				default:
					if(isset($obj['line_type'])) {
						if($obj['line_type'] != "20") {
							// a line with one object...
							$ret = array_merge($ret,$this->createBoxLine($obj,$obj['state'],NULL,$obj[$name]));
						} else {
							// a line with two objects...
							$ret = array_merge($ret,$this->createBoxLine($obj,$obj['state1'],$obj['state2'],$obj[$name]));
						}
					} else {
						$obj = $this->fixIconPosition($obj);
						$ret = array_merge($ret,$this->parseIcon($obj));
					}
				break;	
			}
		}
		return $ret;
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
		$ret[] = "<div class=\"".$obj['class']."\" style=\"background-color:".$obj['background_color'].";left: ".$obj['x']."px; top: ".$obj['y']."px; width: ".$obj['w']."px; overflow: visible;\">";	
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
	
	/**
	 * Wraps all states in an Array to a summary state
	 *
	 * @param	Array	$objState	Array with States in it
	 * @return	String	Status (OK|WARNING|CRITICAL|UNKNOWN|ERROR)
	 * @author Lars Michelsen <larsi@nagios-wiki.de>
	 */ 
	function wrapState($objState) {
		if(in_array("DOWN", $objState) || in_array("CRITICAL", $objState)) {
			return "CRITICAL";
		} elseif(in_array("WARNING", $objState)) {
			return "WARNING";
		} elseif(in_array("UNKNOWN", $objState)) {
			return "UNKNOWN";
		} elseif(in_array("ERROR", $objState)) {
			return "ERROR";
		} else {
			return "OK";
		}
	}
}
