<?php
/**
 * Class of a Servicegroups in Nagios with all necessary informations
 */
class NagVisServicegroup extends NagiosServicegroup {
	var $MAINCFG;
	var $BACKEND;
	var $LANG;
	
	function NagVisServicegroup(&$MAINCFG, &$BACKEND, &$LANG, $backend_id, $servicegroupName) {
		$this->MAINCFG = &$MAINCFG;
		$this->BACKEND = &$BACKEND;
		$this->LANG = &$LANG;
		$this->type = 'servicegroup';
		$this->iconset = 'std_medium';
		parent::NagiosServicegroup($this->MAINCFG, $this->BACKEND, $this->LANG, $backend_id, $servicegroupName);
	}
	
	function parse() {
		return parent::parse();
	}
	
	# End public methods
	# #########################################################################
	
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
			$link = '<a href="'.$this->MAINCFG->getValue('backend_'.$this->backend_id, 'htmlcgi').'/status.cgi?servicegroup='.$this->servicegroup_name.'&amp;style=detail" target="'.$this->url_target.'">';
		}
		if (DEBUG&&DEBUGLEVEL&1) debug('End method NagVisMap::createLink(): '.$link);
		return $link;
	}
}
?>
