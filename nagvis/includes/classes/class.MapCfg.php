<?
##########################################################################
##     	                           NagVis                               ##
##               *** Klasse zum verarbeiten der Config ***              ##
##                               Lizenz GPL                             ##
##########################################################################

/**
* This Class handles the NagVis configuration file
*/
class MapCfg {
	var $MAINCFG;
	
	var $name;
	var $image;
	var $mapConfig;
	
	/**
	* Constructor
	*
	* @param config $MAINCFG
	*
	* @author Lars Michelsen <larsi@nagios-wiki.de>
	*/
	function MapCfg($MAINCFG,$name='') {
		$this->MAINCFG = $MAINCFG;
		
		//if no map was given with parameter, search for a map
		if($name == '') {
			$this->name = $this->getMap();
		} else {
			$this->name = $name;
		}
		$this->getImage();
	}
	
	/**
	* Reads which map we should display, primary use
	* the map defined in the url, if there is no map
	* in url, use first entry of "maps" defined in 
	* the NagVis main config
	*
	* @author Lars Michelsen <larsi@nagios-wiki.de>
    */
	function getMap() {
		$arr = explode(',',$this->MAINCFG->getValue('global', 'maps'));
	    return $arr[0];
	}
	
	/**
	* Reads which map image we should use
	*
	* @author Lars Michelsen <larsi@nagios-wiki.de>
    */
	function getImage() {
		//FIXME: there is only one image in the value, why this?
		$map_image_array = explode(",",trim($this->getValue('global', '', 'map_image')));
		return $this->image = $map_image_array[0];
	}
	
	/**
	* Reads the map config file (copied from readFile->readNagVisCfg())
	*
	* @author Lars Michelsen <larsi@nagios-wiki.de>
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
									if(ereg("name", $entry[0])) {
										$entry[0] = "name";
									}
									
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
				return TRUE;
			} else {
				return FALSE;	
			}
		} else {
			return FALSE;
		}
	}
	
	/**
	* Writes the config file completly from array $mapConfig
	*
	* @author Lars Michelsen <larsi@nagios-wiki.de>
    */
	function writeConfig() {
		//FIXME
	}
	
	/**
	* Writes a value to the map config file. If this value 
	* doesn't exists in the definition, it will be created
	* at the end of it.
	*
	* @author Lars Michelsen <larsi@nagios-wiki.de>
    */
	function writeValue($type,$name,$key) {
		if($this->checkMapConfigReadable(1) && $this->checkMapConfigWriteable(1)) {
			// read file in array
			$file = file($this->MAINCFG->getValue('paths', 'mapcfg').$this->name.".cfg");
			
			$createArray = array("allowed_user","allowed_for_config");
			$l = 0;
			$a = 0;
					
			while(isset($file[$l]) && $file[$l] != "") {
				if(!ereg("^#",$file[$l]) && !ereg("^;",$file[$l])) {
					$defineCln = explode("{", $file[$l]);
					$define = explode(" ",$defineCln[0]);
					if (isset($define[1]) && trim($define[1]) == $type) {
						$l++;
						
						$cfgName = '';
						$cfgLineNr = 0;
						$cfgLine = '';
						while (trim($file[$l]) != "}" || ($cfgName == '' && $cfgLine == '')) {
							$entry = explode("=",$file[$l], 2);
							
							if(($type == 'service' && $entry[0] == 'service_description') || ereg("name", $entry[0])) {
								$cfgName = trim($entry[1]);
							}
							if($key == trim($entry[0])) {
								$cfgLineNr = $l+1;
								$cfgLine = $key.'='.$this->getValue($type, $name, $key);
							}
							
							$l++;	
						}
						if($cfgName == $name) {
							if($cfgLine != '' && $cfgLineNr != 0) {
								$file[$cfgLineNr-1] = $cfgLine."\n";
							} else {
								//FIXME: insert element at line $l+1, don't replace anything
								$tmp = $cfgLine;
								for($i=$l+1; $i < count($file); $i++) {
									$tmp = $file[$i];
									$file[$i] = $tmp;
								}
							}
							print_r($file);
							
							// open file for writing and replace it
						 	$fp = fopen($this->MAINCFG->getValue('paths', 'mapcfg').$this->name.".cfg","w");
						 	fwrite($fp,implode("",$file));
						 	fclose($fp);
						 	
							return TRUE;
						}
					}
				}
				$l++;
			}
			return TRUE;
		} else {
			return FALSE;
		} 
	}
	
	/**
	* Checks for readable map image file
	*
	* @param string $printErr
	*
	* @author Lars Michelsen <larsi@nagios-wiki.de>
    */
	function checkMapImageReadable($printErr) {
		if($this->name != '') {
			if(file_exists($this->MAINCFG->getValue('paths', 'map').$this->image) && is_readable($this->MAINCFG->getValue('paths', 'map').$this->image)) {
				return TRUE;
			} else {
				if($printErr == 1) {
					$FRONTEND = new frontend($this->MAINCFG,$this->MAPCFG);
					$FRONTEND->openSite($rotateUrl);
					$FRONTEND->messageBox("3", "MAPPATH~".$this->MAINCFG->getValue('paths', 'map').$this->image);
					$FRONTEND->closeSite();
					$FRONTEND->printSite();
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
	* @param string $printErr
	*
	* @author Lars Michelsen <larsi@nagios-wiki.de>
    */
	function checkMapConfigReadable($printErr) {
		if($this->name != '') {
			if(file_exists($this->MAINCFG->getValue('paths', 'mapcfg').$this->name.".cfg") && is_readable($this->MAINCFG->getValue('paths', 'mapcfg').$this->name.".cfg")) {
				return TRUE;
			} else {
				if($printErr == 1) {
					$FRONTEND = new frontend($this->MAINCFG,$this->MAPCFG);
					$FRONTEND->openSite($rotateUrl);
					$FRONTEND->messageBox("2", "MAP~".$this->MAINCFG->getValue('paths', 'mapcfg').$map.".cfg");
					$FRONTEND->closeSite();
					$FRONTEND->printSite();
				}
				return FALSE;
			}
		} else {
			return FALSE;
		}
	}
	
	/**
	* Checks for writeable config file
	*
	* @author Lars Michelsen <larsi@nagios-wiki.de>
    */
	function checkMapConfigWriteable($printErr) {
		if(file_exists($this->MAINCFG->getValue('paths', 'mapcfg').$this->name.".cfg") && is_writeable($this->MAINCFG->getValue('paths', 'mapcfg').$this->name.".cfg")) {
			return TRUE;
		} else {
			if($printErr == 1) {
				$FRONTEND = new frontend($this->MAINCFG,$this->MAPCFG);
				$FRONTEND->openSite($rotateUrl);
				$FRONTEND->messageBox("17", "MAP~".$this->MAINCFG->getValue('paths', 'mapcfg').$map.".cfg");
				$FRONTEND->closeSite();
				$FRONTEND->printSite();
			}
			return FALSE;
		}
	}
	
	/**
	* Gets all definitions of type $type
	*
	* @param string $type
	*
	* @author Lars Michelsen <larsi@nagios-wiki.de>
    */
	function getDefinitions($type) {
		if(count($this->mapConfig[$type]) > 0) {
			return $this->mapConfig[$type];
		} else {
			return Array();
		}
	}
	
	/**
	* Sets a config value in the array
	*
	* @param string $type
	* @param string $name
	* @param string $key
	* @param string $value
	*
	* @author Lars Michelsen <larsi@nagios-wiki.de>
    */
	function setValue($type, $name, $key, $value) {
       if($type == 'global') {
			$this->mapConfig['global'][0][$key] = $value;
			return TRUE;
		} else {
			foreach($this->mapConfig[$type] AS $var => $val) {
				if(($type == 'service' && $val['service_description'] == $name) || $val['name'] == $name) {
					$this->mapConfig[$type][$var][$key] = $value;
					return TRUE;	
				}
			}
			return FALSE;
		}
	}
	
	/**
	* Gets a config value from the array
	*
	* @param string $type
	* @param string $name
	* @param string $key
	*
	* @author Lars Michelsen <larsi@nagios-wiki.de>
    */
	function getValue($type, $name, $key) {
		if($type == 'global') {
			return $this->mapConfig['global'][0][$key];
		} else {
			foreach($this->mapConfig[$type] AS $var => $val) {
				if(($type == 'service' && $val['service_description'] == $name) || $val['name'] == $name) {
					return $val[$key];	
				}
			}
			
			return FALSE;
		}
	}
	
	/**
	* Getter for $name
	*
	* @author Lars Michelsen <larsi@nagios-wiki.de>
    */
	function getName() {
		return $this->name;	
	}
	
}
?>
