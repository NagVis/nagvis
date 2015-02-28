<?php
/*****************************************************************************
 *
 * std_speedometer.php - Sample gadget for NagVis
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
 * dynamic image for visualizing things as graphics in NagVis.
 *
 * The gadget gets its data from the NagVis frontend by parameters. This
 * gadget only needs the "perfdata" parameter. NagVis also passes the
 * following parameters to the gadgets:
 *  - name1:     Hostname
 *  - name2:     Service description
 *  - state:     Current state
 *  - stateType: Current state type (soft/hard)
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
$_MODE          = 'img';

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

header("Content-type: image/png");

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
$warn = $aPerfdata[0]['warning'];
$crit = $aPerfdata[0]['critical'];
$min = $aPerfdata[0]['min'];
$max = $aPerfdata[0]['max'];

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

//================
// Calculate degrees of value, warn, critical
//================

$p = 180 / ( $max + $min ) * $value;
$warnp = -180 + (180 / ( $max - $min ) * ( $warn - $min ) );
$critp = -180 + (180 / ( $max - $min ) * ( $crit - $min ) );

// If the critp is bigger than -1 it can not be rendered by the php functions.
// Set it to -1 for having at least a small critical area drawn
if($critp > -1) {
	$critp = -1;
}

/**
 * Don't change anything below (except you know what you do)
 */

//==================
// Set image sizing.
//==================

$imgwidth = 220;
$imgheight = 110;
$innerdia = 0;
$outerdia = 150;
$linedia = 160;
$linewidth = 3;
$centerx = $imgwidth / 2;
$centery = $imgheight - 20;
$innerrad = $innerdia / 2;
$outerrad = $outerdia / 2-1;
$linerad = $linedia / 2;
$lineext = $linewidth/2;

//====================
// Create tacho image.
//====================

$img=imagecreatetruecolor($imgwidth, $imgheight);

$oBackground = imagecolorallocate($img, 122, 23, 211);
$oBlack = imagecolorallocate($img, 0, 0, 0);
$oGreen = imagecolorallocate($img, 0, 255, 0);
$oYellow = imagecolorallocate($img, 255, 255, 0);
$oRed = imagecolorallocate($img, 255, 0, 0);
$oBlue = imagecolorallocate($img, 0, 0, 255);

imagefill($img, 0, 0, $oBackground);
imagecolortransparent($img, $oBackground);

// Base
imagefilledarc($img,$centerx, $centery, $outerdia, $outerdia, 180, 0, $oGreen, IMG_ARC_EDGED);

// Warning
if($warn && $warnp <= -1) {
	// The "360 +" fix has been worked out by hipska. Thanks for that!
	imagefilledarc($img, $centerx, $centery, $outerdia, $outerdia, 360 + $warnp, 0, $oYellow, IMG_ARC_EDGED);
}
// Critical
if($crit && $critp <= -1) {
	// The "360 +" fix has been worked out by hipska. Thanks for that!
	imagefilledarc($img,$centerx, $centery, $outerdia, $outerdia, 360 + $critp, 0, $oRed, IMG_ARC_EDGED);
}

// Borders
imagearc($img, $centerx, $centery+1, $outerdia+2, $outerdia+2, 180, 0, $oBlack);
imagefilledarc($img, $centerx, $centery, $outerdia/10, $outerdia/10,180, 0, $oBlue, IMG_ARC_EDGED);

//===================
// Create tacho line.
//===================

$diffy = sin (deg2rad(-$p+360))*(($outerdia+10)/2);
$diffx = cos (deg2rad(-$p+360))*(($outerdia+10)/2);
imagefilledarc($img, ($centerx-$diffx), ($centery+$diffy), ($outerdia+10), ($outerdia+10),($p-1),($p+1), $oBlue, IMG_ARC_EDGED);

//===================
// Labels
//===================

// Speedometer labels

imageline($img, ($centerx-$outerdia/2-5), ($centery+1), ($centerx+$outerdia/2+5), ($centery+1), $oBlack);
imagestring($img, 1, ($centerx-$outerdia/2-15), ($centery-6), $min , $oBlack); 
imagestring($img, 1, ($centerx+$outerdia/2+8), ($centery-6), $max, $oBlack);

$count = 1;
$iOffsetX = -10;
for($degrees=45; $degrees<180; $degrees = $degrees+45) {
	$bediffy=sin (deg2rad(-$degrees+360))*(($outerdia+10)/2);
	$bediffx=cos (deg2rad(-$degrees+360))*(($outerdia+10)/2);
	$bediffy1=sin (deg2rad(-$degrees+360))*(($outerdia-10)/2);
	$bediffx1=cos (deg2rad(-$degrees+360))*(($outerdia-10)/2);
	
	imageline($img, ($centerx-$bediffx), ($centery+$bediffy),($centerx-$bediffx1), ($centery+$bediffy1), $oBlack);
	imagestring($img , 1 ,($centerx-$bediffx+$iOffsetX-8), ($centery+$bediffy-10) , (($max-$min)/4*$count+$min) , $oBlack); 
	
	$count = $count+1;
	$iOffsetX = $iOffsetX + 10;
}

//==============
// Output image.
//==============

if(function_exists('imageantialias')) {
	imageantialias($img, true);
}

imagepng($img);
imagedestroy($img);
?>
