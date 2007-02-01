<?php
/** 
 * Class for printing the map in NagVis Wui
 **/

class WuiMap extends GlobalMap {
	var $MAINCFG;
	var $MAPCFG;
	var $LANG;
	
	var $objects;
	var $moveable;
	var $actId;
	
	function WuiMap(&$MAINCFG,&$MAPCFG,&$LANG) {
		$this->MAINCFG = &$MAINCFG;
		$this->MAPCFG = &$MAPCFG;
		$this->LANG = &$LANG;
		
		// FIXME 2nd MAPCFG is just a dummy
		parent::GlobalMap($MAINCFG,$MAPCFG,$MAPCFG);
		
		$this->loadPermissions();
		$this->objects = $this->getMapObjects(0,1);
	}
	
	function loadPermissions() {
		$mapOptions = '[ ';
		$a = 0;
		foreach($this->getMaps() AS $map) {
			if($a > 0) {
				$mapOptions .= ', ';	
			}
			// FIXME: Config check reports an error
			$MAPCFG1 = new GlobalMapCfg($this->MAINCFG,$map);
			$MAPCFG1->readMapConfig(1);
			$mapOptions .= '{ mapName: "'.$map.'"';
			
			// used image
			$mapOptions .= ', mapImage:"'.$MAPCFG1->getValue('global', '0', 'map_image').'"';
			
			// permited users for writing
			$mapOptions .= ', allowedUsers:[ ';
			$arr = $MAPCFG1->getValue('global', '0', 'allowed_for_config');
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
			
			$mapOptions .= ' }';
			$a++;
		}
		$mapOptions .= ' ]';
		$this->MAINCFG->setRuntimeValue('mapOptions',$mapOptions);
	}
	
	function parseMap() {
		$ret = Array();
		$ret = array_merge($ret,$this->getBackground('img'));
		$ret = array_merge($ret,$this->parseJs(array_merge($this->getJsGraphicObj(),$this->getJsLang())));
		$ret = array_merge($ret,$this->parseObjects());
		$ret = array_merge($ret,$this->parseInvisible());
		$ret = array_merge($ret,$this->makeObjectsMoveable());
		$ret = array_merge($ret,Array("<script type=\"text/javascript\" src=\"./includes/js/wz_tooltip.js\"></script>"));
		
		return $ret;
	}
	
	function getJsGraphicObj() {
		$ret = Array();
		$ret[] = "myshape_background = new jsGraphics('mymap');";
		$ret[] = "myshape_background.setColor('#FF0000');";
		$ret[] = "myshape_background.setStroke(1);\n";
		
		return $ret;
	}
	
	function makeObjectsMoveable() {
		$ret = Array();
		
		if(strlen($this->moveable) != 0) {
			$ret = $this->parseJs("SET_DHTML(TRANSPARENT,CURSOR_HAND,".substr($this->moveable,0,strlen($this->moveable)-1).");\n");
		}
		return $ret;
	}
	
	function parseJs($js) {
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
		
		return $ret;
	}
	
	function parseObjects() {
		$ret = Array();
		
		foreach($this->objects AS $var => $obj) {
			switch($obj['type']) {
				case 'textbox':
					$obj['class'] = "box";
					$obj['icon'] = "20x20.gif";
					
					$ret = array_merge($ret,$this->textBox($obj));
					$obj = $this->fixIconPosition($obj);
					$ret = array_merge($ret,$this->parseIcon($obj));
				break;
				default:
					if(isset($obj['line_type'])) {
						list($pointa_x,$pointb_x) = explode(",", $obj['x']);
						list($pointa_y,$pointb_y) = explode(",", $obj['y']);
						$ret[] = "<script type=\"text/javascript\">myshape_background.drawLine(".$pointa_x.",".$pointa_y.",".$pointb_x.",".$pointb_y.");</script>";
						
						$obj['icon'] = '20x20.gif';
					} else {
						// add this object to the list of the components which will have to be movable, if it's not a line or a textbox
						if(!isset($obj['line_type']) && $obj['type'] != 'textbox') {
							$this->moveable .= "\"box_".$obj['type']."_".$obj['id']."\",";
						}
					}
					
					$obj = $this->fixIconPosition($obj);
					$ret = array_merge($ret,$this->parseIcon($obj));
				break;	
			}
		}
		return $ret;
	}
	
	/**
	 * Adds paths to the icon
	 *
	 * @param	Array	$obj	Array with object informations
	 * @return	Array	Array with object informations
	 * @author	Lars Michelsen <larsi@nagios-wiki.de>
	 */
	function fixIconPosition($obj) {
		return parent::fixIconPosition($this->getIconPaths($obj));
	}
	
	/**
	* Parses the HTML-Code of an icon
	*
	* @param Array $obj
	* @author Lars Michelsen <larsi@nagios-wiki.de>
	*/
	function parseIcon($obj) {
		$ret = Array();
				
		if($obj['type'] == 'service') {
			$name = 'host_name';
		} else {
			$name = $obj['type'] . '_name';
		}
		
		$ret[] = "<div id=\"box_".$obj['type']."_".$obj['id']."\" class=\"icon\" style=\"left:".$obj['x']."px; top:".$obj['y']."px;z-index:".$obj['z']."\">";
		$ret[] = "\t\t<img src=\"".$obj['htmlPath'].$obj['icon']."\" alt=\"".$obj['type']."_".$obj['id']."\" ".$this->infoBox($obj).">";
		$ret[] = "</div>";
		
		return $ret;
	}
	
	/**
	* Create a Comment-Textbox
	*
	* @param Array $obj
	* @author Lars Michelsen <larsi@nagios-wiki.de>
	*/
	function textBox($obj) {
		$ret = Array();
		
		if(isset($obj['w'])) {
			$obj['w'] = $obj['w'].'px';
		} else {
			$obj['w'] = 'auto';
		}
		
		$ret[] = "<div class=\"".$obj['class']."\" style=\"left: ".$obj['x']."px; top: ".$obj['y']."px; width: ".$obj['w']."; overflow: visible;\">";	
		$ret[] = "\t<span>".$obj['text']."</span>";
		$ret[] = "</div>";
		
		return $ret;	
	}
	
	/**
	* Creates a Javascript-Box with information.
	*
	* @param Array $obj
	*
	* @author Michael Luebben <michael_luebben@web.de>
	* @author Lars Michelsen <larsi@nagios-wiki.de>
	* FIXME: optimize
    */
	function infoBox($obj) {
		if($obj['type'] == 'service') {
			$name = 'host_name';
		} else {
			$name = $obj['type'] . '_name';
		}
		
		unset($obj['stateOutput']);
		unset($obj['state']);
		
		# we add all the object's defined properties to the tooltip body
		$tooltipText="";
		
		foreach($obj AS $var => $val) {
			if(!preg_match('/^(|id|icon|type|x|y|z|path|htmlPath)$/i',$var) && $val != '') {
				$tooltipText .= $var.": ".$val."<br>";
			}
		}
		
		$tooltipText .= "<br><a href=\'./addmodify.php?action=modify&amp;map=".$this->MAPCFG->getName()."&amp;type=".$obj['type']."&amp;id=".$obj['id']."\' onclick=\'fenetre(href); return false;\'>".$this->LANG->getLabel('change')."</a>";
		$tooltipText .= "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";	
		$tooltipText .= "<a href=\'./wui.function.inc.php?myaction=delete&amp;map=".$this->MAPCFG->getName()."&amp;type=".$obj['type']."&amp;id=".$obj['id']."\' onClick=\'return confirm_object_deletion();return false;\'>".$this->LANG->getLabel('delete')."</a>";
		
		# lines and textboxes have one more link in the tooltip : "size/position"	
		if(isset($obj['line_type']) || $obj['type']=='textbox') {
			$tooltipText .= "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
			$tooltipText .= "<a href=javascript:objid=".$obj['id'].";get_click(\'".$obj['type']."\',2,\'modify\');>".$this->LANG->getLabel('positionSize')."</a>";			
		}
		
		$info = "onmouseover=\"this.T_DELAY=1000;this.T_STICKY=true;this.T_OFFSETX=6;this.T_OFFSETY=6;this.T_WIDTH=200;this.T_FONTCOLOR='#000000';this.T_BORDERCOLOR='#000000';this.T_BGCOLOR='#FFFFFF';this.T_STATIC=true;this.T_TITLE='<b>".$this->LANG->getLabel($obj['type'])."</b>';return escape('".$tooltipText."');\"";
		
		return $info;
	}
	
	function getMaps() {
		$files = Array();
		
		if ($handle = opendir($this->MAINCFG->getValue('paths', 'mapcfg'))) {
 			while (false !== ($file = readdir($handle))) {
				if ($file != "." && $file != ".." && substr($file,strlen($file)-4,4) == ".cfg") {
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
	* Parses the needed language strings to javascript
	*
	* @return	Array Html
	* @author Lars Michelsen <larsi@nagios-wiki.de>
	*/
	function getJsLang() {
		$ret = Array();
		$ret[] = 'var langMenu = Array();';
		$ret[] = 'langMenu["save"] = "'.$this->LANG->getLabel('save').'";';
		$ret[] = 'langMenu["restore"] = "'.$this->LANG->getLabel('restore').'";';
		$ret[] = 'langMenu["properties"] = "'.$this->LANG->getLabel('properties').'";';
		$ret[] = 'langMenu["addObject"] = "'.$this->LANG->getLabel('addObject').'";';
		$ret[] = 'langMenu["nagVisConfig"] = "'.$this->LANG->getLabel('nagVisConfig').'";';
		$ret[] = 'langMenu["help"] = "'.$this->LANG->getLabel('help').'";';
		$ret[] = 'langMenu["open"] = "'.$this->LANG->getLabel('open').'";';
		$ret[] = 'langMenu["manageMaps"] = "'.$this->LANG->getLabel('manageMaps').'";';
		$ret[] = 'langMenu["manageBackends"] = "'.$this->LANG->getLabel('manageBackends').'";';
		$ret[] = 'langMenu["icon"] = "'.$this->LANG->getLabel('icon').'";';
		$ret[] = 'langMenu["line"] = "'.$this->LANG->getLabel('line').'";';
		$ret[] = 'langMenu["host"] = "'.$this->LANG->getLabel('host').'";';
		$ret[] = 'langMenu["service"] = "'.$this->LANG->getLabel('service').'";';
		$ret[] = 'langMenu["hostgroup"] = "'.$this->LANG->getLabel('hostgroup').'";';
		$ret[] = 'langMenu["servicegroup"] = "'.$this->LANG->getLabel('servicegroup').'";';
		$ret[] = 'langMenu["map"] = "'.$this->LANG->getLabel('map').'";';
		$ret[] = 'langMenu["textbox"] = "'.$this->LANG->getLabel('textbox').'";';
		$ret[] = 'langMenu["shape"] = "'.$this->LANG->getLabel('shape').'";';
		$ret[] = 'var lang = Array();';
		$ret[] = 'lang["clickMapToSetPoints"] = "'.$this->LANG->getMessageText('clickMapToSetPoints').'";';
		$ret[] = 'lang["confirmDelete"] = "'.$this->LANG->getMessageText('confirmDelete').'";';
		$ret[] = 'lang["confirmRestore"] = "'.$this->LANG->getMessageText('confirmRestore').'";';
		
		return $ret;	
	}
	
	/**
	* Parses the invisible forms
	*
	* @author FIXME
	* FIXME: much to optimize
    */
	function parseInvisible() {
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
		$arr[] = '<form id="myvalues" style="display:none;" name="myvalues" action="./wui.function.inc.php?myaction=save" method="post">
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
			
		return $arr;
	}
}