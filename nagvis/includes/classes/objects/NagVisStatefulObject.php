<?php
/**
 * Class of all NagVis objects which have a state
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
	
	/**
	 * Class constructor
	 *
	 * @param		Object 		Object of class GlobalMainCfg
	 * @param		Object 		Object of class GlobalBackendMgmt
	 * @param		Object 		Object of class GlobalLanguage
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	function NagVisStatefulObject(&$MAINCFG, &$BACKEND, &$LANG) {
		if(DEBUG&&DEBUGLEVEL&1) debug('Start method NagVisStatefulObject::NagVisStatefulObject(MAINCFG,BACKEND,LANG)');
		$this->MAINCFG = &$MAINCFG;
		$this->BACKEND = &$BACKEND;
		$this->LANG = &$LANG;
		
		$this->GRAPHIC = new GlobalGraphic();
		
		$this->iconPath = $this->MAINCFG->getValue('paths', 'icon');
		$this->iconHtmlPath = $this->MAINCFG->getValue('paths', 'htmlicon');
		
		parent::NagVisObject($this->MAINCFG, $this->LANG);
		if(DEBUG&&DEBUGLEVEL&1) debug('Stop method NagVisStatefulObject::NagVisStatefulObject()');
	}
	
	function getState() {
		return $this->state;
	}
	
	function getOutput() {
		return $this->output;
	}
	
	function getAcknowledgement() {
		return $this->problem_has_been_acknowledged;
	}
	
	function getSummaryState() {
		return $this->summary_state;
	}
	
	function getSummaryOutput() {
		return $this->summary_output;
	}
	
	function getSummaryAcknowledgement() {
		return $this->summary_problem_has_been_acknowledged;
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
		if(DEBUG&&DEBUGLEVEL&1) debug('Start method NagVisStatefulObject::parse()');
		$this->replaceMacros();
		//$this->fixIconPosition();
		
		// Some specials for lines
		if(isset($this->line_type)) {
			$this->getLineHoverArea();
		}
		
		if(DEBUG&&DEBUGLEVEL&1) debug('Stop method NagVisStatefulObject::parse(): HTML Code');
		return $this->parseIcon().$this->parseLabel();
	}
	
	# End public methods
	# #########################################################################
	
	/**
	 * Fetches the icon for the object depending on the summary state
	 *
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	function fetchIcon() {
		if(DEBUG&&DEBUGLEVEL&1) debug('Start method NagVisStatefulObject::fetchIcon()');
		if($this->getSummaryState() != '') {
			$stateLow = strtolower($this->getSummaryState());
			
			switch($stateLow) {
				case 'unreachable':
				case 'down':
				case 'critical':
				case 'warning':
					if($this->getSummaryAcknowledgement() == 1) {
						$icon = $this->iconset.'_ack.png';
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
			
			$this->iconPath = $this->MAINCFG->getValue('paths', 'icon');
			$this->iconHtmlPath = $this->MAINCFG->getValue('paths', 'htmlicon');
			//Checks whether the needed file exists
			if(@fclose(@fopen($this->MAINCFG->getValue('paths', 'icon').$icon,'r'))) {
				$this->icon = $icon;
			} else {
				$this->icon = $this->iconset.'_error.png';
			}
		} else {
			$this->icon = $this->iconset.'_error.png';
		}
		if(DEBUG&&DEBUGLEVEL&1) debug('Stop method NagVisStatefulObject::fetchIcon()');
	}
	
	/**
	 * Replaces macros of urls and hover_urls
	 *
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	function replaceMacros() {
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method NagVisStatefulObject::replaceMacros()');
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
		
		if(DEBUG&&DEBUGLEVEL&1) debug('Stop method NagVisStatefulObject::replaceMacros()');
	}
	
	/**
	 * Calculates the position of the line hover area
	 *
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	function getLineHoverArea() {
		if(DEBUG&&DEBUGLEVEL&1) debug('Start method NagVisStatefulObject::getLineHoverArea()');
		
		list($xFrom,$xTo) = explode(',', $this->getX());
		list($yFrom,$yTo) = explode(',', $this->getY());
		
		$this->x = $this->GRAPHIC->middle($xFrom,$xTo) - 10;
		$this->y = $this->GRAPHIC->middle($yFrom,$yTo) - 10;
		$this->icon = '20x20.gif';
		
		if(DEBUG&&DEBUGLEVEL&1) debug('Stop method NagVisStatefulObject::getLineHoverArea()');
	}
	
	
	/**
	 * Parses the HTML-Code of an icon
	 *
	 * @return	String		HTML code of the icon
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	function parseIcon() {
		if(DEBUG&&DEBUGLEVEL&1) debug('Start method NagVisStatefulObject::parseIcon()');
		
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
		
		if(DEBUG&&DEBUGLEVEL&1) debug('Stop method NagVisStatefulObject::parseIcon(): HTML code');
		return $ret;
	}
	
	/**
	 * Parses the HTML-Code of a label
	 *
	 * @return	String		HTML code of the label
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	function parseLabel() {
		if(DEBUG&&DEBUGLEVEL&1) debug('Start method NagVisStatefulObject::parseLabel()');
		
		if(isset($this->label_show) && $this->label_show == '1') {
			if($this->type == 'service') {
				$name = 'host_name';
			} else {
				$name = $this->type . '_name';
			}
			
			// If there is a presign it should be relative to the objects x/y
			if(preg_match('/^(\+|\-)/', $this->label_x)) {
				$this->label_x = $this->x + $this->label_x;
			}
			if(preg_match('/^(\+|\-)/',$this->label_y)) {
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
			
			$ret  = '<div class="object_label" style="background:'.$this->label_background.';left:'.$this->label_x.'px;top:'.$this->label_y.'px;width:'.$this->label_width.';z-index:'.($this->z+1).';overflow:visible;">';
			$ret .= '<span>'.$this->label_text.'</span>';
			$ret .= '</div>';
			
			if(DEBUG&&DEBUGLEVEL&1) debug('Stop method NagVisStatefulObject::parseLabel(): HTML code');
			return $ret;
		} else {
			if(DEBUG&&DEBUGLEVEL&1) debug('Stop method NagVisStatefulObject::parseLabel()');
			return '';
		}
	}
	
	/**
	 * PRIVATE fetchSummaryOutput()
	 *
	 * Fetches the summary output from objects and all child objects
	 *
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	function fetchSummaryOutput(&$arrStates, $objLabel) {
		if(DEBUG&&DEBUGLEVEL&1) debug('Start method NagVisStatefulObject::fetchSummaryOutput()');
		
		$this->summary_output .= $this->LANG->getLabel('childStatesAre').' ';
		foreach($arrStates AS $state => $num) {
			if($num > 0) {
				$this->summary_output .= $num.' '.$state.', ';
			}
		}
		
		// Remove last comata
		$this->summary_output = preg_replace('/, $/', '', $this->summary_output);
		
		$this->summary_output .= ' '.$objLabel.'.';
		
		if(DEBUG&&DEBUGLEVEL&1) debug('Stop method NagVisStatefulObject::fetchSummaryOutput()');
	}
	
	/**
	 * Wraps the state of the current object with the given child object
	 *
	 * @param		Object		Object with a state
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	function wrapChildState(&$OBJ) {
		if(DEBUG&&DEBUGLEVEL&1) debug('Start method NagVisStatefulObject::wrapChildState(OBJ)');
		
		$arrStates = Array('UNREACHABLE' => 6, 'DOWN' => 5, 'CRITICAL' => 5, 'WARNING' => 4, 'UNKNOWN' => 3, 'ERROR' => 2, 'UP' => 1, 'OK' => 1, 'PENDING' => 0);
		if($this->getSummaryState() != '') {
			if($arrStates[$this->getSummaryState()] < $arrStates[$OBJ->getSummaryState()]) {
				$this->summary_state = $OBJ->getSummaryState();
				
				if($OBJ->getSummaryAcknowledgement() == 1) {
					$this->summary_problem_has_been_acknowledged = 1;
				} else {
					$this->summary_problem_has_been_acknowledged = 0;
				}
			}
		} else {
			$this->summary_state = $OBJ->getSummaryState();
		}
 		
		if(DEBUG&&DEBUGLEVEL&1) debug('Stop method NagVisStatefulObject::wrapChildState()');
	}
}
?>
