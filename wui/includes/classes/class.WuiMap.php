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
		$this->objects = $this->getMapObjects(0,0);
	}
	
	function loadPermissions() {
		#############################################
		# we read ALL the maps definition files, to build the lists of allowed users and map_images. At the end we have s.th like
		# demo=root,nagiosadmin^map2=user1
		# demo=back1.png^map2=mynetwork.png
		#
		# These lists will be stored in invisible fields, in the form 'myvalues' in this page.
		# The list of allowed_user will be used : 
		#		- to list, in the right click menu, only the maps the user is granted access to
		#		- to prevent the user to rename or delete a map he's not granted access to
		#
		# The list of map_images will be used :
		#		- to make sure a background image is not in use by another map, before it's deleted
		if ($handle2 = opendir($this->MAINCFG->getValue('paths', 'mapcfg'))) {
			$files = Array();
			while (false !== ($file = readdir($handle2))) {
				if ($file != "." && $file != ".." && substr($file,strlen($file)-4,4) == ".cfg" ) {
					if(substr($file,0,strlen($file)-4) != '') {
						$files[] = substr($file,0,strlen($file)-4);
					}
				}				
			}
			
			if(count($files) > 1) {
				natcasesort($files);
			}
			
			$all_allowed_user="";
			$all_map_image="";
			$all_map_name="";
			
			foreach($files as $file) {
				$MAPCFG1 = new GlobalMapCfg($this->MAINCFG,$file);
				$MAPCFG1->readMapConfig();
				
				if(is_array($MAPCFG1->getValue('global', 0,'allowed_for_config'))) {
					$users = implode(',',$MAPCFG1->getValue('global', 0,'allowed_for_config'));
				} else {
					$users = $MAPCFG1->getValue('global', 0,'allowed_for_config');
				}
				$all_allowed_user .= "^".$file."=".$users;	
				$all_map_image .= "^".$file."=".$MAPCFG1->getValue('global', 0,'map_image');
				
				foreach($MAPCFG1->getDefinitions('map') AS $key => $obj) {
					$all_map_name .= "^".$file."=".$obj['map_name'];
				}
			}
		}
		closedir($handle2);
		
		# we remove the first ^
		$this->MAINCFG->setRuntimeValue('AllMapsAllowedUsers',substr($all_allowed_user,1,strlen($all_allowed_user)));
		$this->MAINCFG->setRuntimeValue('AllMapsImages',substr($all_map_image,1,strlen($all_map_image)));
		$this->MAINCFG->setRuntimeValue('AllMapsNames',substr($all_map_name,1,strlen($all_map_name)));
	}
	
	function parseMap() {
		$ret = Array();
		$ret = array_merge($ret,$this->getBackground('img'));
		$ret = array_merge($ret,$this->getJsGraphicObj());
		$ret = array_merge($ret,$this->getJsLang());
		$ret = array_merge($ret,$this->parseObjects());
		$ret = array_merge($ret,$this->parseInvisible());
		$ret = array_merge($ret,$this->makeObjectsMoveable());
		
		return $ret;
	}
	
	function getJsGraphicObj() {
		$ret = Array();
		$ret[] = "<script type=\"text/javascript\">";
		$ret[] = "\t\tmyshape_background = new jsGraphics('mycanvas');";
		$ret[] = "\t\tmyshape_background.setColor('#FF0000');";
		$ret[] = "\t\tmyshape_background.setStroke(1);";
		$ret[] = "</script>";
		
		return $ret;
	}
	
	function makeObjectsMoveable() {
		$arr = Array();
		
		if(strlen($this->moveable) != 0) {
			$arr[] = "<script type='text/javascript'>";
			$arr[] = "<!--\n";
			$arr[] = "SET_DHTML(TRANSPARENT,CURSOR_HAND,".substr($this->moveable,0,strlen($this->moveable)-1).");\n";
			$arr[] = "//-->\n";
			$arr[] = "</script>\n";
		}
		return $arr;
	}
	
	function parseObjects() {
		$ret = Array();
		
		foreach($this->objects AS $var => $obj) {
			if($obj['type'] == 'textbox') {
				$obj['class'] = "box";
				$obj['icon'] = "20x20.gif";
				
				$ret = array_merge($ret,$this->textBox($obj));
			}
			
			if(isset($obj['line_type'])) {
				list($pointa_x,$pointb_x) = explode(",", $obj['x']);
				list($pointa_y,$pointb_y) = explode(",", $obj['y']);
				$ret[] = "<script type=\"text/javascript\">myshape_background.drawLine(".$pointa_x.",".$pointa_y.",".$pointb_x.",".$pointb_y.");</script>";
				
				$obj['icon'] = '20x20.gif';
			}
			
			$obj = $this->fixIconPosition($obj);
			$ret = array_merge($ret,$this->parseIcon($obj));
			
			// add this object to the list of the components which will have to be movable, if it's not a line or a textbox
			if(!isset($obj['line_type']) && $obj['type'] != 'textbox') {
				$this->moveable .= "\"box_".$obj['type']."_".$obj['id']."\",";
			}
		}
		return $ret;
	}
	
	/**
	* Parses the HTML-Code of an icon
	*
	* @param Array $obj
	*
	* @author Lars Michelsen <larsi@nagios-wiki.de>
	*/
	function parseIcon($obj) {
		$ret = Array();
		
		if($obj['type'] == 'service') {
			$name = 'host_name';
		} else {
			$name = $obj['type'] . '_name';
		}
		
		$ret[] = "\t<div id=\"box_".$obj['type']."_".$obj['id']."\" class=\"icon\" style=\"left:".$obj['x']."px; top:".$obj['y']."px;z-index:".$obj['z'].";\">";
		$ret[] = "\t\t<img src=\"".$this->MAINCFG->getValue('paths', 'htmlicon').$obj['icon']."\" ".$this->infoBox($obj).">";
		$ret[] = "\t</div>";
		
		return $ret;
	}
	
	/**
	* Create a Comment-Textbox
	*
	* @param Array $obj
	*
	* @author Joerg Linge
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
			if( $var != ""  && $var != "id" && $var != "icon" && $var != "type" && $var != "x" && $var != "y") {
				$tooltipText .= $var.": ".$val."<br>";
			}
		}
		
		$tooltipText .= "<br><a href=\'./addmodify.php?action=modify&map=".$this->MAPCFG->getName()."&type=".$obj['type']."&id=".$obj['id']."\' onclick=\'fenetre(href); return false;\'>".$this->LANG->getLabel('change')."</a>";
		$tooltipText .= "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";	
		$tooltipText .= "<a href=\'./wui.function.inc.php?myaction=delete&map=".$this->MAPCFG->getName()."&type=".$obj['type']."&id=".$obj['id']."\' onClick=\'return confirm_object_deletion();return false;\'>".$this->LANG->getLabel('delete')."</a>";
		
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
		$ret[] = '<script type="text/javascript" language="JavaScript"><!--';
		$ret[] = 'var langMenu = Array();';
		$ret[] = 'langMenu["save"] = "'.$this->LANG->getLabel('save').'";';
		$ret[] = 'langMenu["restore"] = "'.$this->LANG->getLabel('restore').'";';
		$ret[] = 'langMenu["properties"] = "'.$this->LANG->getLabel('properties').'";';
		$ret[] = 'langMenu["maps"] = "'.$this->LANG->getLabel('maps').'";';
		$ret[] = 'langMenu["addObject"] = "'.$this->LANG->getLabel('addObject').'";';
		$ret[] = 'langMenu["nagVisConfig"] = "'.$this->LANG->getLabel('nagVisConfig').'";';
		$ret[] = 'langMenu["help"] = "'.$this->LANG->getLabel('help').'";';
		$ret[] = 'langMenu["open"] = "'.$this->LANG->getLabel('open').'";';
		$ret[] = 'langMenu["manage"] = "'.$this->LANG->getLabel('manage').'";';
		$ret[] = 'langMenu["icon"] = "'.$this->LANG->getLabel('icon').'";';
		$ret[] = 'langMenu["line"] = "'.$this->LANG->getLabel('line').'";';
		$ret[] = 'langMenu["host"] = "'.$this->LANG->getLabel('host').'";';
		$ret[] = 'langMenu["service"] = "'.$this->LANG->getLabel('service').'";';
		$ret[] = 'langMenu["hostgroup"] = "'.$this->LANG->getLabel('hostgroup').'";';
		$ret[] = 'langMenu["servicegroup"] = "'.$this->LANG->getLabel('servicegroup').'";';
		$ret[] = 'langMenu["map"] = "'.$this->LANG->getLabel('map').'";';
		$ret[] = 'langMenu["textbox"] = "'.$this->LANG->getLabel('textbox').'";';
		$ret[] = 'langMenu["shape"] = "'.$this->LANG->getLabel('shape').'";';
		$ret[] = '//--></script>';
		
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
		#
		# the other fields of this form are used to store datas the other pages will use
		$arr[] = '<form name="myvalues" action="./wui.function.inc.php?myaction=save" method="post">
			<input type="hidden" name="image">
			<input type="hidden" name="formulaire" value="'.$this->MAPCFG->getName().'">
			<input type="hidden" name="valx">
			<input type="hidden" name="valy">
			<input type="hidden" name="autosave" value="'.$this->MAINCFG->getRuntimeValue('justAdded').'">
			<input type="hidden" name="username" value="'.$this->MAINCFG->getRuntimeValue('user').'">
			<textarea name="menu_labels"></textarea>
			<input type="text" name="allowed_users_by_map" value="'.$this->MAINCFG->getRuntimeValue('AllMapsAllowedUsers').'">
			<input type="text" name="image_map_by_map" value="'.$this->MAINCFG->getRuntimeValue('AllMapsImages').'">
			<input type="text" name="mapname_by_map" value="'.$this->MAINCFG->getRuntimeValue('AllMapsNames').'">
			<input type="text" name="backup_available" value="'.file_exists($this->MAINCFG->getValue('paths', 'mapcfg').$this->MAPCFG->getName().".cfg.bak").'">
			<input name="submit" type=submit value="Save this map">
		</form> 
		
		<form name="add_object" action="./wui/wui.function.inc.php?myaction=add_modify" method="post" onsubmit="return check_new_object();">
			<input type="hidden" name="formulaire" value="'.$this->MAPCFG->getName().'">
			<input type="hidden" name="modify_line" value="">		
				<select name="add_type" style="width: 108px">
					<option value="host">host</option>
					<option value="hostgroup">hostgroup</option>
					<option value="service">service</option>
					<option value="servicegroup">servicegroup</option>
					<option value="map">map</option>
					<option value="textbox">textbox</option>
					<option value="shape">shape</option>
				</select>		
			<input name="add" type=submit value="Add object">		
		</form>';
		
		// list all maps in a javascript variable
		$arrMaps = "var arrMaps = Array(";
		$i = 0;
		foreach($this->getMaps() AS $file) {
			if($i != 0) {
				$arrMaps .= ",";
			}
			$arrMaps .= "'".$file."'";
			$i++;
		}
		$arrMaps .= ");";
		
		$arr[] = "<script type=\"text/javascript\" language=\"JavaScript\">
		  <!--
		  	".$arrMaps."
		  	
			// make the forms invisible
			document.forms['myvalues'].style.visibility='hidden';
			document.forms['add_object'].style.visibility='hidden';
			
			// build the right-click menu
			initjsDOMenu();
			
			// draw the shapes on the background
			myshape_background.paint();
		  //-->
		</script>";
		
		return $arr;
	}
}