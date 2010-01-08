<?php
/*****************************************************************************
 *
 * FrontendModLogonDialog.php - Module for showing the logon dialog in NagVis
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
 *****************************************************************************/
 
/**
 * @author	Lars Michelsen <lars@vertical-visions.de>
 */
class FrontendModLogonDialog extends FrontendModule {
	protected $CORE;
	
	public function __construct($CORE) {
		$this->CORE = $CORE;
		
		$this->aActions = Array('view' => 0);
	}
	
	public function handleAction() {
		$sReturn = '';
		
		if($this->offersAction($this->sAction)) {
			switch($this->sAction) {
				case 'view':
					// Check if user is already authenticated
					if(!isset($this->AUTHENTICATION) || !$this->AUTHENTICATION->isAuthenticated()) {
						$VIEW = new NagVisLoginView($this->CORE);
						$sReturn = $VIEW->parse();
					} else {
						// When the user is already authenticated redirect to start page (overview)
						Header('Location:'.$this->CORE->getMainCfg()->getValue('paths', 'htmlbase'));
					}
				break;
			}
		}
		
		return $sReturn;
	}
}

?>
