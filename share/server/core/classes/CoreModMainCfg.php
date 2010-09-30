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
		$this->sName = 'MainCfg';
		$this->CORE = $CORE;
		
		// Register valid actions
		$this->aActions = Array(
			// WUI specific actions
			'edit'             => REQUIRES_AUTHORISATION,
			'manageBackends'   => 'edit',
			'doEdit'           => 'edit',
			'doBackendDefault' => 'edit',
			'doBackendAdd'     => 'edit',
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
					$this->handleResponse('handleResponseEdit', 'doEdit',
						                    $this->CORE->getLang()->getText('The main configuration has been updated.'),
																$this->CORE->getLang()->getText('The main configuration could not be updated.'),
																1);
				break;

				case 'manageBackends':
					$VIEW = new WuiViewManageBackends($this->AUTHENTICATION, $this->AUTHORISATION);
					$sReturn = json_encode(Array('code' => $VIEW->parse()));
				break;
				case 'doBackendDefault':
					$this->handleResponse('handleResponseBackendDefault', 'doBackendDefault',
						                    $this->CORE->getLang()->getText('The default backend has been changed.'),
																$this->CORE->getLang()->getText('The default backend could not be changed.'),
																1);
				break;
				case 'doBackendAdd':
					$this->handleResponse('handleResponseBackendAdd', 'doBackendAdd',
						                    $this->CORE->getLang()->getText('The new backend has been added.'),
																$this->CORE->getLang()->getText('The new backend could not be added.'),
																1);
				break;
			}
		}
		
		return $sReturn;
	}

	/**
	 * Set the default backend in the main configuration
	 */
	protected function doBackendDefault($a) {
			$this->CORE->getMainCfg()->setValue('defaults', 'backend', $_POST['defaultbackend']);
			$this->CORE->getMainCfg()->writeConfig();
			return true;
	}

	protected function handleResponseBackendDefault() {
		$FHANDLER = new CoreRequestHandler($_POST);

		if(!$FHANDLER->isSetAndNotEmpty('defaultbackend'))
			new GlobalMessage('ERROR', $this->CORE->getLang()->getText('mustValueNotSet',
			                                        Array('ATTRIBUTE' => 'defaultbackend')));
		$this->verifyValuesSet($FHANDLER, Array('defaultbackend'));

		return Array('defaultbackend' => $FHANDLER->get('map_new_name'));
	}
	
	protected function doEdit($a) {
		foreach($a['opts'] AS $key => $val) {
			$key = explode('_', $key, 2);
			$this->CORE->getMainCfg()->setValue($key[0], $key[1], $val);
		}
		
		// Write the changes to the main configuration file
		$this->CORE->getMainCfg()->writeConfig();
		
		return true;
	}
	
	protected function handleResponseEdit() {
		$bValid = true;
		// FIXME: Validate the response
		
		// Store response data
		if($bValid === true)
			return Array('opts' => $_POST);
		else
			return false;
	}

	protected function handleResponseBackendAdd() {
		$FHANDLER = new CoreRequestHandler($_POST);

		$this->verifyValuesSet($FHANDLER, Array('backendid', 'backendtype'));

		return Array('backendid'   => $FHANDLER->get('backendid'),
		             'backendtype' => $FHANDLER->get('backendtype'),
								 'opts'        => $_POST);
	}

	protected function doBackendAdd($a) {
		$bFoundOption = false;
		$aOpt = Array();
		
		// Loop all aviable options for this backend
		$arr = $this->CORE->getMainCfg()->getValidObjectType('backend');
		foreach($arr['options'][$a['backendtype']] AS $key => $arr) {
			// If there is a value for this option, set it
			if(isset($a['opts'][$key]) && $a['opts'][$key] != '') {
				$bFoundOption = true;
				$aOpt[$key] = $a['opts'][$key];
			}
		}
		
		// If there is at least one option set...
		if($bFoundOption) {
			// Set standard values
			$this->CORE->getMainCfg()->setSection('backend_'.$a['backendid']);
			$this->CORE->getMainCfg()->setValue('backend_'.$a['backendid'], 'backendtype', $a['backendtype']);
			
			// Set all options
			foreach($aOpt AS $key => $val) {
				$this->CORE->getMainCfg()->setValue('backend_'.$a['backendid'], $key, $val);
			}
		}
		
		// Write the changes to the main configuration
		$this->CORE->getMainCfg()->writeConfig();
		return true;
	}

	/*
	 * Edit the values of the backend with the given BACKEND-ID
	 *
	case 'mgt_backend_edit':
		if(!isset($_POST['backendid']) || $_POST['backendid'] == '') {
			echo $CORE->getLang()->getText('mustValueNotSet', 'ATTRIBUTE~backendid');
		} else {
			// Loop all aviable options for this backend
			$arr = $CORE->getMainCfg()->getValidObjectType('backend');
			foreach($arr['options'][$CORE->getMainCfg()->getValue('backend_'.$_POST['backendid'],'backendtype')] AS $key => $arr) {
				// If there is a value for this option, set it
				if(isset($_POST[$key]) && $_POST[$key] != '') {
					$CORE->getMainCfg()->setValue('backend_'.$_POST['backendid'],$key,$_POST[$key]);
				}
			}
			
			// Write the changes to the main configuration
			$CORE->getMainCfg()->writeConfig();
			
			// Open the management page again
			print("<script>window.history.back();</script>");
		}
	break;
	/*
	 * Delete the specified backend with the given BACKEND-ID
	 *
	case 'mgt_backend_del':
		if(!isset($_POST['backendid']) || $_POST['backendid'] == '') {
			echo $CORE->getLang()->getText('mustValueNotSet', 'ATTRIBUTE~backendid');
		} else {
			// Delete the section of the backend
			$CORE->getMainCfg()->delSection('backend_'.$_POST['backendid']);
			
			// Write the changes to the main configuration
			$CORE->getMainCfg()->writeConfig();
			
			// Open the management page again
			print("<script>window.history.back();</script>");
		}
		break;*/
}
?>
