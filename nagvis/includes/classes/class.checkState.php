<?
##########################################################################
##     	                           NagVis                              ##
##             *** Klasse zum Prüfen ein eines Status ***               ##
##                               Lizenz GPL                             ##
##########################################################################

#############################################################################################
// Fehlercode Methode		Bechreibung				Ursache
//     -1     findIndex		Ein Index wurde nicht gefunden 		Host- oder Servicename falsch 
#############################################################################################

class checkState
{
	// Status für einen Host ermitteln.
        function findStateHost($Hostname,$RecognizeServices,$StatusCgi,$CgiUser) {
	        putenv("REQUEST_METHOD=GET");
                putenv("REMOTE_USER=$CgiUser");
                putenv("QUERY_STRING=host=$Hostname");
                $handle = popen($StatusCgi, 'r');
                $text = fread($handle, 4000);
                pclose($handle);

                if (preg_match('/0 Matching Service Entries Displayed/', $text)) {
			$state['State'] = 'UNKNOWN';
			$state['Count'] = '0';
			$state['Output'] = '0 Matching Service Entries Displayed';
                }
                else {
                        preg_match('/<TD CLASS=\'hostTotalsPROBLEMS\'>(.*)<\/TD>/', $text, $hostTotalsPROBLEMS);
                        preg_match('/<TD CLASS=\'hostTotalsUP\'>(.*)<\/TD>/', $text, $hostTotalsUP);
                }

                if(isset($hostTotalsPROBLEMS[1]) && $hostTotalsPROBLEMS[1] != 0) {
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
                        $state['Output'] = 'No data :-(';
                }
                //FIXME: Alle Counts (OK, WARNING, CRITICAL etc.) ausgeben


                if($RecognizeServices == 1) {
                        preg_match('/<TD CLASS=\'serviceTotalsOK\'>(.*)<\/TD>/', $text, $serviceTotalsOK);
                        preg_match('/<TD CLASS=\'serviceTotalsCRITICAL\'>(.*)<\/TD>/', $text, $serviceTotalsCRITICAL);
                        preg_match('/<TD CLASS=\'serviceTotalsWARNING\'>(.*)<\/TD>/', $text, $serviceTotalsWARNING);

                        if(isset($serviceTotalsCRITICAL[1]) && $serviceTotalsCRITICAL[1] != 0) {
                                $state['State'] = 'CRITICAL';
                                $state['Count'] = $serviceTotalsCRITICAL[1];
                                $state['Output'] = $serviceTotalsCRITICAL[1].' Services in state CRITICAL';
                        }
                        elseif(isset($serviceTotalsWARNING[1]) && $serviceTotalsWARNING != 0) {
                                $state['State'] = 'WARNING';
                                $state['Count'] = $serviceTotalsWARNING[1];
                                $state['Output'] = $serviceTotalsWARNING[1].' Service in state WARNING';
                        }
                        elseif(isset($serviceTotalsOK[1]) && $serviceTotalsOK[1] != 0) {
                                $state['State'] = 'OK';
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
                        $state['Output'] = 'No data :-(';

		}
		//FIXME: Alle Counts (OK, WARNING, CRITICAL etc.) ausgeben
		

		if($RecognizeServices == '1') {
			$rotateUrl = "";
			preg_match('/<TD CLASS=\'serviceTotalsOK\'>(.*)<\/TD>/', $text, $serviceTotalsOK);
                        preg_match('/<TD CLASS=\'serviceTotalsCRITICAL\'>(.*)<\/TD>/', $text, $serviceTotalsCRITICAL);
                        preg_match('/<TD CLASS=\'serviceTotalsWARNING\'>(.*)<\/TD>/', $text, $serviceTotalsWARNING);
	
			if(isset($serviceTotalsCRITICAL[1]) && $serviceTotalsCRITICAL[1] != 0) {
	                        $state['State'] = 'CRITICAL';
        	                $state['Count'] = $serviceTotalsCRITICAL[1];
				$state['Output'] = $serviceTotalsCRITICAL[1].' services in state CRITICAL';
                	}
	                elseif(isset($serviceTotalsWARNING[1]) && $serviceTotalsWARNING != 0) {
        	                $state['State'] = 'WARNING';
                	        $state['Count'] = $serviceTotalsWARNING[1];
				$state['Output'] = $serviceTotalsWARNING[1].' services in state WARNING';
	                }
        	        elseif(isset($serviceTotalsOK[1]) && $serviceTotalsOK[1] != 0) {
                	        $state['State'] = 'OK';
                        	$state['Count'] = $serviceTotalsOK[1];
				$state['Output'] = $serviceTotalsOK[1].' services in state OK';
			}else {
                        $state['State'] = 'UNKNOWN';
                        $state['Count'] = '0';
                        $state['Output'] = 'No data :-(';

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
                $text = fread($handle, 4000);
                pclose($handle);
		preg_match('/.*Current Status:<\/TD><TD CLASS=\'dataVal\'><DIV CLASS=\'(.*)\'>.*/', $text, $state);
		preg_match('/.*Status Information:<\/TD><TD CLASS=\'dataVal\'>(.*)<\/TD>.*/', $text, $output);
		
		if(!isset($output[1])){
			preg_match('/<P><DIV CLASS=\'errorMessage\'>(.*)<\/DIV><\/P>/', $text, $output);
		}

		$state['Output'] = $output[1];

		if($state[1] == 'serviceOK') {

			$state['State'] = "OK";
		}elseif($state[1] == 'serviceWARNING') {

			$state['State'] = "WARNING";

		}elseif($state[1] == 'serviceCRITICAL') {

			$state['State'] = "CRITICAL";

                }else{

			$state['State'] = "UNKNOWN";

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
                        $state['Output'] = 'No data :-(';
		}

		return ($state);
	}


		// Höchsten Status aus einem Array ermitteln
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
	
	// ******************** Icon ********************
	// Icon für den aktuellen Status ermitteln.
	// Position auf die Mitte des Icons verschieben
	function fixIcon($State,$mapCfg,$Index,$IconConfigCfg) {
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
		if(ereg("error",$StateLow)) {
			$Icon = "error";
		}
		elseif(isset($mapCfg[$Index]['iconset'])) {
			if(isset($State['Acknowledge']) && $State['Acknowledge'] == "1") {
				$Icon = $mapCfg[$Index]['iconset']."_acknowledged";
			}
			else {
				$Icon = $mapCfg[$Index]['iconset']."_".$StateLow;
			}
		}
		elseif(isset($mapCfg[1]['iconset'])) {
			if(isset($State['Acknowledge']) && $State['Acknowledge'] == "1") {
				$Icon = $mapCfg[1]['iconset']."_acknowledged";
			}
			else {
				$Icon = $mapCfg[1]['iconset']."_".$StateLow;
			}
		}
		else {
			if($State['Acknowledge'] == "1") {
				$Icon = $IconConfigCfg."_acknowledged";
			}
			else {
				$Icon = $IconConfigCfg."_".$StateLow;
			}
		}
		
		for($i=0;$i<count($valid_format);$i++) {
			if(file_exists($Base."iconsets/".$Icon.".".$valid_format[$i])) {
                                $Icon .= ".".$valid_format[$i];
			}
                }
		if(file_exists($Base."iconsets/".$Icon)) {	
		return($Icon);
		}else{
		$Icon = $mapCfg[1]['iconset']."_error.png";
		return $Icon;
		}
	}

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
