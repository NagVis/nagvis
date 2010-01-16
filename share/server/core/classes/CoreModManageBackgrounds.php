<?php
/*******************************************************************************
 *
 * CoreModManageBackgrounds.php - Core module to manage backgrounds
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
class CoreModManageBackgrounds extends CoreModule {
	private $name = null;
	
	public function __construct(GlobalCore $CORE) {
		$this->CORE = $CORE;
		
		// Register valid actions
		$this->aActions = Array(
			// WUI specific actions
			'view' => REQUIRES_AUTHORISATION,
			'doCreate' => REQUIRES_AUTHORISATION,
			'doUpload' => REQUIRES_AUTHORISATION,
			'doDelete' => REQUIRES_AUTHORISATION,
		);
	}
	
	public function handleAction() {
		$sReturn = '';
		
		if($this->offersAction($this->sAction)) {
			switch($this->sAction) {
				case 'view':
					$VIEW = new WuiViewManageBackgrounds($this->AUTHENTICATION, $this->AUTHORISATION);
					$sReturn = json_encode(Array('code' => $VIEW->parse()));
				break;
				case 'doCreate':
					$aReturn = $this->handleResponseCreate();
					
					if($aReturn !== false) {
						// Try to create the map
						if($this->doCreate($aReturn)) {
							new GlobalMessage('NOTE', $this->CORE->getLang()->getText('The background has been created.'),
								                  null,
								                  null,
								                  1);
							$sReturn = '';
						} else {
							new GlobalMessage('ERROR', $this->CORE->getLang()->getText('The background could not be created.'));
							$sReturn = '';
						}
					} else {
						new GlobalMessage('ERROR', $this->CORE->getLang()->getText('You entered invalid information.'));
						$sReturn = '';
					}
				break;
				case 'doDelete':
					$aReturn = $this->handleResponseDelete();
					
					if($aReturn !== false) {
						// Try to create the map
						if($this->doDelete($aReturn)) {
							new GlobalMessage('NOTE', $this->CORE->getLang()->getText('The background has been deleted.'),
							                  null,
							                  null,
							                  1);
							$sReturn = '';
						} else {
							new GlobalMessage('ERROR', $this->CORE->getLang()->getText('The background could not be deleted.'));
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
	
	private function doDelete($a) {
		$BACKGROUND = new GlobalBackground($this->CORE, $a['image']);
		$BACKGROUND->deleteImage();
		
		return true;
	}
	
	private function handleResponseDelete() {
		$bValid = true;
		// Validate the response
		
		$FHANDLER = new CoreRequestHandler($_POST);
		
		// Check for needed params
		if($bValid && !$FHANDLER->isSetAndNotEmpty('map_image')) {
			$bValid = false;
		}
		
		// Check if the map exists
		if($bValid && !in_array($FHANDLER->get('map_image'), $this->CORE->getAvailableBackgroundImages())) {
			new GlobalMessage('ERROR', $this->CORE->getLang()->getText('The background does not exist.'));
			$bValid = false;
		}
		
		// Store response data
		if($bValid === true) {
			// Return the data
			return Array('image' => $FHANDLER->get('map_image'));
		} else {
			return false;
		}
	}
	
	private function doCreate($a) {
		$BACKGROUND = new GlobalBackground($this->CORE, $a['name'].'.png');
		$BACKGROUND->createImage($a['color'], $a['width'], $a['height']);
		
		return true;
	}
	
	private function handleResponseCreate() {
		$bValid = true;
		// Validate the response
		
		$FHANDLER = new CoreRequestHandler($_POST);
		
		// Check for needed params
		if($bValid && !$FHANDLER->isSetAndNotEmpty('image_name')) {
			$bValid = false;
		}
		if($bValid && !$FHANDLER->isSetAndNotEmpty('image_color')) {
			$bValid = false;
		}
		if($bValid && !$FHANDLER->isSetAndNotEmpty('image_width')) {
			$bValid = false;
		}
		if($bValid && !$FHANDLER->isSetAndNotEmpty('image_height')) {
			$bValid = false;
		}
		
		// Check if the background exists
		if($bValid && in_array($FHANDLER->get('image_name').'.png', $this->CORE->getAvailableBackgroundImages())) {
			new GlobalMessage('ERROR', $this->CORE->getLang()->getText('The background does already exist.'));
			$bValid = false;
		}
		
		// FIXME: Validate all options
		
		// Store response data
		if($bValid === true) {
			// Return the data
			return Array('name' => $FHANDLER->get('image_name'),
			             'color' => $FHANDLER->get('image_color'),
			             'width' => $FHANDLER->get('image_width'),
			             'height' => $FHANDLER->get('image_height'));
		} else {
			return false;
		}
	}
}
?>
