<?php
/*****************************************************************************
 *
 * WuiBackendManagement.php - Class for managing backends in WUI
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
class WuiBackendManagement extends WuiPage {
	var $CORE;
	
	var $DEFBACKENDFORM;
	var $ADDBACKENDFORM;
	
	var $propCount;
	
	/**
	 * Class Constructor
	 *
	 * @param 	GlobalMainCfg 	$CORE
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	function WuiBackendManagement($CORE) {
		$this->CORE = $CORE;
		
		$this->propCount = 0;
		
		$prop = Array('title' => $this->CORE->getMainCfg()->getValue('internal', 'title'),
					  'jsIncludes'=> Array($CORE->getMainCfg()->getValue('paths', 'htmlbase').'/frontend/wui/js/BackendManagement.js'),
					  'extHeader'=> '');
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
		
		$this->DEFBACKENDFORM = new WuiForm(Array('name'=>'backend_default',
			'id'=>'backend_default',
			'method'=>'POST',
			'action'=>'./form_handler.php?myaction=mgt_backend_default',
			'onSubmit'=>'return update_param(\'backend_default\');',
			'cols'=>'2'));
		
		$code .= $this->DEFBACKENDFORM->initForm();
		$code .= $this->DEFBACKENDFORM->getCatLine($this->CORE->getLang()->getText('setDefaultBackend'));
		$code .= $this->getDefaultFields();
		$code .= $this->DEFBACKENDFORM->getSubmitLine($this->CORE->getLang()->getText('save'));
		$code .= $this->DEFBACKENDFORM->closeForm();
		
		$this->ADDBACKENDFORM = new WuiForm(Array('name'=>'backend_add',
			'id'=>'backend_add',
			'method'=>'POST',
			'action'=>'./form_handler.php?myaction=mgt_backend_add',
			'onSubmit'=>'return check_backend_add();',
			'cols'=>'2'));
		
		$code .= $this->ADDBACKENDFORM->initForm();
		$code .= $this->ADDBACKENDFORM->getCatLine($this->CORE->getLang()->getText('addBackend'));
		$code .= $this->getAddFields();
		$code .= $this->ADDBACKENDFORM->getSubmitLine($this->CORE->getLang()->getText('save'));
		$code .= $this->ADDBACKENDFORM->closeForm();
		
		$this->EDITBACKENDFORM = new WuiForm(Array('name'=>'backend_edit',
			'id'=>'backend_edit',
			'method'=>'POST',
			'action'=>'./form_handler.php?myaction=mgt_backend_edit',
			'onSubmit'=>'return check_backend_edit();',
			'cols'=>'2'));
		
		$code .= $this->EDITBACKENDFORM->initForm();
		$code .= $this->EDITBACKENDFORM->getCatLine($this->CORE->getLang()->getText('editBackend'));
		$code .= $this->getEditFields();
		$code .= $this->EDITBACKENDFORM->getSubmitLine($this->CORE->getLang()->getText('save'));
		$code .= $this->EDITBACKENDFORM->closeForm();
		
		$this->DELBACKENDFORM = new WuiForm(Array('name'=>'backend_del',
			'id'=>'backend_del',
			'method'=>'POST',
			'action'=>'./form_handler.php?myaction=mgt_backend_del',
			'onSubmit'=>'return check_backend_del();',
			'cols'=>'2'));
			
		$code .= $this->DELBACKENDFORM->initForm();
		$code .= $this->DELBACKENDFORM->getCatLine($this->CORE->getLang()->getText('delBackend'));
		$code .= $this->getDelFields();
		$code .= $this->DELBACKENDFORM->getSubmitLine($this->CORE->getLang()->getText('save'));
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
		
		foreach($this->CORE->getMainCfg()->getValidObjectType('backend') as $propname => $prop) {
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
		return $this->DEFBACKENDFORM->getSelectLine($this->CORE->getLang()->getText('defaultBackend'),'defaultbackend',$this->CORE->getDefinedBackends(),$this->CORE->getMainCfg()->getValue('defaults','backend',TRUE),TRUE);
	}
}
?>
