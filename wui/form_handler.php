<?php
/*****************************************************************************
 *
 * form_handler.php - Handler for form request of WUI
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
// Start the user session (This is needed by some caching mechanism)
@session_start();

// Include defines
require('../nagvis/includes/defines/global.php');
require('../nagvis/includes/defines/matches.php');

// Include global functions
require('../nagvis/includes/functions/debug.php');

// Include needed WUI specific functions
require('./includes/functions/form_handler.php');

// Include needed global classes
require('../nagvis/includes/classes/GlobalLanguage.php');
require('../nagvis/includes/classes/GlobalMainCfg.php');
require('../nagvis/includes/classes/GlobalPage.php');
require('../nagvis/includes/classes/GlobalMapCfg.php');
require('../nagvis/includes/classes/GlobalBackground.php');

// Include needed WUI specific classes
require("./includes/classes/WuiCore.php");
require('./includes/classes/WuiMainCfg.php');
require('./includes/classes/WuiMapCfg.php');
require('./includes/classes/WuiBackground.php');

// Load the core
$CORE = new WuiCore();

// Now do the requested action
switch($_GET['myaction']) {
	/*
	 * Save the map with the specified MAPNAME
	 */
	case 'save':
		if(!isset($_POST['mapname']) || $_POST['mapname'] == '') {
			echo $CORE->LANG->getText('mustValueNotSet', 'ATTRIBUTE~mapname');
		} else {
			$MAPCFG = new WuiMapCfg($CORE, $_POST['mapname']);
			$MAPCFG->readMapConfig();
			
			// convert lists to arrays
			$elements = explode(',',$_POST['image']);
			$x = explode(',',$_POST['valx']);
			$y = explode(',',$_POST['valy']);
				
			// sth. modified?
			if(is_array($elements)) {
				$i = 0;
				foreach($elements AS $element) {
					$element = explode('_',$element);
					// don't write empty elements
					if($element[0] != '' && $element[1] != '' && $x[$i] != '' && $y[$i] != '') {
						// set coordinates ($element[0] is type, $element[1] is id)
						$MAPCFG->setValue($element[0], $element[1], 'x', $x[$i]);
						$MAPCFG->setValue($element[0], $element[1], 'y', $y[$i]);
						// write element to file
						$MAPCFG->writeElement($element[0],$element[1]);
						$i++;
					}
				}
				
				backup($CORE->MAINCFG, $_POST['mapname']);
			}
			
			// delete map lock
			if(!$MAPCFG->deleteMapLock()) {
				print("<script>alert('".$CORE->LANG->getText('mapLockNotDeleted')."');</script>");
			}
			
			// display the same map again
			print "<script>document.location.href='./index.php?map=".$_POST['mapname']."';</script>\n";
		}
	break;
	/*
	 * Modify an object of the given TYPE with the given ID on the given MAP
	 */
	case 'modify':
		if(!isset($_POST['map']) || $_POST['map'] == '') {
			echo $CORE->LANG->getText('mustValueNotSet', 'ATTRIBUTE~map');
		} elseif(!isset($_POST['type']) || $_POST['type'] == '') {
			echo $CORE->LANG->getText('mustValueNotSet', 'ATTRIBUTE~type');
		} elseif(!isset($_POST['properties']) || $_POST['properties'] == '') {
			echo $CORE->LANG->getText('mustValueNotSet', 'ATTRIBUTE~properties');
		} else {
			$MAPCFG = new WuiMapCfg($CORE, $_POST['map']);
			$MAPCFG->readMapConfig();
			
			// set options in the array
			foreach(getArrayFromProperties($_POST['properties']) AS $key => $val) {
				$MAPCFG->setValue($_POST['type'], $_POST['id'], $key, $val);
			}
			
			// write element to file
			$MAPCFG->writeElement($_POST['type'],$_POST['id']);
			
			// do the backup
			backup($CORE->MAINCFG, $_POST['map']);
			
			// delete map lock
			if(!$MAPCFG->deleteMapLock()) {
				print("<script>alert('".$CORE->LANG->getText('mapLockNotDeleted')."');</script>");
			}
			
			// refresh the map
			print "<script>window.opener.document.location.href='./index.php?map=".$_POST['map']."';</script>\n";
			
			// close the popup window
			print "<script>window.close();</script>\n";
		}
	break;
	/*
	 * Add an object of the given TYPE to the given MAP
	 */
	case 'add':
		if(!isset($_POST['map']) || $_POST['map'] == '') {
			echo $CORE->LANG->getText('mustValueNotSet', 'ATTRIBUTE~map');
		} elseif(!isset($_POST['type']) || $_POST['type'] == '') {
			echo $CORE->LANG->getText('mustValueNotSet', 'ATTRIBUTE~type');
		} elseif(!isset($_POST['properties']) || $_POST['properties'] == '') {
			echo $CORE->LANG->getText('mustValueNotSet', 'ATTRIBUTE~properties');
		} else {
			$MAPCFG = new WuiMapCfg($CORE, $_POST['map']);
			$MAPCFG->readMapConfig();
			
			// append a new object definition line in the map cfg file
			$elementId = $MAPCFG->addElement($_POST['type'],getArrayFromProperties($_POST['properties']));
			$MAPCFG->writeElement($_POST['type'],$elementId);
			
			// do the backup
			backup($CORE->MAINCFG, $_POST['map']);
			
			// delete map lock
			if(!$MAPCFG->deleteMapLock()) {
				print("<script>alert('".$CORE->LANG->getText('mapLockNotDeleted')."');</script>");
			}
			
			// display the same map again, with the autosave value activated: the map will automatically be saved
			// after the next drag and drop (after the user placed the new object on the map)
			print "<script>window.opener.document.location.href='./index.php?map=".$_POST['map']."&autosave=true';</script>\n";
			print "<script>window.close();</script>\n";
		}
	break;
	/*
	 * Delete an object of the given TYPE with the given ID from the given MAP
	 */
	case 'delete':
		if(!isset($_GET['map']) || $_GET['map'] == '') {
			echo $CORE->LANG->getText('mustValueNotSet', 'ATTRIBUTE~map');
		} elseif(!isset($_GET['type']) || $_GET['type'] == '') {
			echo $CORE->LANG->getText('mustValueNotSet', 'ATTRIBUTE~type');
		} elseif(!isset($_GET['id']) || $_GET['id'] == '') {
			echo $CORE->LANG->getText('mustValueNotSet', 'ATTRIBUTE~id');
		} else {
			// initialize map and read map config
			$MAPCFG = new WuiMapCfg($CORE, $_GET['map']);
			$MAPCFG->readMapConfig();
			
			// first delete element from array
			$MAPCFG->deleteElement($_GET['type'],$_GET['id']);
			// then write new array to file
			$MAPCFG->writeElement($_GET['type'],$_GET['id']);
					
			// do the backup
			backup($CORE->MAINCFG,$_GET['map']);
			
			// delete map lock
			if(!$MAPCFG->deleteMapLock()) {
				print("<script>alert('".$CORE->LANG->getText('mapLockNotDeleted')."');</script>");
			}
			
			// Reload the map
			print "<script>document.location.href='./index.php?map=".$_GET['map']."';</script>\n";
		}
	break;
	/*
	 * Change the NagVis main configuration
	 */
	case 'update_config':
		if(!isset($_POST['properties']) || $_POST['properties'] == '') {
			echo $CORE->LANG->getText('mustValueNotSet', 'ATTRIBUTE~properties');
		} else {
			// Parse properties to array and loop each
			foreach(getArrayFromProperties($_POST['properties']) AS $key => $val) {
				$CORE->MAINCFG->setValue($CORE->MAINCFG->findSecOfVar($key),$key,$val);
			}
			
			// Write the changes to the main configuration file
			$CORE->MAINCFG->writeConfig();
			
			// Reload the map
			print "<script>window.opener.document.location.reload();</script>\n";
			
			// Close the popup window
			print "<script>window.close();</script>\n";
		}
	break;
	/*
	 * Create a new map
	 */
	case 'mgt_map_create':
		if(!isset($_POST['map_name']) || $_POST['map_name'] == '') {
			echo $CORE->LANG->getText('mustValueNotSet', 'ATTRIBUTE~map_name');
		} elseif(!isset($_POST['allowed_users']) || $_POST['allowed_users'] == '') {
			echo $CORE->LANG->getText('mustValueNotSet', 'ATTRIBUTE~allowed_users');
		} elseif(!isset($_POST['allowed_for_config']) || $_POST['allowed_for_config'] == '') {
			echo $CORE->LANG->getText('mustValueNotSet', 'ATTRIBUTE~allowed_for_config');
		} else {
			$MAPCFG = new WuiMapCfg($CORE, $_POST['map_name']);
			if(!$MAPCFG->createMapConfig()) {
				exit;
			}
			
			$MAPCFG->addElement('global',Array('allowed_user'=>$_POST['allowed_users'],'allowed_for_config'=>$_POST['allowed_for_config'],'iconset'=>$_POST['map_iconset'],'map_image'=>$_POST['map_image']));
			$MAPCFG->writeElement('global','0');
			
			// do the backup
			backup($CORE->MAINCFG, $_POST['map_name']);
			
			// Redirect to the new map
			print("<script>window.opener.document.location.href='./index.php?map=".$_POST['map_name']."';</script>");
			
			// Close the popup window
			print("<script>window.close();</script>");
		}
	break;
	/*
	 * Rename a new map
	 */
	case 'mgt_map_rename':
		// alter name: $_POST['map_name']
		// neuer name: $_POST['map_new_name']
		// gerade offene map: $_POST['map']
		
		if(!isset($_POST['map_name']) || $_POST['map_name'] == '') {
			echo $CORE->LANG->getText('mustValueNotSet', 'ATTRIBUTE~map_name');
		} elseif(!isset($_POST['map_new_name']) || $_POST['map_new_name'] == '') {
			echo $CORE->LANG->getText('mustValueNotSet', 'ATTRIBUTE~map_new_name');
		} elseif(!isset($_POST['map']) || $_POST['map'] == '') {
			echo $CORE->LANG->getText('mustValueNotSet', 'ATTRIBUTE~map');
		} else {
			$files = Array();
			
			// loop all map configs to replace mapname in all map configs
			foreach($MAINCFG->getMaps() as $mapName) {
				$MAPCFG1 = new WuiMapCfg($CORE, $mapName);
				$MAPCFG1->readMapConfig();
				
				$i = 0;
				// loop definitions of type map
				foreach($MAPCFG1->getDefinitions('map') AS $key => $obj) {
					// check if old map name is linked...
					if($obj['map_name'] == $_POST['map_name']) {
						$MAPCFG1->setValue('map', $i, 'map_name', $_POST['map_new_name']);
						$MAPCFG1->writeElement('map',$i);
					}
					$i++;
				}
			}
			
			// rename config file
			rename($CORE->MAINCFG->getValue('paths', 'mapcfg').$_POST['map_name'].'.cfg',$CORE->MAINCFG->getValue('paths', 'mapcfg').$_POST['map_new_name'].'.cfg');
			
			// if renamed map is open, redirect to new name
			if($_POST['map'] == 'undefined' || $_POST['map'] == '' || $_POST['map'] == $_POST['map_name']) {
				$map = $_POST['map_new_name'];
			} else {
				$map = $_POST['map'];
			}
			
			// Refresh open map if it's not the renamed map or redirect to renamed map
			print "<script>window.opener.document.location.href='./index.php?map=".$map."';</script>\n";
			
			// Close the popup
			print "<script>window.close();</script>\n";
		}
	break;
	/*
	 * Delete a new map
	 */
	case 'mgt_map_delete':
		// $_POST['map_name'];
		
		if(!isset($_POST['map_name']) || $_POST['map_name'] == '') {
			echo $CORE->LANG->getText('mustValueNotSet', 'ATTRIBUTE~map_name');
		} else {
			$MAPCFG = new WuiMapCfg($CORE, $_POST['map_name']);
			$MAPCFG->readMapConfig();
			
			$MAPCFG->deleteMapConfig();
			
			print("<script>alert('".$CORE->LANG->getText('mapDeleted')."');</script>");
			
			// Open the management page again
			print("<script>window.history.back();</script>");
		}
	break;
	/*
	 * Export a new map
	 */
	case 'mgt_map_export':
		// $_POST['map_name'];
		
		if(!isset($_POST['map_name']) || $_POST['map_name'] == '') {
			echo $CORE->LANG->getText('mustValueNotSet', 'ATTRIBUTE~map_name');
		} else {
			$MAPCFG = new WuiMapCfg($CORE, $_POST['map_name']);
			
			if(!$MAPCFG->exportMap()) {
				// Error Handling
				print "<script>alert('An error occured while exporting the map.')</script>";
			}
		}
	break;
	/*
	 * Import a new map
	 */
	case 'mgt_map_import':
		if(!isset($_FILES['map_file']) || !is_array($_FILES['map_file'])) {
			echo $CORE->LANG->getText('mustValueNotSet', 'ATTRIBUTE~map_file');
		} else {
			// check the file (the map) is properly uploaded
			if(is_uploaded_file($_FILES['map_file']['tmp_name'])) {
				$mapName = $_FILES['map_file']['name'];
				if(preg_match('/\.cfg/i',$fileName)) {
					if(move_uploaded_file($_FILES['map_file']['tmp_name'], $CORE->MAINCFG->getValue('paths', 'mapcfg').$mapName)) {
						chmod($CORE->MAINCFG->getValue('paths', 'mapcfg').$mapName,0666);
						print("<script>window.history.back();</script>");
					} else {
						print "The file could not be moved to destination (".$CORE->MAINCFG->getValue('paths', 'mapcfg').$mapName.").";
						return;
					}
				} else {
					print "This is no *.cfg file (".$fileName.").";
					return;
				}
			} else {
				print "The file could not be uploaded.";
				return;
			}
		}
	break;
	/*
	 * Create a new background image of the given color
	 */
	case 'mgt_image_create':
		if(!isset($_POST['image_name']) || $_POST['image_name'] == '') {
			echo $CORE->LANG->getText('mustValueNotSet', 'ATTRIBUTE~image_name');
		} elseif(!isset($_POST['image_color']) || $_POST['image_color'] == '') {
			echo $CORE->LANG->getText('mustValueNotSet', 'ATTRIBUTE~image_color');
		} elseif(!isset($_POST['image_width']) || $_POST['image_width'] == '') {
			echo $CORE->LANG->getText('mustValueNotSet', 'ATTRIBUTE~image_width');
		} elseif(!isset($_POST['image_height']) || $_POST['image_height'] == '') {
			echo $CORE->LANG->getText('mustValueNotSet', 'ATTRIBUTE~image_height');
		} else {
			$BACKGROUND = new WuiBackground($CORE, $_POST['image_name'].'.png');
			$BACKGROUND->createImage($_POST['image_color'], $_POST['image_width'], $_POST['image_height']);
			
			// Open the management page again
			print("<script>window.history.back();</script>");
		}
	break;
	/*
	 * Delete the given background image
	 */
	case 'mgt_image_delete':
		if(!isset($_POST['map_image']) || $_POST['map_image'] == '') {
			echo $LANG->getText('mustValueNotSet', 'ATTRIBUTE~map_image');
		} else {
			$BACKGROUND = new WuiBackground($CORE, $_POST['map_image']);
			$BACKGROUND->deleteImage();
			
			// Open the management page again
			print("<script>window.history.back();</script>");
		}
	break;
	/*
	 * Upload a new background image
	 */
	case 'mgt_image_upload':
		if(!isset($_FILES['image_file']) || !is_array($_FILES['image_file'])) {
			echo $LANG->getText('mustValueNotSet', 'ATTRIBUTE~image_file');
		} else {
			$BACKGROUND = new WuiBackground($CORE, '');
			$BACKGROUND->uploadImage($_FILES['image_file']);
			
			// Open the management page again
			print("<script>window.history.back();</script>");
		}
	break;
	/*
	 * Restore a backup of the given MAP
	 */
	case 'map_restore':
		if(!isset($_GET['map']) || $_GET['map'] == '') {
			echo $CORE->LANG->getText('mustValueNotSet', 'ATTRIBUTE~map');
		} else {
			// delete current config
			$MAPCFG = new WuiMapCfg($CORE, $_GET['map']);
			$MAPCFG->readMapConfig();
			$MAPCFG->deleteMapConfig();
			// restore backup
			if(file_exists($CORE->MAINCFG->getValue('paths', 'mapcfg').$_GET['map'].'.cfg.bak')) {
				copy($CORE->MAINCFG->getValue('paths', 'mapcfg').$_GET['map'].'.cfg.bak',$CORE->MAINCFG->getValue('paths', 'mapcfg').$_GET['map'].'.cfg');	
			}
			// reset status
			$done = FALSE;
			
			// is status for this map there?
			$file = file($CORE->MAINCFG->getValue('paths', 'mapcfg').'autobackup.status');
			foreach($file AS $key => $val) {
				if(ereg("^".$mapname."=",$val)) {
					// $arr[1] is value
					$arr = explode('=',$val);
					
					$file[$key] = $mapname.'='.$CORE->MAINCFG->getValue('wui', 'autoupdatefreq')."\n";
					$done = TRUE;
				}
			}
			
			if($done == FALSE) {
				$file[] = $mapname.'='.$CORE->MAINCFG->getValue('wui', 'autoupdatefreq')."\n";
			}
			
			//write array back to file
			$fp = fopen($CORE->MAINCFG->getValue('paths', 'mapcfg').'autobackup.status',"w");
			fwrite($fp,implode("",$file));
			fclose($fp);
			
			print "<script>window.document.location.href='./index.php?map=".$_GET['map']."';</script>\n";
		}
	break;
	/*
	 * Set the default backend in the main configuration
	 */
	case 'mgt_backend_default':
		if(!isset($_POST['defaultbackend']) || $_POST['defaultbackend'] == '') {
			echo $CORE->LANG->getText('mustValueNotSet', 'ATTRIBUTE~defaultbackend');
		} else {
			// Set the default backend
			$CORE->MAINCFG->setValue($CORE->MAINCFG->findSecOfVar('backend'),'backend',$_POST['defaultbackend']);
			
			// Write the changes to the main configuration
			$CORE->MAINCFG->writeConfig();
			
			// Open the management page again
			print("<script>window.history.back();</script>");
		}
	break;
	/*
	 * Add a new backend to the main configuration
	 */
	case 'mgt_backend_add':
		// $_POST['backend_id'], $_POST['backendtype']
		
		if(!isset($_POST['backend_id']) || $_POST['backend_id'] == '') {
			echo $CORE->LANG->getText('mustValueNotSet', 'ATTRIBUTE~backend_id');
		} elseif(!isset($_POST['backendtype']) || $_POST['backendtype'] == '') {
			echo $CORE->LANG->getText('mustValueNotSet', 'ATTRIBUTE~backendtype');
		} else {
			$bFoundOption = FALSE;
			$aOpt = Array();
			
			// Loop all aviable options for this backend
			foreach($CORE->MAINCFG->validConfig['backend']['options'][$_POST['backendtype']] AS $key => $arr) {
				// If there is a value for this option, set it
				if(isset($_POST[$key]) && $_POST[$key] != '') {
					$bFoundOption = TRUE;
					$aOpt[$key] = $_POST[$key];
				}
			}
			
			// If there is at least one option set...
			if($bFoundOption) {
				// Set standard values
				$CORE->MAINCFG->setSection('backend_'.$_POST['backend_id']);
				$CORE->MAINCFG->setValue('backend_'.$_POST['backend_id'],'backendid',$_POST['backend_id']);
				$CORE->MAINCFG->setValue('backend_'.$_POST['backend_id'],'backendtype',$_POST['backendtype']);
				
				// Set all options
				foreach($aOpt AS $key => $val) {
					$CORE->MAINCFG->setValue('backend_'.$_POST['backend_id'],$key,$val);
				}
			}
			
			// Write the changes to the main configuration
			$CORE->MAINCFG->writeConfig();
			
			// Open the management page again
			print("<script>window.history.back();</script>");
		}
	break;
	/*
	 * Edit the values of the backend with the given BACKEND-ID
	 */
	case 'mgt_backend_edit':
		if(!isset($_POST['backend_id']) || $_POST['backend_id'] == '') {
			echo $CORE->LANG->getText('mustValueNotSet', 'ATTRIBUTE~backend_id');
		} else {
			// Loop all aviable options for this backend
			foreach($CORE->MAINCFG->validConfig['backend']['options'][$CORE->MAINCFG->getValue('backend_'.$_POST['backend_id'],'backendtype')] AS $key => $arr) {
				// If there is a value for this option, set it
				if(isset($_POST[$key]) && $_POST[$key] != '') {
					$CORE->MAINCFG->setValue('backend_'.$_POST['backend_id'],$key,$_POST[$key]);
				}
			}
			
			// Write the changes to the main configuration
			$CORE->MAINCFG->writeConfig();
			
			// Open the management page again
			print("<script>window.history.back();</script>");
		}
	break;
	/*
	 * Delete the specified backend with the given BACKEND-ID
	 */
	case 'mgt_backend_del':
		if(!isset($_POST['backend_id']) || $_POST['backend_id'] == '') {
			echo $CORE->LANG->getText('mustValueNotSet', 'ATTRIBUTE~backend_id');
		} else {
			// Delete the section of the backend
			$CORE->MAINCFG->delSection('backend_'.$_POST['backend_id']);
			
			// Write the changes to the main configuration
			$CORE->MAINCFG->writeConfig();
			
			// Open the management page again
			print("<script>window.history.back();</script>");
		}
	break;
	/*
	 * Upload a new shape image file
	 */
	case 'mgt_shape_add':
		if(!isset($_FILES['shape_image']) || !is_array($_FILES['shape_image'])) {
			echo $CORE->LANG->getText('mustValueNotSet', 'ATTRIBUTE~shape_image');
		} else {
			// Include page specific global/wui classes
			require("../nagvis/includes/classes/GlobalForm.php");
			require("./includes/classes/WuiShapeManagement.php");
			
			$FRONTEND = new WuiShapeManagement($CORE);
			$FRONTEND->uploadShape($_FILES['shape_image']);
		}
	break;
	/*
	 * Delete the specified shape image file
	 */
	case 'mgt_shape_delete':
		if(!isset($_POST['shape_image']) || $_POST['shape_image'] == '') {
			echo $CORE->LANG->getText('mustValueNotSet', 'ATTRIBUTE~shape_image');
		} else {
			// Include page specific global/wui classes
			require("../nagvis/includes/classes/GlobalForm.php");
			require("./includes/classes/WuiShapeManagement.php");
			
			$FRONTEND = new WuiShapeManagement($CORE);
			$FRONTEND->deleteShape($_POST['shape_image']);
		}
	break;
	/*
	 * Fallback
	 */
	default:
		echo $LANG->getText('unknownAction', 'ACTION~'.$_GET['action']);
	break;
}
?>
