<?php
/*****************************************************************************
 *
 * WuiBackendManagement.php - Class for managing backends in WUI
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
class WuiBackendManagement extends GlobalPage {
	var $CORE;
	var $MAINCFG;
	var $LANG;
	
	var $DEFBACKENDFORM;
	var $ADDBACKENDFORM;
	
	var $propCount;
	
	/**
	 * Class Constructor
	 *
	 * @param 	GlobalMainCfg 	$CORE
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	function WuiBackendManagement(&$CORE) {
		$this->CORE = &$CORE;
		$this->MAINCFG = &$CORE->MAINCFG;
		$this->LANG = &$CORE->LANG;
		
		$this->propCount = 0;
		
		$prop = Array('title' => $this->CORE->MAINCFG->getValue('internal', 'title'),
					  'cssIncludes'=>Array('./includes/css/wui.css'),
					  'jsIncludes'=>Array('./includes/js/BackendManagement.js'),
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
		
		$this->DEFBACKENDFORM = new GlobalForm(Array('name'=>'backend_default',
			'id'=>'backend_default',
			'method'=>'POST',
			'action'=>'./form_handler.php?myaction=mgt_backend_default',
			'onSubmit'=>'return update_param(\'backend_default\');',
			'cols'=>'2'));
		
		$code .= $this->DEFBACKENDFORM->initForm();
		$code .= $this->DEFBACKENDFORM->getCatLine($this->LANG->getText('setDefaultBackend'));
		$code .= $this->getDefaultFields();
		$code .= $this->DEFBACKENDFORM->getSubmitLine($this->LANG->getText('save'));
		$code .= $this->DEFBACKENDFORM->closeForm();
		
		$this->ADDBACKENDFORM = new GlobalForm(Array('name'=>'backend_add',
			'id'=>'backend_add',
			'method'=>'POST',
			'action'=>'./form_handler.php?myaction=mgt_backend_add',
			'onSubmit'=>'return check_backend_add();',
			'cols'=>'2'));
		
		$code .= $this->ADDBACKENDFORM->initForm();
		$code .= $this->ADDBACKENDFORM->getCatLine($this->LANG->getText('addBackend'));
		$code .= $this->getAddFields();
		$code .= $this->ADDBACKENDFORM->getSubmitLine($this->LANG->getText('save'));
		$code .= $this->ADDBACKENDFORM->closeForm();
		
		$this->EDITBACKENDFORM = new GlobalForm(Array('name'=>'backend_edit',
			'id'=>'backend_edit',
			'method'=>'POST',
			'action'=>'./form_handler.php?myaction=mgt_backend_edit',
			'onSubmit'=>'return check_backend_edit();',
			'cols'=>'2'));
		
		$code .= $this->EDITBACKENDFORM->initForm();
		$code .= $this->EDITBACKENDFORM->getCatLine($this->LANG->getText('editBackend'));
		$code .= $this->getEditFields();
		$code .= $this->EDITBACKENDFORM->getSubmitLine($this->LANG->getText('save'));
		$code .= $this->EDITBACKENDFORM->closeForm();
		
		$this->DELBACKENDFORM = new GlobalForm(Array('name'=>'backend_del',
			'id'=>'backend_del',
			'method'=>'POST',
			'action'=>'./form_handler.php?myaction=mgt_backend_del',
			'onSubmit'=>'return check_backend_del();',
			'cols'=>'2'));
			
		$code .= $this->DELBACKENDFORM->initForm();
		$code .= $this->DELBACKENDFORM->getCatLine($this->LANG->getText('delBackend'));
		$code .= $this->getDelFields();
		$code .= $this->DELBACKENDFORM->getSubmitLine($this->LANG->getText('save'));
		$code .= $this->DELBACKENDFORM->closeForm();
		
		return $code;
	}
	
	/**
	 * Gets edit fields of the form
	 *
	 * @return	Array	HTML Code
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
     */
	function getEditFields() {
		return $this->EDITBACKENDFORM->getSelectLine('backendid',
		                                             'backendid', 
		                                             array_merge(Array(''=>''), $this->CORE->getDefinedBackends()), 
		                                             '',
		                                             TRUE,
		                                             "updateBackendOptions('', this.value, '".$this->EDITBACKENDFORM->getId()."');");
	}
	
	/**
	 * Gets delete fields of the form
	 *
	 * @return	Array	HTML Code
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
     */
	function getDelFields() {
		return $this->DELBACKENDFORM->getSelectLine('backendid','backendid',array_merge(Array(''=>''),$this->CORE->getDefinedBackends()),'',TRUE);
	}
	
	/**
	 * Gets add fields of the form
	 *
	 * @return	Array	HTML Code
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
     */
	function getAddFields() {
		$ret = '';
		$ret .= $this->ADDBACKENDFORM->getInputLine('backendid','backendid','',TRUE);
		
		foreach($this->MAINCFG->getValidObjectType('backend') as $propname => $prop) {
			if($propname == "backendtype") {
				$ret .= $this->ADDBACKENDFORM->getSelectLine($propname,
					$propname,array_merge(Array(''=>''),$this->CORE->getAvailableBackends()),
					'',
					$prop['must'],"updateBackendOptions(this.value, '', '".$this->ADDBACKENDFORM->getId()."');");
			}
		}
		
		return $ret;
	}
	
	/**
	 * Gets default fields of the form
	 *
	 * @return	Array	HTML Code
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
     */
	function getDefaultFields() {
		return $this->DEFBACKENDFORM->getSelectLine($this->LANG->getText('defaultBackend'),'defaultbackend',$this->CORE->getDefinedBackends(),$this->MAINCFG->getValue('defaults','backend',TRUE),TRUE);
	}
}
?>
