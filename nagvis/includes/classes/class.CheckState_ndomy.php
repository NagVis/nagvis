<?
##########################################################################
##     	        NagVis - The Nagios Visualisation Addon                 ##
##########################################################################
## class.CheckState_ndomy- Backend module to fetch the status from      ##
##                         Nagios NDO Mysql DB. All not special to one	##
##                         Backend related things should removed here!  ##
##                         (e.g. fixIcon, this is needed for ALL        ##
##                         Backends	)				##
##########################################################################
## Licenced under the terms and conditions of the GPL Licence, 		##
## please see attached "LICENCE" file	                                ##
##########################################################################
## This Backend is maintained by Andreas Husch (dowanup@nagios-wiki.de) ##
##########################################################################

class backend
{
	var $CONFIG;
	var $dbName;
	var $dbUser;
	var $dbPass;
	var $dbHost;
	var $dbPrefix;
	var $dbInstanceId;
	
	/**
	* Constructor
	*
	* @param config $CONFIG
	*
	* @author Andreas Husch, Lars Michelsen <larsi@nagios-wiki.de>
	*/
	function backend($CONFIG) {
		$this->CONFIG = $CONFIG;
        $this->dbName = $this->CONFIG->getValue('backend_ndo', 'dbname');
        $this->dbUser = $this->CONFIG->getValue('backend_ndo', 'dbuser');
        $this->dbPass = $this->CONFIG->getValue('backend_ndo', 'dbpass');
        $this->dbHost = $this->CONFIG->getValue('backend_ndo', 'dbhost');
        $this->dbPort = $this->CONFIG->getValue('backend_ndo', 'dbport');
		$this->dbPrefix = $this->CONFIG->getValue('backend_ndo', 'dbprefix');
		$this->dbInstanceId = $this->CONFIG->getValue('backend_ndo', 'dbinstanceid');

		if (!extension_loaded('mysql')) {
			dl('mysql.so');
		}
		
		$CONN = mysql_connect($this->dbHost.':'.$this->dbPort, $this->dbUser, $this->dbPass);
		$returnCode = mysql_select_db($this->dbName, $CONN);
		
		if( $returnCode != TRUE){
			echo "Error selecting Database, maybe wrong db or insufficient permissions?";
			exit;
		}
		return 0;
	}
	
	// Get the state for a HOST #####################################################################################################
	function findStateHost($hostName,$recognizeServices) {
		
		$QUERYHANDLE = mysql_query("SELECT object_id FROM ".$this->dbPrefix."objects WHERE objecttype_id = '1' AND name1 = '".$hostName."' AND instance_id='".$this->dbInstanceId."'");
		$hostObjectId = mysql_fetch_row($QUERYHANDLE);  
	
		if (mysql_num_rows($QUERYHANDLE) == 0) {
			//FIXME: All this outputs should be handled over a language file
			$state['State'] = "ERROR";
			$state['Output'] = "The Host <b>" .$hostName. "</b> was not found in the Database. Maybe it is spelled wrong?";
			return($state);
		}

		$QUERYHANDLE = mysql_query("SELECT current_state, output, problem_has_been_acknowledged FROM ".$this->dbPrefix."hoststatus  WHERE host_object_id = '$hostObjectId[0]' AND instance_id='$this->dbInstanceId'");
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
			$QUERYHANDLE = mysql_query("SELECT object_id FROM ".$this->dbPrefix."objects WHERE objecttype_id='2' AND name1='$hostName' AND instance_id='$this->dbInstanceId'");
			while($services = mysql_fetch_array($QUERYHANDLE)) {
				$objectId = $services['object_id'];
				//Query the Servicestates
				$QUERYHANDLE_2 = mysql_query("SELECT current_state, output, problem_has_been_acknowledged FROM ".$this->dbPrefix."servicestatus WHERE service_object_id = '$objectId' AND instance_id='$this->dbInstanceId'");
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

   
	// Hostgroup ###############################################################################################################
	function findStateHostgroup($hostGroupName,$recognizeServices) {
		//First we have to get the hostgroup_id
		$QUERYHANDLE = mysql_query("SELECT object_id FROM ".$this->dbPrefix."objects WHERE objecttype_id='3' AND name1='$hostGroupName' AND instance_id='$this->dbInstanceId'");
		$objectId = mysql_fetch_row($QUERYHANDLE);
		
		if (mysql_num_rows($QUERYHANDLE) == 0) {
			//FIXME: All this outputs should be handled over a language file
			$state['State'] = "ERROR";
			$state['Output'] = "The Hostgroup <b>" .$hostGroupName. "</b> was not found in the Database. Maybe it is spelled wrong?";
			return($state);
		}
		
		$QUERYHANDLE = mysql_query("SELECT hostgroup_id FROM ".$this->dbPrefix."hostgroups WHERE hostgroup_object_id='$objectId[0]' AND instance_id='$this->dbInstanceId'");
		$hostGroupId = mysql_fetch_row($QUERYHANDLE);
		
		$hostsCritical=0;
		$hostsWarning=0;
		$hostsUnknown=0;
		$hostsAck=0;
		$hostsOk=0;

		//Now we have the Group Id and can get the hosts
		$QUERYHANDLE = mysql_query("SELECT host_object_id FROM ".$this->dbPrefix."hostgroup_members WHERE hostgroup_id='$hostGroupId[0]' AND instance_id='$this->dbInstanceId'");	
		while($hosts = mysql_fetch_array($QUERYHANDLE)) {
				$objectId = $hosts['host_object_id'];
				//Get the Host Name for the objectId Again so we can use our own host function
				//this ist not really nice because the name gets changed back to the id there, maybe split the host funktions in to parts
				$QUERYHANDLE_2 = mysql_query("SELECT name1 FROM ".$this->dbPrefix."objects WHERE (objecttype_id = '1' AND object_id = '$objectId' AND instance_id='$this->dbInstanceId')");
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
			$state['Output'] = $hostsWarning. " Hosts are WARNING and " .$hostsUnknown. " UNKNOWN";
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

	// Service #####################################################################################################################
	function findStateService($hostName,$serviceName) {
		$QUERYHANDLE = mysql_query("SELECT object_id FROM ".$this->dbPrefix."objects WHERE (objecttype_id = '2' AND name1 = '$hostName' AND name2 ='$serviceName' AND instance_id='$this->dbInstanceId')");
		if (mysql_num_rows($QUERYHANDLE) == 0) {
			//FIXME: All this outputs should be handled over a language file
			$state['State'] = "ERROR";
			$state['Output'] = "The Service <b>" .$serviceName. "</b> on Host <b>" .$hostName. "</b> was not found in the Database. Maybe it is spelled wrong?";
			return($state);
		}
		$serviceObjectId = mysql_fetch_row($QUERYHANDLE);

		$QUERYHANDLE = mysql_query("SELECT current_state, output, problem_has_been_acknowledged FROM ".$this->dbPrefix."servicestatus WHERE service_object_id = '$serviceObjectId[0]' AND instance_id='$this->dbInstanceId'");
		$service = mysql_fetch_array($QUERYHANDLE);				
		if($service['current_state'] == 0) {
				$state['Count'] = "1";
				$state['State'] = "OK";
				$state['Output'] = $service['output'];
		}
		elseif($service['problem_has_been_acknowledged'] == 1) {
				$state['Count'] = "1";
				$state['State'] = "SACK";
				$state['Output'] = $service['output'];			
		}
		elseif($service['current_state'] == 1) {
				$state['Count'] = "1";
				$state['State'] = "WARNING";
				$state['Output'] = $service['output'];		
		}
		elseif($service['current_state'] == 2) {
				$state['Count'] = "1";
				$state['State'] = "CRITICAL";
				$state['Output'] = $service['output'];		
		}
		elseif($service['current_state'] == 3) {
				$state['Count'] = "1";
				$state['State'] = "UNKNOWN";
				$state['Output'] = $service['output'];	
		}	
		
		return($state);
	}

	// Servicegroup ############################################################################################################
	function findStateServicegroup($serviceGroupName) {
		//First we have to get the servicegroup_id
		$QUERYHANDLE = mysql_query("SELECT object_id FROM ".$this->dbPrefix."objects WHERE objecttype_id='4' AND name1='$serviceGroupName' AND instance_id='$this->dbInstanceId'");
		$objectId = mysql_fetch_row($QUERYHANDLE);
	
		if (mysql_num_rows($QUERYHANDLE) == 0) {
			//FIXME: All this outputs should be handled over a language file
			$state['State'] = "ERROR";
			$state['Output'] = "The Servicegroup <b>" .$serviceGroupName. "</b> was not found in the Database. Maybe it is spelled wrong?";
			return($state);
		}
		
		$QUERYHANDLE = mysql_query("SELECT servicegroup_id FROM ".$this->dbPrefix."servicegroups WHERE servicegroup_object_id='$objectId[0]' AND instance_id='$this->dbInstanceId'");
		$serviceGroupId = mysql_fetch_row($QUERYHANDLE);
		
		$servicesCritical=0;
		$servicesWarning=0;
		$servicesUnknown=0;
		$servicesAck=0;
		$servicesOk=0;

		//Now we have the Group Id and can get the hosts
		$QUERYHANDLE = mysql_query("SELECT service_object_id FROM ".$this->dbPrefix."servicegroup_members WHERE servicegroup_id='$serviceGroupId[0]' AND instance_id='$this->dbInstanceId'");	
		while($services = mysql_fetch_array($QUERYHANDLE)) {
				$objectId = $services['service_object_id'];

				//Query the Servicestates
				$QUERYHANDLE_2 = mysql_query("SELECT current_state, output, problem_has_been_acknowledged FROM ".$this->dbPrefix."servicestatus WHERE service_object_id = '$objectId' AND instance_id='$this->dbInstanceId'");
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
				$state['Output'] = $servicesCritical." CRITICAL, " .$servicesWarning. " WARNING and " .$servicesUnknown. " UNKNOWN Services";
				$state['State'] = "CRITICAL";
			}
			elseif($servicesWarning > 0) {
				$state['Count'] = $servicesWarning;
				$state['Output'] = $servicesWarning. " WARNING and " .$servicesUnknown. " UNKNOWN Services";
				$state['State'] = "WARNING";		
			}
			elseif($servicesUnknown > 0) {
				$state['Count'] = $servicesUnknown;
				$state['Output'] = $servicesUnknown." Services in UNKNOWN state";
				$state['State'] = "UNKNOWN";
				
			}
			elseif($servicesAck > 0) {
				$state['Count'] = $servicesAck;
				$state['Output'] = $servicesAck." services are in a NON-OK State but all are ACKNOWLEDGED";
				$state['State'] = "ACK";
			}
			elseif($servicesOk > 0) {
				$state['Count'] = $servicesOk;
				$state['Output'] = "All ".$servicesOk." services are OK";
				$state['State'] = "OK";		
			}

		return($state);
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
	function checkStates($Type,$Name,$RecognizeServices,$ServiceName="",$StatePos="0")
	{
		$rotateUrl = "";
		unset($state);
		//Status vom Host oder Service Prüfen.
		if($Type == "host") {
			if(isset($Name)) {
				$state = $this->findStateHost($Name,$RecognizeServices);
			}
		}
		elseif($Type == "service") {
			if(isset($Name)) {
				$state = $this->findStateService($Name,$ServiceName);
            }
        }

		elseif($Type == "hostgroup") {
			if(isset($Name)) {
				$state = $this->findStateHostgroup($Name,$RecognizeServices);
			}
		}
		//Status einer Servicegroup prüfen.
		elseif($Type == "servicegroup") {
			if(isset($Name)) {
				$state = $this->findStateServicegroup($Name);
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
}
?>
