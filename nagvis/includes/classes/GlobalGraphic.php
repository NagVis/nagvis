<?php
/*****************************************************************************
 *
 * GlobalGraphic.php - class for calculating graphics and coords
 *
 * Copyright (c) 2004-2008 NagVis Project (Contact: lars@vertical-visions.de)
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
 *****************************************************************************/
 
/**
 * @author	Lars Michelsen <lars@vertical-visions.de>
 */
class GlobalGraphic {
	var $black;
	var $red;
	var $white;
	
	function GlobalGraphic() {
	}
	
	function init(&$image) {
		$this->black = imagecolorallocate($image, 0,0,0);
		// UNUSED: $this->red = imagecolorallocate($image, 255, 0, 0);
		// UNUSED:$this->white = imagecolorallocate($image, 255, 255, 255);
	}
	
	function middle($x1,$x2) {
		$ret = ($x1+($x2-$x1)/2);
		return $ret;
	}
	
	function drawArrow(&$im,$x1,$y1,$x2,$y2,$w,$solid,$color) {
		$point[0]=$x1 + $this->newX($x2-$x1, $y2-$y1, 0, $w);
		$point[1]=$y1 + $this->newY($x2-$x1, $y2-$y1, 0, $w);
		$point[2]=$x2 + $this->newX($x2-$x1, $y2-$y1, -4*$w, $w);
		$point[3]=$y2 + $this->newY($x2-$x1, $y2-$y1, -4*$w, $w);
		$point[4]=$x2 + $this->newX($x2-$x1, $y2-$y1, -4*$w, 2*$w);
		$point[5]=$y2 + $this->newY($x2-$x1, $y2-$y1, -4*$w, 2*$w);
		$point[6]=$x2;
		$point[7]=$y2;
		$point[8]=$x2 + $this->newX($x2-$x1, $y2-$y1, -4*$w, -2*$w);
		$point[9]=$y2 + $this->newY($x2-$x1, $y2-$y1, -4*$w, -2*$w);
		$point[10]=$x2 + $this->newX($x2-$x1, $y2-$y1, -4*$w, -$w);
		$point[11]=$y2 + $this->newY($x2-$x1, $y2-$y1, -4*$w, -$w);
		$point[12]=$x1 + $this->newX($x2-$x1, $y2-$y1, 0, -$w);
		$point[13]=$y1 + $this->newY($x2-$x1, $y2-$y1, 0, -$w);
		
		if ($solid) {
				imagefilledPolygon($im,$point,7,$color);
				imagepolygon($im,$point,7,$this->black);
		} else{
			imagepolygon($im,$point,7,$this->black);
		}
	}
	
	function newX($a,$b,$x,$y) {
		$ret = (cos(atan2($y,$x)+atan2($b,$a))*sqrt($x*$x+$y*$y));
		return $ret;
	}
	
	function newY($a,$b,$x,$y) {
		$ret = (sin( atan2($y,$x) + atan2($b,$a) ) * sqrt( $x*$x + $y*$y ));
		return $ret;
	}
}
?>