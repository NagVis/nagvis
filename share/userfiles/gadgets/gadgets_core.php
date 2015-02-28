<?php
/*****************************************************************************
 *
 * gadgets_core.php - Core code for standard gadgets, provides basic functions
 *
 * Copyright (c) 2004-2015 NagVis Project (Contact: info@nagvis.org)
 *
 * License:
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2 as
 * published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 675 Mass Ave, Cambridge, MA 02139, USA.
 *
 *****************************************************************************
 *
 * A gadget is a script which generates a dynamic image for visualizing things
 * as graphics in NagVis.
 *
 * The gadget gets its data from the NagVis frontend via parameters.
 * This code provides some functions common to all gadgets:
 *  - examining and parsing the passed parameters
 *  - creating data structures using the given data
 *  - showing an error box if the need arises
 *
 * NagVis passes the following parameters to the gadgets:
 *  - name1:     Hostname
 *  - name2:     Service description
 *  - state:     Current state
 *  - stateType: Current state type (soft/hard)
 *  - scale:    Scale of the gadget in percent (default 100)
 *
 *****************************************************************************
 *
 * Datastructure:
 * 
 * $aPerfdata as 2-dimensional array where index ranges from 0 to n-1,
 * depending on how many perfdata values are provided by the service
 * 
 * aPerfdata[index]['label']         -   label of the perfdata
 *                 ['value']         -   actual perfdata
 *                 ['uom']           -   unit of measurement (might be NULL)
 *                 ['warning']       -   warning threshold (if over)
 *                 ['warning_min']   -   warning threshold (if below)
 *                 ['warning_max']   -   warning threshold (if above)
 *                 ['critical']      -   critical threshold (might be NULL)
 *                 ['critical_min']  -   critical threshold (if below)
 *                 ['critical_max']  -   critical threshold (if above)
 *                 ['min']           -   minimum possible value (might be NULL)
 *                 ['max']           -   maximum possible value (might be NULL)
 *
 * 
 * $aOpts as array of the parameters passed to the gadget
 * 
 * aOpts['name1']                 -   Hostname
 * aOpts['name2']                 -   Servicename
 * aOpts['state']                 -   State of the service (OK, WARNING,
 *                                    CRITICAL, UNKNOWN)  
 * aOpts['stateType']             -   Type of the state (HARD, SOFT)
 * aOpts['scale']                 -   Scale of the gadget in percent (INTEGER)
 *  
 ******************************************************************************/

/**
 * parsePerfdata() parses a Nagios performance data string to an array
 *
 * Function adapted from PNP process_perfdata.pl. Thanks to Jörg Linge.
 * The function is originally taken from Nagios::Plugin::Performance
 * Thanks to Gavin Carr and Ton Voon
 *
 * @param   String  Nagios performance data
 * @return  Array   Array which contains parsed performance data information
 * @author      Lars Michelsen <lars@vertical-visions.de>
 */
function parsePerfdata($sPerfdata) {
	$aMatches = Array();
	$aPerfdata = Array();
	
	// Cleanup
	$sPerfdata = str_replace(',', '.', $sPerfdata);
	$sPerfdata = preg_replace('/\s*=\s*/', '=', $sPerfdata);
	
	// Nagios::Plugin::Performance
	$sTmpPerfdata = $sPerfdata;
	
	// Parse perfdata
	// We are trying to match the following string:
	//  temp=78.8F;55:93;50:98;0;100;
	//               metric    current    unit      warning          critical          min           max
	preg_match_all('/([^=]+)=([\d\.\-]+)([\w%\/]*);?([\d\.\-:~@]+)?;?([\d\.\-:~@]+)?;?([\d\.\-]+)?;?([\d\.\-]+)?\s*/', $sPerfdata, $aMatches, PREG_SET_ORDER);
	
	// When no match found
	if(!isset($aMatches[0])) {
		errorBox('ERROR: Found no valid performance data in string');
	}
	for($i = 0, $len = sizeof($aMatches); $i < $len; $i++) {
		$aTmp = $aMatches[$i];
		
		// Save needed values
		$aSet = Array('label' => $aTmp[1], 'value' => $aTmp[2]);
		
		// Save optional values
		if(isset($aTmp[3])) {
			$aSet['uom'] = $aTmp[3];
		} else {
			$aSet['uom'] = null;
		}
		if(isset($aTmp[4])) {
			$aSet['warning'] = $aTmp[4];
			preg_match_all('/([\d\.]+):([\d\.]+)/',$aTmp[4], $matches);
			if(isset($matches[0]) && isset($matches[0][0])) {
				$aSet['warning_min'] = $matches[1][0];
				$aSet['warning_max'] = $matches[2][0];
			}
		} else {
			$aSet['warning'] = null;
		}
		if(isset($aTmp[5])) {
			$aSet['critical'] = $aTmp[5];
			preg_match_all('/([\d\.]+):([\d\.]+)/',$aTmp[5], $matches);
			if(isset($matches[0]) && isset($matches[0][0])) {
				$aSet['critical_min'] = $matches[1][0];
				$aSet['critical_max'] = $matches[2][0];
			}
		} else {
			$aSet['critical'] = null;
		}
		if(isset($aTmp[6])) {
			$aSet['min'] = $aTmp[6];
		} else {
			$aSet['min'] = null;
		}
		if(isset($aTmp[7])) {
			$aSet['max'] = $aTmp[7];
		} else {
			$aSet['max'] = null;
		}
		
		$aPerfdata[] = $aSet;
	}

	return $aPerfdata;
}

/**
 * Prints out an error box
 *
 * @param       String  $msg    String with error message
 * @author      Lars Michelsen <lars@vertical-visions.de>
 */
function errorBox($msg) {
    global $_MODE;
    if(isset($_MODE) && $_MODE === 'html') {
        echo '<strong>'.$msg.'</strong>';
	exit;
    } else {
	$img = imagecreate(400,40);
    	
    	$bgColor = imagecolorallocate($img, 255, 255, 255);
    	imagefill($img, 0, 0, $bgColor);
    	
    	$fontColor = imagecolorallocate($img, 10, 36, 106);
    	imagestring($img, 2, 8, 8, $msg, $fontColor);
    	
    	imagepng($img);
    	imagedestroy($img);
    	exit;
    }
}

/* Now read the parameters */

$aOpts = Array();
$aPerfdata = Array();

/**
 * Needed:
 *  perfdata=load1=0.960;5.000;10.000;0; load5=0.570;4.000;6.000;0; load15=0.540;3.000;4.000;0;
 *
 * Optional
 *  name1=localhost
 *  name2=Current Load
 *  state=OK
 *  stateType=HARD
 *  scale=100
 */

// Get params without default values
foreach(Array('opts'  => null, 'name1'     => null, 'name2' => null,
              'state' => null, 'stateType' => null, 'scale' => 100,
              'ack'   => null, 'downtime'  => null) AS $opt => $default)
	if(isset($_GET[$opt]) && $_GET[$opt] != '')
		$aOpts[$opt] = $_GET[$opt];
	elseif($default !== null)
		$aOpts[$opt] = $default;

if(isset($_GET['perfdata']) && $_GET['perfdata'] != '')
	$aOpts['perfdata'] = $_GET['perfdata'];
elseif(isset($_GET['conf']) &&  $_GET['conf'] != '' && isset($sDummyPerfdata) && $sDummyPerfdata != '')
	$aOpts['perfdata'] = $sDummyPerfdata;
elseif(!isset($_GET['opts']) || strpos($_GET['opts'], 'no_perf') === false)
	errorBox('ERROR: The needed parameter "perfdata" is missing.');

/* Now parse the perfdata */
if(isset($_GET['opts']) && $_GET['opts'] != '') {
	if(strpos($_GET['opts'], 'no_perf') === false) {
		$aPerfdata = parsePerfdata($aOpts['perfdata']);
	}
} else {
	$aPerfdata = parsePerfdata($aOpts['perfdata']);
}

?>
