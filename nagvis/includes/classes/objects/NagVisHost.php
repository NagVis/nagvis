<?php
/**
 * Class of a Host in Nagios with all necessary informations
 *
 * @author	Lars Michelsen <lars@vertical-visions.de>
 */
class NagVisHost extends NagiosHost {
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
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	function NagVisHost(&$MAINCFG, &$BACKEND, &$LANG, $backend_id, $hostName) {
		if(DEBUG&&DEBUGLEVEL&1) debug('Start method NagVisHost::NagVisHost(MAINCFG,BACKEND,LANG,'.$backend_id.','.$hostName.')');
		$this->MAINCFG = &$MAINCFG;
		$this->BACKEND = &$BACKEND;
		$this->LANG = &$LANG;
		$this->type = 'host';
		$this->iconset = 'std_medium';
		parent::NagiosHost($this->MAINCFG, $this->BACKEND, $this->LANG, $backend_id, $hostName);
		if(DEBUG&&DEBUGLEVEL&1) debug('Stop method NagVisHost::NagVisHost()');
	}
	
	/**
	 * PUBLIC parse()
	 *
	 * Parses the object in HTML format
	 *
	 * @return	String		HTML code of the object
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	function parse() {
		if(DEBUG&&DEBUGLEVEL&1) debug('Start method NagVisHost::parse()');
		if(DEBUG&&DEBUGLEVEL&1) debug('Stop method NagVisHost::parse()');
		return parent::parse();
	}
	
	/**
	 * PUBLIC parseGraphviz()
	 *
	 * Parses the object in graphviz configuration format
	 *
	 * @return	String		graphviz configuration code of the object
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	function parseGraphviz() {
		if(DEBUG&&DEBUGLEVEL&1) debug('Start method NagVisHost::parseGraphviz()');
		$strReturn = $this->type.'_'.str_replace('-','__',$this->host_name).' [ ';
		$strReturn .= 'label=<<table border="0">';
		$strReturn .= '<tr><td><img src="'.$this->MAINCFG->getValue('paths', 'icon').$this->icon.'"></img></td></tr>';
		$strReturn .= '<tr><td>'.$this->host_name.'</td></tr>';
		$strReturn .= '</table>>, ';
		$strReturn .= 'URL="'.$this->MAINCFG->getValue('backend_'.$this->backend_id, 'htmlcgi').'/status.cgi?host='.str_replace('-','__',$this->host_name).'", ';
		$strReturn .= 'target="'.$this->url_target.'", ';
		$strReturn .= 'tooltip="'.$this->host_name.'"';
		$strReturn .= ' ];'."\n ";
		foreach($this->getChilds() As $OBJ) {
			$strReturn .= $OBJ->parseGraphviz();
			$strReturn .= $this->type.'_'.str_replace('-','__',$this->host_name).' -- '.$OBJ->type.'_'.str_replace('-','__',$OBJ->host_name).' [color=black, decorate=1, style=solid, weight=2 ];'."\n ";
		}
		if(DEBUG&&DEBUGLEVEL&1) debug('Stop method NagVisHost::parseGraphviz(): String HTML');
		return $strReturn;
	}
	
	# End public methods
	# #########################################################################
	
	/**
	 * Fetches the icon for the object depending on the summary state
	 *
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	function fetchIcon() {
		if(DEBUG&&DEBUGLEVEL&1) debug('Start method NagVisHost::fetchIcon()');
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
		if(DEBUG&&DEBUGLEVEL&1) debug('Stop method NagVisHost::fetchIcon()');
	}
	
	/**
	 * Creates a link to Nagios, when this is not set in the Config-File
	 *
	 * @return	String	The Link
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	function createLink() {
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method NagVisHost::createLink()');
		
		if(isset($this->url) && $this->url != '') {
			$link = parent::createLink();
		} else {
			$link = '<a href="'.$this->MAINCFG->getValue('backend_'.$this->backend_id, 'htmlcgi').'/status.cgi?host='.$this->host_name.'" target="'.$this->url_target.'">';
		}
		if (DEBUG&&DEBUGLEVEL&1) debug('End method NagVisHost::createLink(): '.$link);
		return $link;
	}
}
?>
