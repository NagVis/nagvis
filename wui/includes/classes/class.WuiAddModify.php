<?php
class WuiAddModify extends GlobalPage {
	var $MAINCFG;
	var $MAPCFG;
	var $LANG;
	var $FORM;
	var $prop;
	var $propertiesList;
	var $propCount;
	
	function WuiAddModify(&$MAINCFG,&$MAPCFG,$prop) {
		$this->MAINCFG = &$MAINCFG;
		$this->MAPCFG = &$MAPCFG;
		$this->prop = $prop;
		$this->propCount = 0;
		
		# we load the language file
		$this->LANG = new GlobalLanguage($MAINCFG,'wui:addModify');
		
		$prop = Array('title'=>$MAINCFG->getValue('internal', 'title'),
					  'cssIncludes'=>Array('./includes/css/wui.css'),
					  'jsIncludes'=>Array('./includes/js/addmodify.js'),
					  'extHeader'=>Array(''),
					  'allowedUsers' => Array('EVERYONE'));
		parent::GlobalPage($MAINCFG,$prop,'wui:addModify');
	}
	
	/**
	* If enabled, the form is added to the page
	*
	* @author Lars Michelsen <larsi@nagios-wiki.de>
	*/
	function getForm() {
		// Inititalize language for JS
		$this->addBodyLines($this->getJsLang());
		
		$this->FORM = new GlobalForm(Array('name'=>'addmodify',
			'id'=>'addmodify',
			'method'=>'POST',
			'action'=>'./wui.function.inc.php?myaction='.$this->prop['action'],
			'onSubmit'=>'return check_object();',
			'cols'=>'2'));
		$this->addBodyLines($this->FORM->initForm());
		$this->addBodyLines($this->getFields());
		$this->addBodyLines($this->fillFields());
		$this->addBodyLines($this->getSubmit());
		$this->addBodyLines($this->resizeWindow());
	}
	
	function resizeWindow() {
		$ret = Array();
		$ret[] = "<script type=\"text/javascript\" language=\"JavaScript\"><!--";
		$ret[] = "// we resize the window (depending on the number of properties displayed)";
		$ret[] = "window.resizeTo(410,".$this->propCount."*40+60)";
		$ret[] = "//--></script>";
		
		return $ret;
	}
	
	function fillFields() {
		$ret = Array();
		
		if($this->prop['action'] == 'modify') {
			$myval = $this->prop['id'];
			$ret[] = "<script type=\"text/javascript\" language=\"JavaScript\"><!--\n";
			foreach($this->MAPCFG->getDefinitions($this->prop['type']) AS $key => $obj) {
				if($key == $myval) {
					foreach($this->MAPCFG->validConfig[$this->prop['type']] as $propname => $prop) {
						if(isset($obj[$propname])) {
							if($propname == 'line_type') {
								$ret[] = "document.addmodify.elements['".$propname."'].value='".substr($obj[$propname],strlen($obj[$propname])-1,1)."';\n";
							} else {
								if(is_array($obj[$propname])) {
									$val = implode(',',$obj[$propname]);
								} else {
									$val = $obj[$propname];
								}
								$ret[] = "document.addmodify.elements['".$propname."'].value='".$val."';\n";
							}
						}
					}
				}
			}
			
			if($this->prop['coords'] != "") {
				$val_coords = explode(',',$this->prop['coords']);
				if ($mytype == "textbox") {
					$objwidth = $val_coords[2] - $val_coords[0];
					$ret[] = "document.addmodify.elements['x'].value='".$val_coords[0]."';\n";
					$ret[] = "document.addmodify.elements['y'].value='".$val_coords[1]."';\n";
					$ret[] = "document.addmodify.elements['w'].value='".$objwidth."';\n";
				} else {
					$ret[] = "document.addmodify.elements['x'].value='".$val_coords[0].",".$val_coords[2]."';\n";
					$ret[] = "document.addmodify.elements['y'].value='".$val_coords[1].",".$val_coords[3]."';\n";
				}
			}
			$ret[] = "//--></script>\n";	
		}
		##########################################
		# if the action specified in the URL is "add", we set the object coordinates (that we retrieve from the mycoords parameter)
		else if($this->prop['action'] == "add") {
			if($this->prop['coords'] != "") {
				$val_coords = explode(',',$this->prop['coords']);
				$ret[] = "<script type=\"text/javascript\" language=\"JavaScript\"><!--\n";
				if(count($val_coords) == 2) {			
					$ret[] = "document.addmodify.elements['x'].value='".$val_coords[0]."';\n";
					$ret[] = "document.addmodify.elements['y'].value='".$val_coords[1]."';\n";
				} elseif(count($val_coords) == 4) {
					if ($this->prop['type'] == "textbox") {
						$objwidth = $val_coords[2] - $val_coords[0];
						
						$ret[] = "document.addmodify.elements['x'].value='".$val_coords[0]."';\n";
						$ret[] = "document.addmodify.elements['y'].value='".$val_coords[1]."';\n";
						$ret[] = "document.addmodify.elements['w'].value='".$objwidth."';\n";
					} else {
						$ret[] = "document.addmodify.elements['x'].value='".$val_coords[0].",".$val_coords[2]."';\n";
						$ret[] = "document.addmodify.elements['y'].value='".$val_coords[1].",".$val_coords[3]."';\n";
					}		
				}
				$ret[] = "//--></script>\n";
			}
		}
		
		return $ret;
	}
	
	function getFields() {
		$ret = Array();
		$ret = array_merge($ret,$this->FORM->getHiddenField('type',$this->prop['type']));
		$ret = array_merge($ret,$this->FORM->getHiddenField('id',$this->prop['id']));
		$ret = array_merge($ret,$this->FORM->getHiddenField('map',$this->MAPCFG->getName()));
		$ret = array_merge($ret,$this->FORM->getHiddenField('properties',''));
		
		// loop all properties
		foreach($this->MAPCFG->validConfig[$this->prop['type']] as $propname => $prop) {
			# we treat the special case of iconset, which will display a listbox instead of the normal textbox
			if($propname == "iconset") {
				$ret = array_merge($ret,$this->FORM->getSelectLine($propname,$propname,$this->getIconsets(),'',$prop['must']));
				$this->propCount++;
			}
			# we treat the special case of map_image, which will display a listbox instead of the normal textbox
			else if($propname == "map_image") {
				$ret = array_merge($ret,$this->FORM->getSelectLine($propname,$propname,$this->getMapImages(),'',$prop['must']));
				$this->propCount++;
			}
			
			# we treat the special case of recognize_services, which will display a "yes/no" listbox instead of the normal textbox
			else if($propname == "recognize_services") {
				$opt = Array(Array('label' => $this->LANG->getLabel('yes'),'value'=>'1'),Array('label' => $this->LANG->getLabel('no'),'value'=>'0'));
				$ret = array_merge($ret,$this->FORM->getSelectLine($propname,$propname,$opt,'',$prop['must']));
				$this->propCount++;
			}
			# we treat the special case of line_type, which will display a listbox showing the different possible shapes for the line
			else if($propname == "line_type") {
				$opt = Array(Array('label' => '------><------','value' => '0'),Array('label' => '-------------->','value'=>'1'));
				$ret = array_merge($ret,$this->FORM->getSelectLine($propname,$propname,$opt,'',$prop['must']));
				$this->propCount++;
			}
			# we treat the special case of map_name, which will display a listbox instead of the normal textbox
			else if($propname == "map_name") {
				$ret = array_merge($ret,$this->FORM->getSelectLine($propname,$propname,$this->getMaps(),'',$prop['must']));
				$this->propCount++;
			}
			# we treat the special case of <object-type>_name, if ndo backend is used
			// FIXME: TODO: service_description with filtering the services of the actual host_name
			else if(($propname == 'host_name' || $propname == 'hostgroup_name' || $propname == 'servicegroup_name')) {
				$BACKEND = new GlobalBackend($this->MAINCFG);
				
				if(method_exists($BACKEND,'getObjects')) {
					$ret = array_merge($ret,$this->FORM->getSelectLine($propname,$propname,$BACKEND->getObjects(str_replace('_name','',$propname),'',''),'',$prop['must']));
				} else {
					$ret = array_merge($ret,$this->FORM->getInputLine($propname,$propname,'',$prop['must']));
				}
				$this->propCount++;
			}
			else if($propname == 'type') {
				// Do nothing, type is only internal
			}	
			# we display a simple textbox
			else {
				$ret = array_merge($ret,$this->FORM->getInputLine($propname,$propname,'',$prop['must']));
				$this->propCount++;
			}
		}
		
		return $ret;
	}
	
	function getSubmit() {
		return array_merge($this->FORM->getSubmitLine($this->LANG->getLabel('check')),$this->FORM->closeForm());
	}
	
	function getIconsets() {
		$files = Array();
		
		if ($handle = opendir($this->MAINCFG->getValue('paths', 'icon'))) {
 			while (false !== ($file = readdir($handle))) {
				if ($file != "." && $file != ".." && preg_match('/_ok.png$/', $file)) {
					$files[] = str_replace('_ok.png','',$file);
				}				
			}
			
			if ($files) {
				natcasesort($files);
			}
		}
		closedir($handle);
		
		return $files;
	}
	
	function getMaps() {
		$files = Array();
		
		if ($handle = opendir($this->MAINCFG->getValue('paths', 'mapcfg'))) {
 			while (false !== ($file = readdir($handle))) {
				if ($file != "." && $file != ".." && preg_match('/.cfg$/', $file)) {
					$files[] = str_replace('.cfg','',$file);
				}				
			}
			
			if ($files) {
				natcasesort($files);
			}
		}
		closedir($handle);
		
		return $files;
	}
	
	function getMapImages() {
		$files = Array();
		
		if ($handle = opendir($this->MAINCFG->getValue('paths', 'map'))) {
 			while (false !== ($file = readdir($handle))) {
				if ($file != "." && $file != ".." && preg_match('/.png$/', $file)) {
					$files[] = $file;
				}				
			}
			
			if ($files) {
				natcasesort($files);
			}
		}
		closedir($handle);
		
		return $files;
	}
	
	function getJsLang() {
		$ret = Array();
		$ret[] = '<script type="text/javascript" language="JavaScript"><!--';
		$ret[] = 'var lang = Array();';
		$ret[] = 'var user = "'.$this->MAINCFG->getRuntimeValue('user').'";';
		$ret[] = 'lang["unableToWorkWithMap"] = "'.$this->LANG->getMessageText('unableToWorkWithMap').'";';
		$ret[] = 'lang["mustValueNotSet"] = "'.$this->LANG->getMessageText('mustValueNotSet').'";';
		$ret[] = 'lang["chosenLineTypeNotValid"] = "'.$this->LANG->getMessageText('chosenLineTypeNotValid').'";';
		$ret[] = 'lang["onlyLineOrIcon"] = "'.$this->LANG->getMessageText('onlyLineOrIcon').'"';
		$ret[] = 'lang["not2coordsX"] = "'.$this->LANG->getMessageText('not2coords','','COORD=X').'";';
		$ret[] = 'lang["not2coordsY"] = "'.$this->LANG->getMessageText('not2coords','','COORD=Y').'";';
		$ret[] = 'lang["only1or2coordsX"] = "'.$this->LANG->getMessageText('only1or2coords','','COORD=X').'";';
		$ret[] = 'lang["only1or2coordsY"] = "'.$this->LANG->getMessageText('only1or2coords','','COORD=Y').'";';
		$ret[] = 'lang["lineTypeNotSelectedX"] = "'.$this->LANG->getMessageText('lineTypeNotSelected','','COORD=X').'";';
		$ret[] = 'lang["lineTypeNotSelectedY"] = "'.$this->LANG->getMessageText('lineTypeNotSelected','','COORD=Y').'";';
		$ret[] = '//--></script>';
		
		return $ret;	
	}
}
?>