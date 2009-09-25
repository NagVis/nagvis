<?php
/*****************************************************************************
 *
 * form_handler.php - Functions which are needed by the form handler
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
 * Parses a String of properties which are submited by WUI forms and saves the 
 * values to an array.
 *
 * @param		String	String of all properties which were set by WUI
 * @return	Array		List of the properties
 * @author 	Lars Michelsen <lars@vertical-visions.de>
 */
function getArrayFromProperties(&$properties) {
	$prop = Array();
	// The sets are seperated by ^ signs, seperate them
	$properties = explode('^',$properties);
	
	// Loop each property
	foreach($properties AS $var => $line) {
		// seperate string in an array
		$arr = @explode('=',$line);
		
		// read key from array and delete it
		$key = @strtolower(@trim($arr[0]));
		unset($arr[0]);
		
		// build string from rest of array
		$prop[$key] = @trim(@implode('=', $arr));
	}
	return $prop;
}

/**
 * Automaticaly makes a backup each X (autoupdatefreq) save of the specified map
 *
 * @param		WuiMainCfg	Main cofniguration object
 * @param		String			Name of the map which gets saved
 * @return	Boolean			Succesful?
 * @author 	Lars Michelsen <lars@vertical-visions.de>
 */
function backup(&$MAINCFG, $mapname) {
	if($MAINCFG->getValue('wui', 'autoupdatefreq') == 0) {
		// delete all *.bak
		foreach(getAllFiles() AS $file) {
			unlink($MAINCFG->getValue('paths', 'mapcfg').$file.'.cfg.bak');
		}
		// delete statusfile
		unlink($MAINCFG->getValue('paths', 'mapcfg').'autobackup.status');
		
		return TRUE;
	} else {
		//no statusfile? create!
		if(!file_exists($MAINCFG->getValue('paths', 'mapcfg').'autobackup.status')) {
			// create file
			$fp = fopen($MAINCFG->getValue('paths', 'mapcfg').'autobackup.status', "w");
			fwrite($fp,$mapname.'='.$MAINCFG->getValue('wui', 'autoupdatefreq')."\n");
			fclose($fp); 
			// set permissions
  			chmod($MAINCFG->getValue('paths', 'mapcfg').'autobackup.status',0666);
  			
  			return TRUE;
		} else {
			$done = FALSE;
			
			// is status for this map there?
			$file = file($MAINCFG->getValue('paths', 'mapcfg').'autobackup.status');
			foreach($file AS $key => $val) {
				if(preg_match('/^'.$mapname.'=/',$val)) { 
					// $arr[1] is value
					$arr = explode('=',$val);
					
					if($arr[1]-1 == 0) {
						// erstelle backup
						copy($MAINCFG->getValue('paths', 'mapcfg').$arr[0].'.cfg',$MAINCFG->getValue('paths', 'mapcfg').$arr[0].'.cfg.bak');	
						// zur�cksetzen
						$nextval = $MAINCFG->getValue('wui', 'autoupdatefreq');
					} elseif($arr[1]-1 >= $MAINCFG->getValue('wui', 'autoupdatefreq')) {
						$nextval = $MAINCFG->getValue('wui', 'autoupdatefreq');
					} else {
						$nextval = $arr[1]-1;
					}
					$file[$key] = $mapname.'='.$nextval."\n";
					
					$done = TRUE;
				}
			}
			
			if($done == FALSE) {
				$file[] = $mapname.'='.$MAINCFG->getValue('wui', 'autoupdatefreq')."\n";
			}
			
			//write array back to file
			$fp = fopen($MAINCFG->getValue('paths', 'mapcfg').'autobackup.status','w');
		 	fwrite($fp,implode('',$file));
		 	fclose($fp);
		 	
		 	return TRUE;
		}
	}
}
?>
