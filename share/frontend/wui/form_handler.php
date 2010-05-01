<?php
/*****************************************************************************
 *
 * form_handler.php - Handler for form request of WUI
 *
 * Copyright (c) 2004-2010 NagVis Project (Contact: info@nagvis.org)
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

// Include global defines
require('../../server/core/defines/global.php');
require('../../server/core/defines/matches.php');

// Include wui related defines
require('defines/wui.php');

// Include functions
require("../../server/core/functions/autoload.php");
require("../../server/core/functions/debug.php");
require("../../server/core/functions/oldPhpVersionFixes.php");

// This defines wether the GlobalMessage prints HTML or ajax error messages
define('CONST_AJAX' , FALSE);

// Initialize the core
$CORE = WuiCore::getInstance();

// Now do the requested action
switch($_GET['myaction']) {
	/*
	 * Export a new map
	 */
	case 'mgt_map_export':
		// $_POST['map_name'];
		
		if(!isset($_POST['map_name']) || $_POST['map_name'] == '') {
			echo $CORE->getLang()->getText('mustValueNotSet', 'ATTRIBUTE~map_name');
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
			echo $CORE->getLang()->getText('mustValueNotSet', 'ATTRIBUTE~map_file');
		} else {
			// check the file (the map) is properly uploaded
			if(is_uploaded_file($_FILES['map_file']['tmp_name'])) {
				$mapName = $_FILES['map_file']['name'];
				if(preg_match('/\.cfg/i',$mapName)) {
					if(move_uploaded_file($_FILES['map_file']['tmp_name'], $CORE->getMainCfg()->getValue('paths', 'mapcfg').$mapName)) {
						chmod($CORE->getMainCfg()->getValue('paths', 'mapcfg').$mapName,0666);
						print("<script>window.history.back();</script>");
					} else {
						print "The file could not be moved to destination (".$CORE->getMainCfg()->getValue('paths', 'mapcfg').$mapName.").";
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
	 * Set the default backend in the main configuration
	 */
	case 'mgt_backend_default':
		if(!isset($_POST['defaultbackend']) || $_POST['defaultbackend'] == '') {
			echo $CORE->getLang()->getText('mustValueNotSet', 'ATTRIBUTE~defaultbackend');
		} else {
			// Set the default backend
			$CORE->getMainCfg()->setValue('defaults', 'backend', $_POST['defaultbackend']);
			
			// Write the changes to the main configuration
			$CORE->getMainCfg()->writeConfig();
			
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
			echo $CORE->getLang()->getText('mustValueNotSet', 'ATTRIBUTE~backendid');
		} elseif(!isset($_POST['backendtype']) || $_POST['backendtype'] == '') {
			echo $CORE->getLang()->getText('mustValueNotSet', 'ATTRIBUTE~backendtype');
		} else {
			$bFoundOption = FALSE;
			$aOpt = Array();
			
			// Loop all aviable options for this backend
			$arr = $CORE->getMainCfg()->getValidObjectType('backend');
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
				$CORE->getMainCfg()->setSection('backend_'.$_POST['backendid']);
				$CORE->getMainCfg()->setValue('backend_'.$_POST['backendid'], 'backendtype', $_POST['backendtype']);
				
				// Set all options
				foreach($aOpt AS $key => $val) {
					$CORE->getMainCfg()->setValue('backend_'.$_POST['backendid'], $key, $val);
				}
			}
			
			// Write the changes to the main configuration
			$CORE->getMainCfg()->writeConfig();
			
			// Open the management page again
			print("<script>window.history.back();</script>");
		}
	break;
	/*
	 * Edit the values of the backend with the given BACKEND-ID
	 */
	case 'mgt_backend_edit':
		if(!isset($_POST['backendid']) || $_POST['backendid'] == '') {
			echo $CORE->getLang()->getText('mustValueNotSet', 'ATTRIBUTE~backendid');
		} else {
			// Loop all aviable options for this backend
			$arr = $CORE->getMainCfg()->getValidObjectType('backend');
			foreach($arr['options'][$CORE->getMainCfg()->getValue('backend_'.$_POST['backendid'],'backendtype')] AS $key => $arr) {
				// If there is a value for this option, set it
				if(isset($_POST[$key]) && $_POST[$key] != '') {
					$CORE->getMainCfg()->setValue('backend_'.$_POST['backendid'],$key,$_POST[$key]);
				}
			}
			
			// Write the changes to the main configuration
			$CORE->getMainCfg()->writeConfig();
			
			// Open the management page again
			print("<script>window.history.back();</script>");
		}
	break;
	/*
	 * Delete the specified backend with the given BACKEND-ID
	 */
	case 'mgt_backend_del':
		if(!isset($_POST['backendid']) || $_POST['backendid'] == '') {
			echo $CORE->getLang()->getText('mustValueNotSet', 'ATTRIBUTE~backendid');
		} else {
			// Delete the section of the backend
			$CORE->getMainCfg()->delSection('backend_'.$_POST['backendid']);
			
			// Write the changes to the main configuration
			$CORE->getMainCfg()->writeConfig();
			
			// Open the management page again
			print("<script>window.history.back();</script>");
		}
	break;
	/*
	 * Upload a new shape image file
	 */
	case 'mgt_shape_add':
		if(!isset($_FILES['shape_image']) || !is_array($_FILES['shape_image'])) {
			echo $CORE->getLang()->getText('mustValueNotSet', 'ATTRIBUTE~shape_image');
		} else {
			// check the file (the map) is properly uploaded
			if(is_uploaded_file($_FILES['shape_image']['tmp_name'])) {
				if(preg_match(MATCH_PNG_GIF_JPG_FILE, $_FILES['shape_image']['name'])) {
					if(@move_uploaded_file($_FILES['shape_image']['tmp_name'], $CORE->getMainCfg()->getValue('paths', 'shape').$_FILES['shape_image']['name'])) {
						// Change permissions of the file after the upload
						chmod($CORE->getMainCfg()->getValue('paths', 'shape').$_FILES['shape_image']['name'],0666);
						// Go back to last page
						print("<script>window.history.back();</script>");
					} else {
						// Error handling
						print("ERROR: ".$CORE->getLang()->getText('moveUploadedFileFailed','PATH~'.$CORE->getMainCfg()->getValue('paths', 'shape')));
					}
				} else {
					// Error handling
					print("ERROR: ".$CORE->getLang()->getText('mustBePngFile'));
				}
			} else {
				// Error handling
				print("ERROR: ".$CORE->getLang()->getText('uploadFailed'));
			}
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
