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
					if(isset($obj['view_type']) && $obj['view_type'] == 'line') {
						list($pointa_x,$pointb_x) = explode(",", $obj['x']);
						list($pointa_y,$pointb_y) = explode(",", $obj['y']);
						$ret .= "<script type=\"text/javascript\">myshape_background.drawLine(".$pointa_x.",".$pointa_y.",".$pointb_x.",".$pointb_y.");</script>";
						
						$obj['x'] = round(($pointa_x+($pointb_x-$pointa_x)/2) - 10);
						$obj['y'] = round(($pointa_y+($pointb_y-$pointa_y)/2) - 10);
						$obj['icon'] = '20x20.gif';
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
					$ret .= $this->parseIcon($obj);
					
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
	function parseIcon(&$obj) {
		$ret = '';
				
		if($obj['type'] == 'service') {
			$name = 'host_name';
		} else {
			$name = $obj['type'] . '_name';
		}
		
		$ret .= "<div id=\"box_".$obj['type']."_".$obj['id']."\" class=\"icon\" style=\"left:".$obj['x']."px; top:".$obj['y']."px;z-index:".$obj['z']."\">";
		$ret .= "\t\t<img src=\"".$obj['htmlPath'].$obj['icon'].$obj['iconParams']."\" alt=\"".$obj['type']."_".$obj['id']."\" ".$this->infoBox($obj).">";
		$ret .= "</div>";
		
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
		
		$ret  = '<div '.$id.' class="object_label" style="background:'.$obj['label_background'].';border-color:'.$obj['label_border'].';left:'.$obj['label_x'].'px;top:'.$obj['label_y'].'px;width:'.$obj['label_width'].';z-index:'.($obj['z']+1).';overflow:visible;">';
		$ret .= '<span>'.$obj['label_text'].'</span>';
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
				
				if($obj['type'] != 'textbox' && $obj['type'] != 'shape') {
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
		
		$ret .= "<div id=\"".$id."\" class=\"box resizeMe\" style=\"border-color:".$sBorderColor.";background-color:".$sBgColor.";left:".$obj['x']."px;top:".$obj['y']."px;z-index:".$obj['z'].";width:".$obj['w'].";height:".$obj['h'].";overflow:visible;\" ".$this->infoBox($obj).">";	
		$ret .= "\t<span>".$obj['text']."</span>";
		$ret .= "</div>";
		$ret .= $this->parseJs('var obj = document; addEvent(obj, "mousedown", doDown); addEvent(obj, "mouseup", doUp); addEvent(obj, "mousemove", doMove); obj = null;');
		
		return $ret;	
	}
	
	/**
	 * Creates a Javascript-Box with information.
	 *
	 * @param	Array	$obj	Array with object informations
	 * @author Lars Michelsen <lars@vertical-visions.de>
     */
	function infoBox(&$obj) {
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
		foreach($this->MAPCFG->getValidTypeKeys($obj['type']) AS $key) {
			$bGlobal = FALSE;
			$value = $this->MAPCFG->getValue($obj['type'], $obj['id'], $key, TRUE);
			
			// Get global value when nothing set
			if($value == FALSE) {
				$bGlobal = TRUE;
				$value = $this->MAPCFG->getValue($obj['type'], $obj['id'], $key, FALSE);
			}
			
			// Cleanup some bad signs
			$value = str_replace('\"','&quot;', $value);
			$value = str_replace('"','&quot;', $value);
			
			if($bGlobal) {
				$defaultText .= '<tr class=\\\'inherited\\\'><td>'.$key.'</td><td>'.$value.'</td></tr>';
			} else {
				$configuredText .= '<tr><td>'.$key.'</td><td>'.$value.'</td></tr>';
			}
		}
		
		/*
		 * Add action links
		 */
		
		$tooltipText .= "<tr><th colspan=\'2\' style=\'height:34px;\'>";
		$tooltipText .= "<ul class=\'nav\'>";
		
		// Edit link
		$tooltipText .= "<li><a style=\'background-image:url(".$this->CORE->getMainCfg()->getValue('paths','htmlbase')."/frontend/wui/images/internal/modify.png)\'"
		  ." href=# onclick=popupWindow(\'".$this->CORE->getLang()->getText('change')."\',"
			."getSyncRequest(\'./ajax_handler.php?action=getFormContents&form=addmodify&do=modify&map=".$this->MAPCFG->getName()."&type=".$obj['type']."&id=".$obj['id']."\',true,false));>"
			."<span>".$this->CORE->getLang()->getText('change')."</span></a></li>";
		
		// Position/Size link on textboxes/lines
		//$tooltipText .= "&nbsp;".$positionSizeText;
		if(isset($obj['view_type']) && $obj['view_type'] == 'line') {
			$tooltipText .= "<li><a style=\'background-image:url(".$this->CORE->getMainCfg()->getValue('paths','htmlbase')."/frontend/wui/images/internal/move.png)\'"
						." href=javascript:objid=".$obj['id'].";get_click(\'".$obj['type']."\',2,\'modify\');>"
						."<span>".$this->CORE->getLang()->getText('positionSize')."</span></a></li>";			
		}
		
		// Delete link
		$tooltipText .= "<li><a style=\'background-image:url(".$this->CORE->getMainCfg()->getValue('paths','htmlbase')."/frontend/wui/images/internal/delete.png)\'"
		  ." href=\'#\' id=\'delete_".$obj['type']."_".$obj['id']."\'"
			." onClick=\'return deleteMapObject(this);return false;\'>"
		  ."<span>".$this->CORE->getLang()->getText('delete')."</span></a></li>";
		
		$tooltipText .= "</ul>";
		$tooltipText .= "</th></tr>";
		
		
		// Print configured settings
		$tooltipText .= '<tr><th colspan=\\\'2\\\'>'.$this->CORE->getLang()->getText('configured').'</th></tr>'.$configuredText;
		
		// Print inherited settings
		$tooltipText .= '<tr class=\\\'inherited\\\'><th colspan=\\\'2\\\'>'.$this->CORE->getLang()->getText('inherited').'</th></tr>'.$defaultText;
		
		$tooltipText .= '</table>';
		
		$info = "onmouseout=\"UnTip();\""
		       ."onmouseover=\"Tip('".$tooltipText."',"
		       ."DELAY,200,"
		       ."STICKY,true,"
		       ."OFFSETX,6,"
		       ."OFFSETY,6,"
		       ."CLICKCLOSE,true,"
		       ."BORDERWIDTH,0,"
		       ."BGCOLOR,'#FFFFFF');\"";
		
		return $info;
	}
}
