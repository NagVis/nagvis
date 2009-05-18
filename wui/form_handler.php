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

// Include defines
require('../nagvis/includes/defines/global.php');
require('../nagvis/includes/defines/matches.php');

// Include global functions
require("../nagvis/includes/functions/autoload.php");
require('../nagvis/includes/functions/debug.php');
require("../nagvis/includes/functions/getuser.php");

// Include needed WUI specific functions
require('./includes/functions/form_handler.php');

// This defines wether the GlobalFrontendMessage prints HTML or ajax error messages
define('CONST_AJAX' , FALSE);

// Load the core
$CORE = new WuiCore();

// Now do the requested action
switch($_GET['myaction']) {
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
			print("<script>document.location.href='./index.php?map=".$_POST['map_name']."';</script>");
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
			foreach($CORE->getAvailableMaps() as $mapName) {
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
			print "<script>document.location.href='./index.php?map=".$map."';</script>\n";
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
			
			// Open the management page again
			print "<script>document.location.href='./index.php';</script>\n";
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
				if(preg_match('/\.cfg/i',$mapName)) {
					if(move_uploaded_file($_FILES['map_file']['tmp_name'], $CORE->MAINCFG->getValue('paths', 'mapcfg').$mapName)) {
						chmod($CORE->MAINCFG->getValue('paths', 'mapcfg').$mapName,0666);
						print("<script>window.history.back();</script>");
					} else {
						print "The file could not be moved to destination (".$CORE->MAINCFG->getValue('paths', 'mapcfg').$mapName.").";
						return;
					}
				} else {
					print "This is no *.cfg file (".$mapName.").";
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
			$CORE->MAINCFG->setValue('defaults', 'backend', $_POST['defaultbackend']);
			
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
		// $_POST['backendid'], $_POST['backendtype']
		
		if(!isset($_POST['backendid']) || $_POST['backendid'] == '') {
			echo $CORE->LANG->getText('mustValueNotSet', 'ATTRIBUTE~backendid');
		} elseif(!isset($_POST['backendtype']) || $_POST['backendtype'] == '') {
			echo $CORE->LANG->getText('mustValueNotSet', 'ATTRIBUTE~backendtype');
		} else {
			$bFoundOption = FALSE;
			$aOpt = Array();
			
			// Loop all aviable options for this backend
			$arr = $CORE->MAINCFG->getValidObjectType('backend');
			foreach($arr['options'][$_POST['backendtype']] AS $key => $arr) {
				// If there is a value for this option, set it
				if(isset($_POST[$key]) && $_POST[$key] != '') {
					$bFoundOption = TRUE;
					$aOpt[$key] = $_POST[$key];
				}
			}
			
			// If there is at least one option set...
			if($bFoundOption) {
				// Set standard values
				$CORE->MAINCFG->setSection('backend_'.$_POST['backendid']);
				$CORE->MAINCFG->setValue('backend_'.$_POST['backendid'], 'backendtype', $_POST['backendtype']);
				
				// Set all options
				foreach($aOpt AS $key => $val) {
					$CORE->MAINCFG->setValue('backend_'.$_POST['backendid'], $key, $val);
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
		if(!isset($_POST['backendid']) || $_POST['backendid'] == '') {
			echo $CORE->LANG->getText('mustValueNotSet', 'ATTRIBUTE~backendid');
		} else {
			// Loop all aviable options for this backend
			$arr = $CORE->MAINCFG->getValidObjectType('backend');
			foreach($arr['options'][$CORE->MAINCFG->getValue('backend_'.$_POST['backendid'],'backendtype')] AS $key => $arr) {
				// If there is a value for this option, set it
				if(isset($_POST[$key]) && $_POST[$key] != '') {
					$CORE->MAINCFG->setValue('backend_'.$_POST['backendid'],$key,$_POST[$key]);
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
		if(!isset($_POST['backendid']) || $_POST['backendid'] == '') {
			echo $CORE->LANG->getText('mustValueNotSet', 'ATTRIBUTE~backendid');
		} else {
			// Delete the section of the backend
			$CORE->MAINCFG->delSection('backend_'.$_POST['backendid']);
			
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
