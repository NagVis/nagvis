<?
##########################################################################
##     	        NagVis - The Nagios Visualisation Addon                 ##
##########################################################################
## class.CheckState_html - Backend module to fetch the status from      ##
##             			   the Nagios CGIs. All not special to one 		##
##						   Backend related things should removed here!  ##
##						   (e.g. fixIcon, this is needed for ALL        ##
##						   Backends	)									##
##########################################################################
## Licenced under the terms and conditions of the GPL Licence, 			##
## please see attached "LICENCE" file	                                ##
##########################################################################


class backend
{
	//Initialize function, must be called before anything else of this class. Maybe we should set this as constructor.
	function backendInitialize() {
		//THIS backend needs no inital preparation, but the function must be present to be compatible
		return 0;
	}

	// Status für einen Host ermitteln.
	function findStateHost($Hostname,$RecognizeServices,$StatusCgi,$CgiUser) {
	        putenv("REQUEST_METHOD=GET");
                putenv("REMOTE_USER=$CgiUser");
                putenv("QUERY_STRING=host=$Hostname");
                $handle = popen($StatusCgi, 'r');
                $text = fread($handle, 9000);
                pclose($handle);
                
		if (preg_match('/0 Matching Service Entries Displayed/', $text)) {
			$state['State'] = 'UNKNOWN';
			$state['Count'] = '0';
			$state['Output'] = '0 Matching Service Entries Displayed';
                }
                else {
                        preg_match('/<TD CLASS=\'hostTotalsPROBLEMS\'>(.*)<\/TD>/', $text, $hostTotalsPROBLEMS);
                        preg_match('/<TD CLASS=\'hostTotalsUP\'>(.*)<\/TD>/', $text, $hostTotalsUP);
                        preg_match('/<TD CLASS=\'status(.*)\'>/', $text, $hostTotalsACK);
                }

                if(isset($hostTotalsACK) && $hostTotalsACK[1] == 'HOSTDOWNACK') {
                        $state['State'] = 'ACK';
                        $state['Count'] = $hostTotalsPROBLEMS[1];
                        $state['Output'] = $hostTotalsPROBLEMS[1].' Hosts DOWN';
                }
                elseif(isset($hostTotalsPROBLEMS[1]) && $hostTotalsPROBLEMS[1] != 0) {
                        $state['State'] = 'DOWN';
                        $state['Count'] = $hostTotalsPROBLEMS[1];
                        $state['Output'] = $hostTotalsPROBLEMS[1].' Hosts DOWN';
                }
                elseif(isset($hostTotalsUP[1]) && $hostTotalsUP[1] != 0) {
                        $state['State'] = 'UP';
                        $state['Count'] = $hostTotalsUP[1];
                        $state['Output'] = $hostTotalsUP[1].' Hosts UP';
                }
                else {
						$state['State'] = 'UNKNOWN';
                        $state['Count'] = '0';
                        $state['Output'] = 'HTML-Backend (CheckState_html) got NO DATA from the CGI while tring to parse a Host!';
                }

                if($RecognizeServices == 1) {
                        preg_match('/<TD CLASS=\'serviceTotalsOK\'>(.*)<\/TD>/', $text, $serviceTotalsOK);
                        preg_match('/<TD CLASS=\'serviceTotalsCRITICAL\'>(.*)<\/TD>/', $text, $serviceTotalsCRITICAL);
                        preg_match('/<TD CLASS=\'serviceTotalsWARNING\'>(.*)<\/TD>/', $text, $serviceTotalsWARNING);
                        
			if(isset($serviceTotalsCRITICAL[1]) && $serviceTotalsCRITICAL[1] != 0) {
				if(!isset($hostTotalsACK)) {
					$state['State'] = 'DOWN';
				}
				$state['Count'] = $serviceTotalsCRITICAL[1];
                                $state['Output'] = $serviceTotalsCRITICAL[1].' Services in state CRITICAL';
                        }
                        elseif(isset($serviceTotalsWARNING[1]) && $serviceTotalsWARNING != 0) {
				if(!isset($hostTotalsACK)) {
					$state['State'] = 'DOWN';
                                }
                                $state['Count'] = $serviceTotalsWARNING[1];
                                $state['Output'] = $serviceTotalsWARNING[1].' Service in state WARNING';
                        }
                        elseif(isset($serviceTotalsOK[1]) && $serviceTotalsOK[1] != 0) {
                                $state['Count'] = $serviceTotalsOK[1];
                                $state['Output'] = $serviceTotalsOK[1].' Services in state OK';
                        }
                }
                return($state);
        }
	
	// Status für eine Hostgroup ermitteln.
	function findStateHostgroup($Hostgroupname,$RecognizeServices,$StatusCgi,$CgiUser) {
		$rotateUrl = "";
		putenv("REQUEST_METHOD=GET");
		putenv("REMOTE_USER=$CgiUser");
		putenv("QUERY_STRING=hostgroup=$Hostgroupname");
                $handle = popen($StatusCgi, 'r');
                $text = fread($handle, 4000);
                pclose($handle);
		if (preg_match('/<DIV CLASS=\'errorMessage\'>Sorry, but host group(.*)<\/DIV>/', $text)) {

			$state['State'] = 'UNKNOWN';
			$state['Count'] = '0';
			$state['Output'] = 'Sorry, but host group '.$Hostgroupname.' not found';
		}
		else {
			preg_match('/<TD CLASS=\'hostTotalsPROBLEMS\'>(.*)<\/TD>/', $text, $hostTotalsPROBLEMS);
			preg_match('/<TD CLASS=\'hostTotalsUP\'>(.*)<\/TD>/', $text, $hostTotalsUP);
		}
		if(isset($hostTotalsPROBLEMS[1]) && $hostTotalsPROBLEMS[1] != 0) {
			$state['State'] = 'DOWN';
			$state['Count'] = $hostTotalsPROBLEMS[1];
			$state['Output'] = $hostTotalsPROBLEMS[1].' Host down';
		}
		elseif(isset($hostTotalsUP[1]) && $hostTotalsUP[1] != 0) {
			$state['State'] = 'UP';
			$state['Count'] = $hostTotalsUP[1];
			$state['Output'] = $hostTotalsUP[1].' Host up';
		}
		else {
			$state['State'] = 'UNKNOWN';
                        $state['Count'] = '0';
                        $state['Output'] = 'HTML-Backend (CheckState_html) got NO DATA from the CGI while tring to parse a Hostgroup!';
		}

		if($RecognizeServices == '1') {
			$rotateUrl = "";
			preg_match('/<TD CLASS=\'serviceTotalsOK\'>(.*)<\/TD>/', $text, $serviceTotalsOK);
                        preg_match('/<TD CLASS=\'serviceTotalsCRITICAL\'>(.*)<\/TD>/', $text, $serviceTotalsCRITICAL);
                        preg_match('/<TD CLASS=\'serviceTotalsWARNING\'>(.*)<\/TD>/', $text, $serviceTotalsWARNING);
	
			if(isset($serviceTotalsCRITICAL[1]) && $serviceTotalsCRITICAL[1] != 0) {
        	                $state['Count'] = $serviceTotalsCRITICAL[1];
				$state['Output'] = $serviceTotalsCRITICAL[1].' services in state CRITICAL';
                	}
	                elseif(isset($serviceTotalsWARNING[1]) && $serviceTotalsWARNING != 0) {
                	        $state['Count'] = $serviceTotalsWARNING[1];
				$state['Output'] = $serviceTotalsWARNING[1].' services in state WARNING';
	                }
        	        elseif(isset($serviceTotalsOK[1]) && $serviceTotalsOK[1] != 0) {
                        	$state['Count'] = $serviceTotalsOK[1];
				$state['Output'] = $serviceTotalsOK[1].' services in state OK';
			}else {
				$state['Count'] = '0';
				$state['Output'] = 'HTML-Backend (CheckState_html) got NO DATA from the CGI while tring to parse a Hostgroups Services!';
			}
		}
		return($state);
	}

	function findStateService($HostName,$ServiceName,$StatusCgi,$CgiUser) {
		$rotateUrl = "";
		putenv("REQUEST_METHOD=GET");
        putenv("REMOTE_USER=$CgiUser");
        putenv("QUERY_STRING=type=2&host=$HostName&service=$ServiceName");
        $handle = popen($StatusCgi, 'r');
        $text = fread($handle, 9000);
        pclose($handle);
		preg_match('/.*Current Status:<\/TD><TD CLASS=\'dataVal\'><DIV CLASS=\'(.*)\'>.*/', $text, $state);
		preg_match('/.*Status Information:<\/TD><TD CLASS=\'dataVal\'>(.*)<\/TD>.*/', $text, $output);
	        
		$HostAck=$this->findStateHost($HostName,'0','/usr/local/nagios/sbin/status.cgi',$CgiUser);
		
		if(!isset($output[1])){
			preg_match('/<P><DIV CLASS=\'errorMessage\'>(.*)<\/DIV><\/P>/', $text, $output);
		}
		
		if($state[1] == 'serviceOK') {
			$state['State'] = 'OK';
		}
		elseif($HostAck['State'] == 'ACK') {
			$state['State'] = 'SACK';
		}
		elseif($state[1] == 'serviceWARNING') {
			$state['State'] = 'WARNING';
		}
		elseif($state[1] == 'serviceCRITICAL') {
			$state['State'] = 'CRITICAL';
                }
		else{
			$state['State'] = 'UNKNOWN';
		}
                return $state;
        }

	function findStateServicegroup($ServiceGroup,$StatusCgi,$CgiUser) {
		$rotateUrl = "";
		putenv("REQUEST_METHOD=GET");
		putenv("REMOTE_USER=$CgiUser");
		putenv("QUERY_STRING=servicegroup=$ServiceGroup");
                $handle = popen($StatusCgi, 'r');
                $text = fread($handle, 4000);
                pclose($handle);
		if (preg_match('/<DIV CLASS=\'errorMessage\'>Sorry, but servicegroup(.*)<\/DIV>/', $text)) {
			$state['State'] = 'UNKNOWN';
			$state['Count'] = '0';
			$state['Output'] = 'Sorry, but servicegroup '.$ServiceGroup.' not found';
		}
		else {
			preg_match('/<TD CLASS=\'serviceTotalsOK\'>(.*)<\/TD>/', $text, $serviceTotalsOK);
			preg_match('/<TD CLASS=\'serviceTotalsCRITICAL\'>(.*)<\/TD>/', $text, $serviceTotalsCRITICAL);
			preg_match('/<TD CLASS=\'serviceTotalsWARNING\'>(.*)<\/TD>/', $text, $serviceTotalsWARNING);
		}

		if(isset($serviceTotalsCRITICAL[1]) && $serviceTotalsCRITICAL[1] != 0) {
			$state['State'] = 'CRITICAL';
			$state['Count'] = $serviceTotalsCRITICAL[1];
			$state['Output'] = $serviceTotalsCRITICAL[1].' Services in state CRITICAL';
		}
		elseif(isset($serviceTotalsWARNING[1]) && $serviceTotalsWARNING != 0) {
			$state['State'] = 'WARNING';
			$state['Count'] = $serviceTotalsWARNING[1];
			$state['Output'] = $serviceTotalsWARNING[1].' Services in state WARNING';
		}
		elseif(isset($serviceTotalsOK[1]) && $serviceTotalsOK[1] != 0) {
			$state['State'] = 'OK';
			$state['Count'] = $serviceTotalsOK[1];
			$state['Output'] = $serviceTotalsOK[1].' Services in state OK';

		}
		else {
			$state['State'] = 'UNKNOWN';
                        $state['Count'] = '0';
                        $state['Output'] = 'HTML-Backend (CheckState_html) got NO DATA from the CGI while tring to parse a Servicegroup';
		}

		return ($state);
	}


	// Höchsten Status aus einem Array ermitteln
	// FIXME: Ha: Move over to other class (not special html backend related)
	function findStateArray($stateArray) {
		$rotateUrl = "";
		unset($state);
		if(in_array("error", $stateArray)) {
			$state = "error";
		}
		elseif (in_array("UNKNOWN", $stateArray)) {
			$state = "UNKNOWN";
		}
		elseif (in_array("CRITICAL", $stateArray)) {
			$state = "CRITICAL";
		}
		elseif (in_array("DOWN", $stateArray)) {
			$state = "DOWN";
		}
		elseif (in_array("WARNING", $stateArray)) {
			$state = "WARNING";
		   }
		elseif (in_array("OK", $stateArray)) {
			$state = "OK";
		}
		elseif (in_array("UP", $stateArray)) {
			$state = "UP";
		}
		return($state);
	}
	
	// Status ermitteln
	function checkStates($Type,$Name,$RecognizeServices,$ServiceName="",$StatePos="0",$CgiPath,$CgiUser)
	{
		$rotateUrl = "";
		unset($state);
		//Status vom Host oder Service Prüfen.
		if($Type == "host") {
			$StatusCgi = $CgiPath.'status.cgi';
			if(isset($Name)) {
				$state = $this->findStateHost($Name,$RecognizeServices,$StatusCgi,$CgiUser);
			}
		}
		elseif($Type == "service") {	
			$StatusCgi = $CgiPath."extinfo.cgi";
			if(isset($Name) && isset($CgiUser)) {
				$state = $this->findStateService($Name,$ServiceName,$StatusCgi,$CgiUser);
                        }
                }

		elseif($Type == "hostgroup") {
			$StatusCgi = $CgiPath.'status.cgi';
			if(isset($Name) && isset($CgiUser)) {
				$state = $this->findStateHostgroup($Name,$RecognizeServices,$StatusCgi,$CgiUser);
			}
		}
		//Status einer Servicegroup prüfen.
		elseif($Type == "servicegroup") {
			$StatusCgi = $CgiPath.'status.cgi';
			if(isset($Name) && isset($CgiUser)) {
				$state = $this->findStateServicegroup($Name,$StatusCgi,$CgiUser);
			}
		}
		if(!isset($state)) {
			$nagvis = new NagVis();	
			$nagvis->openSite($rotateUrl);
			$nagvis->messageBox("12","Kein state");
			$nagvis->closeSite();
			$nagvis->printSite();
			exit;
		}	
		return($state);
	}
	
	function fixIcon($State,$mapCfg,$Index,$IconConfigCfg,$Type) {
		$rotateUrl = "";
		unset($Icon);
                $valid_format = array(
                        0=>"gif",
                        1=>"png",
                        2=>"bmp",
                        3=>"jpg",
                        4=>"jpeg"
                );
		$StateLow = strtolower($State['State']);
		if(!isset($mapCfg[$Index]['iconset'])) {
			$IconPath = "std_medium";
		}
		else {
			$IconPath = $mapCfg[$Index]['iconset'];
		}
		
		switch($Type) {
			case 'map':
				switch($StateLow) {
					case 'ok':
					case 'warning':
					case 'critical':
					case 'unknown':
					case 'ack':		
						$Icon = $IconPath.'_'.$StateLow;
					break;
					default:
						$Icon = $IconPath."_error";
					break;
				}
			break;
			case 'host':
			case 'hostgroup':
				switch($StateLow) {
					case 'down':
					case 'unknown':
					case 'critical':
					case 'unreachable':
					case 'warning':
					case 'ack':
					case 'up':
						$Icon = $IconPath.'_'.$StateLow;
					break;
					default:
						$Icon = $IconPath."_error";
					break;
				}
			break;
			case 'service':
			case 'servicegroup':
				switch($StateLow) {
					case 'critical':
					case 'warning':
					case 'sack':
					case 'unknown':
					case 'ok':
						$Icon = $IconPath.'_'.$StateLow;
					break;
					default:	
						$Icon = $IconPath."_error";
					break;
				}
			break;
			default:
					echo "Unkown Object Type!";
					$Icon = $mapCfg[$Index]['iconset']."_error";
			break;
		}

		for($i=0;$i<count($valid_format);$i++) {
			if(file_exists($Base."iconsets/".$Icon.".".$valid_format[$i])) {
                                $Icon .= ".".$valid_format[$i];
			}
		}
		if(file_exists($Base."iconsets/".$Icon)) {	
			return $Icon;
		}
		else {
			$Icon = $IconPath."_error.png";
			return $Icon;
		}
	}
	// FIXME: Ha: Move over to other class (not special html backend related)
	// Positionierung auf den Mittelpunkt der Icons umrechen
	// Jli
	function fixIconPosition($Icon,$x,$y) {
		$size = getimagesize("./iconsets/$Icon");
		$posy = $y-($size[1]/2);
		$posx = $x-($size[0]/2);
		$IconDIV = '<DIV CLASS="icon" STYLE="left: '.$posx.'px; top : '.$posy.'px">';
		return($IconDIV);
	}	
	// Comment Box
	function TextBox($x,$y,$width,$text) {
	
		$Comment = '<div class="box" style="left : '.$x.'px; top : '.$y.'px; width : '.$width.'px; overflow : visible;">';	
		$Comment .= '<span>'.$text.'</span>';
		$Comment .= '</div>';
		return($Comment);	
	
	}	
}
?>
