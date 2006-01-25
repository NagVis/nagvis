<?
##########################################################################
##     	        NagVis - The Nagios Visualisation Addon                 ##
##########################################################################
## class.CheckState_html - Backend module to fetch the status from      ##
##							the Nagios CGIs. All not special to one 	##
##		   					Backend related things should removed here!	##
##		   																##
##########################################################################
## Licenced under the terms and conditions of the GPL Licence, 			##
## please see attached "LICENCE" file	                                ##
##########################################################################

/**
* This Class read the States from Nagios-CGI'S
*/
class backend
{
	//Initialize function, must be called before anything else of this class. Maybe we should set this as constructor.
	function backendInitialize() {
		//THIS backend needs no inital preparation, but the function must be present to be compatible
		return 0;
	}

	/**
	* Find State from a Host
	*
	* @param string $Hostname
	* @param string $RecognizeServices
	* @param string $StatusCgi
	* @param string $CgiUser
	*
	* @author Michael Luebben <michael_luebben@web.de>
	* @author Andreas Husch
    */
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
        } else {
            preg_match('/<TD CLASS=\'hostTotalsPROBLEMS\'>(.*)<\/TD>/', $text, $hostTotalsPROBLEMS);
            preg_match('/<TD CLASS=\'hostTotalsUP\'>(.*)<\/TD>/', $text, $hostTotalsUP);
            preg_match('/<TD CLASS=\'status(.*)\'>/', $text, $hostTotalsACK);
        }

        if(isset($hostTotalsACK) && $hostTotalsACK[1] == 'HOSTDOWNACK') {
        	$state['State'] = 'ACK';
            $state['Count'] = $hostTotalsPROBLEMS[1];
            $state['Output'] = $hostTotalsPROBLEMS[1].' Host DOWN';
        }
        elseif(isset($hostTotalsPROBLEMS[1]) && $hostTotalsPROBLEMS[1] != 0) {
        	$state['State'] = 'DOWN';
            $state['Count'] = $hostTotalsPROBLEMS[1];
            $state['Output'] = $hostTotalsPROBLEMS[1].' Host DOWN';
        }
        elseif(isset($hostTotalsUP[1]) && $hostTotalsUP[1] != 0) {
     	   $state['State'] = 'UP';
           $state['Count'] = $hostTotalsUP[1];
           $state['Output'] = $hostTotalsUP[1].' Host UP';
        } else {
		   $state['State'] = 'ERROR';
           $state['Count'] = '0';
           $state['Output'] = 'HTML-Backend (CheckState_html) got NO DATA from the CGI while tring to parse a Host!';
        }

        if($RecognizeServices == 1) {
        	preg_match('/<TD CLASS=\'serviceTotalsOK\'>(.*)<\/TD>/', $text, $serviceTotalsOK);
            preg_match('/<TD CLASS=\'serviceTotalsCRITICAL\'>(.*)<\/TD>/', $text, $serviceTotalsCRITICAL);
            preg_match('/<TD CLASS=\'serviceTotalsWARNING\'>(.*)<\/TD>/', $text, $serviceTotalsWARNING);
            preg_match('/<TD CLASS=\'serviceTotalsUNKNOWN\'>(.*)<\/TD>/', $text, $serviceTotalsUNKNOWN);
    
			if(isset($serviceTotalsCRITICAL[1]) && $serviceTotalsCRITICAL[1] != 0) {
				if(!isset($hostTotalsACK)) {
					$state['State'] = 'DOWN';
				}
				$state['Count'] = $serviceTotalsCRITICAL[1];
                $state['Output'] = $serviceTotalsCRITICAL[1].' Services in state CRITICAL';
		$state['State'] = 'CRITICAL';
            }
            elseif(isset($serviceTotalsWARNING[1]) && $serviceTotalsWARNING != 0) {
				if(!isset($hostTotalsACK)) {
					$state['State'] = 'DOWN';
                }
                $state['Count'] = $serviceTotalsWARNING[1];
                $state['Output'] = $serviceTotalsWARNING[1].' Service in state WARNING';
		$state['State'] = 'WARNING';
            }
            elseif(isset($serviceTotalsUNKNOWN[1]) && $serviceTotalsUNKNOWN != 0) {
				if(!isset($hostTotalsACK)) {
					$state['State'] = 'DOWN';
                }
                $state['Count'] = $serviceTotalsUNKNOWN[1];
                $state['Output'] = $serviceTotalsUNKNOWN[1].' Service in state UNKNOWN';
		$state['State'] = 'UNKNOWN';
            }
            elseif(isset($serviceTotalsOK[1]) && $serviceTotalsOK[1] != 0) {
                $state['Count'] = $serviceTotalsOK[1];
                $state['Output'] = $serviceTotalsOK[1].' Services in state OK';
                }
            }
            return($state);
        }
	
	/**
	* Find State from a Hostgroup
	*
	* @param string $Hostgroupname
	* @param string $RecognizeServices
	* @param string $StatusCgi
	* @param string $CgiUser
	*
	* @author Michael Luebben <michael_luebben@web.de>
	* @author Andreas Husch
    */
	function findStateHostgroup($Hostgroupname,$RecognizeServices,$StatusCgi,$CgiUser) {
		$rotateUrl = "";
		putenv("REQUEST_METHOD=GET");
		putenv("REMOTE_USER=$CgiUser");
		putenv("QUERY_STRING=hostgroup=$Hostgroupname");
                $handle = popen($StatusCgi, 'r');
                $text = fread($handle, 9000);
                pclose($handle);
		if (preg_match('/<DIV CLASS=\'errorMessage\'>Sorry, but host group(.*)<\/DIV>/', $text)) {

			$state['State'] = 'ERROR';
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
			$state['State'] = 'ERROR';
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
	
	/**
	* Find State from a Service
	*
	* @param string $Hostname
	* @param string $ServicesName
	* @param string $StatusCgi
	* @param string $CgiUser
	*
	* @author Michael Luebben <michael_luebben@web.de>
	* @author Andreas Husch
    */
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
			$state['Output'] = 'Sorry, but service '.$ServiceName.' not found';
		}
		
		if($state[1] == 'serviceOK') {
			$state['State'] = 'OK';
			$state['Output'] = $output[1];
		}
		elseif($HostAck['State'] == 'ACK') {
			$state['State'] = 'SACK';
		}
		elseif($state[1] == 'serviceWARNING') {
			$state['State'] = 'WARNING';
			$state['Output'] = $output[1];
		}
		elseif($state[1] == 'serviceCRITICAL') {
			$state['State'] = 'CRITICAL';
			$state['Output'] = $output[1];
		}
		elseif($state[1] == 'serviceUNKNOWN') {
			$state['State'] = 'UNKNOWN';
			$state['Output'] = $output[1];
        } else {
			$state['State'] = 'ERROR';
			$state['Output'] = 'HTML-Backend (CheckState_html) got NO DATA from the CGI while tring to parse a Service';
		}
        Return $state;
	}
	
	/**
	* Find State from a Servicegroup
	*
	* @param string $ServiceGroup
	* @param string $StatusCgi
	* @param string $CgiUser
	*
	* @author Michael Luebben <michael_luebben@web.de>
	* @author Andreas Husch
    */
	function findStateServicegroup($ServiceGroup,$StatusCgi,$CgiUser) {
		$rotateUrl = "";
		putenv("REQUEST_METHOD=GET");
		putenv("REMOTE_USER=$CgiUser");
		putenv("QUERY_STRING=servicegroup=$ServiceGroup");
                $handle = popen($StatusCgi, 'r');
                $text = fread($handle, 9000);
                pclose($handle);
		if (preg_match('/<DIV CLASS=\'errorMessage\'>Sorry, but servicegroup(.*)<\/DIV>/', $text)) {
			$state['State'] = 'ERROR';
			$state['Count'] = '0';
			$state['Output'] = 'Sorry, but servicegroup '.$ServiceGroup.' not found';
		}
		else {
			preg_match('/<TD CLASS=\'serviceTotalsOK\'>(.*)<\/TD>/', $text, $serviceTotalsOK);
			preg_match('/<TD CLASS=\'serviceTotalsCRITICAL\'>(.*)<\/TD>/', $text, $serviceTotalsCRITICAL);
			preg_match('/<TD CLASS=\'serviceTotalsWARNING\'>(.*)<\/TD>/', $text, $serviceTotalsWARNING);
			preg_match('/<TD CLASS=\'serviceTotalsUNKNOWN\'>(.*)<\/TD>/', $text, $serviceTotalsUNKNOWN);
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
		elseif(isset($serviceTotalsUNKNOWN[1]) && $serviceTotalsUNKNOWN != 0) {
			$state['State'] = 'UNKNOWN';
			$state['Count'] = $serviceTotalsUNKNOWN[1];
			$state['Output'] = $serviceTotalsUNKNOWN[1].' Services in state UNKNOWN';
		}
		elseif(isset($serviceTotalsOK[1]) && $serviceTotalsOK[1] != 0) {
			$state['State'] = 'OK';
			$state['Count'] = $serviceTotalsOK[1];
			$state['Output'] = $serviceTotalsOK[1].' Services in state OK';

		}
		else {
			$state['State'] = 'ERROR';
            $state['Count'] = '0';
            $state['Output'] = 'HTML-Backend (CheckState_html) got NO DATA from the CGI while tring to parse a Servicegroup';
		}

		return ($state);
	}
	
	/**
	* This function checks the state and return a errorbox, when no state found
	*
	* @param string $Type
	* @param string $Name
	* @param string $RecognizeServices
	* @param string $ServiceName
	* @param string $StatePos
	* @param string $StatusCgi
	* @param string $CgiUser
	*
	* @author Michael Luebben <michael_luebben@web.de>
	* @author Andreas Husch
    */
	function checkStates($Type,$Name,$RecognizeServices,$ServiceName="",$StatePos="0",$CgiPath,$CgiUser)
	{
		$rotateUrl = "";
		unset($state);
		
		if(!isset($RecognizeServices)) {
			$RecognizeServices = "1";
    	}
    	
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
		elseif($Type == "servicegroup") {
			$StatusCgi = $CgiPath.'status.cgi';
			if(isset($Name) && isset($CgiUser)) {
				$state = $this->findStateServicegroup($Name,$StatusCgi,$CgiUser);
			}
		}
		if(!isset($state)) {
			$nagvis = new FRONTEND();	
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
