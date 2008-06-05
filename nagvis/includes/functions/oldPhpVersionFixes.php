<?php
/*****************************************************************************
 *
 * oldPhpVersionFixes.php - This implements some functions which are present
 *                          in newer PHP versions but are already needed by
 *                          NagVis PHP code
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

/**
 * This implements the function date_default_timezone_set() which is needed
 * since PHP 5.1 by all PHP date functions
 *
 * @author 	Lars Michelsen <lars@vertical-visions.de>
 */
if(!function_exists('date_default_timezone_set')) {
	function date_default_timezone_set($timezone_identifier) {
		putenv("TZ=".$timezone_identifier);
		return TRUE;
	}
}


/**
 * Implements handling of PHP to JSON conversion for NagVis
 * (Needed for < PHP 5.2.0)
 *
 * @param		String		Debug message
 * @author 	Lars Michelsen <lars@vertical-visions.de>
 */
if(!function_exists('json_encode')) {
	function json_encode_string($in_str) {
		if(!function_exists('mb_internal_encoding')) {
			//FIXME: Error handling
		}
		
		mb_internal_encoding("UTF-8");
			
		$convmap = array(0x80, 0xFFFF, 0, 0xFFFF);
		$str = "";
			
		for($i=mb_strlen($in_str)-1; $i>=0; $i--) {
		  $mb_char = mb_substr($in_str, $i, 1);
		  if(mb_ereg("&#(\\d+);", mb_encode_numericentity($mb_char, $convmap, "UTF-8"), $match)) {
			$str = sprintf("\\u%04x", $match[1]) . $str;
		  } else {
			$str = $mb_char . $str;
		  }
		}
		return $str;
	}
	
	function json_encode($arr) {
		$json_str = "";
		if(is_array($arr)) {
			$pure_array = true;
			$array_length = count($arr);
			
			for($i=0;$i<$array_length;$i++) {
				if(! isset($arr[$i])) {
					$pure_array = false;
					break;
				}
			}
			
			if($pure_array) {
				$json_str ="[";
				$temp = array();
				
				for($i=0;$i<$array_length;$i++) {
					$temp[] = sprintf("%s", json_encode($arr[$i]));
				}
				
				$json_str .= implode(",",$temp);
				$json_str .="]";
			} else {
				$json_str ="{";
				$temp = array();
				
				foreach($arr as $key => $value) {
					$temp[] = sprintf("\"%s\":%s", $key, json_encode($value));
				}
				
				$json_str .= implode(",",$temp);
				$json_str .="}";
			}
		} else {
			if(is_string($arr)) {
				$json_str = "'". str_replace("\\","\\\\",json_encode_string($arr)) . "'";
			} elseif(is_numeric($arr)) {
				$json_str = $arr;
			} else {
				$json_str = "'". json_encode_string($arr) . "'";
			}
		}
		return $json_str;
	}
}
?>



