<?
##########################################################################
##     	        NagVis - The Nagios Visualisation Addon                 ##
##########################################################################
## class.CheckState_ndomy- Backend module to fetch the status from      ##
##             			   Nagios NDO Mysql DB. All not special to one	##
##						   Backend related things should removed here!  ##
##						   (e.g. fixIcon, this is needed for ALL        ##
##						   Backends	)									##
##########################################################################
## Licenced under the terms and conditions of the GPL Licence, 			##
## please see attached "LICENCE" file	                                ##
##########################################################################


class backend
{
	//The backendInitialize function will be needed for ever, in backends wich need to do nothing here
	//a simple "return 0" function should be implemented
	function backendInitialize() {
		//Connection Parameters, ONLY for testing here, should be defined in the config file later
		$dbUser="nagios";
		$dbPass="nagios";
		$dbName="nagios";
		$dbHost="localhost";

		if (!extension_loaded('mysql')) {
			dl('mysql.so');
		}
		$CONN = mysql_connect($dbHost,$dbUser,$dbPass);
		$returnCode = mysql_select_db($dbName,$CONN);
		
		if( $returnCode != TRUE){
			echo "Error selecting Database";
			exit;
		}
		return 0;
	}


	// Get the state for a HOST
	function findStateHost($hostName,$recognizeServices,$statusCgi,$cgiUser) {
		
		$QUERYHANDLE = mysql_query("SELECT object_id FROM ndo_objects WHERE (objecttype_id = '1' AND name1 = '$hostName')");
		$hostObjectId = mysql_fetch_row($QUERYHANDLE);  
	
		if (mysql_num_rows($QUERYHANDLE) == 0) {
			//FIXME: All this outputs should be handled over a language file
			$state['State'] = "ERROR";
			$state['Output'] = "The Host <b>" .$hostName. "</b> was not found in the Database. Maybe it is spelled wrong?";
			return($state);
		}

		$QUERYHANDLE = mysql_query("SELECT current_state, output, problem_has_been_acknowledged FROM ndo_hoststatus  WHERE object_id = '$hostObjectId[0]'");
		$hostState = mysql_fetch_array($QUERYHANDLE);

		//Host is UP
		if ($hostState['current_state'] == '0') {
			$state['State'] = 'UP';
			$state['Output'] = $hostState['output'];
		} 
		//Host is DOWN
		elseif ($hostState['current_state'] == '1') {
			if($hostState['problem_has_been_acknowledged'] == 1) {
				$state['State'] = 'ACK';
			}
			else {
				$state['State'] = 'DOWN';
			}
			$state['Output'] = $hostState['output'];
		}
		//Host is UNREACHABLE
		elseif ($hostState['current_state'] == '2') {
			if($hostState['problem_has_been_acknowledged'] == 1) {
				$state['State'] = 'ACK';
			}
			else {
				$state['State'] = 'UNREACHABLE';
			}
			$state['Output'] = $hostState['output'];
		}
		//Host is UNKNOWN
		elseif ($hostState['current_state'] == '3') {
			if($hostState['problem_has_been_acknowledged'] == 1) {
				$state['State'] = 'ACK';
			}
			else {
				$state['State'] = 'UNKNOWN';
			}
			$state['Output'] = $hostState['output'];
		}
		
		$hostState = $state['State'];
		
		//Check the Services of the Host if requested and the Host is UP (makes no sence if the host is DOWN ;-),
		//this also makes shure that a host ACK will automatically ACK all services.
		if($recognizeServices == 1 && $hostState == "UP") {
			//Initialise Vars
			$servicesOk=0;
			$servicesWarning=0;
			$servicesCritical=0;
			$servicesUnknown=0;
			$servicesAck=0;
			//Get the object ids from all services of this host
			$QUERYHANDLE = mysql_query("SELECT object_id FROM ndo_objects WHERE objecttype_id='2' AND name1='$hostName'");
			while($services = mysql_fetch_array($QUERYHANDLE)) {
				$objectId = $services['object_id'];
				//Query the Servicestates
				$QUERYHANDLE_2 = mysql_query("SELECT current_state, output, problem_has_been_acknowledged FROM ndo_servicestatus WHERE object_id = '$objectId'");
				$currentService = mysql_fetch_array($QUERYHANDLE_2);				
				if($currentService['current_state'] == 0) {
					$servicesOk++;
				}
				elseif($currentService['problem_has_been_acknowledged'] == 1) {
					$servicesAck++;				
				}
				elseif($currentService['current_state'] == 1) {
					$servicesWarning++;
				}
				elseif($currentService['current_state'] == 2) {
					$servicesCritical++;
				}
				elseif($currentService['current_state'] == 3) {
					$servicesUnknown++;
				}
			}
			
			if($servicesCritical > 0) {
				$state['Count'] = $servicesCritical;
				$state['Output'] = "Host is UP but there are ".$servicesCritical." CRITICAL, " .$servicesWarning. " WARNING and " .$servicesUnknown. " UNKNOWN Services";
				$state['State'] = "CRITICAL";
			}
			elseif($servicesWarning > 0) {
				$state['Count'] = $servicesWarning;
				$state['Output'] = "Host is UP but there are " .$servicesWarning. " WARNING and " .$servicesUnknown. " UNKNOWN Services";
				$state['State'] = "WARNING";		
			}
			elseif($servicesUnknown > 0) {
				$state['Count'] = $servicesUnknown;
				$state['Output'] = "Host is UP but there are ".$servicesUnknown." Services in UNKNOWN state";
				$state['State'] = "UNKNOWN";
				
			}
			elseif($servicesAck > 0) {
				$state['Count'] = $servicesAck;
				$state['Output'] = "Host is UP but ".$servicesAck." services are in a NON-OK State but all are ACKNOWLEDGED";
				$state['State'] = "ACK";
			}
			elseif($servicesOk > 0) {
				$state['Count'] = $servicesOk;
				$state['Output'] = "Host is UP and all ".$servicesOk." services are OK";
				//This must be set before by the host, but to be consitend with the other ifs i define it again here:
				$state['State'] = "UP";		
			}
		}
        return($state);
	}

   
	// Status für eine Hostgroup ermitteln.
	function findStateHostgroup($hostGroupName,$recognizeServices,$statusCgi,$cgiUser) {
		//Because "hostgroup_name" ist missing in the NDO DB,  we have to work with the alias
		//Ok, its not missingn its in the ndo_objects table, i will fetch this later
		$QUERYHANDLE = mysql_query("SELECT object_id FROM ndo_objects WHERE objecttype_id='3' AND name1='$hostGroupName'");
		$objectId = mysql_fetch_row($QUERYHANDLE);
	
		if (mysql_num_rows($QUERYHANDLE) == 0) {
			//FIXME: All this outputs should be handled over a language file
			$state['State'] = "ERROR";
			$state['Output'] = "The Hostgroup <b>" .$hostGroupName. "</b> was not found in the Database. Maybe it is spelled wrong?";
			return($state);
		}
		
		$QUERYHANDLE = mysql_query("SELECT hostgroup_id FROM ndo_hostgroups WHERE object_id='$objectId[0]'");
		$hostGroupId = mysql_fetch_row($QUERYHANDLE);
		
		$hostsCritical=0;
		$hostsWarning=0;
		$hostsUnknown=0;
		$hostsAck=0;
		$hostsOk=0;

		//Now we have the Group Id and can get the hosts
		$QUERYHANDLE = mysql_query("SELECT host_object_id FROM ndo_hostgroup_members WHERE hostgroup_id='$hostGroupId[0]'");	
		while($hosts = mysql_fetch_array($QUERYHANDLE)) {
				$objectId = $hosts['host_object_id'];
				//Get the Host Name for the objectId Again so we can use our own host function
				//this ist not really nice because the name gets changed back to the id there, maybe split the host funktions in to parts
				$QUERYHANDLE_2 = mysql_query("SELECT name1 FROM ndo_objects WHERE (objecttype_id = '1' AND object_id = '$objectId')");
				$hostName = mysql_fetch_array($QUERYHANDLE_2);  
				
				$currentHostState = $this->findStateHost($hostName['name1'],$recognizeServices,"","");
				if($currentHostState['State'] == "UP") {
					$hostsOk++;
				}
				elseif($currentHostState['State'] == "ACK") {
					$hostsAck++;				
				}
				elseif($currentHostState['State'] == "WARNING") {
					$hostsWarning++;
				}
				elseif($currentHostState['State'] == "DOWN" || $currentHostState['State'] == "UNREACHABLE" || $currentHostState['State'] == "CRITICAL") {
					$hostsCritical++;
				}
				elseif($currentHostState['State'] == "UNKOWN") {
					$hostsUnknown++;
				}	
		}
	
		if($hostsCritical > 0) {
			$state['Count'] = $hostsCritical;
			$state['Output'] = $hostsCritical." Hosts are CRITICAL, " .$hostsWarning. " WARNING and " .$hostsUnknown. " UNKNOWN";
			$state['State'] = "CRITICAL";
		}
		elseif($hostsWarning > 0) {
			$state['Count'] = $hostsWarning;
			$state['Output'] = $hostWarninig. "Hosts are WARNING and " .$hostsUnknown. " UNKNOWN";
			$state['State'] = "WARNING";		
		}
		elseif($hostsUnknown > 0) {
			$state['Count'] = $hostsUnkown;
			$state['Output'] = $hostsUnkown." are in UNKNOWN state";
			$state['State'] = "UNKNOWN";
			
		}
		elseif($hostsAck > 0) {
			$state['Count'] = $hostsAck;
			$state['Output'] = $hostsAck." Hosts are in a NON-OK State but all errors are ACKNOWLEDGED";
			$state['State'] = "ACK";
		}
		elseif($hostsOk > 0) {
			$state['Count'] = $hostsOk;
			$state['Output'] = "All " .$hostsOk. " Hosts are OK";
			//This must be set before by the host, but to be consitend with the other ifs i define it again here:
			$state['State'] = "UP";		
		}


		return($state);
	}


	function findStateService($hostName,$serviceName,$StatusCgi,$CgiUser) {
		$QUERYHANDLE = mysql_query("SELECT object_id FROM ndo_objects WHERE (objecttype_id = '2' AND name1 = '$hostName' AND name2 ='$serviceName' )");
		if (mysql_num_rows($QUERYHANDLE) == 0) {
			//FIXME: All this outputs should be handled over a language file
			$state['State'] = "ERROR";
			$state['Output'] = "The Service <b>" .$serviceName. "</b> on Host <b>" .$hostName. "</b> was not found in the Database. Maybe it is spelled wrong?";
			return($state);
		}
		$serviceObjectId = mysql_fetch_row($QUERYHANDLE);

		$QUERYHANDLE = mysql_query("SELECT current_state, output, problem_has_been_acknowledged FROM ndo_servicestatus WHERE object_id = '$serviceObjectId[0]'");
		$service = mysql_fetch_array($QUERYHANDLE);				
				

		if($service['current_state'] == 0) {
				$state['Count'] = "1";
				$state['State'] = "OK";
				$state['Output'] = $service['output'];
		}
		elseif($currentService['problem_has_been_acknowledged'] == 1) {
				$state['Count'] = "1";
				$state['State'] = "SACK";
				$state['Output'] = $service['output'];			
		}
			elseif($currentService['current_state'] == 1) {
				$state['Count'] = "1";
				$state['State'] = "WARNING";
				$state['Output'] = $service['output'];		
		}
			elseif($currentService['current_state'] == 2) {
				$state['Count'] = "1";
				$state['State'] = "CRITICAL";
				$state['Output'] = $service['output'];		
		}
			elseif($currentService['current_state'] == 3) {
				$state['Count'] = "1";
				$state['State'] = "UNKNOWN";
				$state['Output'] = $service['output'];	
		}	
		
		return($state);
	}
/* THE FOLLOWING IS STILL DONE BY THE CGIs, IM CODING ON IT - Andreas */
/*	function findStateService($HostName,$ServiceName,$StatusCgi,$CgiUser) {
		$rotateUrl = "";
		putenv("REQUEST_METHOD=GET");
        putenv("REMOTE_USER=$CgiUser");
        putenv("QUERY_STRING=type=2&host=$HostName&service=$ServiceName");
        $handle = popen($StatusCgi, 'r');
        $text = fread($handle, 9000);
        pclose($handle);
	*/
//	preg_match('/.*Current Status:<\/TD><TD CLASS=\'dataVal\'><DIV CLASS=\'(.*)\'>.*/', $text, $state);
//		preg_match('/.*Status Information:<\/TD><TD CLASS=\'dataVal\'>(.*)<\/TD>.*/', $text, $output);
	/*        
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
*/
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

	//FIXME: Can be made easiert here. Thigs like "CgiPath", "CgiUser" are special to the html backend,
    //  	 so this should be done another way.
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
			$nagvis = new frontend();	
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
