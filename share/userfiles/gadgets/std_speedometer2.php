<?php

/*****************************************************************************
 *
 * std_speedometer2.php - Sample gadget for NagVis
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
 * dynamic image for visualizing things as graphs in NagVis. This one is based
 * on std_speedometer.php.
 *
 * The gadget gets its data from the NagVis frontend by parameters. This
 * gadget only needs the "perfdata" parameter
 * The values are accessible using the array aPerfdata. The structure is
 * shown in gadgets_core.php.

 * The behaviour of the gadget can be influenced by two directives in the
 * service definition of the map config file:
 * gadget_scale=n (n being the size of the graph(s) in percent)
 * gadget_opts=option=value (multiple options are separated by spaces)
 *    option is one of the following:
 *       columns=n 
 *          number of columns of graphs (if applicable), default is 3
 *       string=s 
 *          s is a string the perfdata label has to contain
 *       current=<0|1>    
 *          1 = show the current value along with the label (default)
 *       label=<0|1>
 *          1 = show host name/service description in the upper left
 *              corner of the graph (0 is default) 

 * NagVis also passes the following parameters to the gadget using the array
 * $aOpts:
 *  - name1:     Hostname
 *  - name2:     Service description
 *  - state:     Current state
 *  - stateType: Current state type (soft/hard)
 *  - scale:     Scale of the gadget in percent (default is 100)
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

//==================
// Set image sizing.
//==================

$ratio = $aOpts['scale'] / 100;

$imgwidth  = 220 * $ratio;
$imgheight = 110 * $ratio;
$outerdia  = 150 * $ratio;
$outerdia2 = $outerdia + 10;
$outerrad  = $outerdia / 2;
$outerrad2 = $outerdia2 / 2;
$innerrad  = $outerdia / 10;

/**
 * Don't change anything below (unless you know what you do)
 */

// calculate positions of labels
for ($i=1; $i<=3; $i++) {
	$degrees = deg2rad (-45 * $i + 360);
	$bediffy[$i] = sin ($degrees) * $outerrad2;
	$bediffx[$i] = cos ($degrees) * $outerrad2;
	$bediffy1[$i]= sin ($degrees) * ($outerrad-5);
	$bediffx1[$i]= cos ($degrees) * ($outerrad-5);
}

//==========================================
// Set Minimum, Default, and Maximum values.
//==========================================

$min = 0;
$max = -1;
$default = 0; 
 
$pdc = count($aPerfdata);	// performance data count
$string = '';					// string in perfdata label
$current = 1;					// show current value
$label = 0;						// show host/service label
$cols = 3;						// no. of columns with graphs

//==================
// scan gadget_opts
//==================

if (isset($aOpts['opts']) && ($aOpts['opts'] != '')){
	preg_match_all ('/(\w+)=(\w+)/',$aOpts['opts'],$matches,PREG_SET_ORDER);
	for ($i = 0; $i < count($matches); $i++){
		if ($matches[$i][1] == 'columns') { $cols = $matches[$i][2]; }
		if ($matches[$i][1] == 'string') { $string = $matches[$i][2]; }
		if ($matches[$i][1] == 'current') { $current = $matches[$i][2]; }
		if ($matches[$i][1] == 'label') { $label = $matches[$i][2]; }
	}
}
$rows = ceil($pdc / $cols);	// max. no. of rows with graphs

//====================
// Create tacho image.
//====================

$img=imagecreatetruecolor($imgwidth*$cols, $imgheight*$rows);

$oBackground = imagecolorallocate($img, 122, 23, 211);
$oBlack = imagecolorallocate($img, 0, 0, 0);
$oGreen = imagecolorallocate($img, 0, 255, 0);
$oYellow = imagecolorallocate($img, 255, 255, 0);
$oRed = imagecolorallocate($img, 255, 0, 0);
$oBlue = imagecolorallocate($img, 0, 0, 255);

imagefill($img, 0, 0, $oBackground);
imagecolortransparent($img, $oBackground);

$offG = 0;					// current graph
if ($label == 1) {
	imagestring($img, 1, 0, 0, $aOpts['name1'], $oBlack);	// print hostname
	imagestring($img, 1, 0, 8, $aOpts['name2'], $oBlack);	// print service description
}

for ($i=0; $i < $pdc; $i++){
	$label = preg_replace('(.*::)','',$aPerfdata[$i]['label']);	// omit check_multi description
	if (preg_match("/$string/",$label)) {
		$colour = '';
		$value = $aPerfdata[$i]['value'];
		$warn = $aPerfdata[$i]['warning'];
		$warn = preg_replace ('(:.*)','',$warn);		// ignore range settings
		$crit = $aPerfdata[$i]['critical'];
		$crit = preg_replace ('(:.*)','',$crit);		// ignore range settings
		$min = $aPerfdata[$i]['min'];
		$max = $aPerfdata[$i]['max'];
		$uom = $aPerfdata[$i]['uom'];
		$offX = ($offG % $cols) * $imgwidth;			// calculate left x-axis position
		$offY = floor($offG / $cols) * $imgheight;	// calculate upper y-axis position
		$centerx = $offX + $imgwidth / 2;				// center of graph, x-axis
		$centery = $offY + $imgheight - 20;				// center of graph, y-axis

		// determine the upper limit
		$limit = $max;
		if ($limit < $crit) {
			$limit = $crit;
		}
		if (($warn > $crit) && ($limit < $warn)) {
			$limit = $warn;
		}
		if ($value > $limit) {
			$limit = $value;
		}
		if ($limit < 1) {
			$limit = 1;
		}
		if ($uom == '%') {
			$limit = 100;
		}
		if (isset($warn) && isset($crit)) {
			if ($warn < $crit) {
				if ($value >= $warn) { $colour = $oYellow; };
				if ($value >= $crit) { $colour = $oRed; };
			} else {
				if ($value <= $warn) { $colour = $oYellow; };
				if ($value <= $crit) { $colour = $oRed; };
			}
		}
		// "highlight" graph if non-ok value
		if ($colour != '') {
			imagefilledrectangle ($img, $offX,$offY,$offX+$imgwidth-1,$centery+20,$colour);
		}
		
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
		
		// If there is no max value given set it using the critical value
		if(intval($max) == 0 || $max == '') {
			$max = $crit + 1;
		}
		
		//================
		// Calculate degrees of value, warn, critical
		//================
		
		$p = 180 / $limit * $value;
		$warnp = -180 + (180 / $limit * $warn);
		$critp = -180 + (180 / $limit * $crit);
		
		// If the critp is bigger than -1 it can not be rendered by the php functions.
		// Set it to -1 for having at least a small critical area drawn
		if($critp > -1) {
			$critp = -1;
		}
		
		
		// Base
		imagefilledarc($img,$centerx, $centery, $outerdia, $outerdia, 180, 0, $oGreen, IMG_ARC_EDGED);
		
		// Warning
		if($warn && $warnp <= -1) {
			if ($warn < $crit) {
				// The "360 +" fix has been worked out by hipska. Thanks for that!
				imagefilledarc($img, $centerx, $centery, $outerdia, $outerdia, 360 + $warnp, 0, $oYellow, IMG_ARC_EDGED);
			} else {
				// The "360 +" fix has been worked out by hipska. Thanks for that!
				imagefilledarc($img, $centerx, $centery, $outerdia, $outerdia, 180, 360 + $warnp, $oYellow, IMG_ARC_EDGED);
			}
		}
		// Critical
		if($crit && $critp <= -1) {
			if ($warn < $crit) {
				// The "360 +" fix has been worked out by hipska. Thanks for that!
				imagefilledarc($img,$centerx, $centery, $outerdia, $outerdia, 360 + $critp, 0, $oRed, IMG_ARC_EDGED);
			} else {
				// The "360 +" fix has been worked out by hipska. Thanks for that!
				imagefilledarc($img,$centerx, $centery, $outerdia, $outerdia, 180, 360 + $critp, $oRed, IMG_ARC_EDGED);
			}
		}
		
		// Borders
		imagearc($img, $centerx, $centery+1, $outerdia+2, $outerdia+2, 180, 0, $oBlack);
		imagefilledarc($img, $centerx, $centery, $innerrad, $innerrad, 180, 0, $oBlue, IMG_ARC_EDGED);
		
		//===================
		// Create tacho line.
		//===================
		
		$degrees = deg2rad(-$p+360);
		$diffx = cos ($degrees) * $outerrad2;
		$diffy = sin ($degrees) * $outerrad2;
		imagefilledarc($img, ($centerx-$diffx), ($centery+$diffy), $outerdia2, $outerdia2, ($p-1), ($p+1), $oBlue, IMG_ARC_EDGED);
		
		//===================
		// Labels
		//===================
		
		// Speedometer labels
		
		imageline($img, ($centerx-$outerrad-5), ($centery+1), ($centerx+$outerrad+5), ($centery+1), $oBlack);
		imagestring($img, 1, ($centerx-$outerrad-15), ($centery-6), 0, $oBlack);
		imagestring($img, 1, ($centerx+$outerrad+8), ($centery-6), "$limit $uom", $oBlack);
		
		$count = 1;
		$iOffsetX = -10;
		for($d=1; $d<=3; $d++) {
			
			imageline($img, ($centerx-$bediffx[$d]), ($centery+$bediffy[$d]),($centerx-$bediffx1[$d]), ($centery+$bediffy1[$d]), $oBlack);
			imagestring($img , 1 ,($centerx-$bediffx[$d]+$iOffsetX-8), ($centery+$bediffy[$d]-10) , ($limit/4*$d) , $oBlack);
			
			$iOffsetX = $iOffsetX + 10;
		}

		if ($current == 1) {
			imagestring($img, 1, $offX, $imgheight+$offY-10, $label.': '.$value, $oBlack);
		}
		$offG++;
	}
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
