<?php
/**
 * Class of a Host in Nagios with all necessary informations
 */
class NagVisService extends NagiosService {
	var $MAINCFG;
	var $BACKEND;
	var $LANG;
	
	/**
	 * Class constructor
	 *
	 * @param		Object 		Object of class GlobalMainCfg
	 * @param		Object 		Object of class GlobalBackendMgmt
	 * @param		Object 		Object of class GlobalLanguage
	 * @param		Integer 	ID of queried backend
	 * @param		String		Name of the host
	 * @param		String		Service description
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	function NagVisService(&$MAINCFG, &$BACKEND, &$LANG, $backend_id, $hostName, $serviceDescription) {
		if(DEBUG&&DEBUGLEVEL&1) debug('Start method NagVisService::NagVisService(MAINCFG,BACKEND,LANG,'.$backend_id.','.$hostName.'.'.$serviceDescription.')');
		$this->MAINCFG = &$MAINCFG;
		$this->BACKEND = &$BACKEND;
		$this->LANG = &$LANG;
		$this->type = 'service';
		$this->iconset = 'std_medium';
		parent::NagiosService($this->MAINCFG, $this->BACKEND, $this->LANG, $backend_id, $hostName, $serviceDescription);
		if(DEBUG&&DEBUGLEVEL&1) debug('Stop method NagVisService::NagVisService()');
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
		if(DEBUG&&DEBUGLEVEL&1) debug('Start method NagVisService::parse()');
		if(DEBUG&&DEBUGLEVEL&1) debug('Stop method NagVisService::parse()');
		return parent::parse();
	}
	
	# End public methods
	# #########################################################################
	
	/**
	 * Fetches the icon for the object depending on the summary state
	 *
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	function fetchIcon() {
		if(DEBUG&&DEBUGLEVEL&1) debug('Start method NagVisService::fetchIcon()');
		if($this->getSummaryState() != '') {
			$stateLow = strtolower($this->getSummaryState());
			
			switch($stateLow) {
				case 'critical':
				case 'warning':
					if($this->getSummaryAcknowledgement() == 1) {
						$icon = $this->iconset.'_sack.png';
					} else {
						$icon = $this->iconset.'_'.$stateLow.'.png';
					}
				break;
				case 'unknown':
				case 'ok':
				case 'pending':
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
		if(DEBUG&&DEBUGLEVEL&1) debug('Stop method NagVisService::fetchIcon()');
	}
	
	
	/**
	 * Creates a link to Nagios, when this is not set in the Config-File
	 *
	 * @return	String	The Link
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	function createLink() {
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method NagVisService::createLink()');
		
		if(isset($this->url) && $this->url != '') {
			$link = parent::createLink();
		} else {
			$link = '<a href="'.$this->MAINCFG->getValue('backend_'.$this->backend_id, 'htmlcgi').'/extinfo.cgi?type=2&amp;host='.$this->host_name.'&amp;service='.$this->service_description.'" target="'.$this->url_target.'">';
		}
		if (DEBUG&&DEBUGLEVEL&1) debug('Stop method NagVisService::createLink(): '.$link);
		return $link;
	}
}
?>
