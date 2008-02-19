<?php
/**
 * Class for parsing the NagVis Frontend
 */
class NagVisFrontend extends GlobalPage {
	var $MAINCFG;
	var $MAPCFG;
	var $BACKEND;
	var $LANG;
	
	var $MAP;
	
	/**
	 * Class Constructor
	 *
	 * @param 	GlobalMainCfg 	$MAINCFG
	 * @param 	GlobalMapCfg 	$MAPCFG
	 * @param 	GlobalBackend 	$BACKEND
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	function NagVisFrontend(&$MAINCFG,&$MAPCFG,&$BACKEND) {
		$this->MAINCFG = &$MAINCFG;
		$this->MAPCFG = &$MAPCFG;
		$this->BACKEND = &$BACKEND;
		$this->LANG = new GlobalLanguage($MAINCFG,'nagvis:global');
		$prop = Array('title'=>$MAINCFG->getValue('internal', 'title'),
						'cssIncludes'=>Array('./includes/css/style.css'),
						'jsIncludes'=>Array('./includes/js/nagvis.js','./includes/js/overlib.js','./includes/js/dynfavicon.js'),
						'extHeader'=>Array('<link rel="shortcut icon" href="./images/internal/favicon.png">',
											'<style type="text/css">body.main { background-color: '.$this->MAPCFG->getValue('global',0, 'background_color').'; }</style>'),
						'allowedUsers'=> $this->MAPCFG->getValue('global',0, 'allowed_user'),
						'languageRoot' => 'nagvis:global');
		parent::GlobalPage($this->MAINCFG,$prop);
	}
	
	/**
	 * Displays the automatic index page of all maps
	 *
	 * @return	Array   HTML Code of Index Page
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	function getIndexPage() {
			$ret = Array();
			
			$ret[] = '<div id="overDiv" style="position:absolute; visibility:hidden; z-index:1000;"></div>';
			$ret[] = '<div class="infopage">';
			$ret[] = '<table>';
			$ret[] = '<tr><th colspan="4">'.$this->LANG->getLabel('mapIndex').'</th></tr><tr>';
			$i = 1;
			foreach($this->getMaps() AS $mapName) {
				$MAPCFG = new NagVisMapCfg($this->MAINCFG,$mapName);
				$MAPCFG->readMapConfig();
				
				if($MAPCFG->getValue('global',0, 'show_in_lists') == 1 && ($mapName != '__automap' || ($mapName == '__automap' && $this->MAINCFG->getValue('automap', 'showinlists')))) {
					if($mapName == '__automap') {
						$opts = Array();
						
						// Fetch option array from defaultparams string (extract variable 
						// names and values)
						$params = explode('&',$this->MAINCFG->getValue('automap','defaultparams'));
						unset($params[0]);
						
						foreach($params AS &$set) {
							$arrSet = explode('=',$set);
							$opts[$arrSet[0]] = $arrSet[1];
						}
						
						$opts['preview'] = 1;
						
						$MAP = new NagVisAutoMap($this->MAINCFG, $this->LANG, $this->BACKEND, $opts);
						// If there is no automap image on first load of the index page,
						// render the image
						$MAP->renderMap();
					} else {
						$MAP = new NagVisMap($this->MAINCFG, $MAPCFG, $this->LANG, $this->BACKEND);
					}
					$MAP->MAPOBJ->fetchIcon();
					
					// Check if the user is permited to view this map
					if($MAP->MAPOBJ->checkPermissions($MAPCFG->getValue('global',0, 'allowed_user'),FALSE)) {
						if($MAP->MAPOBJ->checkMaintenance(0)) {
							$class = '';
							
							if($mapName == '__automap') {
								$onClick = 'location.href=\''.$this->MAINCFG->getValue('paths','htmlbase').'/index.php?automap=1'.$this->MAINCFG->getValue('automap','defaultparams').'\';';
							} else {
								$onClick = 'location.href=\''.$this->MAINCFG->getValue('paths','htmlbase').'/index.php?map='.$mapName.'\';';
							}
							
							$summaryOutput = $MAP->MAPOBJ->getSummaryOutput();
						} else {
							$class = 'class="disabled"';
							
							$onClick = 'alert(\''.$this->LANG->getMessageText('mapInMaintenance').'\');';
							$summaryOutput = $this->LANG->getMessageText('mapInMaintenance');
						}
					} else {
						$class = 'class="disabled"';
						
						$onClick = 'alert(\''.$this->LANG->getMessageText('noReadPermissions').'\');';
						$summaryOutput = $this->LANG->getMessageText('noReadPermissions');
					}
					
					// If this is the automap display the last rendered image
					if($mapName == '__automap') {
						$imgPath = $this->MAINCFG->getValue('paths','var').'automap.png';
						$imgPathHtml = $this->MAINCFG->getValue('paths','htmlvar').'automap.png';
					} else {
						$imgPath = $this->MAINCFG->getValue('paths','map').$MAPCFG->BACKGROUND->getFileName();
						$imgPathHtml = $this->MAINCFG->getValue('paths','htmlmap').$MAPCFG->BACKGROUND->getFileName();
					}
					
					// Now form the cell with it's contents
					$ret[] = '<td '.$class.' style="width:200px;height:200px;" onMouseOut="this.style.cursor=\'auto\';this.bgColor=\'\';return nd();" onMouseOver="this.style.cursor=\'pointer\';this.bgColor=\'#ffffff\';return overlib(\'<table class=\\\'infopage_hover_table\\\'><tr><td>'.strtr(addslashes($summaryOutput),Array('"' => '\'', "\r" => '', "\n" => '')).'</td></tr></table>\');" onClick="'.$onClick.'">';
					$ret[] = '<img align="right" src="'.$MAP->MAPOBJ->iconHtmlPath.$MAP->MAPOBJ->icon.'" />';
					$ret[] = '<h2>'.$MAPCFG->getValue('global', '0', 'alias').'</h2><br />';
					if($this->MAPCFG->getValue('global', 0,'usegdlibs') == '1' && $MAP->checkGd(1)) {
						$ret[] = '<img style="width:200px;height:150px;" src="'.$this->createThumbnail($imgPath, $mapName).'" /><br />';
					} else {
						$ret[] = '<img style="width:200px;height:150px;" src="'.$imgPathHtml.'" /><br />';
					}
					$ret[] = '</td>';
					if($i % 4 == 0) {
							$ret[] = '</tr><tr>';
					}
					$i++;
				}
			}
			// Fill table with empty cells if there are not enough maps to get the line filled
			if(($i - 1) % 4 != 0) {
					for($a=0;$a < (4 - (($i - 1) % 4));$a++) {
							$ret[] = '<td>&nbsp;</td>';
					}
			}
			$ret[] = '</tr>';
			$ret[] = '</table>';
			
			/**
			 * Infobox lists all map rotation pools
			 */
			$ret[] = '<table class="infobox">';
			$ret[] = '<tr><th>'.$this->LANG->getLabel('rotationPools').'</th></tr>';
			foreach($this->getRotationPools() AS $poolName) {
				// Form the onClick action
				$onClick = 'location.href=\''.$this->MAINCFG->getValue('paths','htmlbase').'/index.php?rotation='.$poolName.'\';';
				
				// Now form the HTML code for the cell
				$ret[] = '<tr><td onMouseOut="this.style.cursor=\'auto\';this.bgColor=\'\';return nd();" onMouseOver="this.style.cursor=\'pointer\';this.bgColor=\'#ffffff\';" onClick="'.$onClick.'">';
				$ret[] = '<h2>'.$poolName.'</h2><br />';
				$ret[] = '</td>';
				$ret[] = '</tr>';
			}
			$ret[] = '</table>';
			
			$ret[] = '</div>';
			
			return $ret;
	}
	
	/**
	 * Creates thumbnail images for the index map
	 *
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	function createThumbnail($imgPath, $mapName) {
		
		if($this->checkImageExists($imgPath, TRUE)) {
			$imgSize = getimagesize($imgPath);
			// 0: width, 1:height, 2:type
			
			switch($imgSize[2]) {
				case 1: // GIF
				$image = imagecreatefromgif($imgPath);
				break;
				case 2: // JPEG
				$image = imagecreatefromjpeg($imgPath);
				break;
				case 3: // PNG
				$image = imagecreatefrompng($imgPath);
				break;
				default:
					$FRONTEND = new GlobalPage($this->MAINCFG,Array('languageRoot'=>'nagvis:global'));
					$FRONTEND->messageToUser('ERROR','onlyPngOrJpgImages');
				break;
			}
			
			// Maximum size
			$thumbMaxWidth = 200;
			$thumbMaxHeight = 150;
			
			$thumbWidth = $imgSize[0];
			$thumbHeight = $imgSize[1];
			
			if ($thumbWidth > $thumbMaxWidth) {
				$factor = $thumbMaxWidth / $thumbWidth;
				$thumbWidth *= $factor;
				$thumbHeight *= $factor;
			}
			
			if ($thumbHeight > $thumbMaxHeight) {
				$factor = $thumbMaxHeight / $thumbHeight;
				$thumbWidth *= $factor;
				$thumbHeight *= $factor;
			}
			
			$thumb = imagecreatetruecolor($thumbWidth, $thumbHeight);
			imagecopyresampled($thumb, $image, 0, 0, 0, 0, $thumbWidth, $thumbHeight, $imgSize[0], $imgSize[1]);
			
			imagepng($thumb, $this->MAINCFG->getValue('paths','var').$mapName.'-thumb.png');
			
			return $this->MAINCFG->getValue('paths','htmlvar').$mapName.'-thumb.png';
		} else {
			return '';
		}
	}
	
	/**
	 * Checks Image exists
	 *
	 * @param 	String	$imgPath
	 * @param 	Boolean	$printErr
	 * @return	Boolean	Is Check Successful?
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	function checkImageExists($imgPath, $printErr) {
		if(file_exists($imgPath)) {
			return TRUE;
		} else {
			if($printErr == 1) {
				$FRONTEND = new GlobalPage($this->MAINCFG,Array('languageRoot'=>'nagvis:global'));
				$FRONTEND->messageToUser('WARNING','imageNotExists','FILE~'.$imgPath);
			}
			return FALSE;
		}
	}
	
	/**
	 * Reads informations from currently running Apache/PHP installation
	 *
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	function getInstInformations() {
		$ret = Array();
		
		$ret[] = '<div class="infopage">';
		$ret[] = '<table class="instinfo">';
		$ret[] = '<tr><th colspan="2" class="head">'.$this->LANG->getLabel('supportInfo').'</td></tr>';
		$ret[] = '</table><br />';
		
		$ret[] = '<table class="instinfo">';
		$ret[] = '<tr><th colspan="2">Version Informations</td></tr>';
		$ret[] = '<tr><td>NagVis Version</td><td>'.$this->MAINCFG->getValue('internal','version').'</td></tr>';
		$ret[] = '<tr><td>PHP Version</td><td>'.PHP_VERSION.'</td></tr>';
		$ret[] = '<tr><td>MySQL Version</td><td>'.shell_exec('mysql --version').'</td></tr>';
		$ret[] = '<tr><td>OS</td><td>'.shell_exec('uname -a').'</td></tr>';
		$ret[] = '<t><th colspan="2">Webserver Informations</th></tr>';
		$ret[] = '<tr><td>SERVER_SOFTWARE</td><td>'.$_SERVER['SERVER_SOFTWARE'].'</td></tr>';
		$ret[] = '<tr><td>REMOTE_USER</td><td>'.$_SERVER['REMOTE_USER'].'</td></tr>';
		$ret[] = '<tr><td>SCRIPT_FILENAME</td><td>'.$_SERVER['SCRIPT_FILENAME'].'</td></tr>';
		$ret[] = '<tr><td>SCRIPT_NAME</td><td>'.$_SERVER['SCRIPT_NAME'].'</td></tr>';
		$ret[] = '<tr><td>REQUEST_TIME</td><td>'.$_SERVER['REQUEST_TIME'].' ('.date('r',$_SERVER['REQUEST_TIME']).')</td></tr>';
		$ret[] = '<t><th colspan="2">PHP Informations</th></tr>';
		$ret[] = '<tr><td>safe_mode</td><td>'.ini_get('safe_mode').'</td></tr>';
		$ret[] = '<tr><td>max_execution_time</td><td>'.ini_get('max_execution_time').'</td></tr>';
		$ret[] = '<tr><td>memory_limit</td><td>'.ini_get('memory_limit').'</td></tr>';
		$ret[] = '</table>';
		$ret[] = '</div>';
		
		$this->addBodyLines($ret);
	}
	
	/**
	 * If enabled, the header menu is added to the page
	 *
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	function getHeaderMenu() {
		if($this->MAINCFG->getValue('global', 'displayheader') == '1') {
			if($this->checkHeaderTemplateReadable(1)) {
				$ret = file_get_contents($this->MAINCFG->getValue('paths','headertemplate').'tmpl.'.$this->MAPCFG->getValue('global', 0, 'header_template').'.html');
				
				// Replace some macros
				$ret = str_replace('[current_map]',$this->MAPCFG->getName(),$ret);
				$ret = str_replace('[current_map_alias]',$this->MAPCFG->getValue('global', '0', 'alias'),$ret);
				$ret = str_replace('[html_base]',$this->MAINCFG->getValue('paths','htmlbase'),$ret);
				$ret = str_replace('[html_templates]',$this->MAINCFG->getValue('paths','htmlheadertemplates'),$ret);
				$ret = str_replace('[html_template_images]',$this->MAINCFG->getValue('paths','htmlheadertemplateimages'),$ret);
				// Replace language macros
				$ret = str_replace('[lang_select_map]',$this->LANG->getLabel('selectMap'),$ret);
				$ret = str_replace('[lang_edit_map]',$this->LANG->getLabel('editMap'),$ret);
				$ret = str_replace('[lang_need_help]',$this->LANG->getLabel('needHelp'),$ret);
				$ret = str_replace('[lang_online_doc]',$this->LANG->getLabel('onlineDoc'),$ret);
				$ret = str_replace('[lang_forum]',$this->LANG->getLabel('forum'),$ret);
				$ret = str_replace('[lang_support_info]',$this->LANG->getLabel('supportInfo'),$ret);
				$ret = str_replace('[lang_overview]',$this->LANG->getLabel('overview'),$ret);
				$ret = str_replace('[lang_instance]',$this->LANG->getLabel('instance'),$ret);
				$ret = str_replace('[lang_rotation_start]',$this->LANG->getLabel('rotationStart'),$ret);
				$ret = str_replace('[lang_rotation_stop]',$this->LANG->getLabel('rotationStop'),$ret);
				// Replace lists
				if(preg_match_all('/<!-- BEGIN (\w+) -->/',$ret,$matchReturn) > 0) {
					foreach($matchReturn[1] AS &$key) {
						if($key == 'maplist') {
							$sReplace = '';
							preg_match_all('/<!-- BEGIN '.$key.' -->((?s).*)<!-- END '.$key.' -->/',$ret,$matchReturn1);
							foreach($this->getMaps() AS $mapName) {
								$MAPCFG1 = new NagVisMapCfg($this->MAINCFG,$mapName);
								$MAPCFG1->readMapConfig(1);
								
								if($MAPCFG1->getValue('global',0, 'show_in_lists') == 1 && ($mapName != '__automap' || ($mapName == '__automap' && $this->MAINCFG->getValue('automap', 'showinlists')))) {
									$sReplaceObj = str_replace('[map_name]',$MAPCFG1->getName(),$matchReturn1[1][0]);
									$sReplaceObj = str_replace('[map_alias]',$MAPCFG1->getValue('global', '0', 'alias'),$sReplaceObj);
									
									// Add defaultparams to map selection
									if($mapName == '__automap') {
										$sReplaceObj = str_replace('[url_params]', $this->MAINCFG->getValue('automap', 'defaultparams'), $sReplaceObj);
									} else {
										$sReplaceObj = str_replace('[url_params]','',$sReplaceObj);
									}
									
									// auto select current map
									if($mapName == $this->MAPCFG->getName() || ($mapName == '__automap' && isset($_GET['automap']))) {
										$sReplaceObj = str_replace('[selected]','selected="selected"',$sReplaceObj);
									} else {
										$sReplaceObj = str_replace('[selected]','',$sReplaceObj);
									}
									
									$sReplace .= $sReplaceObj;
								}
							}
							$ret = preg_replace('/<!-- BEGIN '.$key.' -->(?:(?s).*)<!-- END '.$key.' -->/',$sReplace,$ret);
						}
					}
				}
				
				$this->addBodyLines('<div class="header">'.$ret.'</div>');
			}
		}
	}
	
	/**
	 * Checks for existing header template
	 *
	 * @param 	Boolean	$printErr
	 * @return	Boolean	Is Check Successful?
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	function checkHeaderTemplateExists($printErr) {
		if(file_exists($this->MAINCFG->getValue('paths', 'headertemplate').'tmpl.'.$this->MAPCFG->getValue('global', 0, 'header_template').'.html')) {
			return TRUE;
		} else {
			if($printErr == 1) {
				$FRONTEND = new GlobalPage($this->MAINCFG,Array('languageRoot'=>'global:global'));
				$FRONTEND->messageToUser('WARNING','headerTemplateNotExists','FILE~'.$this->MAINCFG->getValue('paths', 'headertemplate').'tmpl.'.$this->MAPCFG->getValue('global', 0, 'header_template').'.html');
			}
			return FALSE;
		}
	}
	
	/**
	 * Checks for readable header template
	 *
	 * @param 	Boolean	$printErr
	 * @return	Boolean	Is Check Successful?
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	function checkHeaderTemplateReadable($printErr) {
		if($this->checkHeaderTemplateExists($printErr) && is_readable($this->MAINCFG->getValue('paths', 'headertemplate').'tmpl.'.$this->MAPCFG->getValue('global', 0, 'header_template').'.html')) {
			return TRUE;
		} else {
			if($printErr == 1) {
				$FRONTEND = new GlobalPage($this->MAINCFG,Array('languageRoot'=>'global:global'));
				$FRONTEND->messageToUser('WARNING','headerTemplateNotReadable','FILE~'.$this->MAINCFG->getValue('paths', 'headertemplate').'tmpl.'.$this->MAPCFG->getValue('global', 0, 'header_template').'.html');
			}
			return FALSE;
		}
	}
	
	/**
	 * Adds the map to the page
	 *
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	function getMap() {
		$this->addBodyLines(Array('<div id="overDiv" style="position:absolute; visibility:hidden; z-index:1000;"></div>'));
		$this->addBodyLines(Array('<div class="map">'));
		$this->MAP = new NagVisMap($this->MAINCFG,$this->MAPCFG,$this->LANG,$this->BACKEND);
		$this->MAP->MAPOBJ->checkMaintenance(1);
		$this->addBodyLines($this->MAP->parseMap());
		$this->addBodyLines(Array('</div>'));
	}
	
	/**
	 * Adds the automap to the page
	 *
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	function getAutoMap($arrOptions) {
		$this->addBodyLines(Array('<div id="overDiv" style="position:absolute; visibility:hidden; z-index:1000;"></div>'));
		$this->addBodyLines(Array('<div id="map" class="map">'));
		$this->MAP = new NagVisAutoMap($this->MAINCFG, $this->LANG, $this->BACKEND, $arrOptions);
		$this->addBodyLines($this->MAP->parseMap());
		$this->addBodyLines(Array('</div>'));
	}
	
	/**
	 * Adds the user messages to the page
	 *
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	function getMessages() {
		$this->addBodyLines($this->getUserMessages());
	}

	/**
	 * Gets the javascript code for the map refresh/rotation
	 *
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	function getRefresh() {
		$strReturn = "";
		if(isset($_GET['rotation']) && $_GET['rotation'] != '' && (!isset($_GET['rotate']) || (isset($_GET['rotate']) && $_GET['rotate'] == '1'))) {
			$strReturn .= "var rotate = true;\n";
		} else {
			$strReturn .= "var rotate = false;\n";
		}
		$strReturn .= "var nextRotationUrl = '".$this->getNextRotationUrl()."';\n";
		$strReturn .= "var nextRefreshTime = '".$this->getNextRotationTime()."';\n";
		$strReturn .= "var oRotation = window.setTimeout('countdown()', 1000);\n";
		
	    return $this->parseJs($strReturn);
	}
	
	/**
	 * Returns the next time to refresh or rotate in seconds
	 *
	 * @return	Integer		Returns The next rotation time in seconds
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	function getNextRotationTime() {
		if(isset($_GET['rotation']) && $_GET['rotation'] != '') {
			return $this->MAINCFG->getValue('rotation_'.$_GET['rotation'], 'interval');
		} else {
			return $this->MAINCFG->getValue('rotation', 'interval');
		}
	}
  
	/**
	 * Gets the Next map to rotate to, if enabled
	 * If Next map is in [ ], it will be an absolute url
	 *
	 * @return	String  URL to rotate to
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	function getNextRotationUrl() {
		if(isset($_GET['rotation']) && $_GET['rotation'] != '') {
			if($maps = $this->MAINCFG->getValue('rotation_'.$_GET['rotation'], 'maps')) {
				$maps = explode(',', str_replace('"','',$maps));
				
				if(isset($_GET['url']) && $_GET['url'] != '') {
					$currentMap = '['.$_GET['url'].']';
				} else {
					$currentMap = $this->MAPCFG->getName();
				}
			
				// get position of actual map in the array
				$index = array_search($currentMap,$maps);
				if(($index + 1) >= sizeof($maps)) {
					// if end of array reached, go to the beginning...
					$index = 0;
				} else {
					$index++;
				}
					
				$nextMap = $maps[$index];
				
				
				if(preg_match("/^\[(.+)\]$/",$nextMap,$arrRet)) {
					return 'index.php?rotation='.$_GET['rotation'].'&url='.$arrRet[1];
				} else {
					return 'index.php?rotation='.$_GET['rotation'].'&map='.$nextMap;
				}
			} else {
				// Error Message (Map rotation pool does not exist)
				$FRONTEND = new GlobalPage($this->MAINCFG,Array('languageRoot'=>'nagvis:global'));
				$FRONTEND->messageToUser('ERROR','mapRotationPoolNotExists','ROTATION~'.$_GET['rotation']);
				
				return '';
			}
		} else {
			return '';
		}
	}
	
	/**
	 * Gets all defined maps
	 *
	 * @return	Array maps
	 * @author Lars Michelsen <lars@vertical-visions.de>
	 */
	function getMaps() {
		$files = Array();
		
		if ($handle = opendir($this->MAINCFG->getValue('paths', 'mapcfg'))) {
 			while (false !== ($file = readdir($handle))) {
				if(preg_match('/^.+\.cfg$/', $file)) {
					$files[] = substr($file,0,strlen($file)-4);
				}
			}
			
			if ($files) {
				natcasesort($files);
			}
		}
		closedir($handle);
		
		return $files;
	}
	
	/**
	 * Gets all rotation pools
	 *
	 * @return	Array pools
	 * @author Lars Michelsen <lars@vertical-visions.de>
	 */
	function getRotationPools() {
		$ret = Array();
		
		foreach($this->MAINCFG->config AS $sec => &$var) {
			if(preg_match('/^rotation_/i', $sec)) {
				$ret[] = $var['rotationid'];
			}
		}
		
		return $ret;
	}
}
?>
