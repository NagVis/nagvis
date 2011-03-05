<?php
/*****************************************************************************
 *
 * WuiMap.php - Class for printing the maps in WUI
 *
 * Copyright (c) 2004-2011 NagVis Project (Contact: info@nagvis.org)
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
	protected $CORE;
	public $MAPCFG;
	
	private $objects;
	private $moveable = Array();
	
	/**
	 * Class Constructor
	 *
	 * @param 	$CORE WuiMainCfg
	 * @param 	$MAPCFG  GlobalMapCfg
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	public function WuiMap(WuiCore $CORE, $MAPCFG) {
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
	public function getMoveableObjects() {
		return json_encode($this->moveable);
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
		
		foreach($this->objects AS $objectId => $obj) {
			switch($obj['type']) {
				case 'textbox':
					$ret .= $this->textBox($obj);
					$this->moveable[] =  'box_'.$obj['type'].'_'.$obj['id'];
				break;
				default:
					$objCode = '';
					
					if($obj['type'] == 'line' || (isset($obj['view_type']) && $obj['view_type'] == 'line'))
						$objCode .= $this->parseLine($obj);
					elseif(!$this->isRelativeCoord($obj['x']) && !$this->isRelativeCoord($obj['y']))
						$this->moveable[] =  'box_'.$obj['type'].'_'.$obj['id'];
					
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

		if($obj['type'] == 'line') {
			return $obj;
		}
		
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
					$imgPath = $obj['path'].'/'.$obj['icon'];
				}
			}
		} else {
			if(!isset($obj['path']) || $obj['path'] == '') {
				$imgPath = $obj['icon'];
			} else {
				$imgPath = $obj['path'].'/'.$obj['icon'];
			}
			
			if(!file_exists($imgPath)) {
				new GlobalMessage('WARNING', $this->CORE->getLang()->getText('iconNotExists','IMGPATH~'.$imgPath));
				
				$obj['path']     = '';
				$obj['htmlPath'] = '';
				$obj['icon'] = $this->CORE->getMainCfg()->getPath('html', 'global', 'icons', '20x20.gif');
			}
		}
		
		return $obj;
	}
	
	/**
	 * Gets the paths to the icon
	 *
	 * @param	Array	$obj	Array with object information
	 * @return	Array	Array with object information
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	function getIconPaths(&$obj) {
		if($obj['type'] == 'shape') {
			if(preg_match('/^\[(.*)\]$/',$obj['icon'],$match) > 0) {
				$obj['path']     = '';
				$obj['htmlPath'] = '';
			} else {
				$obj['path']     = dirname($this->CORE->getMainCfg()->getPath('sys',  '',       'shapes', $obj['icon']));
				$obj['htmlPath'] = dirname($this->CORE->getMainCfg()->getPath('html', 'global', 'shapes', $obj['icon']));
			}
		} elseif(isset($obj['view_type']) && $obj['view_type'] == 'gadget') {
			if(preg_match('/^\[(.*)\]$/', $obj['gadget_url'], $match) > 0) {
				$obj['path']     = '';
				$obj['htmlPath'] = '';
			} else {
				$obj['path']     = dirname($this->CORE->getMainCfg()->getPath('sys',  '',       'gadgets', $obj['icon']));
				$obj['htmlPath'] = dirname($this->CORE->getMainCfg()->getPath('html', 'global', 'gadgets', $obj['icon']));
			}
		} else {
			$obj['path']     = dirname($this->CORE->getMainCfg()->getPath('sys',  '',       'icons', $obj['icon']));
			$obj['htmlPath'] = dirname($this->CORE->getMainCfg()->getPath('html', 'global', 'icons', $obj['icon']));
		}
		return $obj;
	}

	function isRelativeCoord($val) {
		return !is_numeric($val) || strlen($val) === 6;
	}

	function parseCoord($val, $dir) {
		if(!$this->isRelativeCoord($val)) {
			return (int) $val;
		} else {
			if(strpos($val, '%') !== false) {
				// Object id with offset -> calculate
				list($parentId, $offset) = explode('%', $val);
				if(!isset($this->objects[$parentId]))
				    return 0;
				$parentCoord = $this->parseCoord($this->objects[$parentId][$dir], $dir);
				return $parentCoord + (int) $offset;
			} else {
				// Object id without offset
				return $this->parseCoord($this->objects[$val][$dir], $dir);
			}
		}
	}
	
	/**
	 * Parses the HTML-Code of an icon
	 *
	 * @param	Array	$obj	Array with object information
	 * @return	String HTML Code
	 * @author Lars Michelsen <lars@vertical-visions.de>
	 */
	function parseIcon($obj) {
		// Add 20x20 icon in the middle of the line in case of line objects
		if($obj['type'] == 'line' || (isset($obj['view_type']) && $obj['view_type'] == 'line')) {
			$x = explode(",", $obj['x']);
			$y = explode(",", $obj['y']);

			for($i = 0, $len = count($x); $i < $len; $i++) {
				$x[$i] = $this->parseCoord($x[$i], 'x');
				$y[$i] = $this->parseCoord($y[$i], 'y');
			}
			
			$xMin = min($x);
			$yMin = min($y);
		
			if(isset($x[2])) {
				$x = $x[1] - $xMin - 10;
				$y = $y[1] - $yMin - 10;
			} else {
				$x = round(($x[0]+($x[1]-$x[0])/2) - 10) - $xMin;
				$y = round(($y[0]+($y[1]-$y[0])/2) - 10) - $yMin;
			}

			$style = 'style="position:absolute;left:'.$x.'px;top:'.$y.'px"';
			
			$obj['icon'] = '20x20.gif';
		} else
			$style = '';
		
		return "<img id=\"icon_".$obj['type']."_".$obj['id']."\" src=\"".$obj['htmlPath'].'/'.$obj['icon'].$obj['iconParams']."\" ".$style." alt=\"".$obj['type']."_".$obj['id']."\" onmouseover=\"toggleBorder(this, 1)\" onmouseout=\"toggleBorder(this, 0)\" onmousedown=\"contextMouseDown(event);\" oncontextmenu=\"return contextShow(event);\" />";
	}
	
	/**
	 * Parses the HTML-Code of a line
	 *
	 * @param	Array	$obj	Array with object information
	 * @return	String HTML Code
	 * @author Lars Michelsen <lars@vertical-visions.de>
	 */
	function parseLine($obj) {
		$ret = '';
		
		$lineId = "line_".$obj['type']."_".$obj['id'];
		
		$x = explode(",", $obj['x']);
		$y = explode(",", $obj['y']);

		for($i = 0, $len = count($x); $i < $len; $i++) {
			$x[$i] = $this->parseCoord($x[$i], 'x');
			$y[$i] = $this->parseCoord($y[$i], 'y');
		}
		
		$xMin = min($x);
		$yMin = min($y);
		
		$ret .= "var ".$lineId." = new jsGraphics('line_".$obj['type']."_".$obj['id']."');";
		$ret .= $lineId.".setColor('#FF0000');";
		$ret .= $lineId.".setStroke(1);";
		// A line can have 3 coords with a repositioned middle
		if(isset($x[2])) {
			$ret .= $lineId.".drawLine(".($x[0]-$xMin).",".($y[0]-$yMin).",".($x[1]-$xMin).",".($y[1]-$yMin).");";
			$ret .= $lineId.".drawLine(".($x[1]-$xMin).",".($y[1]-$yMin).",".($x[2]-$xMin).",".($y[2]-$yMin).");";
		} else
			$ret .= $lineId.".drawLine(".($x[0]-$xMin).",".($y[0]-$yMin).",".($x[1]-$xMin).",".($y[1]-$yMin).");";
		$ret .= $lineId.".paint();";
		
		return '<div style="position:absolute;top:0px;left:0px" id="line_'.$obj['type'].'_'.$obj['id'].'"></div>'.$this->parseJs($ret);
	}
	
	/**
	 * Parses the HTML-Code of an object container
	 *
	 * @param  Array   Array with object information
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

			$x1 = $this->parseCoord($x1, 'x');
			$x2 = $this->parseCoord($x2, 'x');
			$y1 = $this->parseCoord($y1, 'y');
			$y2 = $this->parseCoord($y2, 'y');
			
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
			$x = $this->parseCoord($obj['x'], 'x');
			$y = $this->parseCoord($obj['y'], 'y');
		}
		
		$ret .= "\n<div id=\"box_".$obj['type']."_".$obj['id']."\" class=\"icon\" style=\"left:".$x."px;top:".$y."px;z-index:".$obj['z']."\">";
		$ret .= $html;
		$ret .= "</div>\n";
		
		return $ret;
	}
	
	/**
	 * Parses the HTML-Code of a label
	 *
	 * @param	Array	$obj		Array with object information
	 * @param	String	$base		Array with object information
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
			$this->moveable[] = 'rel_label_'.$obj['type'].'_'.$obj['id'];
		} else {
			$id = 'id="abs_label_'.$obj['type'].'_'.$obj['id'].'"';
			$this->moveable[] = 'abs_label_'.$obj['type'].'_'.$obj['id'];
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
		// object array
		$objects = Array();
		
		foreach($this->MAPCFG->getMapObjects() AS $objectId => $obj) {
			$type = $obj['type'];

			if($type === 'global' || $type === 'template')
				continue;

			// Default object state
			if($type == 'host' || $type == 'hostgroup') {
				$objState = Array('state'=>'UP','stateOutput'=>'Default State');
			} else {
				$objState = Array('state'=>'OK','stateOutput'=>'Default State');
			}

			// workaround
			$obj['id'] = $objectId;
			
			if($mergeWithGlobals) {
				// merge with "global" settings
				foreach($this->MAPCFG->getValidTypeKeys($type) AS $key) {
					$obj[$key] = $this->MAPCFG->getValue($objectId, $key);
				}
			}

			// add default state to the object
			$obj = array_merge($obj, $objState);
			
			if($obj['type'] != 'textbox' && $obj['type'] != 'shape' && $obj['type'] != 'line') {
				$obj['icon'] = $this->getIcon($obj);
			}
			
			// add object to array of objects
			$objects[$objectId] = $obj;
		}
		
		return $objects;
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
		
		if(@fclose(@fopen($this->CORE->getMainCfg()->getPath('sys', '', 'icons', $icon), 'r'))) {
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
		
		$ret .= "<div id=\"".$id."\" class=\"box resizeMe\" style=\"border-color:".$sBorderColor.";background-color:".$sBgColor.";left:".$obj['x']."px;top:".$obj['y']."px;z-index:".$obj['z'].";width:".$obj['w'].";height:".$obj['h'].";overflow:visible;\" onmousedown=\"contextMouseDown(event);\" oncontextmenu=\"return contextShow(event);\">";
		$ret .= "\t<span style='".$obj['style']."'>".$obj['text']."</span>";
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
		
		$ret = '<div id="'.$id.'" class="context" style="z-index:1000;display:none;position:absolute;overflow:visible;">';
		$ret .= $this->infoBox($obj);
		$ret .= '</div>';

		return $ret;
	}
	
	/**
	 * Creates a Javascript-Box with information.
	 *
	 * @param	Array	$obj	Array with object information
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
		$tooltipText = '<table class="infobox">';
		$configuredText = '';
		$defaultText = '';
		
		// Get configured/inherited variables
		foreach($this->MAPCFG->getValidTypeKeys($obj['type']) AS $key) {
			$bGlobal = false;
			$value = $this->MAPCFG->getValue($obj['id'], $key, true);
			
			// Get global value when nothing set
			if($value === false) {
				$bGlobal = true;
				$value = $this->MAPCFG->getValue($obj['id'], $key, false);
			}
			
			// Change array to comma separated string
			if(is_array($value))
				$value = implode(',',$value);
			
			// Cleanup some bad signs
			$value = str_replace('\"','&quot;', $value);
			$value = str_replace('"','&quot;', $value);
			
			if($bGlobal)
				$defaultText .= '<tr class="inherited"><td>'.$key.'</td><td>'.$value.'</td></tr>';
			else
				$configuredText .= '<tr><td>'.$key.'</td><td>'.$value.'</td></tr>';
		}
		
		/*
		 * Add action links
		 */
		
		$tooltipText .= '<tr><th colspan="2" style="height:34px;">';
		$tooltipText .= '<ul class="nav">';
		
		// Edit link
		$tooltipText .= '<li><a style="background-image:url('.$this->CORE->getMainCfg()->getValue('paths','htmlbase').'/frontend/wui/images/modify.png)"'
		  ." href=\"#\" onclick=\"showFrontendDialog('".$this->CORE->getMainCfg()->getValue('paths', 'htmlbase')."/server/core/ajax_handler.php?mod=Map&act=addModify&do=modify&show=".$this->MAPCFG->getName()."&type=".$obj['type']."&id='+getObjectIdOfLink(this), '".$this->CORE->getLang()->getText('change')."');contextHide();\">"
			."<span>".$this->CORE->getLang()->getText('change')."</span></a></li>";
		
		// Position/Size link on lines
		if($obj['type'] == 'line' || (isset($obj['view_type']) && $obj['view_type'] == 'line')) {
			$tooltipText .= "<li><a style='background-image:url(".$this->CORE->getMainCfg()->getValue('paths','htmlbase')."/frontend/wui/images/move.png)'"
						." onclick=\"objid=getObjectIdOfLink(this);contextHide();\" href=\"javascript:get_click('".$obj['type']."',2,'modify');\">"
						."<span>".$this->CORE->getLang()->getText('positionSize')."</span></a></li>";			
		}
		
		// Show clone link only for icons
		if((isset($obj['view_type']) && $obj['view_type'] == 'icon') || $obj['type'] == 'shape') {
			$tooltipText .= "<li><a style='background-image:url(".$this->CORE->getMainCfg()->getValue('paths','htmlbase')."/frontend/wui/images/clone.png)'"
						." onclick=\"objid=getObjectIdOfLink(this);contextHide();\" href=\"javascript:get_click('".$obj['type']."', 1, 'clone');\">"
						."<span>".$this->CORE->getLang()->getText('Clone')."</span></a></li>";
		}
		
		// Delete link
		$tooltipText .= "<li><a style='background-image:url(".$this->CORE->getMainCfg()->getValue('paths','htmlbase')."/frontend/wui/images/delete.png)'"
		  ." href='#' onclick='deleteMapObject(\"box_".$obj['type']."_\"+getObjectIdOfLink(this));contextHide();return false;'>"
		  ."<span>".$this->CORE->getLang()->getText('delete')."</span></a></li>";
		
		$tooltipText .= '</ul>';
		$tooltipText .= '</th></tr>';
		
		
		// Print configured settings
		$tooltipText .= '<tr><th colspan="2">'.$this->CORE->getLang()->getText('configured').'</th></tr>'.$configuredText;
		
		// Print inherited settings
		$tooltipText .= '<tr class="inherited"><th colspan="2">'.$this->CORE->getLang()->getText('inherited').'</th></tr>'.$defaultText;
		
		$tooltipText .= '</table>';
		
		return $tooltipText;
	}
}
