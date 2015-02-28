<?php

/*****************************************************************************
 *
 * std_bar.php - Sample gadget for NagVis
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
 * dynamic image for visualizing things as graphs in NagVis.
 * This one was inspired by "fs_usage_bar" (Copyright Richard Leitner)
 * http://exchange.nagvis.org/exchange/Gadgets/Filesystem-Usage-Bar/
 *
 * The gadget gets its data from the NagVis frontend by parameters. This
 * gadget only needs the "perfdata" parameter
 * The values are accessible using the array aPerfdata. The structure is
 * shown in gadgets_core.php.

 * The behaviour of the gadget can be influenced by two directives in the
 * service definition of the map config file:
 * gadget_scale=n (n being the size of the graph(s) in percent, default = 100)
 * gadget_opts=option=value (multiple options are separated by spaces)
 *    option is one of the following:
 *       columns=n
 *          number of columns of graphs (if applicable), default is 3
 *       string=s
 *          s is a string the perfdata label has to contain
 *       current=<0|1>
 *          1 = show the current value along with the label (default)
 *       label=<0|1>
 *          1 = show host name/performance label in the last line of the graph
 *              (1 is default)

 * NagVis also passes the following parameters to the gadget using the array
 * $aOpts:
 *  - name1:     Hostname
 *  - name2:     Service description
 *  - state:     Current state
 *  - stateType: Current state type (soft/hard)
 *               This value is ignored as the performance data might contain
 *               several values
 *  - scale:     Scale of the gadget in percent
 *               This value is ignored in favour of gadget_scale
 *
 *****************************************************************************/

/**
 * Dummy perfdata for WUI
 *
 * This string needs to be set in every gadget to have some sample data in the
 * WUI to be able to place the gadget easily on the map
 * It has to be set BEFORE including gadgets_core.php
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

header("Content-type: image/png");

//=====================
// Set image parameters
//=====================

$ratio = $aOpts['scale'] / 100;
$fontDir = '/usr/share/fonts/truetype'; // openSuSE, SLES
// $fontDir = '/usr/share/fonts/truetype/ttf-dejavu'; // Debian, Ubuntu
// $fontDir = '/usr/share/fonts/dejavu-lgc'; // CentOS
// $fontDir = '/usr/share/fonts/dejavu'; // Fedora
$fontName = 'DejaVuSans-Bold.ttf';
$imgwidth  = 400 * $ratio;
$imgheight = 50 * $ratio;

/**
 * Don't change anything below (unless you know what you do)
 */

$font = "$fontDir/$fontName";

//==========================================
// Set Minimum, Default, and Maximum values.
//==========================================

$min = 0;
$max = -1;
$default = 0;

$pdc = count($aPerfdata);  // performance data count
$string = '';              // string in perfdata label
$current = 1;              // show current value
$label = 1;                // show host/service label
$cols = 3;                 // no. of columns with graphs
$threshold = 'pct';        // threshold values in percent

$sect1 = intval($imgheight / 5);
$sect2 = intval($imgheight / 2);
$sect3 = intval($imgheight / 5)*3;
$chrSize = $ratio * 5;
if ($chrSize < 1) { $chrSize = 1; }

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
      if ($matches[$i][1] == 'threshold') { $threshold = $matches[$i][2]; }
   }
}
$rows = ceil($pdc / $cols);   // max. no. of rows with graphs

//====================
// Create image
//====================

$img=imagecreatetruecolor($imgwidth*$cols, $imgheight*$rows);

$oBackground = imagecolorallocate($img, 122, 23, 211);
$oBlack = imagecolorallocate($img, 0, 0, 0);
$oGreen = imagecolorallocate($img, 0, 255, 0);
$oYellow = imagecolorallocate($img, 255, 255, 0);
$oYellowAck = imagecolorallocate($img, 200, 255, 0);
$oRed = imagecolorallocate($img, 255, 0, 0);
$oRedAck = imagecolorallocate($img, 200, 0, 0);
$oBlue = imagecolorallocate($img, 0, 0, 255);

imagefill($img, 0, 0, $oBackground);
imagecolortransparent($img, $oBackground);

$offG = 0;              // current graph
for ($i=0; $i < $pdc; $i++){
   $desc = preg_replace('(.*::)','',$aPerfdata[$i]['label']);  // omit check_multi description
   if (preg_match("/$string/",$desc)) {
      $colour = '';
      $value = $aPerfdata[$i]['value'];
      $warn = $aPerfdata[$i]['warning'];
      $warn = preg_replace ('(:.*)','',$warn);     // ignore range settings
      $crit = $aPerfdata[$i]['critical'];
      $crit = preg_replace ('(:.*)','',$crit);     // ignore range settings
      $min = $aPerfdata[$i]['min'];
      $max = $aPerfdata[$i]['max'];
      $uom = $aPerfdata[$i]['uom'];
      $ack = $aPerfdata[$i]['ack'];
      $downtime = $aPerfdata[$i]['downtime'];
      $offX = ($offG % $cols) * $imgwidth;         // calculate left x-axis position
      $offY = floor($offG / $cols) * $imgheight;   // calculate upper y-axis position
      $maxX = $imgwidth-15;
      $maxY = $imgheight-5;

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
            if ($value >= $warn) { $colour = ($ack) ? $oYellowAck : $oYellow; };
            if ($value >= $crit) { $colour = ($ack) ? $oRedAck : $oRed; };
         } else {
            if ($value <= $warn) { $colour = ($ack) ? $oYellowAck : $oYellow; };
            if ($value <= $crit) { $colour = ($ack) ? $oRedAck : $oRed; };
         }
      }
      // create box
      imagefilledrectangle ($img, $offX, $offY+1, $offX+$maxX, $offY+$maxY, $oGreen);
      imageRectangle($img, $offX, $offY, $offX+$maxX, $offY+$maxY, $oBlack);
      $maxX--;
      $maxY--;

      // "highlight" graph if non-ok value
      if ($colour != '') {
         imagefilledrectangle ($img, $offX+1, $offY+$sect3+1, $offX+$maxX, $offY+$maxY, $colour);
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
      // Calculate value, warn, critical percentages/values
      //================

      $p = 100 / $limit * $value;
      $warnp = round(100 / $limit * $warn,0);
      $critp = round(100 / $limit * $crit,0);
      $valuev = ($maxX / 100 * $p);
      $warnv = intval($maxX / 100 * $warnp);
      $critv = intval($maxX / 100 * $critp);
      $warnt = ($threshold == 'pct') ? $warnp : $warn;
      $critt = ($threshold == 'pct') ? $critp : $crit;

      //===================
      // create warning/critical areas, current value
      //===================

      // Warning
      if($warn) {
         if ($warn < $crit) {
            imageFilledRectangle($img, $offX+$warnv+1, $offY+1, $offX+$maxX, $offY+$sect1, $oYellow);
         } else {
            imageFilledRectangle($img, $offX+1, $offY+1, $offX+$warnv-1, $offY+$sect1, $oYellow);
         }
         if (file_exists ("$font")) {
            ImageTTFText($img, $chrSize*2, 0, $offX+$warnv+1, $offY+$sect1, $oBlack, $font, intval($warnt));
         } else {
            imagestring($img, $chrSize, $offX+$warnv+1, $offY-2, intval($warnt), $oBlack);
         }
      }
      // Critical
      if($crit) {
         if ($warn < $crit) {
            imageFilledRectangle($img, $offX+$critv+1, $offY+1, $offX+$maxX, $offY+$sect1, $oRed);
         } else {
            imageFilledRectangle($img, $offX+1, $offY+1, $offX+$critv-1, $offY+$sect1, $oRed);
         }
         if (file_exists ("$font")) {
            ImageTTFText($img, $chrSize*2, 0, $offX+$critv+1, $offY+$sect1, $oBlack, $font, intval($critt));
         } else {
            imagestring($img, $chrSize, $offX+$critv+1, $offY-2, intval($critt), $oBlack);
         }
      }
      imagefilledRectangle($img, $offX+1, $offY+$sect1+1, $offX+$valuev+1, $offY+$sect3, $oBlue);

      //===================
      // Labels
      //===================

      if ($current == 1) {
         $maxv = "";
         if (isset($aPerfdata[$i]['max'])) { $maxv = " of ".$aPerfdata[$i]['max']; }
         if ($down) { $maxv = " [down]"; }
         if (file_exists ("$font")) {
            ImageTTFText($img, $chrSize*3.5, 0, $offX+5, $offY+$sect3-1, $oBlack, $font, $desc . ':' . $value . $uom . $maxv);
         } else {
            imagestring($img, $chrSize, $offX+3, $offY+$sect1+2, $desc.': '.$value . $uom . $maxv, $oBlack);
         }

      if ($label == 1) {
         $hostname = (strlen($aOpts['name1']) > 15) ? substr($aOpts['name1'],0,14)."..." : $aOpts['name1'];
         $svcdesc = (strlen($aOpts['name2']) > 15) ? substr($aOpts['name2'],0,14)."..." : $aOpts['name2'];
         if (strlen($desc) > 15) {
            $desc = substr($desc,0,14)."...";
         }
         if (file_exists ("$font")) {
            ImageTTFText($img, $chrSize*2.5, 0, $offX+3, $offY+$maxY-1, $oBlack, $font, $hostname);
            ImageTTFText($img, $chrSize*2.5, 0, $offX+$imgwidth/2, $offY+$maxY-1, $oBlack, $font, $svcdesc);
//          ImageTTFText($img, $chrSize*2.5, 0, $offX+$imgwidth/2, $offY+$maxY-1, $oBlack, $font, $desc);
         } else {
            imagestring($img, $chrSize, $offX+3, $offY+$sect3, $hostname, $oBlack);
            imagestring($img, $chrSize, $offX+$imgwidth/2, $offY+$sect3, $svcdesc, $oBlack);
//          imagestring($img, $chrSize, $offX+$imgwidth/2, $offY+$sect3, $desc, $oBlack); // perf label
         }
      }
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

define service {
host_name=uxze01
service_description=root-volume
x=50
y=50
view_type=gadget
gadget_url=std_bar.php
gadget_scale=100
}
