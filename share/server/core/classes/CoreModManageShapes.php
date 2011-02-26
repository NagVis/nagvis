<?php
/*******************************************************************************
 *
 * CoreModManageShapes.php - Core Map module to manage shapes in WUI
 *
 * Copyright (c) 2004-2011 NagVis Project (Contact: info@nagvis.org)
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
class CoreModManageShapes extends CoreModule {
	private $name = null;
	
	public function __construct(GlobalCore $CORE) {
		$this->sName = 'ManageShapes';
		$this->CORE = $CORE;
		
		// Register valid actions
		$this->aActions = Array(
			// WUI specific actions
			'view'     => 'manage',
			'doUpload' => 'manage',
			'doDelete' => 'manage',
		);
	}
	
	public function handleAction() {
		$sReturn = '';
		
		if($this->offersAction($this->sAction)) {
			switch($this->sAction) {
				case 'view':
					$VIEW = new WuiViewManageShapes($this->AUTHENTICATION, $this->AUTHORISATION);
					$sReturn = json_encode(Array('code' => $VIEW->parse()));
				break;
				case 'doDelete':
					$this->handleResponse('handleResponseDelete', 'doDelete',
						                    $this->CORE->getLang()->getText('The shape has been deleted.'),
																$this->CORE->getLang()->getText('The shape could not be deleted.'),
					                      1);
				break;
				case 'doUpload':
					if($this->handleResponse('handleResponseDoUpload', 'doUpload'))
						header('Location:'.$_SERVER['HTTP_REFERER']);
				break;
			}
		}
		
		return $sReturn;
	}

	protected function handleResponseDoUpload() {
		$FHANDLER = new CoreRequestHandler($_FILES);
		$this->verifyValuesSet($FHANDLER, Array('image_file'));
		return Array('image_file' => $FHANDLER->get('image_file'));
	}

	protected function doUpload($a) {
		if(!is_uploaded_file($a['image_file']['tmp_name']))
			new GlobalMessage('ERROR', $this->CORE->getLang()->getText('The file could not be uploaded (Error: [ERROR]).',
              Array('ERROR' => $a['image_file']['error'].': '.$this->CORE->getUploadErrorMsg($a['image_file']['error']))));
	
		$fileName = $a['image_file']['name'];

		if(!preg_match(MATCH_PNG_GIF_JPG_FILE, $fileName))
			new GlobalMessage('ERROR', $this->CORE->getLang()->getText('The uploaded file is no image (png,jpg,gif) file or contains unwanted chars.'));

		$filePath = $this->CORE->getMainCfg()->getPath('sys', 'local', 'shapes').$fileName;
		return move_uploaded_file($a['image_file']['tmp_name'], $filePath) && $this->CORE->setPerms($filePath);
	}
	
	protected function doDelete($a) {
		$path = $this->CORE->getMainCfg()->getPath('sys', '', 'shapes', $a['image']);
		if($path !== '')
			return unlink($path);
		else
			return false;
	}
	
	protected function handleResponseDelete() {
		$FHANDLER = new CoreRequestHandler($_POST);
		$attr = Array('image' => MATCH_PNG_GIF_JPG_FILE);
		$this->verifyValuesSet($FHANDLER,   $attr);
		$this->verifyValuesMatch($FHANDLER, $attr);
		
		if(!in_array($FHANDLER->get('image'), $this->CORE->getAvailableShapes()))
			new GlobalMessage('ERROR', $this->CORE->getLang()->getText('The shape does not exist.'));
		
		return Array('image' => $FHANDLER->get('image'));
	}
}
?>
