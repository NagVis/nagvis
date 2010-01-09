<?php
/*******************************************************************************
 *
 * CoreModMainCfg.php - Core Map module to handle ajax requests
 *
 * Copyright (c) 2004-2010 NagVis Project (Contact: info@nagvis.org)
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
 ******************************************************************************/

/**
 * @author Lars Michelsen <lars@vertical-visions.de>
 */
class CoreModMainCfg extends CoreModule {
	private $name = null;
	
	public function __construct(GlobalCore $CORE) {
		$this->CORE = $CORE;
		
		// Register valid actions
		$this->aActions = Array(
			// WUI specific actions
			'edit' => REQUIRES_AUTHORISATION,
			'doEdit' => REQUIRES_AUTHORISATION,
		);
	}
	
	public function handleAction() {
		$sReturn = '';
		
		if($this->offersAction($this->sAction)) {
			switch($this->sAction) {
				case 'edit':
					$VIEW = new WuiViewEditMainCfg($this->AUTHENTICATION, $this->AUTHORISATION);
					$sReturn = json_encode(Array('code' => $VIEW->parse()));
				break;
				case 'doEdit':
					$aReturn = $this->handleResponseEdit();
					
					if($aReturn !== false) {
						// Try to create the map
						if($this->doEdit($aReturn)) {
							new GlobalMessage('NOTE', 
							                  $this->CORE->getLang()->getText('The main configuration has been updated.'),
							                  null,
							                  null,
							                  1);
							$sReturn = '';
						} else {
							new GlobalMessage('ERROR', $this->CORE->getLang()->getText('The main configuration could not be updated.'));
							$sReturn = '';
						}
					} else {
						new GlobalMessage('ERROR', $this->CORE->getLang()->getText('You entered invalid information.'));
						$sReturn = '';
					}
				break;
			}
		}
		
		return $sReturn;
	}
	
	private function doEdit($a) {
		foreach($a['opts'] AS $key => $val) {
			$key = explode('_', $key);
			$this->CORE->getMainCfg()->setValue($key[0], $key[1], $val);
		}
		
		// Write the changes to the main configuration file
		$this->CORE->getMainCfg()->writeConfig();
		
		return true;
	}
	
	private function handleResponseEdit() {
		$bValid = true;
		// Validate the response
		
		// Store response data
		if($bValid === true) {
			// Return the data
			return Array('opts' => $_POST);
		} else {
			return false;
		}
	}
}
?>
