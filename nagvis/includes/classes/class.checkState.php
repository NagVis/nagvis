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
                $text = chop(`$StatusCgi`);
                if (preg_match('/0 Matching Service Entries Displayed/', $text)) {
			$state['State'] = 'UNKNOWN';
			$state['Count'] = '0';
			$state['Output'] .= '<br>->0 Matching Service Entries Displayed';
                }
                else {
                        preg_match('/<TD CLASS=\'hostTotalsPROBLEMS\'>(.*)<\/TD>/', $text, $hostTotalsPROBLEMS);
                        preg_match('/<TD CLASS=\'hostTotalsUP\'>(.*)<\/TD>/', $text, $hostTotalsUP);
			preg_match('/CLASS=\'statusHOSTDOWNACK\'><A HREF=\'extinfo.cgi\?type=1&host=.*\'>(.*)<\/A>/i', $text, $hostack);
		}
		
                if(isset($hostTotalsPROBLEMS[1]) && $hostTotalsPROBLEMS[1] != 0) {
                        $state['State'] = 'DOWN';
                        $state['Count'] = $hostTotalsPROBLEMS[1];
                        $state['Output'] .= '<br>->'.$hostTotalsPROBLEMS[1].' Hosts DOWN';
			if(isset($hostack[1])) {
				$state['Acknowledge'] = 1;
			}
			else {
				$state['Acknowledge'] = 0;
				$state['Output'] .= '<br>->host '.$Hostname.' is not acknowledged!!!';
			}
                }
                elseif(isset($hostTotalsUP[1]) && $hostTotalsUP[1] != 0) {
                        $state['State'] = 'UP';
                        $state['Count'] = $hostTotalsUP[1];
                        $state['Output'] .= '<br>->'.$hostTotalsUP[1].' Hosts UP';
                }
                else {
			$state['State'] = 'UNKNOWN';
                        $state['Count'] = '0';
                        $state['Output'] .= '<br>->No data :-(';
                }

                if($RecognizeServices == 1) {
                        preg_match('/<TD CLASS=\'serviceTotalsOK\'>(.*)<\/TD>/', $text, $serviceTotalsOK);
                        preg_match('/<TD CLASS=\'serviceTotalsCRITICAL\'>(.*)<\/TD>/', $text, $serviceTotalsCRITICAL);
                        preg_match('/<TD CLASS=\'serviceTotalsWARNING\'>(.*)<\/TD>/', $text, $serviceTotalsWARNING);
			preg_match_all('/<TD ALIGN=LEFT valign=center CLASS=\'statusBGCRITICAL\'><A HREF=\'extinfo.cgi\?type=2&host=(.*)&service=(.*)\'>/', $text, $serviceacksc);
			preg_match_all('/<TD ALIGN=LEFT valign=center CLASS=\'statusBGWARNING\'><A HREF=\'extinfo.cgi\?type=2&host=(.*)&service=(.*)\'>/', $text, $serviceacksw);
			preg_match_all('/<TD ALIGN=LEFT valign=center CLASS=\'statusBGUNKNOWN\'><A HREF=\'extinfo.cgi\?type=2&host=(.*)&service=(.*)\'>/', $text, $serviceacksu);
			
			for($i=0;$i<count($serviceacksc[1]);$i++) {
				$state['Output'] .= '<br>->service '.$serviceacksc[2][$i].' from host '.$serviceacksc[1][$i].' is CRITICAL and not acknowledged!!!';
			}
			for($i=0;$i<count($serviceacksw[1]);$i++) {
				$state['Output'] .= '<br>->service '.$serviceacksw[2][$i].' from host '.$serviceacksw[1][$i].' is WARNING and not acknowledged!!!';
			}
			for($i=0;$i<count($serviceacksu[1]);$i++) {
				$state['Output'] .= '<br>->service '.$serviceacksu[2][$i].' from host '.$serviceacksu[1][$i].' is UNKNOWN and not acknowledged!!!';
			}

                        if(isset($serviceTotalsCRITICAL[1]) && $serviceTotalsCRITICAL[1] != 0) {
                                $state['State'] = 'CRITICAL';
                                $state['Count'] = $serviceTotalsCRITICAL[1];
                                $state['Output'] .= '<br>->'.$serviceTotalsCRITICAL[1].' Services in state CRITICAL';
				if(isset($hostack[1])) {
					$state['Acknowledge'] = '1';
				}	
				elseif(!isset($serviceacksc[1][0]) && !isset($serviceacksw[1][0]) && !isset($serviceacksu[1][0])) {
					$state['Acknowledge'] = '1';
				}
				else {
					$state['Acknowledge'] = '0';
				}
                        }
                        elseif(isset($serviceTotalsWARNING[1]) && $serviceTotalsWARNING != 0) {
                                $state['State'] = 'WARNING';
                                $state['Count'] = $serviceTotalsWARNING[1];
                                $state['Output'] .= '<br>->'.$serviceTotalsWARNING[1].' Service in state WARNING';
				if(isset($hostack[1])) {
					$state['Acknowledge'] = '1';
				}	
				elseif(!isset($serviceacksc[1][0]) && !isset($serviceacksw[1][0]) && !isset($serviceacksu[1][0])) {
					$state['Acknowledge'] = '1';
				}
				else {
					$state['Acknowledge'] = '0';
				}
                        }
                        elseif(isset($serviceTotalsUNKNOWN[1]) && $serviceTotalsUNKNOWN != 0) {
                                $state['State'] = 'UNKNOWN';
                                $state['Count'] = $serviceTotalsUNKNOWN[1];
                                $state['Output'] .= '<br>->'.$serviceTotalsUNKNOWN[1].' Service in state UNKNOWN';
				if(isset($hostack[1])) {
					$state['Acknowledge'] = '1';
				}	
				elseif(!isset($serviceacksc[1][0]) && !isset($serviceacksw[1][0]) && !isset($serviceacksu[1][0])) {
					$state['Acknowledge'] = '1';
				}
				else {
					$state['Acknowledge'] = '0';
				}
                        }
                        elseif(isset($serviceTotalsOK[1]) && $serviceTotalsOK[1] != 0) {
                                $state['State'] = 'OK';
                                $state['Count'] = $serviceTotalsOK[1];
                                $state['Output'] .= '<br>->'.$serviceTotalsOK[1].' Services in state OK';
                        }
                }
                return($state);
        }
	
	// Status für eine Hostgroup ermitteln.
	function findStateHostgroup($Hostgroupname,$RecognizeServices,$StatusCgi,$CgiUser) {
		$rotateUrl = "";
		putenv("REQUEST_METHOD=GET");
		putenv("REMOTE_USER=$CgiUser");
		putenv("QUERY_STRING=hostgroup=$Hostgroupname&style=detail");
		$text = chop(`$StatusCgi`);
		
		if (preg_match('/<DIV CLASS=\'errorMessage\'>Sorry, but host group(.*)<\/DIV>/', $text)) {
			$state['State'] = 'UNKNOWN';
			$state['Count'] = '0';
			$state['Output'] = '<br>->Sorry, but host group '.$Hostgroupname.' not found';
		}
		else {
			preg_match('/<TD CLASS=\'hostTotalsPROBLEMS\'>(.*)<\/TD>/', $text, $hostTotalsPROBLEMS);
			preg_match('/<TD CLASS=\'hostTotalsUP\'>(.*)<\/TD>/', $text, $hostTotalsUP);
			preg_match_all('/CLASS=\'statusHOSTDOWN\'><A HREF=\'extinfo.cgi\?type=1&host=.*\'>(.*)<\/A>/i', $text, $hosts);
		}
		for($i=0;$i<count($hosts[1]);$i++) {
			$state['Output'] .= '<br>->host '.$hosts[1][$i].' is not acknowledged!!!';
		}
		if(isset($hostTotalsPROBLEMS[1]) && $hostTotalsPROBLEMS[1] != 0 && !isset($hosts[1][0])) {
			$state['Acknowledge'] = '1';
			$state['State'] = 'DOWN';
			$state['Count'] = $hostTotalsPROBLEMS[1];
			$state['Output'] .= '<br>->'.$hostTotalsPROBLEMS[1].' Host down';
		}
		elseif(isset($hostTotalsPROBLEMS[1]) && $hostTotalsPROBLEMS[1] != 0) {
			$state['State'] = 'DOWN';
			$state['Count'] = $hostTotalsPROBLEMS[1];
			$state['Output'] .= '<br>->'.$hostTotalsPROBLEMS[1].' Host down';
		}
		elseif(isset($hostTotalsUP[1]) && $hostTotalsUP[1] != 0) {
			$state['State'] = 'UP';
			$state['Count'] = $hostTotalsUP[1];
			$state['Output'] .= '<br>->'.$hostTotalsUP[1].' Host up';
		}
		else {
			$state['State'] = 'UNKNOWN';
                        $state['Count'] = '0';
                        $state['Output'] .= '<br>->No data :-(';
		}

                if($RecognizeServices == 1) {
                        preg_match('/<TD CLASS=\'serviceTotalsOK\'>(.*)<\/TD>/', $text, $serviceTotalsOK);
                        preg_match('/<TD CLASS=\'serviceTotalsCRITICAL\'>(.*)<\/TD>/', $text, $serviceTotalsCRITICAL);
                        preg_match('/<TD CLASS=\'serviceTotalsWARNING\'>(.*)<\/TD>/', $text, $serviceTotalsWARNING);
			preg_match_all('/<TD ALIGN=LEFT valign=center CLASS=\'statusBGCRITICAL\'><A HREF=\'extinfo.cgi\?type=2&host=(.*)&service=(.*)\'>/', $text, $serviceacksc);
			preg_match_all('/<TD ALIGN=LEFT valign=center CLASS=\'statusBGWARNING\'><A HREF=\'extinfo.cgi\?type=2&host=(.*)&service=(.*)\'>/', $text, $serviceacksw);
			preg_match_all('/<TD ALIGN=LEFT valign=center CLASS=\'statusBGUNKNOWN\'><A HREF=\'extinfo.cgi\?type=2&host=(.*)&service=(.*)\'>/', $text, $serviceacksu);
			
			for($i=0;$i<count($serviceacksc[1]);$i++) {
				$state['Output'] .= '<br>->service '.$serviceacksc[2][$i].' from host '.$serviceacksc[1][$i].' is CRITICAL and not acknowledged!!!';
			}
			for($i=0;$i<count($serviceacksw[1]);$i++) {
				$state['Output'] .= '<br>->service '.$serviceacksw[2][$i].' from host '.$serviceacksw[1][$i].' is WARNING and not acknowledged!!!';
			}
			for($i=0;$i<count($serviceacksu[1]);$i++) {
				$state['Output'] .= '<br>->service '.$serviceacksu[2][$i].' from host '.$serviceacksu[1][$i].' is UNKNOWN and not acknowledged!!!';
			}

                        if(isset($serviceTotalsCRITICAL[1]) && $serviceTotalsCRITICAL[1] != 0) {
                                $state['State'] = 'CRITICAL';
                                $state['Count'] = $serviceTotalsCRITICAL[1];
                                $state['Output'] .= '<br>->'.$serviceTotalsCRITICAL[1].' Services in state CRITICAL';
				if(isset($hostack[1])) {
					$state['Acknowledge'] = '1';
				}	
				elseif(!isset($serviceacksc[1][0]) && !isset($serviceacksw[1][0]) && !isset($serviceacksu[1][0])) {
					$state['Acknowledge'] = '1';
				}
				else {
					$state['Acknowledge'] = '0';
				}
			}
                        elseif(isset($serviceTotalsWARNING[1]) && $serviceTotalsWARNING != 0) {
                                $state['State'] = 'WARNING';
                                $state['Count'] = $serviceTotalsWARNING[1];
                                $state['Output'] .= '<br>->'.$serviceTotalsWARNING[1].' Service in state WARNING';
				if(isset($hostack[1])) {
					$state['Acknowledge'] = '1';
				}	
				elseif(!isset($serviceacksc[1][0]) && !isset($serviceacksw[1][0]) && !isset($serviceacksu[1][0])) {
					$state['Acknowledge'] = '1';
				}
				else {
					$state['Acknowledge'] = '0';
				}
                        }
                        elseif(isset($serviceTotalsUNKNOWN[1]) && $serviceTotalsUNKNOWN != 0) {
                                $state['State'] = 'UNKNOWN';
                                $state['Count'] = $serviceTotalsUNKNOWN[1];
                                $state['Output'] .= '<br>->'.$serviceTotalsUNKNOWN[1].' Service in state UNKNOWN';
				if(isset($hostack[1])) {
					$state['Acknowledge'] = '1';
				}	
				elseif(!isset($serviceacksc[1][0]) && !isset($serviceacksw[1][0]) && !isset($serviceacksu[1][0])) {
					$state['Acknowledge'] = '1';
				}
				else {
					$state['Acknowledge'] = '0';
				}
                        }
                        elseif(isset($serviceTotalsOK[1]) && $serviceTotalsOK[1] != 0) {
                                $state['State'] = 'OK';
                                $state['Count'] = $serviceTotalsOK[1];
                                $state['Output'] .= '<br>->'.$serviceTotalsOK[1].' Services in state OK';
                        }
                }
		return($state);
	}
        function findStateService($HostName,$ServiceName,$StatusCgi,$CgiUser) {
                $rotateUrl = "";
                putenv("REQUEST_METHOD=GET");
                putenv("REMOTE_USER=$CgiUser");
                putenv("QUERY_STRING=type=2&host=$HostName&service=$ServiceName");
                $text = chop(`$StatusCgi`);

                preg_match('/.*Current Status:<\/TD><TD CLASS=\'dataVal\'><DIV CLASS=\'(.*)\'>.*/', $text, $state);
                preg_match('/.*Status Information:<\/TD><TD CLASS=\'dataVal\'>(.*)<\/TD>.*/', $text, $output);
                preg_match('/Acknowledgement/', $text, $serviceAck);
                if(!isset($output[1])) {
                        preg_match('/<P><DIV CLASS=\'errorMessage\'>(.*)<\/DIV><\/P>/', $text, $output);
                }

                $state['Output'] = '<br>->'.$output[1];

		if(isset($serviceAck[0])) {
			$state['Acknowledge'] = 1;
		}
			
                if($state[1] == 'serviceOK') {
                        $state['State'] = "OK";
                }
                elseif($state[1] == 'serviceWARNING') {
                        $state['State'] = "WARNING";
                }
                elseif($state[1] == 'serviceCRITICAL') {
                        $state['State'] = "CRITICAL";
                }
                else {
                        $state['State'] = "UNKNOWN";
                }
                return $state;
        }

	function findStateServicegroup($ServiceGroup,$StatusCgi,$CgiUser) {
		$rotateUrl = "";
		putenv("REQUEST_METHOD=GET");
		putenv("REMOTE_USER=$CgiUser");
		putenv("QUERY_STRING=servicegroup=$ServiceGroup");
		$text = chop(`$StatusCgi`);
		if (preg_match('/<DIV CLASS=\'errorMessage\'>Sorry, but servicegroup(.*)<\/DIV>/', $text)) {
			$state['State'] = 'UNKNOWN';
			$state['Count'] = '0';
			$state['Output'] = '<br>->Sorry, but servicegroup '.$ServiceGroup.' not found';
		}
		else {
			preg_match('/<TD CLASS=\'serviceTotalsOK\'>(.*)<\/TD>/', $text, $serviceTotalsOK);
			preg_match('/<TD CLASS=\'serviceTotalsCRITICAL\'>(.*)<\/TD>/', $text, $serviceTotalsCRITICAL);
			preg_match('/<TD CLASS=\'serviceTotalsWARNING\'>(.*)<\/TD>/', $text, $serviceTotalsWARNING);
			preg_match_all('/<TD ALIGN=LEFT valign=center CLASS=\'statusBGCRITICAL\'><A HREF=\'extinfo.cgi\?type=2&host=(.*)&service=(.*)\'>/', $text, $serviceacksc);
			preg_match_all('/<TD ALIGN=LEFT valign=center CLASS=\'statusBGWARNING\'><A HREF=\'extinfo.cgi\?type=2&host=(.*)&service=(.*)\'>/', $text, $serviceacksw);
			preg_match_all('/<TD ALIGN=LEFT valign=center CLASS=\'statusBGUNKNOWN\'><A HREF=\'extinfo.cgi\?type=2&host=(.*)&service=(.*)\'>/', $text, $serviceacksu);
			
			for($i=0;$i<count($serviceacksc[1]);$i++) {
				$state['Output'] .= '<br>->service '.$serviceacks[2][$i].' from host '.$serviceacksc[1][$i].' is CRITICAL and not acknowledged!!!';
			}
			for($i=0;$i<count($serviceacksw[1]);$i++) {
				$state['Output'] .= '<br>->service '.$serviceacks[2][$i].' from host '.$serviceacksw[1][$i].' is WARNING and not acknowledged!!!';
			}
			for($i=0;$i<count($serviceacksu[1]);$i++) {
				$state['Output'] .= '<br>->service '.$serviceacks[2][$i].' from host '.$serviceacksu[1][$i].' is UNKNOWN and not acknowledged!!!';
			}
		}

		if(isset($serviceTotalsCRITICAL[1]) && $serviceTotalsCRITICAL[1] != 0) {
			$state['State'] = 'CRITICAL';
			$state['Count'] = $serviceTotalsCRITICAL[1];
			$state['Output'] .= '<br>->'.$serviceTotalsCRITICAL[1].' services in state CRITICAL';
			if(!isset($serviceacksc[1][0]) && !isset($serviceacksw[1][0]) && !isset($serviceacksu[1][0])) {
				$state['Acknowledge'] = '1';
			}
			else {
				$state['Acknowledge'] = '0';
			}
		}
		elseif(isset($serviceTotalsWARNING[1]) && $serviceTotalsWARNING != 0) {
			$state['State'] = 'WARNING';
			$state['Count'] = $serviceTotalsWARNING[1];
			$state['Output'] = '<br>->'.$serviceTotalsWARNING[1].' Services in state WARNING';
			if(!isset($serviceacksc[1][0]) && !isset($serviceacksw[1][0]) && !isset($serviceacksu[1][0])) {
				$state['Acknowledge'] = '1';
			}
			else {
				$state['Acknowledge'] = '0';
			}
		}
		elseif(isset($serviceTotalsOK[1]) && $serviceTotalsOK[1] != 0) {
			$state['State'] = 'OK';
			$state['Count'] = $serviceTotalsOK[1];
			$state['Output'] = '<br>->'.$serviceTotalsOK[1].' Services in state OK';

		}
		else {
			$state['State'] = 'UNKNOWN';
                        $state['Count'] = '0';
                        $state['Output'] = '<br>->No data :-(';
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
	function checkStates($Type,$Name,$RecognizeServices="0",$ServiceName="",$StatePos="0",$CgiPath,$CgiUser)
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
			/*
				FIXME:
				Acknowledge = 1 means a HOST Acknowledgement
				Acknowledge = 2 means a SERVICE Acknowlegement

			*/
			
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
			/* extra icons for critical/warning?
				elseif(isset($State['Acknowledge']) && $State['Acknowledge'] == "2") {
					$Icon = $mapCfg[1]['iconset']."_acknowledged";
				}
				elseif(isset($State['Acknowledge']) && $State['Acknowledge'] == "3") {
					$Icon = $mapCfg[1]['iconset']."_acknowledged";
				}
			*/
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
