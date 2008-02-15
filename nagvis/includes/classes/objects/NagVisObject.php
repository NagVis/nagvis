<?php
/**
 * Class of a Host in Nagios with all necessary informations
 *
 * @author	Lars Michelsen <lars@vertical-visions.de>
 */
class NagVisObject {
	var $MAINCFG;
	var $LANG;
	
	var $conf;
	
	// "Global" Configuration variables for all objects
	var $type;
	var $x;
	var $y;
	var $z;
	var $icon;
	
	var $hover_menu;
	var $hover_childs_show;
	var $hover_childs_order;
	var $hover_childs_limit;
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
	function NagVisObject(&$MAINCFG, &$LANG) {
		$this->MAINCFG = &$MAINCFG;
		$this->LANG = &$LANG;
		
		$this->conf = Array();
		
	}
	
	function get($option) {
		return $this->{$option};
	}
	
	/**
	 * Get method for x coordinate of the object
	 *
	 * @return	Integer		x coordinate on the map
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	function getX() {
		return $this->x;
	}
	
	/**
	 * Get method for y coordinate of the object
	 *
	 * @return	Integer		y coordinate on the map
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	function getY() {
		return $this->y;
	}
	
	/**
	 * Get method for z coordinate of the object
	 *
	 * @return	Integer		z coordinate on the map
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	function getZ() {
		return $this->z;
	}
	
	/**
	 * Get method for type of the object
	 *
	 * @return	String		Type of the object
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	function getType() {
		return $this->type;
	}
	
	/**
	 * Get method for the name of the object
	 *
	 * @return	String		Name of the object
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	function getName() {
		if($this->type == 'service') {
			return $this->host_name;
		} else {
			return $this->{$this->getType().'_name'};
		}
	}
	
	/**
	 * Get method for the hover template of the object
	 *
	 * @return	String		Hover template of the object
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	function getHoverTemplate() {
		return $this->hover_template;
	}
	
	/**
	 * Set method for the object coords
	 *
	 * @return	Array		Array of the objects coords
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	function setMapCoords($arrCoords) {
		$this->x = $arrCoords['x'];
		$this->y = $arrCoords['y'];
		$this->z = $arrCoords['z'];
	}
	
	/**
	 * PUBLIC setConfiguration()
	 *
	 * Sets options of the object
	 *
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	function setConfiguration(&$obj) {
		foreach($obj AS $key => $val) {
			$this->conf[$key] = $val;
			$this->{$key} = $val;
		}
	}
	
	/**
	 * PUBLIC setObjectInformation()
	 *
	 * Sets extended informations of the object
	 *
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	function setObjectInformation(&$obj) {
		foreach($obj AS $key => $val) {
			$this->{$key} = $val;
		}
	}
	
	/**
	 * PULBLIC getObjectConfiguration()
	 *
	 * Gets the configuration of the object
	 *
	 * @return	Array		Object configuration
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	function getObjectConfiguration() {
		// There have to be removed some options which are only for this object
		$arr = $this->conf;
		unset($arr['id']);
		unset($arr['type']);
		unset($arr['host_name']);
		unset($arr[$this->getType().'_name']);
		unset($arr['service_description']);
		return $arr;
	}
	
	/**
	 * PUBLIC replaceMacros()
	 *
	 * Replaces macros of urls and hover_urls
	 *
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	function replaceMacros() {
		return TRUE;
	}
	
	/**
	 * PUBLIC parseIcon()
	 *
	 * Parses the HTML-Code of an icon
	 *
	 * @return	String		String with Html Code
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
	 * PUBLIC getHoverMenu
	 *
	 * Creates a hover box for objects
	 *
	 * @return	String		HTML code for the hover box
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	function getHoverMenu() {
		$ret = '';
		
		if($this->hover_menu) {
			$ret .= 'onmouseover="return overlib(';
			if(isset($this->hover_url) && $this->hover_url != '') {
				$ret .= $this->readHoverUrl();
			} else {
				$ret .= $this->readHoverTemplate();
			}
			
			$ret .= ', WRAP, VAUTO, DELAY, '.($this->hover_delay*1000).');" onmouseout="return nd();"';
			
			return $ret;
		}
	}
	
	/**
	 * PRIVATE readHoverUrl()
	 *
	 * Reads the given hover url form an object and forms it to a readable format for the hover box
	 *
	 * @return	String		HTML code for the hover box
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	function readHoverUrl() {
		/* FIXME: Context is supported in php >= 5.0
		* $http_opts = array(
		*      'http'=>array(
		*      'method'=>"GET",
		*      'header'=>"Accept-language: en\r\n" .
		*                "Authorization: Basic ".base64_encode("user:pw"),
		*      'request_fulluri'=>true  ,
		*      'proxy'=>"tcp://proxy.url.de"
		*   )
		* );
		* $context = stream_context_create($http_opts);
		* $content = file_get_contents($obj['hover_url'],FALSE,$context);
		*/
		if(!$content = file_get_contents($this->hover_url)) {
			$FRONTEND = new GlobalPage($this->MAINCFG,Array('languageRoot'=>'nagvis:global'));
			$FRONTEND->messageToUser('WARNING','couldNotGetHoverUrl','URL~'.$this->hover_url);
		}
		
		return str_replace('"','\\\'',str_replace('\'','\\\'',str_replace("\t",'',str_replace("\n",'',str_replace("\r\n",'',$content)))));
	}
	
	/**
	 * readHoverTemplate 
	 *
	 * Reads the contents of the hover template file
	 *
	 * @return	String		HTML Code for the hover menu
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	function readHoverTemplate() {
		// Read hover cache contents
		$arrHoverCache = $this->MAINCFG->getRuntimeValue('hover_cache');
		if(isset($arrHoverCache[$this->getHoverTemplate()])) {
			$ret = $arrHoverCache[$this->getHoverTemplate()];
		} else {
			if($this->checkHoverTemplateReadable(1)) {
				$ret = file_get_contents($this->MAINCFG->getValue('paths','hovertemplate').'tmpl.'.$this->getHoverTemplate().'.html');
				
				// Replace the static macros (language, paths)
				if(strpos($ret,'[lang_alias]') !== FALSE) {
					$ret = str_replace('[lang_alias]',$this->LANG->getLabel('alias'),$ret);
				}
				
				if(strpos($ret,'[lang_state]') !== FALSE) {
					$ret = str_replace('[lang_state]',$this->LANG->getLabel('state'),$ret);
				}
				
				if(strpos($ret,'[lang_summary_state]') !== FALSE) {
					$ret = str_replace('[lang_summary_state]',$this->LANG->getLabel('summaryState'),$ret);
				}
				
				if(strpos($ret,'[lang_output]') !== FALSE) {
					$ret = str_replace('[lang_output]',$this->LANG->getLabel('output'),$ret);
				}
				
				if(strpos($ret,'[lang_summary_output]') !== FALSE) {
					$ret = str_replace('[lang_summary_output]',$this->LANG->getLabel('summaryOutput'),$ret);
				}
				
				if(strpos($ret,'[lang_overview]') !== FALSE) {
					$ret = str_replace('[lang_overview]',$this->LANG->getLabel('overview'),$ret);
				}
				
				if(strpos($ret,'[lang_instance]') !== FALSE) {
					$ret = str_replace('[lang_instance]',$this->LANG->getLabel('instance'),$ret);
				}
				
				if(strpos($ret,'[lang_next_check]') !== FALSE) {
				$ret = str_replace('[lang_next_check]',$this->LANG->getLabel('nextCheck'),$ret);
				}
				
				if(strpos($ret,'[lang_last_check]') !== FALSE) {
					$ret = str_replace('[lang_last_check]',$this->LANG->getLabel('lastCheck'),$ret);
				}
				
				if(strpos($ret,'[lang_state_type]') !== FALSE) {
					$ret = str_replace('[lang_state_type]',$this->LANG->getLabel('stateType'),$ret);
				}
				
				if(strpos($ret,'[lang_current_attempt]') !== FALSE) {
					$ret = str_replace('[lang_current_attempt]',$this->LANG->getLabel('currentAttempt'),$ret);
				}
				
				if(strpos($ret,'[lang_last_state_change]') !== FALSE) {
					$ret = str_replace('[lang_last_state_change]',$this->LANG->getLabel('lastStateChange'),$ret);
				}
				
				if(strpos($ret,'[lang_state_duration]') !== FALSE) {
					$ret = str_replace('[lang_state_duration]',$this->LANG->getLabel('stateDuration'),$ret);
				}
				
				if(strpos($ret,'[lang_service_description]') !== FALSE) {
					$ret = str_replace('[lang_service_description]',$this->LANG->getLabel('servicename'),$ret);
				}
				
				if(strpos($ret,'[html_base]') !== FALSE) {
					$ret = str_replace('[html_base]',$this->MAINCFG->getValue('paths','htmlbase'),$ret);
				}
				
				if(strpos($ret,'[html_templates]') !== FALSE) {
					$ret = str_replace('[html_templates]',$this->MAINCFG->getValue('paths','htmlhovertemplates'),$ret);
				}
				
				if(strpos($ret,'[html_template_images]') !== FALSE) {
					$ret = str_replace('[html_template_images]',$this->MAINCFG->getValue('paths','htmlhovertemplateimages'),$ret);
				}
				
				// Build cache for the hover template
				if(!isset($arrHoverCache[$this->getHoverTemplate()])) {
					$this->MAINCFG->setRuntimeValue('hover_cache', Array($this->getHoverTemplate() => $ret));
				} else {
					$arrHoverCache[$this->getHoverTemplate()] = $ret;
					$this->MAINCFG->setRuntimeValue('hover_cache', $arrHoverCache);
				}
			}
		}
		
		$childObjects = '[]';
		$dontShowChilds = '';
		// Replace the macros
		if($this->hover_childs_show == '1' && (($this->type == 'host' && $this->getNumServices() > 0) || (($this->type == 'hostgroup' || $this->type == 'servicegroup') && $this->getNumMembers() > 0) || ($this->type == 'map' && $this->getNumObjects() > 0))) {
			$childObjects = $this->getHoverTemplateChildReplacements($ret);
		} else {
			$dontShowChilds = '\'<!--\\\sBEGIN\\\schilds\\\s-->.+?<!--\\\sEND\\\schilds\\\s-->\': \'\', ';
		}
		
		// Parse the JS code for the hover template macro replacements
		$ret = 'replaceHoverTemplateMacros(\''.strtr(addslashes($ret),Array('"' => '\'', "\r" => '', "\n" => '')).'\', {'.$dontShowChilds.$this->getHoverTemplateReplacements().'}, '.$childObjects.')';
		
		return $ret;
	}
	
	/**
	 * PRIVATE getHoverTemplateReplacements()
	 *
	 * This methods forms keys and values for he replaceHoverTemplateMacros 
	 * function in Javascript
	 *
	 * @return	String		Code of Javascript Array Elements
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	function getHoverTemplateReplacements($child=0) {
		$ret = '';
		
		/**
		 * Now replace the regular macros
		 */
		$ret .= '\'[lang_obj_type]\': \''.$this->LANG->getLabel($this->type).'\', ';
		
		$ret .= '\'[obj_type]\': \''.$this->type.'\', ';
		
		// On child service objects in hover menu replace obj_name with 
		// service_description
		if($this->type == 'service') {
			$ret .= '\'[obj_name]\': \''.$this->getServiceDescription().'\', ';
		} else {
			$ret .= '\'[obj_name]\': \''.$this->getName().'\', ';
		}
		
		if(isset($this->alias) && $this->alias != '') {
			$ret .= '\'[obj_alias]\': \''.$this->alias.'\', ';
		} else {
			$ret .= '\'[obj_alias]\': \'\', ';
		}
		
		if(isset($this->display_name) && $this->display_name != '') {
			$ret .= '\'[obj_display_name]\': \''.$this->display_name.'\', ';
		} else {
			$ret .= '\'[obj_display_name]\': \'\', ';
		}
		
		$ret .= '\'[obj_state]\': \''.$this->getState().'\', ';
		$ret .= '\'[obj_summary_state]\': \''.$this->getSummaryState().'\', ';
		
		if($this->getSummaryAcknowledgement() == 1) {
			$ret .= '\'[obj_summary_acknowledged]\': \' (Acknowledged)\', ';
		} else {
			$ret .= '\'[obj_summary_acknowledged]\': \'\', ';
		}
		
		if($this->getAcknowledgement() == 1) {
			$ret .= '\'[obj_acknowledged]\': \' (Acknowledged)\', ';
		} else {
			$ret .= '\'[obj_acknowledged]\': \'\', ';
		}
		
		if($this->getInDowntime() == 1) {
			$ret .= '\'[obj_summary_in_downtime]\': \' (Downtime)\', ';
			$ret .= '\'[obj_in_downtime]\': \' (Downtime)\', ';
		} else {
			$ret .= '\'[obj_summary_in_downtime]\': \'\', ';
			$ret .= '\'[obj_in_downtime]\': \'\', ';
		}
		
		if(!$child && $this->type != 'map') {
			$ret .= '\'[obj_backendid]\': \''.$this->backend_id.'\', ';
			
			if($this->MAINCFG->getValue('backend_'.$this->backend_id,'backendtype') == 'ndomy') {
				$ret .= '\'[obj_backend_instancename]\': \''.$this->MAINCFG->getValue('backend_'.$this->backend_id,'dbinstancename').'\', ';
			} else {
				$ret .= '\'[obj_backend_instancename]\': \'\', ';
			}
		} else {
			// Remove the macros in map objects
			$ret .= '\'[obj_backendid]\': \'\', ';
			$ret .= '\'[obj_backend_instancename]\': \'\', ';
		}
		
		$ret .= '\'[obj_output]\': \''.strtr($this->output, Array("\r" => '<br />', "\n" => '<br />', '"' => '&quot;', '\'' => '&#145;')).'\', ';
		$ret .= '\'[obj_summary_output]\': \''.strtr($this->getSummaryOutput(), Array("\r" => '<br />', "\n" => '<br />', '"' => '&quot;', '\'' => '&#145;')).'\', ';
		
		if($this->type == 'service') {
			$name = 'hostname';
		} else {
			$name = $this->type . 'name';
		}
		$ret .= '\'[lang_name]\': \''.$this->LANG->getLabel($name).'\', ';
		
		// Macros which are only for services and hosts
		if($this->type == 'host' || $this->type == 'service') {
			$ret .= '\'[obj_last_check]\': \''.$this->getLastCheck().'\', ';
			$ret .= '\'[obj_next_check]\': \''.$this->getNextCheck().'\', ';
			$ret .= '\'[obj_state_type]\': \''.$this->getStateType().'\', ';
			$ret .= '\'[obj_current_check_attempt]\': \''.$this->getCurrentCheckAttempt().'\', ';
			$ret .= '\'[obj_max_check_attempts]\': \''.$this->getMaxCheckAttempts().'\', ';
			$ret .= '\'[obj_last_state_change]\': \''.$this->getLastStateChange().'\', ';
			$ret .= '\'[obj_last_hard_state_change]\': \''.$this->getLastHardStateChange().'\', ';
			$ret .= '\'[obj_state_duration]\': \''.$this->getStateDuration().'\', ';
		}
		
		// Macros which are only for services
		if($this->type == 'service') {
			$ret .= '\'[service_description]\': \''.$this->getServiceDescription().'\', ';
			$ret .= '\'[pnp_service_description]\': \''.str_replace(' ','%20',$this->getServiceDescription()).'\', ';
		} else {
			$ret .= '\'<!--\\\sBEGIN\\\sservice\\\s-->.+?<!--\\\sEND\\\sservice\\\s-->\': \'\', ';
		}
		
		// Macros which are only for hosts
		if($this->type == 'host') {
			$ret .= '\'[pnp_hostname]\': \''.str_replace(' ','%20',$this->getName()).'\', ';
		} else {
			$ret .= '\'<!--\\\sBEGIN\\\shost\\\s-->.+?<!--\\\sEND\\\shost\\\s-->\': \'\', ';
		}
		
		// Remove the last comma and return the javascript code
		return rtrim($ret,', ');
	}
	
	/**
	 * PRIVATE getHoverTemplateChildReplacements
	 *
	 * Get the hover template child replacement options
	 *
	 * @param		String		HTML code with macros
	 * @return	String		HTML code for the hover menu
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	function getHoverTemplateChildReplacements($htmlTemplate) {
		$matches = Array();
		$childs = '';
		switch($this->type) {
			case 'host':
				$arrObjects = &$this->getServices();
			break;
			case 'hostgroup':
			case 'servicegroup':
				$arrObjects = &$this->getMembers();
			break;
			case 'map':
				$arrObjects = &$this->getMapObjects();
			break;
		}
		
		// Sort the array of child objects by the sort option
		switch($this->hover_childs_order) {
			case 's':
				// Order by State
				usort($arrObjects, Array("NagVisObject", "sortObjectsByState"));
			break;
			case 'a':
			default:
				// Order alhpabetical
				usort($arrObjects, Array("NagVisObject", "sortObjectsAlphabetical"));
			break;
		}
		
		// Count only once, not in loop header
		$numObjects = count($arrObjects);
		
		$ret = '[ ';
		// Loop all child object until all looped or the child limit is reached
		for($i = 0; $i < $this->hover_childs_limit, $i < $numObjects; $i++) {
			if($arrObjects[$i]->getType() != 'textbox' && $arrObjects[$i]->getType() != 'shape') {
				$ret .= '{'.$arrObjects[$i]->getHoverTemplateReplacements(1).'},';
			}
		}
		$ret .= ']';
		
		return $ret;
	}
	
	/**
	 * PRIVATE STATIC sortObjectsAlphabetical()
	 *
	 * Sorts the both alhabeticaly by the name
	 *
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	function sortObjectsAlphabetical($a, $b) {
		switch($this->type) {
			case 'host':
				$al = strtolower($a->getServiceDescription());
				$bl = strtolower($b->getServiceDescription());
			break;
			default:
				$al = strtolower($a->getName());
				$bl = strtolower($b->getName());
			break;
		}
		
        if ($al == $bl) {
            return 0;
        } elseif($al > $bl) {
			return +1;
		} else {
			return -1;
		}
    }
	/**
	 * PRIVATE STATIC sortObjectsAlphabetical()
	 *
	 * Sorts the both alhabeticaly by the name
	 *
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	function sortObjectsByState($a, $b) {
		$arrStates = Array('UNREACHABLE' => 6, 'DOWN' => 5, 'CRITICAL' => 5, 'WARNING' => 4, 'UNKNOWN' => 3, 'ERROR' => 2, 'UP' => 1, 'OK' => 1, 'PENDING' => 0);
		
		$al = $a->getSummaryState();
		$bl = $b->getSummaryState();
		
        if($arrStates[$al] == $arrStates[$bl]) {
            return 0;
        } elseif($arrStates[$al] < $arrStates[$bl]) {
			return +1;
		} else {
			return -1;
		}
    }
	
	/**
	 * PRIVATE checkHoverTemplateReadable()
	 *
	 * Checks if the requested hover template file is readable
	 *
	 * @param		Boolean		Switch for enabling/disabling error messages
	 * @return	Boolean		Check Result
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	function checkHoverTemplateReadable($printErr) {
		if($this->checkHoverTemplateExists($printErr) && is_readable($this->MAINCFG->getValue('paths','hovertemplate').'tmpl.'.$this->getHoverTemplate().'.html')) {
			return TRUE;
		} else {
			if($printErr == 1) {
				$FRONTEND = new GlobalPage($this->MAINCFG,Array('languageRoot'=>'nagvis:global'));
				$FRONTEND->messageToUser('ERROR','hoverTemplateNotReadable','FILE~'.$this->MAINCFG->getValue('paths','hovertemplate').'tmpl.'.$this->getHoverTemplate().'.html');
			}
			return FALSE;
		}
	}
	
	/**
	 * PRIVATE checkHoverTemplateExists()
	 *
	 * Checks if the requested hover template file exists
	 *
	 * @param		Boolean		Switch for enabling/disabling error messages
	 * @return	Boolean		Check Result
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	function checkHoverTemplateExists($printErr) {
		if(file_exists($this->MAINCFG->getValue('paths','hovertemplate').'tmpl.'.$this->getHoverTemplate().'.html')) {
			return TRUE;
		} else {
			if($printErr == 1) {
				$FRONTEND = new GlobalPage($this->MAINCFG,Array('languageRoot'=>'nagvis:global'));
				$FRONTEND->messageToUser('ERROR','hoverTemplateNotExists','FILE~'.$this->MAINCFG->getValue('paths', 'hovertemplate').'tmpl.'.$this->getHoverTemplate().'.html');
			}
			return FALSE;
		}
	}
	
	/**
	 * PRIVATE createLink()
	 *
	 * Creates a link to the configured url
	 *
	 * @return	String	HTML code of the Link
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	function createLink() {
		return '<a href="'.$this->url.'" target="'.$this->url_target.'">';
	}
}
?>
