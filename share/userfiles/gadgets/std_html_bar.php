<?php
/*****************************************************************************
 *
 * std_html_bar.php - Sample HTML based gadget for NagVis
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
 * This is a simple gadget for NagVis. A gadget is a script which generates a
 * small dynamic part on the NagVis map. A gadget can be images based or,
 * since NagVis 1.6, HTML based for visualizing things in NagVis.
 *
 * The gadget gets its data from the NagVis frontend by parameters. This
 * gadget only needs the "perfdata" parameter. NagVis also passes the
 * following parameters to the gadgets:
 *  - name1:     Hostname
 *  - name2:     Service description
 *  - state:     Current state
 *  - stateType: Current state type (soft/hard)
 *  - ack:       Acknowledged or not
 *  - downtime:  In downtime or not
 *  + user defined params
 *
 *****************************************************************************/

/** 
 * Dummy perfdata for WUI
 *
 * This string needs to be set in every gadget to have some sample data in the 
 * WUI to be able to place the gadget easily on the map
 ******************************************************************************/
$sDummyPerfdata = 'config=20%;80;90;0;100';

/**
 * Needs to be configured to tell gadgets_core.php how to handle the outputs
 * e.g. in case of error messages. This defaults to 'img'.
 */
$_MODE          = 'html';

// Include the gadgets core. Also handle OMD default and local paths
if(substr($_SERVER["SCRIPT_FILENAME"], 0, 4) == '/omd') {
    $core = dirname($_SERVER["SCRIPT_FILENAME"]) . '/gadgets_core.php';
    if(file_exists($core))
        require($core);
    else
        require(str_replace('local/share/', 'share/', $core));
} else {
    require('./gadgets_core.php');
}

/*******************************************************************************
 * Start gadget main code
 ******************************************************************************/

//==========================================
// Set Minimum, Default, and Maximum values.
//==========================================

$min = 0;
$max = -1;
$default = 0; 
 
/* Now read the parameters */

// This gadget is simple and dirty, it only recognizes the first dataset of
// performance data

$value = $aPerfdata[0]['value'];
$uom   = $aPerfdata[0]['uom'];
$warn  = $aPerfdata[0]['warning'];
$crit  = $aPerfdata[0]['critical'];
$min   = $aPerfdata[0]['min'];
$max   = $aPerfdata[0]['max'];

//================
// Normalize / Fix value and max
//================

if($value == null) {
    $value = $default;
} else {
    if($max != '' && $value < $min) {
    $value = $min;
    } elseif($max != '' && $max != -1 && $value > $max) {
	$value = $max;
    }
}

// If there is no max value given set it critical or warning value
if(intval($max) == 0 || $max == '')
    if(intval($crit) == 0 || $crit != '')
        $max = $crit + 1;
    else
	$max = $warn + 1;

$width = (int) $value * 100 / $max;

echo "<div style='position:absolute;left:0;width:100px;height:30px;text-align:center;line-height:30px;'>".$value.$uom."</div>";
echo "<table style='width:100px;border:1px solid #000;height:30px;'><tr><td style='text-align:center;background-color:#dfdfdf;width:".$width."px'></td><td></td></tr></table>";
exit(0);
?>
