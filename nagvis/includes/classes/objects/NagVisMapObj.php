<?php
/**
 * Class of a map objects in Nagios with all necessary informations
 */
class NagVisMapObj extends NagVisStatefulObject {
	var $MAINCFG;
	var $MAPCFG;
	var $MAP;
	var $BACKEND;
	var $LANG;
	
	var $objects;
	var $linkedMaps;
	
	var $map_name;
	var $alias;
	
	var $state;
	var $output;
	var $problem_has_been_acknowledged;
	
	var $summary_state;
	var $summary_output;
	var $summary_problem_has_been_acknowledged;
	
	function NagVisMapObj(&$MAINCFG, &$BACKEND, &$LANG, &$MAPCFG) {
		$this->MAINCFG = &$MAINCFG;
		$this->MAPCFG = &$MAPCFG;
		$this->BACKEND = &$BACKEND;
		$this->LANG = &$LANG;
		
		$this->map_name = $this->MAPCFG->getName();
		$this->type = 'map';
		$this->iconset = 'std_medium';
		$this->objects = Array();
		$this->linkedMaps = Array();
		
		$this->state = '';
		$this->summary_state = '';
		$this->has_been_acknowledged = 0;
		
		parent::NagVisStatefulObject($this->MAINCFG, $this->BACKEND, $this->LANG);
	}
	
	function parse() {
		return parent::parse();
	}
	
	function getMapObjects() {
		return $this->objects;
	}
	
	function fetchState() {
		// Get all Members and states
		$this->fetchMapObjects();
		
		// Also get summary state
		$this->fetchSummaryState();
		
		// At least summary output
		$this->fetchSummaryOutput();
		$this->state = $this->summary_state;
	}
	
	# End public methods
	# #########################################################################
	
	function fetchSummaryOutput() {
		$arrStates = Array('CRITICAL'=>0,'DOWN'=>0,'WARNING'=>0,'UNKNOWN'=>0,'UP'=>0,'OK'=>0,'ERROR'=>0,'ACK'=>0,'PENDING'=>0);
		$output = '';
		
		// FIXME: Get summary state of this and child objects
		foreach($this->objects AS $OBJ) {
			if(method_exists($OBJ,'getSummaryState') && $OBJ->getSummaryState() != '') {
				$arrStates[$OBJ->getSummaryState()]++;
			}
		}
		
		// FIXME: LANGUAGE
		$this->summary_output = 'There are '.($arrStates['DOWN']+$arrStates['CRITICAL']).' DOWN/CRTICAL, '.$arrStates['WARNING'].' WARNING, '.$arrStates['UNKNOWN'].' UNKNOWN and '.($arrStates['UP']+$arrStates['OK']).' UP/OK objects';
	}
	
	/**
	 * Gets all objects of the map
	 *
	 * @param	Boolean	$getState	With state?
	 * @return	Array	Array of Objects of this map
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	function fetchMapObjects($getState=1,$mergeWithGlobals=1) {
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method GlobalMap::getMapObjects('.$getState.','.$mergeWithGlobals.')');
		
		foreach($this->MAPCFG->validConfig AS $type => $arr) {
			if($type != 'global' && is_array($objs = $this->MAPCFG->getDefinitions($type))){
				foreach($objs AS $index => $objConf) {
					if (DEBUG&&DEBUGLEVEL&2) debug('Start object of type: '.$type);
					// workaround
					$objConf['id'] = $index;
					
					if($mergeWithGlobals) {
						// merge with "global" settings
						foreach($this->MAPCFG->validConfig[$type] AS $key => $values) {
							if((!isset($objConf[$key]) || $objConf[$key] == '') && isset($values['default'])) {
								$objConf[$key] = $values['default'];
							}
						}
					}
					
					switch($type) {
						case 'host':
							$OBJ = new NagVisHost($this->MAINCFG, $this->BACKEND, $this->LANG, $objConf['backend_id'], $objConf['host_name']);
						break;
						case 'service':
							$OBJ = new NagVisService($this->MAINCFG, $this->BACKEND, $this->LANG, $objConf['backend_id'], $objConf['host_name'], $objConf['service_description']);
						break;
						case 'hostgroup':
							$OBJ = new NagVisHostgroup($this->MAINCFG, $this->BACKEND, $this->LANG, $objConf['backend_id'], $objConf['hostgroup_name']);
						break;
						case 'servicegroup':
							$OBJ = new NagVisServicegroup($this->MAINCFG, $this->BACKEND, $this->LANG, $objConf['backend_id'], $objConf['servicegroup_name']);
						break;
						case 'map':
							$SUBMAPCFG = new NagVisMapCfg($this->MAINCFG,$objConf['map_name']);
							$SUBMAPCFG->readMapConfig();
							$OBJ = new NagVisMapObj($this->MAINCFG, $this->BACKEND, $this->LANG, $SUBMAPCFG);
						break;
						case 'shape':
							$OBJ = new NagVisShape($this->MAINCFG, $this->BACKEND, $this->LANG, $objConf['icon']);
						break;
						case 'textbox':
							$OBJ = new NagVisTextbox($this->MAINCFG, $this->BACKEND, $this->LANG);
						break;
						default:
							//FIXME: Unhandled
							echo 'Unhandled: '.$type;
						break;
					}
					
					$OBJ->setConfiguration($objConf);
					
					// Before getting state of maps we have to check if there is a loop in the maps
					if(get_class($OBJ) != 'NagVisMapObj' || (get_class($OBJ) == 'NagVisMapObj' && $this->checkLoop($OBJ))) {
						if($getState && ($OBJ->type != 'textbox' && $OBJ->type != 'shape')) {
							$OBJ->fetchState();
						}
					}
					
					$OBJ->fetchIcon();
					
					$this->objects[] = $OBJ;
					
					if (DEBUG&&DEBUGLEVEL&2) debug('End object of type: '.$type);
				}
				
				if (DEBUG&&DEBUGLEVEL&1) debug('End method GlobalMap::getObjectsOfType(): Array(...)');
			}
		}
		
		if (DEBUG&&DEBUGLEVEL&1) debug('End method GlobalMap::getMapObjects(): Array(...)');
	}
	
	function checkLoop(&$OBJ) {
		// prevent direct loops (map including itselfes as map icon)
		if($this->MAPCFG->getName() == $OBJ->MAPCFG->getName()) {
			$FRONTEND = new GlobalPage($this->MAINCFG,Array('languageRoot'=>'global:global'));
			$FRONTEND->messageToUser('WARNING','loopInMapRecursion');
			
			$OBJ->summary_state = 'UNKNOWN';
			$OBJ->summary_output = $this->LANG->getMessageText('loopInMapRecursion');
			
			return FALSE;
		} else {
			// No direct loop, now check the harder one: indirect loop
			// Also check for permissions to view the state of the map
			
			// Start a new map wihtout getting the state
			$SUBMAP = new NagVisMap($this->MAINCFG, $OBJ->MAPCFG, $this->LANG, $this->BACKEND, FALSE);
			if($SUBMAP->checkPermissions($OBJ->MAPCFG->getValue('global',0, 'allowed_user'), FALSE)) {
				
				// Loop all objects on the child map to find out if there is a link back to this map (loop)
				foreach($OBJ->MAPCFG->getDefinitions('map') AS $arrChildMap) {
					if($this->MAPCFG->getName() == $arrChildMap['map_name']) {
						$FRONTEND = new GlobalPage($this->MAINCFG,Array('languageRoot'=>'global:global'));
						$FRONTEND->messageToUser('WARNING','loopInMapRecursion');
						
						$LANG = new GlobalLanguage($this->MAINCFG,'global:global');
						$OBJ->summary_state = 'UNKNOWN';
						$OBJ->summary_output = $this->LANG->getMessageText('loopInMapRecursion');
						
						return FALSE;
					} else {
						//$OBJ->fetchMapObjects();
						return TRUE;
					}
				}
				
				// This is just a fallback if the above loop is not looped when there
				// are no child maps on this map
				return TRUE;
			} else {
				// FIXME: Language entry
				$OBJ->summary_state = 'UNKNOWN';
				$OBJ->summary_output = 'Error: You are not permited to view the state of this map.';
				
				return FALSE;
			}
		}
	}
	
	function fetchIcon() {
		if($this->getSummaryState() != '') {
			$stateLow = strtolower($this->getSummaryState());
			
			switch($stateLow) {
				case 'unreachable':
				case 'down':
					if($this->getSummaryAcknowledgement() == 1) {
						$icon = $this->iconset.'_ack.png';
					} else {
						$icon = $this->iconset.'_'.$stateLow.'.png';
					}
				break;
				case 'critical':
				case 'warning':
					if($this->getSummaryAcknowledgement() == 1) {
						$icon = $this->iconset.'_sack.png';
					} else {
						$icon = $this->iconset.'_'.$stateLow.'.png';
					}
				break;
				case 'up':
				case 'ok':
					$icon = $this->iconset.'_up.png';
				break;
				case 'unknown':
					$icon = $this->iconset.'_'.$stateLow.'.png';
				break;
				default:
					$icon = $this->iconset.'_error.png';
				break;
			}
			
			//Checks whether the needed file exists
			if(@fclose(@fopen($this->MAINCFG->getValue('paths', 'icon').$icon,'r'))) {
				$this->icon = $icon;
			} else {
				$this->icon = $this->iconset.'_error.png';
			}
		} else {
			$this->icon = $this->iconset.'_error.png';
		}
	}
	
	/**
	 * Creates a link to Nagios, when this is not set in the Config-File
	 *
	 * @return	String	The Link
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	function createLink() {
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method NagVisMap::createLink(&$obj)');
		
		if(isset($this->url) && $this->url != '') {
			$link = parent::createLink();
		} else {
			$link = '<a href="'.$this->MAINCFG->getValue('paths', 'htmlbase').'/index.php?map='.$this->map_name.'" target="'.$this->url_target.'">';
		}
		if (DEBUG&&DEBUGLEVEL&1) debug('End method NagVisMap::createLink(): '.$link);
		return $link;
	}
	
	function fetchSummaryState() {
		// Get summary state member objects
		foreach($this->objects AS $OBJ) {
			if(method_exists($OBJ,'getSummaryState')) {
				$this->wrapChildState($OBJ);
			}
		}
	}
}
?>
