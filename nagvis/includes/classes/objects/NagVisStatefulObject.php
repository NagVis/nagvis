<?php
/*****************************************************************************
 *
 * NagVisStatefulObject.php - Abstract class of a stateful object in NagVis
 *                  with all necessary informations which belong to the object
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
	var $MAINCFG;
	var $BACKEND;
	var $LANG;
	var $GRAPHIC;
	
	// "Global" Configuration variables for all stateful objects
	var $iconset;
	
	var $label_show;
	var $recognize_services;
	var $only_hard_states;
	
	var $iconPath;
	var $iconHtmlPath;
	
	var $dateFormat;
	
	/**
	 * Class constructor
	 *
	 * @param		Object 		Object of class GlobalMainCfg
	 * @param		Object 		Object of class GlobalBackendMgmt
	 * @param		Object 		Object of class GlobalLanguage
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	function NagVisStatefulObject(&$CORE, &$BACKEND) {
		$this->CORE = &$CORE;
		$this->MAINCFG = &$CORE->MAINCFG;
		$this->LANG = &$CORE->LANG;
		
		$this->BACKEND = &$BACKEND;
		
		$this->GRAPHIC = '';
		
		parent::NagVisObject($this->CORE);
	}
	
	/**
	 * PUBLIC getObjectId()
	 *
	 * Get method for the object id
	 *
	 * @return	Integer		Object ID
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	function getObjectId() {
		return $this->object_id;
	}
	
	/**
	 * PUBLIC getInDowntime()
	 *
	 * Get method for the in downtime option
	 *
	 * @return	Boolean		True: object is in downtime, False: not in downtime
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	function getInDowntime() {
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
	function getDowntimeAuthor() {
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
	function getDowntimeData() {
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
	function getDowntimeStart() {
		if(isset($this->in_downtime) && $this->in_downtime == 1) {
			if($this->dateFormat == '') {
				$this->dateFormat = $this->MAINCFG->getValue('global','dateformat');
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
	function getDowntimeEnd() {
		if(isset($this->in_downtime) && $this->in_downtime == 1) {
			if($this->dateFormat == '') {
				$this->dateFormat = $this->MAINCFG->getValue('global','dateformat');
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
	function getSummaryInDowntime() {
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
	function getOnlyHardStates() {
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
	function getRecognizeServices() {
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
	function getState() {
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
	function getOutput() {
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
	function getAcknowledgement() {
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
	function getSummaryState() {
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
	function getSummaryOutput() {
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
	function getSummaryAcknowledgement() {
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
	function getStateDuration() {
		if(isset($this->last_state_change) && $this->last_state_change != '0') {
			if($this->dateFormat == '') {
				$this->dateFormat = $this->MAINCFG->getValue('global','dateformat');
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
	function getLastStateChange() {
		if(isset($this->last_state_change) && $this->last_state_change != '0') {
			if($this->dateFormat == '') {
				$this->dateFormat = $this->MAINCFG->getValue('global','dateformat');
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
	function getLastHardStateChange() {
		if(isset($this->last_hard_state_change) && $this->last_hard_state_change != '0') {
			if($this->dateFormat == '') {
				$this->dateFormat = $this->MAINCFG->getValue('global','dateformat');
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
	function getLastCheck() {
		if(isset($this->last_check) && $this->last_check != '0') {
			if($this->dateFormat == '') {
				$this->dateFormat = $this->MAINCFG->getValue('global','dateformat');
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
	function getNextCheck() {
		if(isset($this->next_check) && $this->next_check != '0') {
			if($this->dateFormat == '') {
				$this->dateFormat = $this->MAINCFG->getValue('global','dateformat');
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
	function getStateType() {
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
	function getCurrentCheckAttempt() {
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
	function getMaxCheckAttempts() {
		if(isset($this->max_check_attempts) && $this->max_check_attempts != '') {
			return $this->max_check_attempts;
		} else {
			return '';
		}
	}
	
	/**
	 * PUBLIC parse()
	 *
	 * Parses the object
	 *
	 * @return	String		HTML code of the object
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	function parse() {
		$this->replaceMacros();
		
		// Some specials for lines
		if(isset($this->line_type)) {
			$this->getLineHoverArea();
		}
		
		return $this->parseIcon().$this->parseLabel();
	}
	
	# End public methods
	# #########################################################################
	
	/**
	 * Replaces macros of urls and hover_urls
	 *
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	function replaceMacros() {
		if($this->type == 'service') {
			$name = 'host_name';
		} else {
			$name = $this->type . '_name';
		}
		
		if(isset($this->url) && $this->url != '') {
			$this->url = str_replace('['.$name.']',$this->$name,$this->url);
			if($this->type == 'service') {
				$this->url = str_replace('[service_description]', $this->service_description, $this->url);
			}
		}
		
		if(isset($this->hover_url) && $this->hover_url != '') {
			$this->hover_url = str_replace('[name]',$this->$name, $this->hover_url);
			if($this->type == 'service') {
				$this->hover_url = str_replace('[service_description]', $this->service_description, $this->hover_url);
			}
		}
		
		if(isset($this->label_text) && $this->label_text != '') {
			// For maps use the alias as display string
			if($this->type == 'map') {
				$name = 'alias';   
			}
			
			$this->label_text = str_replace('[name]', $this->$name, $this->label_text);
			$this->label_text = str_replace('[output]',$this->output, $this->label_text);
			if($this->type == 'service') {
				$this->label_text = str_replace('[service_description]', $this->service_description, $this->label_text);
			}
		}
		
		parent::replaceMacros();
		
	}
	
	/**
	 * Calculates the position of the line hover area
	 *
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	function getLineHoverArea() {
		list($xFrom,$xTo) = explode(',', $this->getX());
		list($yFrom,$yTo) = explode(',', $this->getY());
		
		// If the graphic class is not initialized
		if($this->GRAPHIC == '') {
			// Initialize the GlobalGraphic class
			$this->GRAPHIC = new GlobalGraphic();
		}
		
		$this->x = $this->GRAPHIC->middle($xFrom,$xTo) - 10;
		$this->y = $this->GRAPHIC->middle($yFrom,$yTo) - 10;
		$this->icon = '20x20.gif';
	}
	
	/**
	 * Parses the HTML-Code of an icon
	 *
	 * @return	String		HTML code of the icon
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	function parseIcon() {
		if($this->type == 'service') {
			$name = 'host_name';
			$alt = $this->host_name.'-'.$this->service_description;
		} else {
			$alt = $this->{$this->type.'_name'};
		}
		
		$ret = '<div class="icon" style="left:'.$this->x.'px;top:'.$this->y.'px;z-index:'.$this->z.';">';
		$ret .= $this->createLink();
		$ret .= '<img src="'.$this->iconHtmlPath.$this->icon.'" '.$this->getHoverMenu().' alt="'.$this->type.'-'.$alt.'">';
		$ret .= '</a>';
		$ret .= '</div>';
		
		return $ret;
	}
	
	/**
	 * Parses the HTML-Code of a label
	 *
	 * @return	String		HTML code of the label
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	function parseLabel() {
		if(isset($this->label_show) && $this->label_show == '1') {
			if($this->type == 'service') {
				$name = 'host_name';
			} else {
				$name = $this->type . '_name';
			}
			
			// If there is a presign it should be relative to the objects x/y
			if(preg_match('/^(?:\+|\-)/', $this->label_x)) {
				$this->label_x = $this->x + $this->label_x;
			}
			if(preg_match('/^(?:\+|\-)/',$this->label_y)) {
				$this->label_y = $this->y + $this->label_y;
			}
			
			// If no x/y coords set, fallback to object x/y
			if(!isset($this->label_x) || $this->label_x == '' || $this->label_x == 0) {
				$this->label_x = $this->x;
			}
			if(!isset($this->label_y) || $this->label_y == '' || $this->label_y == 0) {
				$this->label_y = $this->y;
			}
			
			if(isset($this->label_width) && $this->label_width != 'auto') {
				$this->label_width .= 'px';	
			}
			
			/**
			 * IE workaround: The transparent for the color is not enough. The border
			 * has really to be hidden.
			 */
			if($this->label_border == 'transparent') {
				$borderStyle = 'border-style:none';
			} else {
				$borderStyle = 'border-style:solid';
			}
			
			$ret  = '<div class="object_label" style="background:'.$this->label_background.';'.$borderStyle.';border-color:'.$this->label_border.';left:'.$this->label_x.'px;top:'.$this->label_y.'px;width:'.$this->label_width.';z-index:'.($this->z+1).';overflow:visible;">';
			$ret .= '<span>'.$this->label_text.'</span>';
			$ret .= '</div>';
			
			return $ret;
		} else {
			return '';
		}
	}
	
	/**
	 * PRIVATE mergeSummaryOutput()
	 *
	 * Merges the summary output from objects and all child objects together
	 *
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	function mergeSummaryOutput(&$arrStates, $objLabel) {
		$this->summary_output .= $this->LANG->getText('childStatesAre').' ';
		foreach($arrStates AS $state => &$num) {
			if($num > 0) {
				$this->summary_output .= $num.' '.$state.', ';
			}
		}
		
		// Remove last comata
		$this->summary_output = preg_replace('/, $/', '', $this->summary_output);
		
		$this->summary_output .= ' '.$objLabel.'.';
	}
	
	/**
	 * Wraps the state of the current object with the given child object
	 *
	 * @param		Object		Object with a state
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	function wrapChildState(&$OBJ) {
		$arrStates = Array('UNREACHABLE' => 6, 
							'DOWN' => 5, 
							'CRITICAL' => 5, 
							'WARNING' => 4, 
							'UNKNOWN' => 3, 
							'ERROR' => 2, 
							'UP' => 1, 
							'OK' => 1, 
							'PENDING' => 0);
		
		$sSummaryState = $this->getSummaryState();
		$sObjSummaryState = $OBJ->getSummaryState();
		if($sSummaryState != '') {
			/* When the state of the current child is not as good as the current
			 * summary state or the state is equal and the sub-state differs.
			 */
			if($arrStates[$sSummaryState] < $arrStates[$sObjSummaryState] || ($arrStates[$sSummaryState] == $arrStates[$sObjSummaryState] && ($this->getSummaryAcknowledgement() || $this->getSummaryInDowntime()))) {
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
