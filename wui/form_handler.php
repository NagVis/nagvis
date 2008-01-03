<?php
#################################################################################
#       Nagvis Web Configurator 												#
#	GPL License																	#
#																				#
#	Web interface to configure Nagvis maps.										#
#																				#
#	Drag & drop, Tooltip and shapes javascript code taken from 					#
#	http://www.walterzorn.com   												#
#################################################################################

@session_start();

require('../nagvis/includes/defines/global.php');
require('../nagvis/includes/defines/matches.php');

require('../nagvis/includes/functions/debug.php');

require('../nagvis/includes/classes/GlobalLanguage.php');
require('../nagvis/includes/classes/GlobalMainCfg.php');
require('../nagvis/includes/classes/GlobalPage.php');
require('../nagvis/includes/classes/GlobalMapCfg.php');
require('../nagvis/includes/classes/GlobalBackground.php');

require('./includes/classes/WuiMainCfg.php');
require('./includes/classes/WuiMapCfg.php');
require('./includes/classes/WuiBackground.php');

$MAINCFG = new WuiMainCfg(CONST_MAINCFG);

############################################
function getArrayFromProperties($properties) {
	$prop = Array();
	$properties = explode('^',$properties);
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

function backup(&$MAINCFG,$mapname) {
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
				if(ereg("^".$mapname."=",$val)) {
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

function getAllMaps(&$MAINCFG) {
	$files = Array();
	
	$fh = opendir($MAINCFG->getValue('paths', 'mapcfg'));
	while(FALSE !== ($file = readdir($fh))) {
		// only handle *.cfg files
		if(ereg('\.cfg$',$file)) {
			$files[] = substr($file,0,strlen($file)-4);
		}
	}
	closedir($fh);
	
	return $files;
}

############################################
# MAIN SCRIPT
############################################

switch($_GET['myaction']) {
	case 'save':
		if(isset($_POST['mapname']) && $_POST['mapname']) {
			$MAPCFG = new WuiMapCfg($MAINCFG,$_POST['mapname']);
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
				
				backup($MAINCFG,$_POST['mapname']);
			}
			// delete map lock
			if(!$MAPCFG->deleteMapLock()) {
					// FIXME: Language entry
					print "<script>alert('Lockfile could not be deleted.');</script>";
			}
			
			// display the same map again
			print "<script>document.location.href='./index.php?map=".$_POST['mapname']."';</script>\n";
		} else {
			print("A needed value is missing.");
		}
	break;
	case 'modify':
		if(isset($_POST['map']) && $_POST['map'] != '' && isset($_POST['type']) && $_POST['type'] != '' && isset($_POST['properties']) && $_POST['properties'] != '') {
			$MAPCFG = new WuiMapCfg($MAINCFG,$_POST['map']);
			$MAPCFG->readMapConfig();
			
			// set options in the array
			foreach(getArrayFromProperties($_POST['properties']) AS $key => $val) {
				$MAPCFG->setValue($_POST['type'], $_POST['id'], $key, $val);
			}
			
			// write element to file
			$MAPCFG->writeElement($_POST['type'],$_POST['id']);
			
			// do the backup
			backup($MAINCFG,$_POST['map']);
			
			// delete map lock
			if(!$MAPCFG->deleteMapLock()) {
					// FIXME: Language entry
					print "<script>alert('Lockfile could not be deleted.');</script>";
			}
			
			// refresh the map
			print "<script>window.opener.document.location.href='./index.php?map=".$_POST['map']."';</script>\n";
			print "<script>window.close();</script>\n";
		} else {
			print("A needed value is missing.");
		}
	break;
	case 'add':
		if(isset($_POST['map']) && $_POST['map'] != '' && isset($_POST['type']) && $_POST['type'] != '' && isset($_POST['properties']) && $_POST['properties'] != '') {
			$MAPCFG = new WuiMapCfg($MAINCFG,$_POST['map']);
			$MAPCFG->readMapConfig();
			
			// append a new object definition line in the map cfg file
			$elementId = $MAPCFG->addElement($_POST['type'],getArrayFromProperties($_POST['properties']));
			$MAPCFG->writeElement($_POST['type'],$elementId);
			
			// do the backup
			backup($MAINCFG,$_POST['map']);
			
			// delete map lock
			if(!$MAPCFG->deleteMapLock()) {
					// FIXME: Language entry
					print "<script>alert('Lockfile could not be deleted.');</script>";
			}
			
			// display the same map again, with the autosave value activated: the map will automatically be saved
			// after the next drag and drop (after the user placed the new object on the map)
			print "<script>window.opener.document.location.href='./index.php?map=".$_POST['map']."&autosave=true';</script>\n";
			print "<script>window.close();</script>\n";
		} else {
			print("A needed value is missing.");
		}
	break;
	case 'delete':
		if(isset($_GET['map']) && $_GET['map'] != '' && isset($_GET['type']) && $_GET['type'] != '' && isset($_GET['id']) && $_GET['id'] != '') {
			// initialize map and read map config
			$MAPCFG = new WuiMapCfg($MAINCFG,$_GET['map']);
			$MAPCFG->readMapConfig();
			
			// first delete element from array
			$MAPCFG->deleteElement($_GET['type'],$_GET['id']);
			// then write new array to file
			$MAPCFG->writeElement($_GET['type'],$_GET['id']);
					
			// do the backup
			backup($MAINCFG,$_GET['map']);
			
			// delete map lock
			if(!$MAPCFG->deleteMapLock()) {
					// FIXME: Language entry
					print "<script>alert('Lockfile could not be deleted.');</script>";
			}
			
			print "<script>document.location.href='./index.php?map=".$_GET['map']."';</script>\n";
		} else {
			print("A needed value is missing.");
		}
	break;
	case 'update_config':
		if(isset($_POST['properties']) && $_POST['properties'] != '') {
			foreach(getArrayFromProperties($_POST['properties']) AS $key => $val) {
				$MAINCFG->setValue($MAINCFG->findSecOfVar($key),$key,$val);
			}
			if($MAINCFG->writeConfig()) {
				print "<script>window.opener.document.location.reload();</script>\n";
				print "<script>window.close();</script>\n";
			} else {
				print "<script>alert('error while opening the file ".$MAINCFG->getValue('paths', 'cfg')."config.ini.php"." for writing.')</script>";
			}
		} else {
			print("A needed value is missing.");
		}
	break;
	case 'mgt_map_create':
		if(isset($_POST['map_name']) && $_POST['map_name'] != '' && isset($_POST['allowed_users']) && $_POST['allowed_users'] != '' && isset($_POST['allowed_for_config']) && $_POST['allowed_for_config'] != '') {
			$MAPCFG = new WuiMapCfg($MAINCFG,$_POST['map_name']);
			if(!$MAPCFG->createMapConfig()) {
				exit;
			}
			
			$MAPCFG->addElement('global',Array('allowed_user'=>$_POST['allowed_users'],'allowed_for_config'=>$_POST['allowed_for_config'],'iconset'=>$_POST['map_iconset'],'map_image'=>$_POST['map_image']));
			$MAPCFG->writeElement('global','0');
			
			// do the backup
			backup($MAINCFG,$_POST['map_name']);
			
			print("<script>window.opener.document.location.href='./index.php?map=".$_POST['map_name']."';</script>");
			print("<script>window.close();</script>");
		} else {
			print("A needed value is missing.");
		}
	break;
	case 'mgt_map_rename':
		if(isset($_POST['map_name']) && $_POST['map_name'] != '' && isset($_POST['map_new_name']) && $_POST['map_new_name'] != '' && isset($_POST['map']) && $_POST['map'] != '') {
			$files = Array();
			// alter name: $_POST['map_name']
			// neuer name: $_POST['map_new_name']
			// gerade offene map: $_POST['map']
			
			// read all files in map-cfg folder
			$files = getAllMaps($MAINCFG);
			
			// loop all map configs to replace mapname in all map configs
			foreach($files as $file) {
				$MAPCFG1 = new WuiMapCfg($MAINCFG,$file);
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
			rename($MAINCFG->getValue('paths', 'mapcfg').$_POST['map_name'].'.cfg',$MAINCFG->getValue('paths', 'mapcfg').$_POST['map_new_name'].'.cfg');
			
			echo $_POST['map']." ".$_POST['map_name'];
			// if renamed map is open, redirect to new name
			if($_POST['map'] == 'undefined' || $_POST['map'] == '' || $_POST['map'] == $_POST['map_name']) {
				$map = $_POST['map_new_name'];
			} else {
				$map = $_POST['map'];
			}
			
			print "<script>window.opener.document.location.href='./index.php?map=".$map."';</script>\n";
			print "<script>window.close();</script>\n";
		} else {
			print("A needed value is missing.");
		}
	break;
	case 'mgt_map_delete':
		if(isset($_POST['map_name']) && $_POST['map_name'] != '') {
			// map to delete: $_POST['map_name'];
			
			$MAPCFG = new WuiMapCfg($MAINCFG,$_POST['map_name']);
			$MAPCFG->readMapConfig();
			if($MAPCFG->deleteMapConfig()) {
				print("<script>alert('Map deleted.');</script>");
				print("<script>window.history.back();</script>");
			} else {
				//Error handling is done by the deleteMapConfig() function, no need here
			}
		} else {
			print("A needed value is missing.");
		}
	break;
	case 'mgt_map_export':
		if(isset($_POST['map_name']) && $_POST['map_name'] != '') {
			// map to delete: $_POST['map_name'];
			if(!isset($_POST['map_name']) || $_POST['map_name'] != '') {
				$MAPCFG = new WuiMapCfg($MAINCFG,$_POST['map_name']);
				
				if(!$MAPCFG->exportMap()) {
					// Error Handling
					print "<script>alert('An error occured while exporting the map.')</script>";
				} else {
					// Export successfull, report nothing
				}
			} else {
				// Error Handling
				print "<script>alert('Can\'t export map, no map name given.')</script>";
			}
		} else {
			print("A needed value is missing.");
		}
	break;
	case 'mgt_map_import':
		if(isset($_FILES['map_file']) && is_array($_FILES['map_file'])) {
			// check the file (the map) is properly uploaded
			if(is_uploaded_file($_FILES['map_file']['tmp_name'])) {
				$mapName = $_FILES['map_file']['name'];
				if(preg_match('/\.cfg/i',$fileName)) {
					if(move_uploaded_file($_FILES['map_file']['tmp_name'], $MAINCFG->getValue('paths', 'mapcfg').$mapName)) {
						chmod($MAINCFG->getValue('paths', 'mapcfg').$mapName,0666);
						print("<script>window.history.back();</script>");
					} else {
						print "The file could not be moved to destination (".$MAINCFG->getValue('paths', 'mapcfg').$mapName.").";
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
		} else {
			print("A needed value is missing.");
		}
	break;
	case 'mgt_image_create':
		if(isset($_POST['image_name']) && isset($_POST['image_color']) && isset($_POST['image_width']) && isset($_POST['image_height'])) {
			$BACKGROUND = new WuiBackground($MAINCFG, $_POST['image_name'].'.png');
			if($BACKGROUND->createImage($_POST['image_color'], $_POST['image_width'], $_POST['image_height'])) {
				print("<script>window.history.back();</script>");
			} else {
				// No need for error handling here
				print("<script>window.history.back();</script>");
			}
		} else {
			print("A needed value is missing.");
		}
	break;
	case 'mgt_image_delete':
		if(isset($_POST['map_image']) && $_POST['map_image'] != '') {
			$BACKGROUND = new WuiBackground($MAINCFG, $_POST['map_image']);
			if($BACKGROUND->deleteImage()) {
				print("<script>window.history.back();</script>");
			} else {
				// No need for error handling here
				print("<script>window.history.back();</script>");
			}
		} else {
			print("A needed value is missing.");
		}
	break;
	case 'mgt_image_upload':
		if(isset($_FILES['image_file']) && is_array($_FILES['image_file'])) {
			$BACKGROUND = new WuiBackground($MAINCFG, '');
			if($BACKGROUND->uploadImage($_FILES['image_file'])) {
				print("<script>window.history.back();</script>");
			} else {
				// No need for error handling here
			}
		} else {
			print("A needed value is missing.");
		}
	break;
	case 'map_restore':
		if(isset($_GET['map']) && $_GET['map'] != '') {
			// delete current config
			$MAPCFG = new WuiMapCfg($MAINCFG,$_GET['map']);
			$MAPCFG->readMapConfig();
			$MAPCFG->deleteMapConfig();
			// restore backup
			if(file_exists($MAINCFG->getValue('paths', 'mapcfg').$_GET['map'].'.cfg.bak')) {
				copy($MAINCFG->getValue('paths', 'mapcfg').$_GET['map'].'.cfg.bak',$MAINCFG->getValue('paths', 'mapcfg').$_GET['map'].'.cfg');	
			}
			// reset status
			$done = FALSE;
			
			// is status for this map there?
			$file = file($MAINCFG->getValue('paths', 'mapcfg').'autobackup.status');
			foreach($file AS $key => $val) {
				if(ereg("^".$mapname."=",$val)) {
					// $arr[1] is value
					$arr = explode('=',$val);
					
					$file[$key] = $mapname.'='.$MAINCFG->getValue('wui', 'autoupdatefreq')."\n";
					$done = TRUE;
				}
			}
			
			if($done == FALSE) {
				$file[] = $mapname.'='.$MAINCFG->getValue('wui', 'autoupdatefreq')."\n";
			}
			
			//write array back to file
			$fp = fopen($MAINCFG->getValue('paths', 'mapcfg').'autobackup.status',"w");
			fwrite($fp,implode("",$file));
			fclose($fp);
			
			print "<script>window.document.location.href='./index.php?map=".$_GET['map']."';</script>\n";
		} else {
			print("A needed value is missing.");
		}
	break;
	case 'mgt_backend_default':
		if(isset($_POST['defaultbackend']) && $_POST['defaultbackend'] != '') {
			$MAINCFG->setValue($MAINCFG->findSecOfVar('backend'),'backend',$_POST['defaultbackend']);
			if($MAINCFG->writeConfig()) {
				print("<script>window.history.back();</script>");
			} else {
				// No need for error handling (it's done by writeConfig())
			}
		} else {
			print("A needed value is missing.");
		}
	break;
	case 'mgt_backend_add':
		// $_POST['backend_id'], $_POST['backendtype']
		if(isset($_POST['backend_id']) && $_POST['backend_id'] != '' && isset($_POST['backendtype']) && $_POST['backendtype'] != '') {
			$bFoundOption = FALSE;
			$aOpt = Array();
			
			foreach($MAINCFG->validConfig['backend']['options'][$_POST['backendtype']] AS $key => $arr) {
				if(isset($_POST[$key]) && $_POST[$key] != '') {
					$bFoundOption = TRUE;
					$aOpt[$key] = $_POST[$key];
				}
			}
			
			if($bFoundOption) {
				$MAINCFG->setSection('backend_'.$_POST['backend_id']);
				$MAINCFG->setValue('backend_'.$_POST['backend_id'],'backendid',$_POST['backend_id']);
				$MAINCFG->setValue('backend_'.$_POST['backend_id'],'backendtype',$_POST['backendtype']);
				
				foreach($aOpt AS $key => $val) {
					$MAINCFG->setValue('backend_'.$_POST['backend_id'],$key,$val);
				}
			}
			
			if($MAINCFG->writeConfig()) {
				print("<script>window.history.back();</script>");
			} else {
				// No need for error handling (it's done by writeConfig())
			}
		} else {
			print("A needed value is missing.");
		}
	break;
	case 'mgt_backend_edit':
		if(isset($_POST['backend_id']) && $_POST['backend_id'] != '') {
			$bFoundOption = FALSE;
			$aOpt = Array();
			
			foreach($MAINCFG->validConfig['backend']['options'][$MAINCFG->getValue('backend_'.$_POST['backend_id'],'backendtype')] AS $key => $arr) {
				if(isset($_POST[$key]) && $_POST[$key] != '') {
					$MAINCFG->setValue('backend_'.$_POST['backend_id'],$key,$_POST[$key]);
				}
			}
			
			if($MAINCFG->writeConfig()) {
				print("<script>window.history.back();</script>");
			} else {
				// No need for error handling (it's done by writeConfig())
			}
		} else {
			print("A needed value is missing.");
		}
	break;
	case 'mgt_backend_del':
		if(isset($_POST['backend_id']) && $_POST['backend_id'] != '') {
			$bFoundOption = FALSE;
			$aOpt = Array();
			
			$MAINCFG->delSection('backend_'.$_POST['backend_id']);
			
			if($MAINCFG->writeConfig()) {
				print("<script>window.history.back();</script>");
			} else {
				// No need for error handling (it's done by writeConfig())
			}
		} else {
			print("A needed value is missing.");
		}
	break;
	case 'mgt_shape_add':
		if(isset($_FILES['shape_image']) && is_array($_FILES['shape_image'])) {
			// check the file (the map) is properly uploaded
			if(is_uploaded_file($_FILES['shape_image']['tmp_name'])) {
				$fileName = $_FILES['shape_image']['name'];
				if(preg_match('/\.png/i',$fileName)) {
					if(@move_uploaded_file($_FILES['shape_image']['tmp_name'], $MAINCFG->getValue('paths', 'shape').$fileName)) {
						chmod($MAINCFG->getValue('paths', 'shape').$fileName,0666);
						print("<script>alert('Shape upload succesfull.');</script>");
						print("<script>window.history.back();</script>");
					} else {
						print "ERROR: Could not move the uploaded file to the shape directory '".$MAINCFG->getValue('paths', 'shape')."': Permission denied";
						return;
					}
				} else {
					print("ERROR: The file has the wrong file extension (Has to be *.png)");
				}
			} else {
				print("ERROR: Upload not possible. There are some problems with your webserver configuration.");
				return;
			}
		} else {
			print("A needed value is missing.");
		}
	break;
	case 'mgt_shape_delete':
		if(isset($_POST['shape_image']) && $_POST['shape_image'] != '') {
			if(file_exists($MAINCFG->getValue('paths', 'shape').$_POST['shape_image'])) {
				if(unlink($MAINCFG->getValue('paths', 'shape').$_POST['shape_image'])) {
					print("<script>alert('Shape deleted.');</script>");
					print("<script>window.history.back();</script>");
				} else {
					print("ERROR: Failed to delete '".$MAINCFG->getValue('paths', 'shape').$_POST['shape_image']."'.");
				}
			} else {
				print("ERROR: File '".$MAINCFG->getValue('paths', 'shape').$_POST['shape_image']."' doesn\'t exist.");
			}
		} else {
			print("A needed value is missing.");
		}
	break;
}
?>
