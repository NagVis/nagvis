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

require('../nagvis/includes/classes/class.GlobalDebug.php');
require('../nagvis/includes/classes/class.GlobalLanguage.php');
require('../nagvis/includes/classes/class.GlobalMainCfg.php');
require('../nagvis/includes/classes/class.GlobalPage.php');
require('../nagvis/includes/classes/class.GlobalMapCfg.php');
require('./includes/classes/class.WuiMainCfg.php');
require('./includes/classes/class.WuiMapCfg.php');

$MAINCFG = new WuiMainCfg('../nagvis/etc/config.ini.php');

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
						// zurücksetzen
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
	break;
	case 'modify':
		if(!isset($_POST['map'])) {
			print "<script>alert('No Map submited');</script>\n";
		} else {
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
		}
	break;
	case 'add':
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
	break;
	case 'delete':
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
	break;
	case 'update_config':
		foreach(getArrayFromProperties($_POST['properties']) AS $key => $val) {
			$MAINCFG->setValue($MAINCFG->findSecOfVar($key),$key,$val);
		}
		if($MAINCFG->writeConfig()) {
			print "<script>window.opener.document.location.reload();</script>\n";
			print "<script>window.close();</script>\n";
		} else {
			print "<script>alert('error while opening the file ".$MAINCFG->getValue('paths', 'cfg')."config.ini.php"." for writing.')</script>";
		}
	break;
	case 'mgt_map_create':
		$MAPCFG = new WuiMapCfg($MAINCFG,$_POST['map_name']);
		if(!$MAPCFG->createMapConfig()) {
			exit;
		}
		
		$MAPCFG->addElement('global',Array('allowed_user'=>$_POST['allowed_users'],'allowed_for_config'=>$_POST['allowed_for_config'],'iconset'=>$_POST['map_iconset'],'map_image'=>$_POST['map_image']));
		$MAPCFG->writeElement('global','0');
		
		// do the backup
		backup($MAINCFG,$_POST['map_name']);
		
		print "<script>window.opener.document.location.href='./index.php?map=".$_POST['map_name']."';</script>\n";
		print "<script>window.close();</script>\n";
	break;
	case 'mgt_map_rename':
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
		
		// if renamed map is open, redirect to new name
		if($_POST['map'] == $_POST['map_name']) {
			$map = $_POST['map_new_name'];
		} else {
			$map = $_POST['map'];
		}
		print "<script>window.opener.document.location.href='./index.php?map=".$map."';</script>\n";
		print "<script>window.close();</script>\n";
	break;
	case 'mgt_map_delete':
		// map to delete: $_POST['map_name'];
		// aktuelle map: $_POST['map'];
		
		$MAPCFG = new WuiMapCfg($MAINCFG,$_POST['map_name']);
		$MAPCFG->readMapConfig();
		$MAPCFG->deleteMapConfig();
		
		// if renamed map is open, redirect to new name
		if($_POST['map'] == $_POST['map_name']) {
			$map = '';
		} else {
			$map = $_POST['map'];
		}
		print "<script>window.opener.document.location.href='./index.php?map=".$map."';</script>\n";
		print "<script>window.close();</script>\n";
	break;
	case 'mgt_map_export':
		// map to delete: $_POST['map_name'];
		if(!isset($_POST['map_name']) || $_POST['map_name'] != '') {
			$MAPCFG = new WuiMapCfg($MAINCFG,$_POST['map_name']);
			
			if(!$MAPCFG->exportMap()) {
				// Error Handling
				print "<script>alert('An error occured while exporting the map.')</script>";
			}
		} else {
			// Error Handling
			print "<script>alert('Can\'t export map, no map name given.')</script>";
		}
	break;
	case 'mgt_map_import':
		if (!isset(${'HTTP_POST_FILES'}) || !is_array(${'HTTP_POST_FILES'})) {
			$HTTP_POST_FILES = $_FILES;
		}
		// check the file (the map) is properly uploaded
		if(is_uploaded_file($HTTP_POST_FILES['map_file']['tmp_name'])) {
		    $mapName = $HTTP_POST_FILES['map_file']['name'];
		    if(substr($mapName,strlen($mapName)-4,4) == '.cfg') {
		    	if(move_uploaded_file($HTTP_POST_FILES['map_file']['tmp_name'], $MAINCFG->getValue('paths', 'mapcfg').$mapName)) {
		    		chmod($MAINCFG->getValue('paths', 'mapcfg').$mapName,0666);
				    print "<script>window.opener.document.location.reload();</script>\n";
				    print "<script>window.close();</script>\n";
				} else {
		    		print "A problem occured!";
					return;
		    	}
		    } else {
	    		print "A problem occured!";
				return;
	    	}
		} else {
			print "A problem occured!";
			return;
		}
	break;
	case 'mgt_image_create':
	    if(isset($_POST['image_name']) && isset($_POST['image_color']) && isset($_POST['image_width']) && isset($_POST['image_height'])) {
    	    if(!file_exists($MAINCFG->getValue('paths', 'map').$_POST['image_name'].'.png')) {
        		$image = imagecreatetruecolor($_POST['image_width'],$_POST['image_height']);
        		
        		// get rgb color from hexcode
        		$_POST['image_color'] = str_replace('#','',$_POST['image_color']);
        		$int = hexdec($_POST['image_color']);
                $r = 0xFF & ($int >> 0x10);
                $g = 0xFF & ($int >> 0x8);
                $b = 0xFF & $int;
        		
        		$bgColor = imagecolorallocate($image, $r, $g, $b);
        		imagefill($image, 0, 0, $bgColor);
        		imagepng($image,$MAINCFG->getValue('paths', 'map').$_POST['image_name'].'.png');
        		imagedestroy($image);
        		
        		print "<script>window.opener.document.location.reload();</script>\n";
        		print "<script>window.close();</script>\n";
        	} else {
        	    print "<script>alert('error: image already exists \"".$MAINCFG->getValue('paths', 'map').$_POST['image_name'].'.png'."\".')</script>";
        	}
        } else {
            print "<script>alert('error: a problem occured!')</script>";
        }
	break;
	case 'mgt_image_delete':
		if(file_exists($MAINCFG->getValue('paths', 'map').$_POST['map_image'])) {
			if(unlink($MAINCFG->getValue('paths', 'map').$_POST['map_image'])) {
				
			} else {
				print "<script>alert('error: failed to delete ".$MAINCFG->getValue('paths', 'map').$_POST['map_image'].".')</script>";
			}
		} else {
			print "<script>alert('error: file ".$MAINCFG->getValue('paths', 'map').$_POST['map_image']." doesn\'t exists.')</script>";
		}
		print "<script>window.opener.document.location.reload();</script>\n";
		print "<script>window.close();</script>\n";
	break;
	case 'mgt_new_image':
		if (!isset(${'HTTP_POST_FILES'}) || !is_array(${'HTTP_POST_FILES'})) {
			$HTTP_POST_FILES = $_FILES;
		}
		// check the file (the map) is properly uploaded
		if(is_uploaded_file($HTTP_POST_FILES['image_file']['tmp_name'])) {
		    $ficname = $HTTP_POST_FILES['image_file']['name'];
		    if(substr($ficname,strlen($ficname)-4,4) == ".png") {
		    	if(move_uploaded_file($HTTP_POST_FILES['image_file']['tmp_name'], $MAINCFG->getValue('paths', 'map').$ficname)) {
		    		chmod($MAINCFG->getValue('paths', 'map').$ficname,0666);
				    print "<script>window.opener.document.location.reload();</script>\n";
				    print "<script>window.close();</script>\n";
				} else {
		    		print "A problem occured !";
					return;
		    	}
		    } 
		} else {
			print "A problem occured !";
			return;
		}
	break;
	case 'map_restore':
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
	break;
	case 'mgt_backend_default':
		$MAINCFG->setValue($MAINCFG->findSecOfVar('backend'),'backend',$_POST['defaultbackend']);
		if($MAINCFG->writeConfig()) {
			print "<script>window.history.back();</script>";
			print "<script>window.opener.document.location.reload();</script>\n";
		} else {
			print "<script>alert('error while opening the file ".$MAINCFG->getValue('paths', 'cfg')."config.ini.php"." for writing.')</script>";
		}
	break;
	
	case 'mgt_backend_add':
		// $_POST['backend_id'], $_POST['backendtype']
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
		    print "<script>window.close();</script>\n";
		} else {
			print "<script>alert('error while opening the file ".$MAINCFG->getValue('paths', 'cfg')."config.ini.php"." for writing.')</script>";
		}
	break;
	case 'mgt_backend_edit':
		$bFoundOption = FALSE;
		$aOpt = Array();
		
		foreach($MAINCFG->validConfig['backend']['options'][$MAINCFG->getValue('backend_'.$_POST['backend_id'],'backendtype')] AS $key => $arr) {
			if(isset($_POST[$key]) && $_POST[$key] != '') {
				$MAINCFG->setValue('backend_'.$_POST['backend_id'],$key,$_POST[$key]);
			}
		}
		
		if($MAINCFG->writeConfig()) {
		    print "<script>window.close();</script>\n";
		} else {
			print "<script>alert('error while opening the file ".$MAINCFG->getValue('paths', 'cfg')."config.ini.php"." for writing.')</script>";
		}
	break;
	case 'mgt_backend_del':
		$bFoundOption = FALSE;
		$aOpt = Array();
		
		$MAINCFG->delSection('backend_'.$_POST['backend_id']);
		
		if($MAINCFG->writeConfig()) {
		    print "<script>window.close();</script>\n";
		} else {
			print "<script>alert('error while opening the file ".$MAINCFG->getValue('paths', 'cfg')."config.ini.php"." for writing.')</script>";
		}
	break;
	case 'mgt_shape_add':
		if (!isset(${'HTTP_POST_FILES'}) || !is_array(${'HTTP_POST_FILES'})) {
			$HTTP_POST_FILES = $_FILES;
		}
		// check the file (the map) is properly uploaded
		if(is_uploaded_file($HTTP_POST_FILES['shape_image']['tmp_name'])) {
		    $fileName = $HTTP_POST_FILES['shape_image']['name'];
		    if(substr($fileName,strlen($fileName)-4,4) == ".png") {
		    	if(move_uploaded_file($HTTP_POST_FILES['shape_image']['tmp_name'], $MAINCFG->getValue('paths', 'shape').$fileName)) {
		    		chmod($MAINCFG->getValue('paths', 'shape').$fileName,0666);
				    print "<script>window.opener.document.location.reload();</script>\n";
				    print "<script>window.close();</script>\n";
				} else {
		    		print "A problem occured !";
					return;
		    	}
		    } 
		} else {
			print "A problem occured !";
			return;
		}
	break;
	case 'mgt_shape_delete':
		if(file_exists($MAINCFG->getValue('paths', 'shape').$_POST['shape_image'])) {
			if(unlink($MAINCFG->getValue('paths', 'shape').$_POST['shape_image'])) {
				
			} else {
				print "<script>alert('error: failed to delete ".$MAINCFG->getValue('paths', 'shape').$_POST['shape_image'].".')</script>";
			}
		} else {
			print "<script>alert('error: file ".$MAINCFG->getValue('paths', 'shape').$_POST['shape_image']." doesn\'t exists.')</script>";
		}
		print "<script>window.opener.document.location.reload();</script>\n";
		print "<script>window.close();</script>\n";
	break;
}
?>