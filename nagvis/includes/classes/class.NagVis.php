<?php
##########################################################################
##     	                           NagVis                               ##
##        *** Klasse zum erzeugen der grafischen Oberfläche ***         ##
##                               Lizenz GPL                             ##
##########################################################################

/**
* This Class creates the Web-Frontend for NagVis
*/

class frontend extends common {
	var $site;
	var $MAINCFG;
	var $MAPCFG;
	
	/**
	* Constructor
	*
	* @param config $MAINCFG
	* @param config $MAPCFG
	*
	* @author Lars Michelsen <larsi@nagios-wiki.de>
	*/
	function frontend(&$MAINCFG,&$MAPCFG) {
		$this->MAINCFG = &$MAINCFG;
		$this->MAPCFG = &$MAPCFG;
		
		$this->getUser();
		//parent::common($this->MAINCFG,$this->MAPCFG);
	}
	
	/**
	* Gets the logged in User
	*
	* @author Lars Michelsen <larsi@nagios-wiki.de>
	*/
	function getUser() {
		if(isset($_SERVER['PHP_AUTH_USER'])) {
			$this->MAINCFG->setRuntimeValue('user',$_SERVER['PHP_AUTH_USER']);
		}
		elseif(isset($_SERVER['REMOTE_USER'])) {
			$this->MAINCFG->setRuntimeValue('user',$_SERVER['REMOTE_USER']);
		}
	}
	
	/**
	* Open a Web-Site in a Array site[].
	*
	* @author Michael Luebben <michael_luebben@web.de>
    */
	function openSite() {
		$this->site[] = '<HTML>';
		$this->site[] = '<HEAD>';
		$this->site[] = '<TITLE>'.$this->MAINCFG->getValue('internal', 'title').'</TITLE>';
		$this->site[] = '<META http-equiv="refresh" CONTENT="'.$this->MAINCFG->getValue('global', 'refreshtime').';'.$this->MAINCFG->getRuntimeValue('rotateUrl').'">';
		$this->site[] = '<SCRIPT TYPE="text/javascript" SRC="./includes/js/nagvis.js"></SCRIPT>';
		$this->site[] = '<SCRIPT TYPE="text/javascript" SRC="./includes/js/overlib.js"></SCRIPT>';
		$this->site[] = '<SCRIPT TYPE="text/javascript" SRC="./includes/js/overlib_shadow.js"></SCRIPT>';
		$this->site[] = '</HEAD>';
		$this->site[] = '<LINK HREF="./includes/css/style.css" REL="stylesheet" TYPE="text/css">';
		$this->site[] = '<BODY CLASS="main"  MARGINWIDTH="0" MARGINHEIGHT="0" TOPMARGIN="0" LEFTMARGIN="0">';
	}
	
	/**
	* Create a Web-Side from the Array site[].
	*
	* @author Michael Luebben <michael_luebben@web.de>
	*/
	function printSite() {
		foreach ($this->site as $row) {
			echo $row."\n";
		}
	}
	
	/**
	* Read the Configuration-File for the Header-Menu.
	*
	* @author Michael Lübben <michael_luebben@web.de>
	*/
	function readHeaderMenu() {
		if($this->checkHeaderConfigReadable(1)) {
			$Menu = file($this->MAINCFG->getValue('paths', 'cfg').$this->MAINCFG->getValue('includes', 'header'));
			$a = 0;
			$b = 0;
			
			while (isset($Menu[$a]) && $Menu[$a] != "") {
				if (!ereg("#",$Menu[$a]) && trim($Menu[$a]) != "") {
					$entry = explode(";",$Menu[$a]);
					$link[$b]['entry'] = $entry[0];
					$link[$b]['url'] = $entry[1];
					$b++;
				}
				$a++;
			}
			return $link;
		} else {
			return FALSE;
		}
	}
	
	/**
	* Create a Header-Menu
	*
	* @author Michael Luebben <michael_luebben@web.de>
	* @author Andreas Husch <michael_luebben@web.de>
    */
	function makeHeaderMenu() {
		$Menu = $this->readHeaderMenu();
		
		$x="0";
		$this->site[] = '<TABLE WIDTH="100%" BORDER="0" CLASS="headerMenu">';
		$this->site[] = '<DIV CLASS="header">';
		while(isset($Menu[$x]['entry']) && $Menu[$x]['entry'] != "") {
			$this->site[] = '<TR VALIGN="BOTTOM">';
			for($d="1";$d<=$this->MAINCFG->getValue('global', 'headercount');$d++) {
				if($Menu[$x]['entry'] != "") {
					$this->site[] = '<TD WIDTH="13">';
					$this->site[] = '<IMG SRC="images/greendot.gif" WIDTH="13" HEIGHT="14" NAME="'.$Menu[$x]['entry'].'_'.$x.'">';
					$this->site[] = '</TD>';
					$this->site[] = '<TD NOWRAP>';
					$this->site[] = '<A HREF='.$Menu[$x]['url'].' onMouseOver="switchdot(\''.$Menu[$x]['entry'].'_'.$x.'\',1)" onMouseOut="switchdot(\''.$Menu[$x]['entry'].'_'.$x.'\',0)" CLASS="NavBarItem">'.$Menu[$x]['entry'].'</A>';
					$this->site[] = '</TD>';
				}
				$x++;
			}
			$this->site[] = '</TR>';
		}
		$this->site[] = '</TABLE></DIV>';
	}
	
	/**
	* Checks for readable header config file
	*
	* @param string $printErr
	*
	* @author Lars Michelsen <larsi@nagios-wiki.de>
    */
	function checkHeaderConfigReadable($printErr) {
		if(file_exists($this->MAINCFG->getValue('paths', 'cfg').$this->MAINCFG->getValue('includes', 'header')) && is_readable($this->MAINCFG->getValue('paths', 'cfg').$this->MAINCFG->getValue('includes', 'header'))) {
			return TRUE;
		} else {
			if($printErr == 1) {
				$FRONTEND = new frontend($this->MAINCFG,$this->MAPCFG);
				$FRONTEND->openSite();
				//FIXME: need new error-definition
				$FRONTEND->messageBox("xxx", "");
				$FRONTEND->closeSite();
				$FRONTEND->printSite();
			}
			return FALSE;
		}
	}
	
	/**
	* Closed a Web-Site in the Array site[]
	*
	* @author Michael Luebben <michael_luebben@web.de>
    */
	function closeSite() {
		$this->site[] = '</DIV>';
		$this->site[] = '</BODY>';
		$this->site[] = '</HTML>';
	}
	
	/**
	* Create a Messagebox for informations and errors.
	*
	* Message-Numbers:
	*  0 = StatusCgi not found!
	*  1 = StatusCgi not executable!
	*  2 = No map config available!
	*  3 = No map image available!
	*  4 = No permissions!
	*  5 = No Nagios logfile!
	*  6 = No support!
	*  7 = Host not found!
	*  8 = Service not found!
	*  9 = Group is empty!
	* 10 = Servicegroup not found!
	* 11 = servicegroup-status!
	* 12 = State not defined!
	* 13 = Service-status!
	* 14 = No User!
	* 15 = GD Lib
	* 16 = wui/wui.function.inc.bash not executable
	*
	* @param string $messagnr
	* @param string $vars
	*
    * @author Michael Luebben <michael_luebben@web.de>
    */
	function messageBox($messagenr, $vars) {
		/*
		 * DEPRECATED
		 $LanguageFile = $this->MAINCFG->getValue('paths', 'cfg')."languages/".$this->MAINCFG->getValue('global', 'language').".txt";
        if(!file_exists($LanguageFile)) {
                $msg[0] = "XXXX";
                $msg[1] = "img_error.png";
                $msg[2] = "Languagefile not found!";
                $msg[3] = "Check if languagefile variable is set right in config.ini.php!";
        }
        elseif(!is_readable($LanguageFile)) {
                $msg[0] = "XXXX";
                $msg[1] = "img_error.png";
                $msg[2] = "Languagefile not readable!";
                $msg[3] = "Check permissions of languagefile ".$LanguageFile."!";
        }
        else {
			$fd=file($LanguageFile);
			if(!explode("~", $fd[$messagenr])) {
			    $msg[0] = "XXXX";
			    $msg[1] = "img_error.png";
			    $msg[2] = "Wrong error number.";
				$msg[3] = "Maybe error-number is not known.";
			}
			else {
			        $msg=explode("~", $fd[$messagenr]);
			}
        }*/
        
		$LANG = new NagVisLanguage($this->MAINCFG,$this->MAPCFG,'errors');
		$LANG->readLanguageFile();

		$vars = str_replace('~','=',$vars);
        $msg = $LANG->getTextReplace($messagenr,$vars);
        $msg = explode("~",$msg);
        
		$messageNr = $messagenr;
		$messageIcon = $msg[0];
		$messageHead = $msg[1];
		$message = $msg[2];
			
		//eval("\$message = \"$message\";");
		//for($i=0;$i<count(explode(' ', $vars));$i++) {
		//	$var = explode('~', $vars);
		//	$message = str_replace("[".$var[0]."]", $var[1], $message);
		//}
	
		$this->site[] = '<BODY>';
		$this->site[] = '<TABLE CLASS="messageBox" WIDTH="50%" ALIGN="CENTER">';
		$this->site[] = ' <TR>';
		$this->site[] = '  <TD CLASS="messageBoxHead" WIDTH="40">';
		$this->site[] = '   <IMG SRC="./images/'.$messageIcon.'" ALIGN="LEFT">';
		$this->site[] = '  </TD>';
		$this->site[] = '  <TD CLASS="messageBoxHead" ALIGN="CENTER">';
		$this->site[] =     $messagenr.":".$messageHead;
		$this->site[] = '  </TD>';
		$this->site[] = ' </TR>';
		$this->site[] = ' <TR>';
		$this->site[] = '  <TD CLASS="messageBoxMessage" ALIGN="CENTER" COLSPAN="2">';
		$this->site[] =     $message;
		$this->site[] = '  </TD>';
		$this->site[] = ' </TR>';
		$this->site[] = '</TABLE>';
	}
	
	/**
	* Create a Background-Image for a Map.
	*
	* @author Michael Luebben <michael_luebben@web.de>
	*/
	function printMap()	{
		$this->site[] = '  <DIV CLASS="map">';
		if ($this->MAINCFG->getValue('global', 'usegdlibs') == "1") {
			$this->site[] = '   <IMG SRC="./draw.php?map='.$this->MAPCFG->getName().'">';
		} else {
			$this->site[] = '   <IMG SRC="'.$this->MAINCFG->getValue('paths', 'htmlbase').$this->MAPCFG->getImage().'">';
		}
	}
	
	/**
	* Creates a Java-Box with information.
	*
	* @param string $Type
	* @param string $Hostname
	* @param string $ServiceDesc
	* @param array $stateArray
	*
	* @author Michael Luebben <michael_luebben@web.de>
    */
	function infoBox($Type,$Hostname,$ServiceDesc,$stateArray) {
		$Type = ucfirst($Type);
		$State = $stateArray['State'];
		$Output = $stateArray['Output'];
		if(!isset($stateArray['Count'])) {
			$stateArray['Count'] = 0;
		}
		$Count=$stateArray['Count'];
		$ServiceHostState = $stateArray['Host'];
		if(!isset($ServiceDesc)) {
			$ServiceDesc = $host_servicename;
		}
		// FIXME meht Output (ackComment, mehr Zahlen etc.)
		$Info = 'onmouseover="return overlib(\'';

		if($Type == "Host") {
			$Info .= '<b>Hostname:</b> '.$Hostname.'<br>';
			$Info .= '<b>State:</b> '.$State.'<br>';
			$Info .= '<b>Output:</b> '.strtr(addslashes($Output), array("\r" => '<br>', "\n" => '<br>')).'<br>'; 
		}
		elseif($Type == "Service") {
			$Info .= '<b>Hostname:</b> '.$Hostname.'<br>';
			$Info .= '<b>Servicename:</b> '.$ServiceDesc.'<br>';
			$Info .= '<b>State:</b> '.$State.'<br>';
			$Info .= '<b>Output:</b> '.strtr(addslashes($Output), array("\r" => '<br>', "\n" => '<br>')).'<br>';
		}
		elseif($Type == "Hostgroup") {
			$Info .= '<b>Hostgroup Name:</b> '.$Hostname.'<br>';
			$Info .= '<b>Hostgroup State:</b> '.$State.'<br>';
			$Info .= '<b>Output:</b> '.strtr(addslashes($Output), array("\r" => '<br>', "\n" => '<br>')).'<br>'; 
		}	
		elseif($Type == "Servicegroup") {
			$Info .= '<b>Servicegroup Name:</b> '.$Hostname.'<br>';
			$Info .= '<b>Servicegroup State:</b> '.$State.'<br>';
			$Info .= '<b>Output:</b> '.strtr(addslashes($Output), array("\r" => '<br>', "\n" => '<br>')).'<br>'; 
		}
		elseif($Type == "Map") {
			$Info .= '<b>Map Name:</b> '.$Hostname.'<br>';
			$Info .= '<b>Map State:</b> '.strtr(addslashes($State), array("\r" => '<br>', "\n" => '<br>')).'<br>'; 
			$Info .= '<b>Output:</b> '.strtr(addslashes($Output), array("\r" => '<br>', "\n" => '<br>')).'<br>'; 
		}
		$Info .= '\', CAPTION, \''.$Type.'\', SHADOW, WRAP, VAUTO);" onmouseout="return nd();" ';
		return($Info);
	}
	
	/**
	* Create a Debug-Output
	*
	* @param array $debug
	*
	* @author Michael Luebben <michael_luebben@web.de>
	*/
	function debug($debug)
	{
		if ($this->MAINCFG->getValue('debug', 'debug') == "1") {
			$this->site[] = '<TABLE CLASS="debugBox" WIDTH="90%" ALIGN="CENTER">';
			$this->site[] = ' <TR>';
			$this->site[] = '  <TD CLASS="debugBoxHead" WIDTH="40" ALIGN="CENTER">';
			$this->site[] = '   <IMG SRC="./images/img_debug.png">';
			$this->site[] = '  </TD>';
			$this->site[] = '  <TD CLASS="debugBoxHead" ALIGN="CENTER">';
			$this->site[] = '   Debugging';
			$this->site[] = '  </TD>';
			$this->site[] = '  <TD CLASS="debugBoxHead" WIDTH="40" ALIGN="CENTER">';
			$this->site[] = '   <IMG SRC="./images/img_debug.png">';
			$this->site[] = '  </TD>';
			$this->site[] = ' </TR>';
			$this->site[] = ' <TR>';
			$this->site[] = '  <TD CLASS="debugBoxMessage" ALIGN="LEFT" COLSPAN="3">';
			foreach ($debug as $debugArray) {
				if(is_array($debugArray)) {
					foreach ($debugArray as $row) {
						$this->site[] = $row."<BR>";
					}
				}
			}
			$this->site[] = '  </TD>';
			$this->site[] = ' </TR>';
			$this->site[] = '</TABLE>';
		}
	}
	
	/**
	* Create a Link for a Line
	*
	* @param array $mapCfg
	*
	* @author FIXME
	*/
	function createBoxLine($mapCfg,$state1,$state2,$name) {
	    if($mapCfg['line_type'] == '10' || $mapCfg['line_type'] == '11'){
			list($x_from,$x_to) = explode(",", $mapCfg['x']);
			list($y_from,$y_to) = explode(",", $mapCfg['y']);
			$x_middle = middle($x_from,$x_to);
			$y_middle = middle($y_from,$y_to);
			$IconPosition = $this->fixIconPosition('20x20.gif',$x_middle,$y_middle);
			$Box = $this->infoBox($mapCfg['type'],$mapCfg[$name],$mapCfg['service_description'],$state1);
			$this->site[] = $IconPosition;
			$this->site[] = $this->createLink($this->MAINCFG->getValue('paths', 'htmlcgi'),$mapCfg['url'],$mapCfg['type'],$mapCfg[$name],$mapCfg['service_description']);
			$this->site[] = '<img src=iconsets/20x20.gif '.$Box.';></A>';
			$this->site[] = '</div>';
		} elseif($mapCfg['line_type'] == '20') {
			list($host_name_from,$host_name_to) = explode(",", $mapCfg[$name]);
			list($service_description_from,$service_description_to) = explode(",", $mapCfg['service_description']);
			list($x_from,$x_to) = explode(",", $mapCfg['x']);
			list($y_from,$y_to) = explode(",", $mapCfg['y']);
			// From
			$x_middle = middle2($x_from,$x_to);
			$y_middle = middle2($y_from,$y_to);
			$IconPosition = $this->fixIconPosition('20x20.gif',$x_middle,$y_middle);
			$Box = $this->infoBox($mapCfg['type'],$host_name_from,$service_description_from,$state1);
			$this->site[] = $IconPosition;
			$this->site[] = $this->createLink($this->MAINCFG->getValue('paths', 'htmlcgi'),$mapCfg['url'],$mapCfg['type'],$host_name_from,$service_description_from);
			$this->site[] = '<img src=iconsets/20x20.gif '.$Box.';></A>';
			$this->site[] = '</div>';
			// To
			$x_middle = middle2($x_to,$x_from);
			$y_middle = middle2($y_to,$y_from);
			$IconPosition = $this->fixIconPosition('20x20.gif',$x_middle,$y_middle);
			$Box = $this->infoBox($mapCfg['type'],$host_name_to,$service_description_to,$state2);
			$this->site[] = $IconPosition;
			$this->site[] = $this->createLink($this->MAINCFG->getValue('paths', 'htmlcgi'),$mapCfg['url'],$mapCfg['type'],$host_name_to,$service_description_to);
			$this->site[] = '<img src=iconsets/20x20.gif '.$Box.';></A>';
			$this->site[] = '</div>';
		}
	}
	
	/**
	* Create a Comment-Textbox
	*
	* @param string $x
	* @param string $y
	* @param string $width
	* @param string $text
	*
	* @author Joerg Linge
	*/
	function TextBox($x,$y,$width,$text) {
		$Comment = '<div class="box" style="left : '.$x.'px; top : '.$y.'px; width : '.$width.'px; overflow : visible;">';	
		$Comment .= '<span>'.$text.'</span>';
		$Comment .= '</div>';
		return($Comment);	
	}
	
	/**
	* Gets all objects from a map and print it or just return an array with states
	*
	* @param object $MAPCFG
	* @param object $FRONTEND
	* @param object $BACKEND
	* @param object $DEBUG
	* @param boolean $print
	*
	* @author Lars Michelsen <larsi@nagios-wiki.de>
	*/
	function getMapObjects(&$MAPCFG,&$FRONTEND,&$BACKEND,&$DEBUG,$print=1) {
		$mapState[] = $this->getState($this->getObjects($MAPCFG,$FRONTEND,$BACKEND,$DEBUG,'map',$print));
		$mapState[] = $this->getState($this->getObjects($MAPCFG,$FRONTEND,$BACKEND,$DEBUG,'host',$print));
		$mapState[] = $this->getState($this->getObjects($MAPCFG,$FRONTEND,$BACKEND,$DEBUG,'service',$print));
		$mapState[] = $this->getState($this->getObjects($MAPCFG,$FRONTEND,$BACKEND,$DEBUG,'hostgroup',$print));
		$mapState[] = $this->getState($this->getObjects($MAPCFG,$FRONTEND,$BACKEND,$DEBUG,'servicegroup',$print));
		$mapState[] = $this->getState($this->getObjects($MAPCFG,$FRONTEND,$BACKEND,$DEBUG,'textbox',$print));
		
		return $mapState;
	}


	/**
	* Gets all objects of the defined type from a map and print it or just return an array with states
	*
	* @param object $MAPCFG
	* @param object $FRONTEND
	* @param object $BACKEND
	* @param object $DEBUG
	* @param string $type
	* @param boolean $print
	*
	* @author Lars Michelsen <larsi@nagios-wiki.de>
	*/
	function getObjects(&$MAPCFG,&$FRONTEND,&$BACKEND,&$DEBUG,$type,$print=1) {
		$objState = Array();
		
		if($type == 'service') {
			$name = 'host_name';
		} else {
			$name = $type . '_name';
		}
		
		if(is_array($MAPCFG->getDefinitions($type))){
			$debug[] = $DEBUG->debug_insertInfo($this->MAINCFG->getValue('debug', 'debugstates'),'Begin to handle the type '.$type.' in map '.$MAPCFG->getName());
			foreach($MAPCFG->getDefinitions($type) as $index => $obj) {
				if($type == 'map') {
					$SUBMAPCFG = new MapCfg($this->MAINCFG,$obj[$name]);
					$SUBMAPCFG->readMapConfig();
					$state = $this->getMapObjects($SUBMAPCFG,$FRONTEND,$BACKEND,$DEBUG,0);
					
					$objState[] = $this->getState($state);
					
					if($print == 1) {
						$stateArr = Array('State' => $this->getState($state), 'Output' => 'State of child Map is '.$this->getState($state));
						
						$Icon = $FRONTEND->findIcon($stateArr,$obj,$type);
						$IconPosition = $FRONTEND->fixIconPosition($Icon,$obj['x'],$obj['y']);
						$FRONTEND->site[] = $IconPosition;
						$Box = $FRONTEND->infoBox($type,$obj[$name],$obj['service_description'],$stateArr);
						$FRONTEND->site[] = '<A HREF="./index.php?map='.$obj[$name].'" TARGET="_self"><IMG SRC='.$this->MAINCFG->getValue('paths', 'htmlicon').$Icon.' '.$Box.'; BORDER="0"></A>';
						$FRONTEND->site[] = "</DIV>";
					}
				} elseif($type == 'textbox') {
					// handle objects of type "textbox"
					
					// draw only the textbox
					if($print == 1) {
						$FRONTEND->site[] = $FRONTEND->TextBox($obj['x'],$obj['y'],$obj['w'],$obj['text']);
					}
				} else {
					//handle all other objects...
					
					// get option to recognize the services - object, main-config, default
					$recognize_services = $FRONTEND->checkOption($obj['recognize_services'],$this->MAINCFG->getValue('global', 'recognize_services'),"1");
					
					// if object is a line...
					if(isset($obj['line_type'])) {
						if($obj['line_type'] != "20") {
							// a line with one object...
							$state = $BACKEND->checkStates($type,$obj[$name],$recognize_services,$obj['service_description'],0);
							$objState[] = $state['State'];
							
							if($print == 1) {
								$FRONTEND->createBoxLine($obj,$state,NULL,$name);
							}
						} else {
							// a line with two objects...
							list($obj_name_from,$obj_name_to) = explode(",", $obj[$name]);
							list($service_description_from,$service_description_to) = explode(",", $obj['service_description']);
							$state1 = $BACKEND->checkStates($type,$obj_name_from,$recognize_services,$service_description_from,1);
							$state2 = $BACKEND->checkStates($type,$obj_name_to,$recognize_services,$service_description_to,2);
							$objState[] = $state1['State'];
							$objState[] = $state2['State'];
							
							if($print == 1) {
								$FRONTEND->createBoxLine($obj,$state1,$state2,$name);
							}
						}
					} else {
						// get the state of the object - type, object name, recognize services - service description - state pos??
						$state = $BACKEND->checkStates($type,$obj[$name],$recognize_services,$obj['service_description'],0);
						$objState[] = $state['State'];
						$debug[] = $DEBUG->debug_checkState($this->MAINCFG->getValue('debug', 'debugstates'),$this->MAINCFG->getValue('debug', 'debugcheckstate'),$index);
						
						
						if($print == 1) {
							$Icon = $FRONTEND->findIcon($state,$obj,$type);
						
							$debug[] = $DEBUG->debug_fixIcon($this->MAINCFG->getValue('debug', 'debugstates'),$this->MAINCFG->getValue('debug', 'debugfixicon'),$index);
							$IconPosition = $FRONTEND->fixIconPosition($Icon,$obj['x'],$obj['y']);
							
							$FRONTEND->site[] = $IconPosition;
							
							$Box = $FRONTEND->infoBox($type,$obj[$name],$obj['service_description'],$state);
							$FRONTEND->site[] = $FRONTEND->createLink($this->MAINCFG->getValue('paths', 'htmlcgi'),$obj['url'],$type,$obj[$name],$obj['service_description']);
							$FRONTEND->site[] = '<IMG SRC='.$this->MAINCFG->getValue('paths', 'htmlicon').$Icon.' '.$Box.';></A>';
							$FRONTEND->site[] = "</DIV>";
							
							$debug[] = $DEBUG->debug_insertRow($this->MAINCFG->getValue('debug', 'debugstates'));
						}
					}
				}
			}
			$debug[] = $DEBUG->debug_insertInfo($this->MAINCFG->getValue('debug', 'debugstates'),'End of handle the type '.$type.' in map '.$MAPCFG->getName());
			
			return $objState;
		}
	}
	
	/**
	* Wraps all states in an Array to a summary state
	*
	* @param array $objState
	*
	* @author Lars Michelsen <larsi@nagios-wiki.de>
	*/ 
	function getState($objState) {
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
?>
