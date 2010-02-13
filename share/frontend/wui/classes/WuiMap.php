<?php
/*****************************************************************************
 *
 * WuiMap.php - Class for printing the maps in WUI
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
class WuiMap extends GlobalMap {
	var $CORE;
	var $MAPCFG;
	
	var $objects;
	var $moveable;
	var $actId;
	
	/**
	 * Class Constructor
	 *
	 * @param 	$CORE WuiMainCfg
	 * @param 	$MAPCFG  GlobalMapCfg
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	function WuiMap(WuiCore $CORE, $MAPCFG) {
		$this->CORE = $CORE;
		$this->MAPCFG = $MAPCFG;
		
		parent::__construct($CORE, $MAPCFG);
		
		$this->objects = $this->getMapObjects(1);
	}
	
	/**
	 * Parses the validation regex of the map configuration values to javascript
	 *
	 * @return  String    JSON encoded array
	 * @author  Lars Michelsen <lars@vertical-visions.de>
	 */
	public function getJsValidMapConfig() {
		return json_encode($this->MAPCFG->getValidConfig());
	}
	
	/**
	 * Makes defined objecs moveable
	 *
	 * @return	String html
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	function getMoveableObjects() {
		$ret = '';
		
		if(strlen($this->moveable) != 0) {
			$ret = substr($this->moveable, 0, strlen($this->moveable) - 1);
		}
		
		return $ret;
	}
	
	/**
	 * Parses given Js code
	 *
	 * @param	String	$js	Javascript code to parse
	 * @return	String 	Html
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	function parseJs($js) {
		$ret = '';
		
		$ret .= "<script type=\"text/javascript\">";
		if(is_array($js)) {
			$ret .= implode("\n",$js);
		} else {
			$ret .= $js;
		}
		$ret .= "</script>";
		
		return $ret;
	}
	
	function middle($x1,$x2) {
		$ret = ($x1+($x2-$x1)/2);
		return $ret;
	}
	
	/**
	 * Parses all objects on the map
	 *
	 * @return	String 	Html
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	function parseObjects() {
		$ret = '';
		
		foreach($this->objects AS $var => $obj) {
			switch($obj['type']) {
				case 'textbox':
					$ret .= $this->textBox($obj);
					
					$this->moveable .= "\"box_".$obj['type']."_".$obj['id']."\",";
				break;
				default:
					$objCode = '';
					
					if($obj['type'] == 'line' || (isset($obj['view_type']) && $obj['view_type'] == 'line')) {
						$objCode .= $this->parseLine($obj);
					} else {
						$this->moveable .= "\"box_".$obj['type']."_".$obj['id']."\",";
					}
					
					if(isset($obj['view_type']) && $obj['view_type'] == 'gadget') {
						$sDelim = '&';
						
						// If there is no ? use the ? as start for the params list
						if(strpos($obj['gadget_url'], '?') === false) {
							$sDelim = '?';
						}

						$sGadgetOpts = '';
						if(isset($obj['gadget_opts']) && $obj['gadget_opts'] != '') {
							$sGadgetOpts = '&opts=' . urlencode($obj['gadget_opts']);
						}
						
						$obj['iconParams'] = $sDelim . 'name1=dummyHost&name2=dummyService&state=OK&stateType=HARD&conf=1&scale=' . $obj['gadget_scale'] . $sGadgetOpts;
					} else {
						$obj['iconParams'] = '';
					}
					
					$obj = $this->fixIcon($obj);
					
					$objCode .= $this->parseIcon($obj);
					$ret .= $this->parseContainer($obj, $objCode).$this->parseContextMenu($obj);
					
					if(isset($obj['label_show']) && $obj['label_show'] == '1') {
						$ret .= $this->parseLabel($obj);
					}
				break;	
			}
		}
		return $ret;
	}
	
	/**
	 * Create a position for a icon on the map
	 *
	 * @param	Array	Array with object properties
	 * @return	Array	Array with object properties
	 * @author	Michael Luebben <michael_luebben@web.de>
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	function fixIcon(&$obj) {
		$obj = $this->getIconPaths($obj);
		
		if($obj['type'] == 'shape' && preg_match('/^\[(.*)\]$/',$obj['icon'],$match) > 0) {
			$obj['icon'] = $match[1];
		} elseif(isset($obj['view_type']) && $obj['view_type'] == 'gadget') {
			if(preg_match('/^\[(.*)\]$/', $obj['gadget_url'], $match) > 0) {
				$obj['icon'] = $match[1];
			} else {
				$obj['icon'] = $obj['gadget_url'];
				
				if(!isset($obj['path']) || $obj['path'] == '') {
					$imgPath = $obj['icon'];
				} else {
					$imgPath = $obj['path'].$obj['icon'];
				}
			}
		} else {
			if(!isset($obj['path']) || $obj['path'] == '') {
				$imgPath = $obj['icon'];
			} else {
				$imgPath = $obj['path'].$obj['icon'];
			}
			
			if(!file_exists($imgPath)) {
				new GlobalMessage('WARNING', $this->CORE->getLang()->getText('iconNotExists','IMGPATH~'.$imgPath));
				
				$obj['path'] = $this->CORE->getMainCfg()->getValue('paths', 'icon');
				$obj['htmlPath'] = $this->CORE->getMainCfg()->getValue('paths', 'htmlicon');
				$obj['icon'] = '20x20.gif';
			}
		}
		
		return $obj;
	}
	
	/**
	 * Gets the paths to the icon
	 *
	 * @param	Array	$obj	Array with object informations
	 * @return	Array	Array with object informations
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	function getIconPaths(&$obj) {
		if($obj['type'] == 'shape') {
			if(preg_match('/^\[(.*)\]$/',$obj['icon'],$match) > 0) {
				$obj['path'] = '';
				$obj['htmlPath'] = '';
			} else {
				$obj['path'] = $this->CORE->getMainCfg()->getValue('paths', 'shape');
				$obj['htmlPath'] = $this->CORE->getMainCfg()->getValue('paths', 'htmlshape');
			}
		} elseif(isset($obj['view_type']) && $obj['view_type'] == 'gadget') {
			if(preg_match('/^\[(.*)\]$/', $obj['gadget_url'], $match) > 0) {
				$obj['path'] = '';
				$obj['htmlPath'] = '';
			} else {
				$obj['path'] = $this->CORE->getMainCfg()->getValue('paths', 'gadget');
				$obj['htmlPath'] = $this->CORE->getMainCfg()->getValue('paths', 'htmlgadgets');
			}
		} else {
			$obj['path'] = $this->CORE->getMainCfg()->getValue('paths', 'icon');
			$obj['htmlPath'] = $this->CORE->getMainCfg()->getValue('paths', 'htmlicon');
		}
		return $obj;
	}
	
	/**
	 * Parses the HTML-Code of an icon
	 *
	 * @param	Array	$obj	Array with object informations
	 * @return	String HTML Code
	 * @author Lars Michelsen <lars@vertical-visions.de>
	 */
	function parseIcon($obj) {
		// Add 20x20 icon in the middle of the line in case of line objects
		if($obj['type'] == 'line' || (isset($obj['view_type']) && $obj['view_type'] == 'line')) {
			list($x1,$x2) = explode(",", $obj['x']);
			list($y1,$y2) = explode(",", $obj['y']);
			
			if($x1 > $x2) {
				$x = $x2;
			} elseif($x1 < $x2) {
				$x = $x1;
			} else {
				$x = $x1;
			}
			
			if($y1 > $y2) {
				$y = $y2;
			} elseif($y1 < $y2) {
				$y = $y1;
			} else {
				$y = $y1;
			}
		
			$x = round(($x1+($x2-$x1)/2) - 10) - $x;
			$y = round(($y1+($y2-$y1)/2) - 10) - $y;
			$style = 'style="position:absolute;left:'.$x.'px;top:'.$y.'px"';
			
			$obj['icon'] = '20x20.gif';
		} else {
			$x = $obj['x'];
			$y = $obj['y'];
			$style = '';
		}
		
		return "<img id=\"icon_".$obj['type']."_".$obj['id']."\" src=\"".$obj['htmlPath'].$obj['icon'].$obj['iconParams']."\" ".$style." alt=\"".$obj['type']."_".$obj['id']."\" onmousedown=\"contextMouseDown(event);\" oncontextmenu=\"return contextShow(event);\" />";
	}
	
	/**
	 * Parses the HTML-Code of a line
	 *
	 * @param	Array	$obj	Array with object informations
	 * @return	String HTML Code
	 * @author Lars Michelsen <lars@vertical-visions.de>
	 */
	function parseLine($obj) {
		$ret = '';
		
		$lineId = "line_".$obj['type']."_".$obj['id'];
		
		list($x1,$x2) = explode(",", $obj['x']);
		list($y1,$y2) = explode(",", $obj['y']);
		
		if($x1 > $x2) {
			$x = $x2;
		} elseif($x1 < $x2) {
			$x = $x1;
		} else {
			$x = $x1;
		}
		
		if($y1 > $y2) {
			$y = $y2;
		} elseif($y1 < $y2) {
			$y = $y1;
		} else {
			$y = $y1;
		}
		
		$ret .= "var ".$lineId." = new jsGraphics('box_".$obj['type']."_".$obj['id']."');";
		$ret .= $lineId.".setColor('#FF0000');";
		$ret .= $lineId.".setStroke(1);";
		$ret .= $lineId.".drawLine(".($x1-$x).",".($y1-$y).",".($x2-$x).",".($y2-$y).");";
		$ret .= $lineId.".paint();";
		
		return $this->parseJs($ret);
	}
	
	/**
	 * Parses the HTML-Code of an object container
	 *
	 * @param  Array   Array with object informations
	 * @param  String  HTML Code
	 * @return String  HTML Code
	 * @author Lars Michelsen <lars@vertical-visions.de>
	 */
	function parseContainer($obj, $html) {
		$ret = '';
		
		// Take the upper left corner for lines
		if($obj['type'] == 'line' || (isset($obj['view_type']) && $obj['view_type'] == 'line')) {
			list($x1,$x2) = explode(",", $obj['x']);
			list($y1,$y2) = explode(",", $obj['y']);
			
			if($x1 > $x2) {
				$x = $x2;
			} elseif($x1 < $x2) {
				$x = $x1;
			} else {
				$x = $x1;
			}
			
			if($y1 > $y2) {
				$y = $y2;
			} elseif($y1 < $y2) {
				$y = $y1;
			} else {
				$y = $y1;
			}
		} else {
			$x = $obj['x'];
			$y = $obj['y'];
		}
		
		$ret .= "\n<div id=\"box_".$obj['type']."_".$obj['id']."\" class=\"icon\" style=\"left:".$x."px;top:".$y."px;z-index:".$obj['z']."\">";
		$ret .= $html;
		$ret .= "</div>\n";
		
		return $ret;
	}
	
	/**
	 * Parses the HTML-Code of a label
	 *
	 * @param	Array	$obj		Array with object informations
	 * @param	String	$base		Array with object informations
	 * @param	Boolean	$link		Add a link to the icon
	 * @param	Boolean	$hoverMenu	Add a hover menu to the icon
	 * @return	String	String with Html Code
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	function parseLabel(&$obj) {
		$relative = 0;
		
		if($obj['type'] == 'service') {
			$name = 'host_name';
		} else {
			$name = $obj['type'] . '_name';
		}
		
		// If there is a presign it should be relative to the objects x/y
		if(preg_match('/^(\+|\-)/',$obj['label_x'])) {
			$obj['label_x'] = $obj['x'] + $obj['label_x'];
			$relative = 1;
		}
		if(preg_match('/^(\+|\-)/',$obj['label_y'])) {
			$obj['label_y'] = $obj['y'] + $obj['label_y'];
			$relative = 1;
		}
		
		// If no x/y coords set, fallback to object x/y
		if(!isset($obj['label_x']) || $obj['label_x'] == '' || $obj['label_x'] == 0) {
			$obj['label_x'] = $obj['x'];
		}
		if(!isset($obj['label_y']) || $obj['label_y'] == '' || $obj['label_y'] == 0) {
			$obj['label_y'] = $obj['y'];
		}
		
		if(isset($obj['label_width']) && $obj['label_width'] != 'auto') {
			$obj['label_width'] .= 'px';	
		}
		
		//
		if($relative == 1) {
			$id = 'id="rel_label_'.$obj['type'].'_'.$obj['id'].'"';
			$this->moveable .= '"rel_label_'.$obj['type'].'_'.$obj['id'].'",';
		} else {
			$id = 'id="abs_label_'.$obj['type'].'_'.$obj['id'].'"';
			$this->moveable .= '"abs_label_'.$obj['type'].'_'.$obj['id'].'",';
		}
		
		// Translate some macros in labels (Only the static ones)
		$label = str_replace('[name]', $obj[$name], $obj['label_text']);
		
		if(isset($obj['alias'])) {
			$label = str_replace('[alias]', $obj['alias'], $label);
		}
		
		if($obj['type'] == 'service') {
			$label = str_replace('[service_description]', $obj['service_description'], $label);
		}
		
		$ret  = '<div '.$id.' class="object_label" style="background:'.$obj['label_background'].';border-color:'.$obj['label_border'].';left:'.$obj['label_x'].'px;top:'.$obj['label_y'].'px;width:'.$obj['label_width'].';z-index:'.($obj['z']+1).';overflow:visible;'.$obj['label_style'].'">';
		$ret .= '<span>'.$label.'</span>';
		$ret .= '</div>';
		
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
		$objects = Array();
		
		$objects = array_merge($objects,$this->getObjectsOfType('map',$mergeWithGlobals));
		$objects = array_merge($objects,$this->getObjectsOfType('host',$mergeWithGlobals));
		$objects = array_merge($objects,$this->getObjectsOfType('service',$mergeWithGlobals));
		$objects = array_merge($objects,$this->getObjectsOfType('hostgroup',$mergeWithGlobals));
		$objects = array_merge($objects,$this->getObjectsOfType('servicegroup',$mergeWithGlobals));
		$objects = array_merge($objects,$this->getObjectsOfType('textbox',$mergeWithGlobals));
		$objects = array_merge($objects,$this->getObjectsOfType('shape',$mergeWithGlobals));
		$objects = array_merge($objects,$this->getObjectsOfType('line',$mergeWithGlobals));
		
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
				// workaround
				$obj['id'] = $index;
				
				if($mergeWithGlobals) {
					// merge with "global" settings
					foreach($this->MAPCFG->getValidTypeKeys($type) AS $key) {
						$obj[$key] = $this->MAPCFG->getValue($type, $index, $key);
					}
				}
				
				// add default state to the object
				$obj = array_merge($obj,$objState);
				
				if($obj['type'] != 'textbox' && $obj['type'] != 'shape' && $obj['type'] != 'line') {
					$obj['icon'] = $this->getIcon($obj);
				}
				
				// add object to array of objects
				$objects[] = $obj;
			}
			
			return $objects;
		}
	}
	
	/**
	 * Searches the icon for an object
	 *
	 * @param	Array	$obj	Array with object properties
	 * @return	String	Name of the icon
	 * @author Michael Luebben <michael_luebben@web.de>
	 * @author Lars Michelsen <lars@vertical-visions.de>
	 */
	function getIcon(&$obj) {
		$fileType = $this->CORE->getIconsetFiletype($obj['iconset']);
		
		switch($obj['type']) {
			case 'service':
			case 'servicegroup':
				$icon = $obj['iconset'].'_ok.'.$fileType;
			break;
			default:
					$icon = $obj['iconset'].'_up.'.$fileType;
			break;
		}
		
		//replaced: if(file_exists($this->CORE->getMainCfg()->getValue('paths', 'icon').$icon)) {
		if(@fclose(@fopen($this->CORE->getMainCfg()->getValue('paths', 'icon').$icon,'r'))) {
			return $icon;
		} else {
			return $obj['iconset'].'_error.'.$fileType;
		}
	}
	
	/**
	 * Create a Comment-Textbox
	 *
	 * @param	String HTML Code
	 * @author Lars Michelsen <lars@vertical-visions.de>
	 */
	function textBox(&$obj) {
		$ret = '';
		
		if(isset($obj['w'])) {
			$obj['w'] = $obj['w'].'px';
		} else {
			$obj['w'] = 'auto';
		}
		
		if(isset($obj['h'])) {
			$obj['h'] = $obj['h'].'px';
		} else {
			$obj['h'] = 'auto';
		}
		
		if(isset($obj['background_color'])) {
			$sBgColor = $obj['background_color'];
		} else {
			$sBgColor = 'transparent';
		}
		
		if(isset($obj['border_color'])) {
			$sBorderColor = $obj['border_color'];
		} else {
			$sBorderColor = 'border_color';
		}
		
		$id = 'box_'.$obj['type'].'_'.$obj['id'];
		
		$ret .= "<div id=\"".$id."\" class=\"box resizeMe\" style=\"border-color:".$sBorderColor.";background-color:".$sBgColor.";left:".$obj['x']."px;top:".$obj['y']."px;z-index:".$obj['z'].";width:".$obj['w'].";height:".$obj['h'].";overflow:visible;".$obj['style']."\" onmousedown=\"contextMouseDown(event);\" oncontextmenu=\"return contextShow(event);\">";
		$ret .= "\t<span>".$obj['text']."</span>";
		$ret .= "</div>";
		$ret .= $this->parseContextMenu($obj);
		$ret .= $this->parseJs('var obj = document; addEvent(obj, "mousedown", doDown); addEvent(obj, "mouseup", doUp); addEvent(obj, "mousemove", doMove); obj = null;');
		
		return $ret;	
	}

	function parseContextMenu($obj) {
		if($obj['type'] === 'textbox') {
			$id = 'box_'.$obj['type'].'_'.$obj['id'].'-context';
		} else {
			$id = 'icon_'.$obj['type'].'_'.$obj['id'].'-context';
		}
		
		$ret = '<div id="'.$id.'" class="context" style="z-index:1000;display:none;position:absolute;overflow:visible;"';
		$ret .= $this->infoBox($obj);
		$ret .= '</div>';

		return $ret;
	}
	
	/**
	 * Creates a Javascript-Box with information.
	 *
	 * @param	Array	$obj	Array with object informations
	 * @author Lars Michelsen <lars@vertical-visions.de>
     */
	function infoBox($obj) {
		if($obj['type'] == 'service') {
			$name = 'host_name';
		} else {
			$name = $obj['type'] . '_name';
		}
		
		unset($obj['stateOutput']);
		unset($obj['state']);
		
		// add all the object's defined properties to the tooltip body
		$tooltipText = '<table class=\'infobox\'>';
		$configuredText = '';
		$defaultText = '';
		
		// Get configured/inherited variables
		foreach($this->MAPCFG->getValidTypeKeys($obj['type']) AS $key) {
			$bGlobal = FALSE;
			$value = $this->MAPCFG->getValue($obj['type'], $obj['id'], $key, TRUE);
			
			// Get global value when nothing set
			if($value == FALSE) {
				$bGlobal = TRUE;
				$value = $this->MAPCFG->getValue($obj['type'], $obj['id'], $key, FALSE);
			}
			
			// Change array to comma separated string
			if(is_array($value)) {
				$value = implode(',',$value);
			}
			
			// Cleanup some bad signs
			$value = str_replace('\"','&quot;', $value);
			$value = str_replace('"','&quot;', $value);
			
			if($bGlobal) {
				$defaultText .= '<tr class=\'inherited\'><td>'.$key.'</td><td>'.$value.'</td></tr>';
			} else {
				$configuredText .= '<tr><td>'.$key.'</td><td>'.$value.'</td></tr>';
			}
		}
		
		/*
		 * Add action links
		 */
		
		$tooltipText .= "<tr><th colspan='2' style='height:34px;'>";
		$tooltipText .= "<ul class='nav'>";
		
		// Edit link
		$tooltipText .= "<li><a style='background-image:url(".$this->CORE->getMainCfg()->getValue('paths','htmlbase')."/frontend/wui/images/modify.png)'"
		  ." href=\"#\" onclick=\"popupWindow('".$this->CORE->getLang()->getText('change')."',"
			."getSyncRequest('".$this->CORE->getMainCfg()->getValue('paths', 'htmlbase')."/server/core/ajax_handler.php?mod=Map&act=addModify&do=modify&map=".$this->MAPCFG->getName()."&type=".$obj['type']."&id=".$obj['id']."',true,false));contextHide();\">"
			."<span>".$this->CORE->getLang()->getText('change')."</span></a></li>";
		
		// Position/Size link on lines
		if($obj['type'] == 'line' || (isset($obj['view_type']) && $obj['view_type'] == 'line')) {
			$tooltipText .= "<li><a style='background-image:url(".$this->CORE->getMainCfg()->getValue('paths','htmlbase')."/frontend/wui/images/move.png)'"
						." href=\"javascript:objid=".$obj['id'].";get_click('".$obj['type']."',2,'modify');\" onclick=\"contextHide();\">"
						."<span>".$this->CORE->getLang()->getText('positionSize')."</span></a></li>";			
		}
		
		// Show clone link only for icons
		if(isset($obj['view_type']) && $obj['view_type'] == 'icon') {
			$tooltipText .= "<li><a style='background-image:url(".$this->CORE->getMainCfg()->getValue('paths','htmlbase')."/frontend/wui/images/clone.png)'"
						." href=\"javascript:objid=".$obj['id'].";get_click('".$obj['type']."', 1, 'clone');\" onclick=\"contextHide();\">"
						."<span>".$this->CORE->getLang()->getText('Clone')."</span></a></li>";
		}
		
		// Delete link
		$tooltipText .= "<li><a style='background-image:url(".$this->CORE->getMainCfg()->getValue('paths','htmlbase')."/frontend/wui/images/delete.png)'"
		  ." href='#' onclick='deleteMapObject(\"box_".$obj['type']."_".$obj['id']."\");contextHide();return false;'>"
		  ."<span>".$this->CORE->getLang()->getText('delete')."</span></a></li>";
		
		$tooltipText .= "</ul>";
		$tooltipText .= "</th></tr>";
		
		
		// Print configured settings
		$tooltipText .= '<tr><th colspan=\'2\'>'.$this->CORE->getLang()->getText('configured').'</th></tr>'.$configuredText;
		
		// Print inherited settings
		$tooltipText .= '<tr class=\'inherited\'><th colspan=\'2\'>'.$this->CORE->getLang()->getText('inherited').'</th></tr>'.$defaultText;
		
		$tooltipText .= '</table>';
		
		return $tooltipText;
	}
}
