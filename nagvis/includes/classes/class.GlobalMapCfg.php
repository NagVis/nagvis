<?php
/**
 * This Class handles the NagVis configuration file
 *
 * @author 	Lars Michelsen <larsi@nagios-wiki.de>
 */
class GlobalMapCfg {
	var $MAINCFG;
	
	var $name;
	var $image;
	var $mapConfig;
	
	// Array for config validation
	var $validConfig;
	
	/**
	 * Class Constructor
	 *
	 * @param	GlobalMainCfg	$MAINCFG	
	 * @param	String			$name		Name of the map
	 * @author	Lars Michelsen <larsi@nagios-wiki.de>
	 */
	function GlobalMapCfg(&$MAINCFG,$name='') {
		$this->MAINCFG = &$MAINCFG;
		$this->name	= $name;
		
		$this->validConfig = Array(
			'global' => Array('type' => Array('must' => 0),
							'allowed_for_config' => Array('must' => 1),
							'allowed_user' => Array('must' => 1),
							'recognize_services' => Array('must' => 0),
							'iconset' => Array('must' => 0),
							'map_image' => Array('must' => 1)),
			'host' => Array('type' => Array('must' => 0),
							'host_name' => Array('must' => 1),
							'x' => Array('must' => 1),
							'y' => Array('must' => 1),
							'recognize_services' => Array('must' => 0),
							'only_hard_states' => Array('must' => 0),
							'backend_id' => Array('must' => 0),
							'hover_url' => Array('must' => 0),
							'iconset' => Array('must' => 0),
							'line_type' => Array('must' => 0),
							'url' => Array('must' => 0)),
			'hostgroup' => Array('type' => Array('must' => 0),
							'hostgroup_name' => Array('must' => 1),
							'x' => Array('must' => 1),
							'y' => Array('must' => 1),
							'recognize_services' => Array('must' => 0),
							'only_hard_states' => Array('must' => 0),
							'backend_id' => Array('must' => 0),
							'hover_url' => Array('must' => 0),
							'iconset' => Array('must' => 0),
							'line_type' => Array('must' => 0),
							'url' => Array('must' => 0)),
			'service' => Array('type' => Array('must' => 0),
							'host_name' => Array('must' => 1),
							'service_description' => Array('must' => 1),
							'x' => Array('must' => 1),
							'y' => Array('must' => 1),
							'only_hard_states' => Array('must' => 0),
							'backend_id' => Array('must' => 0),
							'hover_url' => Array('must' => 0),
							'line_type' => Array('must' => 0),
							'url' => Array('must' => 0),
							'iconset' => Array('must' => 0)),
			'servicegroup' => Array('type' => Array('must' => 0),
							'servicegroup_name' => Array('must' => 1),
							'x' => Array('must' => 1),
							'y' => Array('must' => 1),
							'only_hard_states' => Array('must' => 0),
							'backend_id' => Array('must' => 0),
							'hover_url' => Array('must' => 0),
							'iconset' => Array('must' => 0),
							'line_type' => Array('must' => 0),
							'url' => Array('must' => 0)),
			'map' => Array('type' => Array('must' => 0),
							'map_name' => Array('must' => 1),
							'x' => Array('must' => 1),
							'y' => Array('must' => 1),
							'only_hard_states' => Array('must' => 0),
							'iconset' => Array('must' => 0),
							'url' => Array('must' => 0)),
			'textbox' => Array('type' => Array('must' => 0),
							'text' => Array('must' => 1),
							'x' => Array('must' => 1),
							'y' => Array('must' => 1),
							'w' => Array('must' => 1),
							'host_name' => Array('must' => 0)));
		
		$this->getMap();
	}
	
	/**
	 * Reads which map should be displayed, primary use
	 * the map defined in the url, if there is no map
	 * in url, use first entry of "maps" defined in 
	 * the NagVis main config
	 *
	 * @author	Lars Michelsen <larsi@nagios-wiki.de>
     */
	function getMap() {
		// if no map was given with parameter, search for a map
		if($this->name == '') {
			// only try to get a map, if we are not in wui
			if($this->MAINCFG->getRuntimeValue('wui') == 1) {
				$this->name = '';
			} else {
				$arr = explode(',',$this->MAINCFG->getValue('global', 'maps'));
				$this->name = $arr[0];
			}
		} else {
			// check the $this->name string for security reasons (its the ONLY value we get directly from external...)
			// Allow ONLY Characters, Numbers, - and _ inside the Name of a Map
			$this->name = preg_replace("/[^a-zA-Z0-9_-]/",'',$this->name);
		}
	}
	
	/**
	 * Reads which map image should be used
	 *
	 * @return	String	MapImage
	 * @author 	Lars Michelsen <larsi@nagios-wiki.de>
     */
	function getImage() {
		return $this->image = $this->getValue('global', 0, 'map_image');
	}
	
	/**
	 * Deletes the map image
	 *
	 * @param	Boolean	$printErr
	 * @return	Boolean	Is Check Successful?
	 * @author	Lars Michelsen <larsi@nagios-wiki.de>
     */
	function deleteImage($printErr) {
		if($this->checkMapImageWriteable($printErr)) {
			if(unlink($this->MAINCFG->getValue('paths', 'map').$this->image)) {
				return TRUE;
			} else {
				// FIXME: Need an error message: "Image could not be deleted"
				return FALSE;
			}
		}
	}
	
	/**
	 * Creates a new Configfile
	 *
	 * @return	Boolean	Is Successful?
	 * @author Lars Michelsen <larsi@nagios-wiki.de>
     */
	function createMapConfig() {
		// does file exists?
		if(!$this->checkMapConfigReadable(0)) {
			if($this->MAINCFG->checkMapCfgFolderWriteable(1)) {
				// create empty file
				$fp = fopen($this->MAINCFG->getValue('paths', 'mapcfg').$this->name.".cfg", "w");
				fclose($fp); 
				// set permissions
	  			chmod($this->MAINCFG->getValue('paths', 'mapcfg').$this->name.".cfg",0666);
	  			
  				return TRUE;
  			} else {
  				return FALSE;
  			}
		} else {
			// file exists & is readable
			return FALSE;
		}
	}
	
	/**
	 * Deletes the map configfile
	 *
	 * @return	Boolean	Is Successful?
	 * @author 	Lars Michelsen <larsi@nagios-wiki.de>
     */
	function deleteMapConfig() {
		// is file writeable?
		if($this->checkMapConfigWriteable(0)) {
			if(unlink($this->MAINCFG->getValue('paths', 'mapcfg').$this->name.'.cfg')) {
				return TRUE;
			} else {
				return FALSE;
			}
		} else {
			return FALSE;
		}
	}
	
	/**
	 * Reads the map config file (copied from readFile->readNagVisCfg())
	 *
	 * @return	Boolean	Is Successful?
	 * @author 	Lars Michelsen <larsi@nagios-wiki.de>
     */
	function readMapConfig() {
		if($this->name != '') {
			if($this->checkMapConfigReadable(1)) {
				$this->mapConfig = Array();
				
				// read file in array
				$file = file($this->MAINCFG->getValue('paths', 'mapcfg').$this->name.".cfg");
				
				$type = array("global","host","service","hostgroup","servicegroup","map","textbox");
				$createArray = array("allowed_user","allowed_for_config");
				$l = 0;
				$a = 0;
						
				while (isset($file[$l]) && $file[$l] != "") {
					if(!ereg("^#",$file[$l]) && !ereg("^;",$file[$l])) {
						$defineCln = explode("{", $file[$l]);
						$define = explode(" ",$defineCln[0]);
						if (isset($define[1]) && in_array(trim($define[1]),$type)) {
							$l++;
							$nrOfType = count($this->mapConfig[$define[1]]);
							$this->mapConfig[$define[1]][$nrOfType]['type'] = $define[1];
							while (trim($file[$l]) != "}") {
								$entry = explode("=",$file[$l], 2);
								
								if(isset($entry[1])) {
									if(in_array(trim($entry[0]),$createArray)) {
										$this->mapConfig[$define[1]][$nrOfType][trim($entry[0])] = explode(",",str_replace(' ','',trim($entry[1])));
									} else {
										$this->mapConfig[$define[1]][$nrOfType][trim($entry[0])] = trim($entry[1]);
									}
								}
								$l++;	
							}
						}
					}
					$l++;
				}
				
				if($this->checkMapConfigIsValid(1)) {
					$this->getImage();
					return TRUE;
				} else {
					return FALSE;
				}
			} else {
				return FALSE;	
			}
		} else {
			return FALSE;
		}
	}
	
	/**
	 * Writes the element from array to the config file
	 *
	 * @param	String	$type	Type of the Element
	 * @param	Integer	$id		Id of the Element
	 * @return	Boolean	Is Successful?
	 * @author 	Lars Michelsen <larsi@nagios-wiki.de>
     */
	function writeElement($type,$id) {
		if($this->checkMapConfigExists(1) && $this->checkMapConfigReadable(1) && $this->checkMapConfigWriteable(1)) {
			// read file in array
			$file = file($this->MAINCFG->getValue('paths', 'mapcfg').$this->name.".cfg");
			
			// number of lines in the file
			$l = 0;
			// number of elements of the given type
			$a = 0;
			// done?!
			$done = FALSE;
			while(isset($file[$l]) && $file[$l] != "" && $done == FALSE) {
				// ignore comments
				if(!ereg("^#",$file[$l]) && !ereg("^;",$file[$l])) {
					$defineCln = explode("{", $file[$l]);
					$define = explode(" ",$defineCln[0]);
					// select only elements of the given type
					if(isset($define[1]) && trim($define[1]) == $type) {
						// check if element exists
						if($a == $id) {
							// check if element is an array...
							if(is_array($this->mapConfig[$type][$a])) {
								// ...array: update!
								
								// choose first parameter line
								$l++;
								
								// loop parameters from array
								foreach($this->mapConfig[$type][$id] AS $key => $val) {
									// if key is not type
									if($key != 'type') {
										$cfgLines = 0;
										$cfgLine = '';
										$cfgLineNr = 0;
										// Parameter aus Datei durchlaufen
										while(trim($file[($l+$cfgLines)]) != '}') {
											$entry = explode("=",$file[$l+$cfgLines], 2);
											if($key == trim($entry[0])) {
												$cfgLineNr = $l+$cfgLines;
												if(is_array($val)) {
													$val = implode(",",$val);
												}
												$cfgLine = $key."=".$val."\n";
											}
											$cfgLines++;	
										}
										
										// Wenn der Parameter gefunden wurde...
										if($cfgLineNr != '') {
											// ersetzen
											$file[$cfgLineNr] = $cfgLine;
										} else {
											if(is_array($val)) {
												$val = implode(",",$val);
											}
											// neue Zeile am Ende der Defnition hinzufügen
											$neu = $key."=".$val."\n";
											for($i = $l; $i < count($file);$i++) {
												$tmp = $file[$i];
												$file[$i] = $neu;
												$neu = $tmp;
											}
											$file[count($file)] = $neu;
										}
										$l++;
									}
								}
							} else {
								// ...no array: delete!
								$cfgLines = 0;
								while(trim($file[($l+$cfgLines)]) != '}') {
									$cfgLines++;
								}
								$cfgLines++;
								
								for($i = $l; $i <= $l+$cfgLines;$i++) {
									unset($file[$i]);	
								}
							}
							
							$done = TRUE;
						}
						$a++;
					}
				}
				$l++;	
			}
			
			// reached end of file - couldn't find that element, create a new one...
			if($done == FALSE) {
				if($file[count($file)-1] != "\n") {
					$file[] = "\n";
				}
				$file[] = "define ".$type." {\n";
				foreach($this->mapConfig[$type][$id] AS $key => $val) {
					$file[] = $key."=".$val."\n";
				}
				$file[] = "}\n";
				$file[] = "\n";
			}
			
			// open file for writing and replace it
		 	$fp = fopen($this->MAINCFG->getValue('paths', 'mapcfg').$this->name.".cfg","w");
		 	fwrite($fp,implode("",$file));
		 	fclose($fp);
		 	
			return TRUE;
		} else {
			return FALSE;
		} 
	}
	
	/**
	 * Checks for existing map image file
	 *
	 * @param	Boolean $printErr
	 * @return	Boolean	Is Successful?
	 * @author 	Lars Michelsen <larsi@nagios-wiki.de>
     */
	function checkMapImageExists($printErr) {
		if($this->image != '') {
			if(file_exists($this->MAINCFG->getValue('paths', 'map').$this->image)) {
				return TRUE;
			} else {
				if($printErr == 1) {
					//Error Box
					$FRONTEND = new GlobalPage($this->MAINCFG,Array('languageRoot'=>'global:global'));
		            $FRONTEND->messageToUser('ERROR','backgroundNotExists','IMGPATH~'.$this->MAINCFG->getValue('paths', 'map').$this->image);
				}
				return FALSE;
			}
		} else {
			return FALSE;
		}
	}
	
    /**
	 * Checks for readable map image file
	 *
	 * @param	Boolean $printErr
	 * @return	Boolean	Is Successful?
	 * @author 	Lars Michelsen <larsi@nagios-wiki.de>
     */
	function checkMapImageReadable($printErr) {
		if($this->image != '') {
			if($this->checkMapImageExists($printErr) && is_readable($this->MAINCFG->getValue('paths', 'map').$this->image)) {
				return TRUE;
			} else {
				if($printErr == 1) {
					//Error Box
					$FRONTEND = new GlobalPage($this->MAINCFG,Array('languageRoot'=>'global:global'));
		            $FRONTEND->messageToUser('ERROR','backgroundNotReadable','IMGPATH~'.$this->MAINCFG->getValue('paths', 'map').$this->image);
				}
				return FALSE;
			}
		} else {
			return FALSE;
		}
	}
	
    /**
	 * Checks for writeable map image file
	 *
	 * @param	Boolean $printErr
	 * @return	Boolean	Is Successful?
	 * @author 	Lars Michelsen <larsi@nagios-wiki.de>
     */
	function checkMapImageWriteable($printErr) {
		if($this->image != '') {
			//FIXME: is_writable doesn't check write permissions
			if($this->checkMapImageExists($printErr) /*&& is_writable($this->MAINCFG->getValue('paths', 'map').$this->image)*/) {
				return TRUE;
			} else {
				if($printErr == 1) {
					//Error Box
					$FRONTEND = new GlobalPage($this->MAINCFG,Array('languageRoot'=>'global:global'));
		            $FRONTEND->messageToUser('ERROR','backgroundNotWriteable','IMGPATH~'.$this->MAINCFG->getValue('paths', 'map').$this->image);
				}
				return FALSE;
			}
		} else {
			return FALSE;
		}
	}
	
	/**
	 * Checks for existing config file
	 *
	 * @param	Boolean $printErr
	 * @return	Boolean	Is Successful?
	 * @author 	Lars Michelsen <larsi@nagios-wiki.de>
     */
	function checkMapConfigExists($printErr) {
		if($this->name != '') {
			if(file_exists($this->MAINCFG->getValue('paths', 'mapcfg').$this->name.".cfg")) {
				return TRUE;
			} else {
				if($printErr == 1) {
					$FRONTEND = new GlobalPage($this,Array('languageRoot'=>'global:global'));
		            $FRONTEND->messageToUser('ERROR','mapCfgNotExists','MAP~'.$this->MAINCFG->getValue('paths', 'mapcfg').$this->name.'.cfg');
				}
				return FALSE;
			}
		} else {
			return FALSE;
		}
	}
	
	/**
	 * Checks for readable config file
	 *
	 * @param	Boolean $printErr
	 * @return	Boolean	Is Successful?
	 * @author 	Lars Michelsen <larsi@nagios-wiki.de>
     */
	function checkMapConfigReadable($printErr) {
		if($this->name != '') {
			if($this->checkMapConfigExists($printErr) && is_readable($this->MAINCFG->getValue('paths', 'mapcfg').$this->name.'.cfg')) {
				return TRUE;
			} else {
				if($printErr == 1) {
					//Error Box
					$FRONTEND = new GlobalPage($this->MAINCFG,Array('languageRoot'=>'global:global'));
		            $FRONTEND->messageToUser('ERROR','mapCfgNotReadable','MAP='.$this->MAINCFG->getValue('paths', 'mapcfg').$this->name.'.cfg');
				}
				return FALSE;
			}
		} else {
			return FALSE;
		}
	}
	
	/**
	 * Checks for writeable map image file
	 *
	 * @param	Boolean $printErr
	 * @return	Boolean	Is Successful?
	 * @author 	Lars Michelsen <larsi@nagios-wiki.de>
     */
	function checkMapConfigWriteable($printErr) {
		if($this->checkMapConfigExists($printErr) && is_writeable($this->MAINCFG->getValue('paths', 'mapcfg').$this->name.".cfg")) {
			return TRUE;
		} else {
			if($printErr == 1) {
				//Error Box
				$FRONTEND = new GlobalPage($this->MAINCFG,Array('languageRoot'=>'global:global'));
	            $FRONTEND->messageToUser('ERROR','mapCfgNotWriteable','MAP~'.$this->MAINCFG->getValue('paths', 'mapcfg').$this->name.'.cfg');
			}
			return FALSE;
		}
	}
	
	/**
	 * Checks if the config file is valid
	 *
	 * @param	Boolean $printErr
	 * @return	Boolean	Is Successful?
	 * @author 	Lars Michelsen <larsi@nagios-wiki.de>
     */
	function checkMapConfigIsValid($printErr) {
		foreach($this->mapConfig AS $type => $elements) {
			if(array_key_exists($type,$this->validConfig)) {
				// loop elemtents of type
				foreach($elements AS $id => $element) {
					// loop atributes of element
					foreach($element AS $key => $val) {
						// check for valid atributes - TODO: check valid values
						if(!array_key_exists($key,$this->validConfig[$type])) {
							// unknown atribute
							if($printErr == 1) {
								//Error Box
								$FRONTEND = new GlobalPage($this->MAINCFG,Array('languageRoot'=>'global:global'));
					            $FRONTEND->messageToUser('ERROR','unknownAttribute','ATTRIBUTE~'.$key.',TYPE~'.$type);
							}
							return FALSE;
						}
					}
				}	
			} else {
				// unknown type
				if($printErr == 1) {
					//Error Box
					$FRONTEND = new GlobalPage($this->MAINCFG,Array('languageRoot'=>'global:global'));
		            $FRONTEND->messageToUser('ERROR','unknownObject','TYPE~'.$type);
				}
				return FALSE;
			}
		}
		return TRUE;
	}
	
    /**
	 * Gets all definitions of type $type
	 *
	 * @param	String	$type
	 * @return	Array	All elements of this type
	 * @author 	Lars Michelsen <larsi@nagios-wiki.de>
     */
	function getDefinitions($type) {
		if(count($this->mapConfig[$type]) > 0) {
			return $this->mapConfig[$type];
		} else {
			return Array();
		}
	}
	
    /**
	 * Deletes an element of the specified type to the config array
	 *
	 * @param	String	$type
	 * @param	Integer	$id
	 * @return	Boolean	TRUE
	 * @author 	Lars Michelsen <larsi@nagios-wiki.de>
     */
	function deleteElement($type,$id) {
		$this->mapConfig[$type][$id] = '';
		
		return TRUE;
	}
	
    /**
	 * Adds an element of the specified type to the config array
	 *
	 * @param	String	$type
	 * @param	Array	$properties
	 * @return	Integer	Id of the Element
	 * @author 	Lars Michelsen <larsi@nagios-wiki.de>
     */
	function addElement($type,$properties) {
		//$elementId = (count($this->getDefinitions($type))+1);
		$this->mapConfig[$type][] = $properties;
		
		return count($this->mapConfig[$type])-1;
	}
	
    /**
	 * Sets a config value in the array
	 *
	 * @param	String	$type
	 * @param	Integer	$id
	 * @param	String	$key
	 * @param	String	$value
	 * @return	Boolean	TRUE
	 * @author 	Lars Michelsen <larsi@nagios-wiki.de>
     */
	function setValue($type, $id, $key, $value) {
       $this->mapConfig[$type][$id][$key] = $value;
       return TRUE;
	}
	
    /**
	 * Sets a config value in the array
	 *
	 * @param	String	$type
	 * @param	Integer	$id
	 * @param	String	$key
	 * @return	String	Value
	 * @author 	Lars Michelsen <larsi@nagios-wiki.de>
     */
	function getValue($type, $id, $key) {
		return $this->mapConfig[$type][$id][$key];
	}
	
    /**
	 * Gets the mapName
	 *
	 * @return	String	MapName
	 * @author 	Lars Michelsen <larsi@nagios-wiki.de>
     */
	function getName() {
		return $this->name;	
	}
	
}
?>
