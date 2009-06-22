<?php
/*****************************************************************************
 *
 * WuiBackgroundManagement.php - Class for managing background images in WUI
 *
 * Copyright (c) 2004-2008 NagVis Project (Contact: lars@vertical-visions.de)
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
class WuiBackgroundManagement extends GlobalPage {
	var $CORE;
	var $MAINCFG;
	var $LANG;
	var $ADDFORM;
	var $DELFORM;
	var $propCount;
	
	/**
	 * Class Constructor
	 *
	 * @param	GlobalMainCfg	$CORE
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	function WuiBackgroundManagement(&$CORE) {
		$this->CORE = &$CORE;
		$this->MAINCFG = &$CORE->MAINCFG;
		$this->LANG = &$CORE->LANG;
		
		$this->propCount = 0;
		
		$prop = Array('title' => $this->CORE->MAINCFG->getValue('internal', 'title'),
					  'jsIncludes'=>Array('./includes/js/BackgroundManagement.js'),
					  'extHeader'=> '',
					  'allowedUsers' => $this->CORE->MAINCFG->getValue('wui','allowedforconfig'),
					  'languageRoot' => 'nagvis');
		parent::__construct($CORE, $prop);
	}
	
	/**
	* If enabled, the form is added to the page
	*
	* @author Lars Michelsen <lars@vertical-visions.de>
	*/
	function getForm() {
		$code = '';
		$code .= $this->getJsIncludes();
		
		$this->CREATEFORM = new GlobalForm(Array('name'=>'create_image',
			'id'=>'create_image',
			'method'=>'POST',
			'action'=>'./form_handler.php?myaction=mgt_image_create',
			'onSubmit'=>'return check_image_create();',
			'cols'=>'2'));
		
		$code .= $this->CREATEFORM->initForm();
		$code .= $this->CREATEFORM->getCatLine($this->LANG->getText('createBackground'));
		$code .= $this->getCreateFields();
		$code .= $this->CREATEFORM->getSubmitLine($this->LANG->getText('create'));
		$code .= $this->CREATEFORM->closeForm();
		
		$this->ADDFORM = new GlobalForm(Array('name'=>'new_image',
			'id'=>'new_image',
			'method'=>'POST',
			'action'=>'./form_handler.php?myaction=mgt_image_upload',
			'onSubmit'=>'return check_image_add();',
			'enctype'=>'multipart/form-data',
			'cols'=>'2'));
		
		$code .= $this->ADDFORM->initForm();
		$code .= $this->ADDFORM->getCatLine($this->LANG->getText('uploadBackground'));
		$code .= $this->getAddFields();
		$code .= $this->ADDFORM->getSubmitLine($this->LANG->getText('upload'));
		$code .= $this->ADDFORM->closeForm();
		
		$this->DELFORM = new GlobalForm(Array('name'=>'image_delete',
			'id'=>'image_delete',
			'method'=>'POST',
			'action'=>'./form_handler.php?myaction=mgt_image_delete',
			'onSubmit'=>'return check_image_delete();',
			'cols'=>'2'));
		
		$code .= $this->DELFORM->initForm();
		$code .= $this->DELFORM->getCatLine($this->LANG->getText('deleteBackground'));
		$this->propCount++;
		$code .= $this->getDelFields();
		$this->propCount++;
		$code .= $this->ADDFORM->getSubmitLine($this->LANG->getText('delete'));
		$code .= $this->ADDFORM->closeForm();
		
		return $code;
	}
	
	/**
	 * Gets delete fields
	 *
	 * @return	Array	HTML Code
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
     */
	function getDelFields() {
		return $this->DELFORM->getSelectLine($this->LANG->getText('chooseImage'),'map_image',$this->CORE->getAvailableBackgroundImages(),'');
	}
	
	/**
	 * Gets new image fields
	 *
	 * @return	Array	HTML Code
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
     */
	function getAddFields() {
		return $this->ADDFORM->getHiddenField('MAX_FILE_SIZE','3000000').
		       $this->ADDFORM->getFileLine($this->LANG->getText('chooseImage'),'image_file','');
	}
	
	/**
	 * Gets create image fields
	 *
	 * @return	Array	HTML Code
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
     */
	function getCreateFields() {
		return $this->CREATEFORM->getInputLine($this->LANG->getText('backgroundName'),'image_name','',TRUE)
		      .$this->CREATEFORM->getInputLine($this->LANG->getText('backgroundColor'),'image_color','#',TRUE)
		      .$this->CREATEFORM->getInputLine($this->LANG->getText('backgroundWidth'),'image_width','',TRUE)
		      .$this->CREATEFORM->getInputLine($this->LANG->getText('backgroundHeight'),'image_height','',TRUE);
	}
}
?>
