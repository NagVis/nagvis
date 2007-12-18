<?php
/**
 * Class of a Host in Nagios with all necessary informations
 */
class NagVisHost extends NagiosHost {
	var $MAINCFG;
	var $BACKEND;
	var $LANG;
	
	function NagVisHost(&$MAINCFG, &$BACKEND, &$LANG, $backend_id, $hostName) {
		$this->MAINCFG = &$MAINCFG;
		$this->BACKEND = &$BACKEND;
		$this->LANG = &$LANG;
		$this->type = 'host';
		$this->iconset = 'std_medium';
		parent::NagiosHost($this->MAINCFG, $this->BACKEND, $this->LANG, $backend_id, $hostName);
	}
	
	function parse() {
		return parent::parse();
	}
	
	function parseGraphviz() {
		// shape=plaintext, 
		// color=green, 
		// style="",
		// <<table border="0"><tr><td><img src="'.$this->MAINCFG->getValue('paths', 'htmlicon').$this->icon.'"></img></td></tr><tr><td>'.$this->host_name.'</td></tr></table>>
		$strReturn = $this->type.'_'.$this->host_name.' [ ';
		//$strReturn .= 'label="'.$this->host_name.'", ';
		$strReturn .= 'label=<<table border="0">';
		$strReturn .= '<tr><td><img src="'.$this->MAINCFG->getValue('paths', 'icon').$this->icon.'"></img></td></tr>';
		$strReturn .= '<tr><td>'.$this->host_name.'</td></tr>';
		$strReturn .= '</table>>, ';
		$strReturn .= 'URL="'.$this->MAINCFG->getValue('backend_'.$this->backend_id, 'htmlcgi').'/status.cgi?host='.$this->host_name.'", ';
		$strReturn .= 'target="'.$this->url_target.'", ';
		$strReturn .= 'tooltip="'.$this->host_name.'", ';
		$strReturn .= 'shape="box", ';
		$strReturn .= 'fontcolor=black, fontname=Verdana, fontsize=10];'."\n ";
		foreach($this->getChilds() As $OBJ) {
			$strReturn .= $OBJ->parseGraphviz();
			$strReturn .= $this->type.'_'.$this->host_name.' -- '.$OBJ->type.'_'.$OBJ->host_name.' [color=black, decorate=1, fontcolor=black, fontname=Verdana, fontsize=8, style=solid, weight=2 ];'."\n ";
		}
		return $strReturn;
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
			$link = '<a href="'.$this->MAINCFG->getValue('backend_'.$this->backend_id, 'htmlcgi').'/status.cgi?host='.$this->host_name.'" target="'.$this->url_target.'">';
		}
		if (DEBUG&&DEBUGLEVEL&1) debug('End method NagVisMap::createLink(): '.$link);
		return $link;
	}
}
?>
