<?php
/** 
 * Class for printing the map in NagVis Wui
 */
class WuiMap extends GlobalMap {
	var $MAINCFG;
	var $MAPCFG;
	var $LANG;
	var $GRAPHIC;
	
	var $objects;
	var $moveable;
	var $actId;
	
	/**
	 * Class Constructor
	 *
	 * @param 	$MAINCFG WuiMainCfg
	 * @param 	$MAPCFG  GlobalMapCfg
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	function WuiMap(&$MAINCFG,&$MAPCFG,&$LANG) {
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method WuiMap::WuiMap(&$MAINCFG,&$MAPCFG,&$LANG)');
		$this->MAINCFG = &$MAINCFG;
		$this->MAPCFG = &$MAPCFG;
		$this->LANG = &$LANG;
		
		$this->GRAPHIC = new GlobalGraphic();
		
		parent::GlobalMap($MAINCFG,$MAPCFG);
		
		$this->loadPermissions();
		$this->objects = $this->getMapObjects(1);
		if (DEBUG&&DEBUGLEVEL&1) debug('End method WuiMap::WuiMap()');
	}
	
	/**
	 * Loads and parses permissions of alle maps in js array
	 *
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	function loadPermissions() {
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method WuiMap::LoadPermissions()');
		$mapOptions = '[ ';
		$a = 0;
		foreach($this->getMaps() AS $map) {
			if($a > 0) {
				$mapOptions .= ', ';	
			}
			
			$MAPCFG1 = new WuiMapCfg($this->MAINCFG,$map);
			$MAPCFG1->readMapConfig(0);
			$mapOptions .= '{ mapName:\''.$map.'\'';
			
			// map alias
			$mapOptions .= ', mapAlias:\''.$MAPCFG1->getValue('global', '0', 'alias').'\'';
			
			// used image
			$mapOptions .= ', mapImage:\''.$MAPCFG1->getValue('global', '0', 'map_image').'\'';
			
			// permited users for writing
			$mapOptions .= ', allowedForConfig:[ ';
			$arr = $MAPCFG1->getValue('global', '0', 'allowed_for_config');
			for($i = 0; count($arr) > $i; $i++) {
				if($i > 0) {
					$mapOptions .= ',';	
				}
				$mapOptions .= '\''.$arr[$i].'\' ';
			}
			$mapOptions .= ' ]';
			
			// permited users for viewing the map
			$mapOptions .= ', allowedUsers:[ ';
			$arr = $MAPCFG1->getValue('global', '0', 'allowed_user');
			for($i = 0; count($arr) > $i; $i++) {
				if($i > 0) {
					$mapOptions .= ',';	
				}
				$mapOptions .= '\''.$arr[$i].'\' ';
			}
			$mapOptions .= ' ]';
			
			// linked maps
			$mapOptions .= ', linkedMaps:[ ';
			$i = 0;
			foreach($MAPCFG1->getDefinitions('map') AS $key => $obj) {
				if($i > 0) {
					$mapOptions .= ',';
				}
				$mapOptions .= '\''.$obj['map_name'].'\' ';
				$i++;
			}
			$mapOptions .= ' ]';

			// used shapes
			$mapOptions .= ', usedShapes:[ ';
			$i = 0;
			foreach($MAPCFG1->getDefinitions('shape') AS $key => $obj) {
				if($i > 0) {
					$mapOptions .= ',';
				}
				$mapOptions .= '\''.$obj['icon'].'\' ';
				$i++;
			}
			$mapOptions .= ' ]';
			
			$mapOptions .= ' }';
			$a++;
		}
		$mapOptions .= ' ]';
		$this->MAINCFG->setRuntimeValue('mapOptions',$mapOptions);
		if (DEBUG&&DEBUGLEVEL&1) debug('End method WuiMap::LoadPermissions()');
	}
	
	/**
	 * Parses the map
	 *
	 * @return	Array Html
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	function parseMap() {
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method WuiMap::parseMap()');
		$ret = Array();
		$ret = array_merge($ret,$this->getBackground());
		$ret = array_merge($ret,$this->parseJs(array_merge($this->getJsGraphicObj(),$this->getJsLang())));
		$ret = array_merge($ret,$this->parseObjects());
		$ret = array_merge($ret,$this->parseInvisible());
		$ret = array_merge($ret,$this->makeObjectsMoveable());
		$ret = array_merge($ret,Array("<script type=\"text/javascript\" src=\"./includes/js/wz_tooltip.js\"></script>"));
		
		if (DEBUG&&DEBUGLEVEL&1) debug('End method WuiMap::parseMap(): Array(HTML)');
		return $ret;
	}
	
	/**
	 * Gets the background of the map
	 *
	 * @return	Array	HTML Code
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
     */
	function getBackground() {
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method WuiMap::getBackground('.$type.')');
		$style = '';
		if($this->MAPCFG->getName() != '') {
			$src = $this->MAINCFG->getValue('paths', 'htmlmap').$this->MAPCFG->BACKGROUND->getFileName();
		} else {
			$src = './images/internal/wuilogo.png';
			$style = 'width:800px; height:600px;';
		}
		
		if (DEBUG&&DEBUGLEVEL&1) debug('End method WuiMap::getBackground(): Array(...)');
		return $this->getBackgroundHtml($src, $style);
	}
	
	/**
	 * Gets JS graphic options
	 *
	 * @return	Array Html
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	function getJsGraphicObj() {
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method WuiMap::getJsGraphicObj()');
		$ret = Array();
		$ret[] = "myshape_background = new jsGraphics('mymap');";
		$ret[] = "myshape_background.setColor('#FF0000');";
		$ret[] = "myshape_background.setStroke(1);\n";
		
		if (DEBUG&&DEBUGLEVEL&1) debug('End method WuiMap::getJsGraphicObj(): Array(JS)');
		return $ret;
	}
	
	/**
	 * Makes defined objecs moveable
	 *
	 * @return	Array Html
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	function makeObjectsMoveable() {
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method WuiMap::makeObjectsMoveable()');
		$ret = Array();
		
		if(strlen($this->moveable) != 0) {
			$ret = $this->parseJs("SET_DHTML(TRANSPARENT,CURSOR_HAND,".substr($this->moveable,0,strlen($this->moveable)-1).");\n");
		}
		
		if (DEBUG&&DEBUGLEVEL&1) debug('End method WuiMap::makeObjectsMoveable(): Array(HTML)');
		return $ret;
	}
	
	/**
	 * Parses given Js code
	 *
	 * @param	String	$js	Javascript code to parse
	 * @return	Array 	Html
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	function parseJs($js) {
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method WuiMap::parseJs(Array/String $js)');
		$ret = Array();
		
		$ret[] = "<script type=\"text/javascript\" language=\"JavaScript\">";
		$ret[] = "<!--";
		if(is_array($js)) {
			$ret = array_merge($ret,$js);
		} else {
			$ret[] = $js;
		}
		$ret[] = "//-->";
		$ret[] = "</script>";
		
		if (DEBUG&&DEBUGLEVEL&1) debug('End method WuiMap::makeObjectsMoveable(): Array(HTML)');
		return $ret;
	}
	
	/**
	 * Parses all objects on the map
	 *
	 * @return	Array 	Html
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	function parseObjects() {
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method WuiMap::parseObjects()');
		$ret = Array();
		
		foreach($this->objects AS $var => $obj) {
			switch($obj['type']) {
				case 'textbox':
					$obj['class'] = "box";
					$obj['icon'] = "20x20.gif";
					
					$ret = array_merge($ret,$this->textBox($obj));
					$obj = $this->fixIcon($obj);
					$ret = array_merge($ret,$this->parseIcon($obj));
				break;
				default:
					if(isset($obj['line_type'])) {
						list($pointa_x,$pointb_x) = explode(",", $obj['x']);
						list($pointa_y,$pointb_y) = explode(",", $obj['y']);
						$ret[] = "<script type=\"text/javascript\">myshape_background.drawLine(".$pointa_x.",".$pointa_y.",".$pointb_x.",".$pointb_y.");</script>";
						$obj['x'] = $this->GRAPHIC->middle($pointa_x,$pointb_x) - 10;
						$obj['y'] = $this->GRAPHIC->middle($pointa_y,$pointb_y) - 10;
						
						$obj['icon'] = '20x20.gif';
					} else {
						// add this object to the list of the components which will have to be movable, if it's not a line or a textbox
						if(!isset($obj['line_type']) && $obj['type'] != 'textbox') {
							$this->moveable .= "\"box_".$obj['type']."_".$obj['id']."\",";
						}
					}
					
					$obj = $this->fixIcon($obj);
					$ret = array_merge($ret,$this->parseIcon($obj));
					
					if(isset($obj['label_show']) && $obj['label_show'] == '1') {
						$ret[] = $this->parseLabel($obj);
					}
				break;	
			}
		}
		if (DEBUG&&DEBUGLEVEL&1) debug('End method WuiMap::parseObjects(): Array(HTML)');
		return $ret;
	}
	
	/**
	 * Adds paths to the icon
	 *
	 * @param	Array	$obj	Array with object informations
	 * @return	Array	Array with object informations
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	function fixIcon(&$obj) {
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method WuiMap::fixIcon()');
		$ret = parent::fixIcon($this->getIconPaths($obj));
		if (DEBUG&&DEBUGLEVEL&1) debug('End method WuiMap::fixIcon(): Array(...)');
		return $ret;
	}
	
	/**
	 * Parses the HTML-Code of an icon
	 *
	 * @param	Array	$obj	Array with object informations
	 * @author Lars Michelsen <lars@vertical-visions.de>
	 */
	function parseIcon(&$obj) {
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method WuiMap::parseIcon()');
		$ret = Array();
				
		if($obj['type'] == 'service') {
			$name = 'host_name';
		} else {
			$name = $obj['type'] . '_name';
		}
		
		$ret[] = "<div id=\"box_".$obj['type']."_".$obj['id']."\" class=\"icon\" style=\"left:".$obj['x']."px; top:".$obj['y']."px;z-index:".$obj['z']."\">";
		$ret[] = "\t\t<img src=\"".$obj['htmlPath'].$obj['icon']."\" alt=\"".$obj['type']."_".$obj['id']."\" ".$this->infoBox($obj).">";
		$ret[] = "</div>";
		
		if (DEBUG&&DEBUGLEVEL&1) debug('End method WuiMap::fixIcon(): Array(HTML)');
		return $ret;
	}
	
	/**
	 * Gets all objects of the map
	 *
	 * @param	Boolean	$mergeWithGlobals	Merge with globals
	 * @return	Array	Array of Objects of this map
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
     */
	function getMapObjects($mergeWithGlobals=1) {
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method GlobalMap::getMapObjects('.$mergeWithGlobals.')');
		$objects = Array();
		
		$objects = array_merge($objects,$this->getObjectsOfType('map',$mergeWithGlobals));
		$objects = array_merge($objects,$this->getObjectsOfType('host',$mergeWithGlobals));
		$objects = array_merge($objects,$this->getObjectsOfType('service',$mergeWithGlobals));
		$objects = array_merge($objects,$this->getObjectsOfType('hostgroup',$mergeWithGlobals));
		$objects = array_merge($objects,$this->getObjectsOfType('servicegroup',$mergeWithGlobals));
		$objects = array_merge($objects,$this->getObjectsOfType('textbox',$mergeWithGlobals));
		$objects = array_merge($objects,$this->getObjectsOfType('shape',$mergeWithGlobals));
		
		if (DEBUG&&DEBUGLEVEL&1) debug('End method GlobalMap::getMapObjects(): Array(...)');
		return $objects;
	}
	
	/**
	 * Gets all objects of the defined type from a map and return an array with states
	 *
	 * @param	String	$type				Type of objects
	 * @param	Boolean	$mergeWithGlobals	Merge with globals
	 * @return	Array	Array of Objects of this type on the map
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
     */
	function getObjectsOfType($type,$mergeWithGlobals=1) {
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method GlobalMap::getObjectsOfType('.$type.','.$mergeWithGlobals.')');
		// object array
		$objects = Array();
		
		// Default object state
		if($type == 'host' || $type == 'hostgroup') {
			$objState = Array('state'=>'UP','stateOutput'=>'Default State');
		} else {
			$objState = Array('state'=>'OK','stateOutput'=>'Default State');
		}
		
		if(is_array($objs = $this->MAPCFG->getDefinitions($type))){
			foreach($objs AS $index => $obj) {
				if (DEBUG&&DEBUGLEVEL&2) debug('Start object of type: '.$type);
				// workaround
				$obj['id'] = $index;
				
				if($mergeWithGlobals) {
					// merge with "global" settings
					foreach($this->MAPCFG->validConfig[$type] AS $key => $values) {
						if((!isset($obj[$key]) || $obj[$key] == '') && isset($values['default'])) {
							$obj[$key] = $values['default'];
						}
					}
				}
				
				// add default state to the object
				$obj = array_merge($obj,$objState);
				
				if($obj['type'] != 'textbox' && $obj['type'] != 'shape') {
					$obj['icon'] = $this->getIcon($obj);
				}
				
				// add object to array of objects
				$objects[] = $obj;
				if (DEBUG&&DEBUGLEVEL&2) debug('End object of type: '.$type);
			}
			
			if (DEBUG&&DEBUGLEVEL&1) debug('End method GlobalMap::getObjectsOfType(): Array(...)');
			return $objects;
		}
	}
	
	/**
	 * Create a Comment-Textbox
	 *
	 * @param	Array	$obj	Array with object informations
	 * @author Lars Michelsen <lars@vertical-visions.de>
	 */
	function textBox(&$obj) {
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method WuiMap::textBox(&$obj)');
		$ret = Array();
		
		if(isset($obj['w'])) {
			$obj['w'] = $obj['w'].'px';
		} else {
			$obj['w'] = 'auto';
		}
		
		$ret[] = "<div class=\"".$obj['class']."\" style=\"left: ".$obj['x']."px; top: ".$obj['y']."px; width: ".$obj['w']."; overflow: visible;\">";	
		$ret[] = "\t<span>".$obj['text']."</span>";
		$ret[] = "</div>";
		
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method WuiMap::textBox(): Array(HTML)');
		return $ret;	
	}
	
	/**
	 * Creates a Javascript-Box with information.
	 *
	 * @param	Array	$obj	Array with object informations
	 * @author Lars Michelsen <lars@vertical-visions.de>
     */
	function infoBox(&$obj) {
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method WuiMap::infoBox(&$obj)');
		if($obj['type'] == 'service') {
			$name = 'host_name';
		} else {
			$name = $obj['type'] . '_name';
		}
		
		unset($obj['stateOutput']);
		unset($obj['state']);
		
		// add all the object's defined properties to the tooltip body
		$tooltipText = '<table class=\\\'infobox\\\'>';
		$configuredText = '';
		$defaultText = '';
		
		// Get configured/inherited variables
		foreach($this->MAPCFG->validConfig[$obj['type']] AS $key => $values) {
			$getValue = $this->MAPCFG->getValue($obj['type'],$obj['id'],$key,TRUE);
			if($key != 'type' && isset($getValue) && $getValue != '') {
				$configuredText .= '<tr><td>'.$key.'</td><td>'.$getValue.'</td></tr>';
			} elseif(isset($values['default'])) {
				$defaultText .= '<tr class=\\\'inherited\\\'><td>'.$key.'</td><td>'.$values['default'].'</td></tr>';
			}
		}
		
		// Print configured settings
		$tooltipText .= '<tr><th colspan=\\\'2\\\'>'.$this->LANG->getLabel('configured').'</th></tr>'.$configuredText;
		// Print inherited settings
		$tooltipText .= '<tr class=\\\'inherited\\\'><th colspan=\\\'2\\\'>'.$this->LANG->getLabel('inherited').'</th></tr>'.$defaultText;
		
		// lines and textboxes have one more link in the tooltip: "size/position"	
		if(isset($obj['line_type']) || $obj['type']=='textbox') {
			$positionSizeText = "<a href=javascript:objid=".$obj['id'].";get_click(\'".$obj['type']."\',2,\'modify\');>".$this->LANG->getLabel('positionSize')."</a>";			
		} else {
			$positionSizeText = '';	
		}
		
		$tooltipText .= "<tr><th><a href=\'./index.php?page=addmodify&amp;action=modify&amp;map=".$this->MAPCFG->getName()."&amp;type=".$obj['type']."&amp;id=".$obj['id']."\' onclick=\'open_window(href); return false;\'>".$this->LANG->getLabel('change')."</a>&nbsp;".$positionSizeText."</th>";
		$tooltipText .= "<th><a href=\'./form_handler.php?myaction=delete&amp;map=".$this->MAPCFG->getName()."&amp;type=".$obj['type']."&amp;id=".$obj['id']."\' onClick=\'return confirm_object_deletion();return false;\'>".$this->LANG->getLabel('delete')."</a></th></tr>";
		
		$tooltipText.='</table>';
		
		$info = "onmouseover=\"this.T_DELAY=1000;this.T_STICKY=true;this.T_OFFSETX=6;this.T_OFFSETY=6;this.T_WIDTH=200;this.T_FONTCOLOR='#000000';this.T_BORDERCOLOR='#000000';this.T_BGCOLOR='#FFFFFF';this.T_STATIC=true;this.T_TITLE='<b>".$this->LANG->getLabel($obj['type'])."</b>';return escape('".$tooltipText."');\"";
		
		if (DEBUG&&DEBUGLEVEL&1) debug('End method WuiMap::infoBox(): HTML');
		return $info;
	}
	
	/**
	 * Gets all defined maps
	 *
	 * @return	Array maps
	 * @author Lars Michelsen <lars@vertical-visions.de>
     */
	function getMaps() {
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method WuiMap::getMaps()');
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
		
		if (DEBUG&&DEBUGLEVEL&1) debug('End method WuiMap::getMaps(): Array(...)');
		return $files;
	}
		
	/**
	 * Parses the needed language strings to javascript
	 *
	 * @return	Array Html
	 * @author Lars Michelsen <lars@vertical-visions.de>
	 */
	function getJsLang() {
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method WuiMap::getJsLang()');
		$ret = Array();
		$ret[] = 'var langMenu = Array();';
		$ret[] = 'langMenu[\'save\'] = \''.$this->LANG->getLabel('save').'\';';
		$ret[] = 'langMenu[\'restore\'] = \''.$this->LANG->getLabel('restore').'\';';
		$ret[] = 'langMenu[\'properties\'] = \''.$this->LANG->getLabel('properties').'\';';
		$ret[] = 'langMenu[\'addObject\'] = \''.$this->LANG->getLabel('addObject').'\';';
		$ret[] = 'langMenu[\'nagVisConfig\'] = \''.$this->LANG->getLabel('nagVisConfig').'\';';
		$ret[] = 'langMenu[\'help\'] = \''.$this->LANG->getLabel('help').'\';';
		$ret[] = 'langMenu[\'open\'] = \''.$this->LANG->getLabel('open').'\';';
		$ret[] = 'langMenu[\'openInNagVis\'] = \''.$this->LANG->getLabel('openInNagVis').'\';';
		$ret[] = 'langMenu[\'manageMaps\'] = \''.$this->LANG->getLabel('manageMaps').'\';';
		$ret[] = 'langMenu[\'manageBackends\'] = \''.$this->LANG->getLabel('manageBackends').'\';';
		$ret[] = 'langMenu[\'manageBackgrounds\'] = \''.$this->LANG->getLabel('manageBackgrounds').'\';';
		$ret[] = 'langMenu[\'manageShapes\'] = \''.$this->LANG->getLabel('manageShapes').'\';';
		$ret[] = 'langMenu[\'icon\'] = \''.$this->LANG->getLabel('icon').'\';';
		$ret[] = 'langMenu[\'line\'] = \''.$this->LANG->getLabel('line').'\';';
		$ret[] = 'langMenu[\'special\'] = \''.$this->LANG->getLabel('special').'\';';
		$ret[] = 'langMenu[\'host\'] = \''.$this->LANG->getLabel('host').'\';';
		$ret[] = 'langMenu[\'service\'] = \''.$this->LANG->getLabel('service').'\';';
		$ret[] = 'langMenu[\'hostgroup\'] = \''.$this->LANG->getLabel('hostgroup').'\';';
		$ret[] = 'langMenu[\'servicegroup\'] = \''.$this->LANG->getLabel('servicegroup').'\';';
		$ret[] = 'langMenu[\'map\'] = \''.$this->LANG->getLabel('map').'\';';
		$ret[] = 'langMenu[\'textbox\'] = \''.$this->LANG->getLabel('textbox').'\';';
		$ret[] = 'langMenu[\'shape\'] = \''.$this->LANG->getLabel('shape').'\';';
		$ret[] = 'var lang = Array();';
		$ret[] = 'lang[\'clickMapToSetPoints\'] = \''.$this->LANG->getMessageText('clickMapToSetPoints').'\';';
		$ret[] = 'lang[\'confirmDelete\'] = \''.$this->LANG->getMessageText('confirmDelete').'\';';
		$ret[] = 'lang[\'confirmRestore\'] = \''.$this->LANG->getMessageText('confirmRestore').'\';';
		
		if (DEBUG&&DEBUGLEVEL&1) debug('End method WuiMap::getJsLang(): Array(HTML)');
		return $ret;	
	}
	
	/**
	 * Parses the invisible forms and JS arrays needed in WUI
	 *
	 * @return	Array Html
	 * @author Lars Michelsen <lars@vertical-visions.de>
     */
	function parseInvisible() {
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method WuiMap::parseInvisible()');
		$arr = Array();
		
		##################################
		# important form. it makes possible to communicate the coordinates of all the objects to the server 
		# Idea : when one drags and drops an object, the wz_dragdrop.js which handles this has been modified to update these hidden fields.
		# At any time, the fields are filled like :
		#    image : 2,5  (the numbers representing $key, which is the line number in the map .cfg file, counting from 0)
		#    valx : 12,165
		#    valy : 41,98
		# this simple example represents 2 objects : obj1 (defined line 3 in the map.cfg file) x=12 y=41
		#                                            obj2 (defined line 6 in the map.cfg file) x=165 y=98
		# When the user clicks on the Save buton, these lists are passed to a bash script executed on the server, which will parse them and treat them.
		# This is how it works to save the maps :)
		$arr[] = '<form id="myvalues" style="display:none;" name="myvalues" action="./form_handler.php?myaction=save" method="post">
			<input type="hidden" name="mapname" value="'.$this->MAPCFG->getName().'" />
			<input type="hidden" name="image" value="" />
			<input type="hidden" name="valx" value="" />
			<input type="hidden" name="valy" value="" />
			<input type="submit" name="submit" value="Save" />
		</form>';
		
		$arr = array_merge($arr,$this->parseJs("
			var mapname = '".$this->MAPCFG->getName()."';
			var username = '".$this->MAINCFG->getRuntimeValue('user')."';
			var autosave = '".$this->MAINCFG->getRuntimeValue('justAdded')."';
			var mapOptions = ".$this->MAINCFG->getRuntimeValue('mapOptions').";
			var backupAvailable = '".file_exists($this->MAINCFG->getValue('paths', 'mapcfg').$this->MAPCFG->getName().".cfg.bak")."';
			
			// build the right-click menu
			initjsDOMenu();
			
			// draw the shapes on the background
			myshape_background.paint();
			"));
		
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method WuiMap::parseInvisible(): Array(HTML)');
		return $arr;
	}
}
