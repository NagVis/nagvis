<?php
/*****************************************************************************
 *
 * NagVisStatefulObject.php - Abstract class of a stateful object in NagVis
 *                  with all necessary information which belong to the object
 *                  handling in NagVis
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
class NagVisStatefulObject extends NagVisObject {
	protected $BACKEND;
	private $GRAPHIC;
	
	protected static $arrStates = Array('UNREACHABLE' => 6, 
	                                  'DOWN' => 5, 
	                                  'CRITICAL' => 5, 
	                                  'WARNING' => 4, 
	                                  'UNKNOWN' => 3, 
	                                  'ERROR' => 2, 
	                                  'UP' => 1, 
	                                  'OK' => 1, 
	                                  'PENDING' => 0);
	
	// "Global" Configuration variables for all stateful objects
	protected $backend_id;
	
	protected $iconset;
	
	protected $label_show;
	protected $recognize_services;
	protected $only_hard_states;
	
	protected $state;
	protected $output;
	protected $summary_state;
	protected $summary_output;
	protected $summary_problem_has_been_acknowledged;
	protected $summary_in_downtime;
	protected $problem_has_been_acknowledged;
	
	protected $iconPath;
	protected $iconHtmlPath;
	
	protected $dateFormat;
	
	/**
	 * Class constructor
	 *
	 * @param		Object 		Object of class GlobalMainCfg
	 * @param		Object 		Object of class GlobalBackendMgmt
	 * @param		Object 		Object of class GlobalLanguage
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	public function __construct($CORE, $BACKEND) {
		$this->BACKEND = $BACKEND;
		$this->GRAPHIC = '';
		
		$this->state = '';
		$this->output = '';
		$this->problem_has_been_acknowledged = 0;
		$this->in_downtime = 0;
		
		$this->summary_state = '';
		$this->summary_problem_has_been_acknowledged = 0;
		$this->summary_in_downtime = 0;
		
		parent::__construct($CORE);
	}

	/**
	 * PUBLIC getStateRelevantMembers
	 *
	 * This is a wrapper function. When not implemented by the specific
	 * object it only calls the getMembers() function. It is useful to
	 * exclude uninteresting objects on maps.
	 *
	 * @return  Array  Array of child objects
	 * @author  Lars Michelsen <lars@vertical-visions.de>
	 */
	public function getStateRelevantMembers() {
		return $this->getMembers();
	}
	
	/**
	 * PUBLIC getInDowntime()
	 *
	 * Get method for the in downtime option
	 *
	 * @return	Boolean		True: object is in downtime, False: not in downtime
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	public function getInDowntime() {
		return $this->in_downtime;
	}
	
	/**
	 * PUBLIC getDowntimeAuthor()
	 *
	 * Get method for the in downtime author
	 *
	 * @return	String		The username of the downtime author
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	public function getDowntimeAuthor() {
		return $this->downtime_author;
	}
	
	/**
	 * PUBLIC getDowntimeData()
	 *
	 * Get method for the in downtime data
	 *
	 * @return	String		The downtime data
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	public function getDowntimeData() {
		return $this->downtime_data;
	}
	
	/**
	 * PUBLIC getDowntimeStart()
	 *
	 * Get method for the in downtime start time
	 *
	 * @return	String		The formated downtime start time
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	public function getDowntimeStart() {
		if(isset($this->in_downtime) && $this->in_downtime == 1) {
			if($this->dateFormat == '') {
				$this->dateFormat = $this->CORE->MAINCFG->getValue('global','dateformat');
			}
			
			return date($this->dateFormat, $this->downtime_start);
		} else {
			return 'N/A';
		}
	}
	
	/**
	 * PUBLIC getDowntimeEnd()
	 *
	 * Get method for the in downtime end time
	 *
	 * @return	String		The formated downtime end time
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	public function getDowntimeEnd() {
		if(isset($this->in_downtime) && $this->in_downtime == 1) {
			if($this->dateFormat == '') {
				$this->dateFormat = $this->CORE->MAINCFG->getValue('global','dateformat');
			}
			
			return date($this->dateFormat, $this->downtime_end);
		} else {
			return 'N/A';
		}
	}
	
	/**
	 * PUBLIC getInDowntime()
	 *
	 * Get method for the in downtime option
	 *
	 * @return	Boolean		True: object is in downtime, False: not in downtime
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	public function getSummaryInDowntime() {
		return $this->summary_in_downtime;
	}
	
	/**
	 * PUBLIC getOnlyHardStates()
	 *
	 * Get method for the only hard states option
	 *
	 * @return	Boolean		True: Only hard states, False: Not only hard states
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	public function getOnlyHardStates() {
		return $this->only_hard_states;
	}
	
	/**
	 * PUBLIC getRecognizeServices()
	 *
	 * Get method for the recognize services option
	 *
	 * @return	Boolean		True: Recognize service states, False: Not recognize service states
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	public function getRecognizeServices() {
		return $this->recognize_services;
	}
	
	/**
	 * PUBLIC getState()
	 *
	 * Get method for the state of this object
	 *
	 * @return	String		State of the object
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	public function getState() {
		return $this->state;
	}
	
	/**
	 * PUBLIC getOutput()
	 *
	 * Get method for the output of this object
	 *
	 * @return	String		Output of the object
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	public function getOutput() {
		return $this->output;
	}
	
	/**
	 * PUBLIC getAcknowledgement()
	 *
	 * Get method for the acknowledgement state of this object
	 *
	 * @return	Boolean		True: Acknowledged, False: Not Acknowledged
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	public function getAcknowledgement() {
		return $this->problem_has_been_acknowledged;
	}
	
	/**
	 * PUBLIC getSummaryState()
	 *
	 * Get method for the summary state of this object and members/childs
	 *
	 * @return	String		Summary state
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	public function getSummaryState() {
		return $this->summary_state;
	}
	
	/**
	 * PUBLIC getSummaryOutput()
	 *
	 * Get method for the summary output of this object and members/childs
	 *
	 * @return	String		Summary output
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	public function getSummaryOutput() {
		return $this->summary_output;
	}
	
	/**
	 * PUBLIC getSummaryAcknowledgement()
	 *
	 * Get method for the acknowledgement state of this object and members/childs
	 *
	 * @return	Boolean		True: Acknowledged, False: Not Acknowledged
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	public function getSummaryAcknowledgement() {
		return $this->summary_problem_has_been_acknowledged;
	}
	
	/**
	 * PUBLIC getStateDuration()
	 *
	 * Get method for the duration of the current state
	 *
	 * @return	String		Time in the configured format
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	public function getStateDuration() {
		if(isset($this->last_state_change) && $this->last_state_change != '0') {
			if($this->dateFormat == '') {
				$this->dateFormat = $this->CORE->MAINCFG->getValue('global','dateformat');
			}
			
			return date($this->dateFormat, ($_SERVER['REQUEST_TIME'] - $this->last_state_change));
		} else {
			return 'N/A';
		}
	}
	
	/**
	 * PUBLIC getLastStateChange()
	 *
	 * Get method for the last state change
	 *
	 * @return	String		Time in the configured format
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	public function getLastStateChange() {
		if(isset($this->last_state_change) && $this->last_state_change != '0') {
			if($this->dateFormat == '') {
				$this->dateFormat = $this->CORE->MAINCFG->getValue('global','dateformat');
			}
			
			return date($this->dateFormat, $this->last_state_change);
		} else {
			return 'N/A';
		}
	}
	
	/**
	 * PUBLIC getLastHardStateChange()
	 *
	 * Get method for the last hard state change
	 *
	 * @return	String		Time in the configured format
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	public function getLastHardStateChange() {
		if(isset($this->last_hard_state_change) && $this->last_hard_state_change != '0') {
			if($this->dateFormat == '') {
				$this->dateFormat = $this->CORE->MAINCFG->getValue('global','dateformat');
			}
			
			return date($this->dateFormat, $this->last_hard_state_change);
		} else {
			return 'N/A';
		}
	}
	
	/**
	 * PUBLIC getLastCheck()
	 *
	 * Get method for the time of the last check
	 *
	 * @return	String		Time in the configured format
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	public function getLastCheck() {
		if(isset($this->last_check) && $this->last_check != '0') {
			if($this->dateFormat == '') {
				$this->dateFormat = $this->CORE->MAINCFG->getValue('global','dateformat');
			}
			
			return date($this->dateFormat, $this->last_check);
		} else {
			return 'N/A';
		}
	}
	
	/**
	 * PUBLIC getNextCheck()
	 *
	 * Get method for the time of the next check
	 *
	 * @return	String		Time in the configured format
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	public function getNextCheck() {
		if(isset($this->next_check) && $this->next_check != '0') {
			if($this->dateFormat == '') {
				$this->dateFormat = $this->CORE->MAINCFG->getValue('global','dateformat');
			}
			
			return date($this->dateFormat, $this->next_check);
		} else {
			return 'N/A';
		}
	}
	
	/**
	 * PUBLIC getStateType()
	 *
	 * Get method for the type of the current state
	 *
	 * @return	String		Type of state (HARD/SOFT)
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	public function getStateType() {
		if(isset($this->state_type) && $this->state_type != '') {
			$stateTypes = Array(0 => 'SOFT', 1 => 'HARD');
			return $stateTypes[$this->state_type];
		} else {
			return 'N/A';
		}
	}
	
	/**
	 * PUBLIC getCurrentCheckAttempt()
	 *
	 * Get method for the current check attempt
	 *
	 * @return	Integer		Current check attempt
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	public function getCurrentCheckAttempt() {
		if(isset($this->current_check_attempt) && $this->current_check_attempt != '') {
			return $this->current_check_attempt;
		} else {
			return '';
		}
	}
	
	/**
	 * PUBLIC getMaxCheckAttempts()
	 *
	 * Get method for the maximum check attempt
	 *
	 * @return	Integer		maximum check attempts
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	public function getMaxCheckAttempts() {
		if(isset($this->max_check_attempts) && $this->max_check_attempts != '') {
			return $this->max_check_attempts;
		} else {
			return '';
		}
	}
	
	/**
	 * PUBLIC getObjectStateInformations()
	 *
	 * Gets the state information of the object
	 *
	 * @return	Array		Object configuration
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	public function getObjectStateInformations($bFetchChilds=true) {
		$arr = Array();
		
		/* FIXME: These are no state informations - don't send them
		if(isset($this->alias) && $this->alias != '') {
			$arr['alias'] = $this->alias;
		} else {
			$arr['alias'] = '';
		}
		
		if(isset($this->display_name) && $this->display_name != '') {
			$arr['display_name'] = $this->display_name;
		} else {
			$arr['display_name'] = '';
		}
		
		// Save the number of members
		switch($this->getType()) {
			case 'host':
			case 'map':
			case 'hostgroup':
			case 'servicegroup':
				$arr['num_members'] = $this->getNumMembers();
			break;
		}*/
		
		$arr['state'] = $this->getState();
		$arr['summary_state'] = $this->getSummaryState();
		$arr['summary_problem_has_been_acknowledged'] = $this->getSummaryAcknowledgement();
		$arr['problem_has_been_acknowledged'] = $this->getAcknowledgement();
		$arr['summary_in_downtime'] = $this->getSummaryInDowntime();
		$arr['in_downtime'] = $this->getInDowntime();
		
		$arr['output'] = strtr($this->output, Array("\r" => '<br />', "\n" => '<br />', '"' => '&quot;', '\'' => '&#145;'));
		$arr['summary_output'] = strtr($this->getSummaryOutput(), Array("\r" => '<br />', "\n" => '<br />', '"' => '&quot;', '\'' => '&#145;'));
		
		// Macros which are only for services and hosts
		if($this->type == 'host' || $this->type == 'service') {
			$arr['downtime_author'] = $this->downtime_author;
			$arr['downtime_data'] = $this->downtime_data;
			$arr['downtime_end'] = $this->downtime_end;
			$arr['downtime_start'] = $this->downtime_start;
			$arr['last_check'] = $this->getLastCheck();
			$arr['next_check'] = $this->getNextCheck();
			$arr['state_type'] = $this->getStateType();
			$arr['current_check_attempt'] = $this->getCurrentCheckAttempt();
			$arr['max_check_attempts'] = $this->getMaxCheckAttempts();
			$arr['last_state_change'] = $this->getLastStateChange();
			$arr['last_hard_state_change'] = $this->getLastHardStateChange();
			$arr['state_duration'] = $this->getStateDuration();
			$arr['perfdata'] = strtr($this->perfdata, Array("\r" => '<br />', "\n" => '<br />', '"' => '&quot;', '\'' => '&#145;'));
		}
		
		// Enable/Disable fetching children
		if($bFetchChilds && $this->getNumMembers() > 0) {
			$arr['members'] = $this->getSortedObjectMembers();
		}
		
		return $arr;
	}
	
	/**
	 * PUBLIC parseJson()
	 *
	 * Parses the object in json format
	 *
	 * @return	String  JSON code of the object
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	public function parseJson() {
		// Get the correct url
		$this->url = $this->getUrl();
		
		// When this is a gadget parse the url
		if($this->view_type == 'gadget') {
			$this->parseGadgetUrl();
		}
		
		// Get all information of the object (configuration, state, ...)
		return $this->getObjectInformation();
	}
	
	/**
	 * PUBLIC fetchIcon()
	 *
	 * Fetches the icon for the object depending on the summary state
	 *
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	public function fetchIcon() {
		// Set the paths of this iconset
		$this->iconPath = $this->CORE->MAINCFG->getValue('paths', 'icon');
		$this->iconHtmlPath = $this->CORE->MAINCFG->getValue('paths', 'htmlicon');
		
		// Read the filetype of the iconset
		$fileType = $this->CORE->getIconsetFiletype($this->iconset);
		
		if($this->getSummaryState() != '') {
			$stateLow = strtolower($this->getSummaryState());
			
			switch($stateLow) {
				case 'unknown':
				case 'unreachable':
				case 'down':
					if($this->getSummaryAcknowledgement() == 1) {
						$icon = $this->iconset.'_ack.'.$fileType;
					} elseif($this->getSummaryInDowntime() == 1) {
						$icon = $this->iconset.'_downtime.'.$fileType;
					} else {
						// Handle unreachable state with down icon
						if($stateLow == 'unreachable') {
							$stateLow = 'down';
						}
						$icon = $this->iconset.'_'.$stateLow.'.'.$fileType;
					}
				break;
				case 'critical':
				case 'warning':
					if($this->getSummaryAcknowledgement() == 1) {
						$icon = $this->iconset.'_sack.'.$fileType;
					} elseif($this->getSummaryInDowntime() == 1) {
						$icon = $this->iconset.'_sdowntime.'.$fileType;
					} else {
						$icon = $this->iconset.'_'.$stateLow.'.'.$fileType;
					}
				break;
				case 'up':
				case 'ok':
					if($this->getType() == 'service' || $this->getType() == 'servicegroup') {
						$icon = $this->iconset.'_ok.'.$fileType;
					} else {
						$icon = $this->iconset.'_up.'.$fileType;
					}
				break;
				case 'pending':
					$icon = $this->iconset.'_'.$stateLow.'.'.$fileType;
				break;
				default:
					$icon = $this->iconset.'_error.'.$fileType;
				break;
			}
			
			//Checks whether the needed file exists
			if(@file_exists($this->CORE->MAINCFG->getValue('paths', 'icon').$icon)) {
				$this->icon = $icon;
			} else {
				$this->icon = $this->iconset.'_error.'.$fileType;
			}
		} else {
			$this->icon = $this->iconset.'_error.'.$fileType;
		}
	}
	
	# End public methods
	# #########################################################################
	
	/**
	 * RPROTECTED mergeSummaryOutput()
	 *
	 * Merges the summary output from objects and all child objects together
	 *
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	protected function mergeSummaryOutput(&$arrStates, $objLabel) {
		$this->summary_output .= $this->CORE->LANG->getText('childStatesAre').' ';
		foreach($arrStates AS $state => &$num) {
			if($num > 0) {
				$this->summary_output .= $num.' '.$state.', ';
			}
		}
		
		// Remove last comma
		$this->summary_output = preg_replace('/, $/', '', $this->summary_output);
		
		$this->summary_output .= ' '.$objLabel.'.';
	}
	
	/**
	 * PROTECTED wrapChildState()
	 *
	 * Wraps the state of the current object with the given child object
	 *
	 * @param		Object		Object with a state
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	protected function wrapChildState(&$OBJ) {
		$sSummaryState = $this->getSummaryState();
		$sObjSummaryState = $OBJ->getSummaryState();

		// The state of the compare object is empty: Do not compare!
		if($sObjSummaryState == '') {
			echo "NagVisError: Object state in object (".$OBJ->getName().") is empty\n";
			exit(1);
		}
		
		// The state of the item to be compared is invalid
		if(!isset(self::$arrStates[$sObjSummaryState])) {
			echo "NagVisError: Invalid object state \"".$sObjSummaryState."\" in object (".$OBJ->getName().")\n";
			exit(1);
		}
		
		// When this object has no state yet it is not needed to compare anything
		if($sSummaryState != '') {
			/* When the state of the current child is not as good as the current
			 * summary state or the state is equal and the sub-state differs.
			 */
			 if(self::$arrStates[$sSummaryState] < self::$arrStates[$sObjSummaryState] || (self::$arrStates[$sSummaryState] == self::$arrStates[$sObjSummaryState] && ($this->getSummaryAcknowledgement() || $this->getSummaryInDowntime()))) {
				$this->summary_state = $sObjSummaryState;
				
				if($OBJ->getSummaryAcknowledgement() == 1) {
					$this->summary_problem_has_been_acknowledged = 1;
				} else {
					$this->summary_problem_has_been_acknowledged = 0;
				}
				
				if($OBJ->getSummaryInDowntime() == 1) {
					$this->summary_in_downtime = 1;
				} else {
					$this->summary_in_downtime = 0;
				}
			}
		} else {
			$this->summary_state = $sObjSummaryState;
			$this->summary_problem_has_been_acknowledged = $OBJ->getSummaryAcknowledgement();
			$this->summary_in_downtime = $OBJ->getSummaryInDowntime();
		}
	}
}
?>
