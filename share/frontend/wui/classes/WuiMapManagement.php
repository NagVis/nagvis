<?php
/*****************************************************************************
 *
 * WuiMapManagement.php - Class for managing maps in WUI
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
class WuiMapManagement extends WuiPage {
	var $CORE;
	var $CREATEFORM;
	var $RENAMEFORM;
	var $DELETEFORM;
	var $EXPORTFORM;
	var $IMPORTFORM;
	var $propCount;
	
	/**
	 * Class Constructor
	 *
	 * @param	GlobalMainCfg	$CORE
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	function WuiMapManagement($CORE) {
		$this->CORE = $CORE;
		
		$prop = Array('title' => $this->CORE->getMainCfg()->getValue('internal', 'title'),
					  'jsIncludes'=>Array($CORE->getMainCfg()->getValue('paths', 'htmlbase').'/frontend/wui/js/MapManagement.js'),
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
		
		$this->CREATEFORM = new WuiForm(Array('name'=>'map_create',
			'id'=>'map_create',
			'method'=>'POST',
			'action' => 'javascript:submitFrontendForm(\''.$this->CORE->getMainCfg()->getValue('paths','htmlbase').'/server/core/ajax_handler.php?mod=Map&amp;act=doAdd\', \'map_create\');',
			'onSubmit'=>'return check_create_map();',
			'cols'=>'2'));
		
		$code .= $this->CREATEFORM->initForm();
		$code .= $this->CREATEFORM->getCatLine($this->CORE->getLang()->getText('createMap'));
		$code .= $this->getCreateFields();
		$code .= $this->getSubmit($this->CREATEFORM,$this->CORE->getLang()->getText('create'));
		
		$this->RENAMEFORM = new WuiForm(Array('name'=>'map_rename',
			'id' => 'map_rename',
			'method' => 'POST',
			'action' => 'javascript:submitFrontendForm(\''.$this->CORE->getMainCfg()->getValue('paths','htmlbase').'/server/core/ajax_handler.php?mod=Map&amp;act=doRename\', \'map_rename\');',
			'onSubmit' => 'return check_map_rename();',
			'cols' => '2'));
		
		$code .= $this->RENAMEFORM->initForm();
		$code .= $this->RENAMEFORM->getCatLine($this->CORE->getLang()->getText('renameMap'));
		$code .= $this->getRenameFields();
		$code .= $this->getSubmit($this->RENAMEFORM,$this->CORE->getLang()->getText('rename'));
		
		$this->DELETEFORM = new WuiForm(Array('name' => 'map_delete',
			'id' => 'map_delete',
			'method' => 'POST',
			'action' => 'javascript:submitFrontendForm(\''.$this->CORE->getMainCfg()->getValue('paths','htmlbase').'/server/core/ajax_handler.php?mod=Map&amp;act=doDelete\', \'map_delete\');',
			'onSubmit' => 'return check_map_delete();',
			'cols' => '2'));
		
		$code .= $this->DELETEFORM->initForm();
		$code .= $this->DELETEFORM->getCatLine($this->CORE->getLang()->getText('deleteMap'));
		$code .= $this->getDeleteFields();
		$code .= $this->getSubmit($this->DELETEFORM,$this->CORE->getLang()->getText('delete'));
		
		$this->EXPORTFORM = new WuiForm(Array('name'=>'map_export',
			'id'=>'map_export',
			'method'=>'POST',
			'action'=>'./form_handler.php?myaction=mgt_map_export',
			'onSubmit'=>'return check_map_export();',
			'cols'=>'2'));
		
		$code .= $this->EXPORTFORM->initForm();
		$code .= $this->EXPORTFORM->getCatLine($this->CORE->getLang()->getText('exportMap'));
		$code .= $this->getExportFields();
		$code .= $this->getSubmit($this->EXPORTFORM,$this->CORE->getLang()->getText('export'));
		
		$this->IMPORTFORM = new WuiForm(Array('name'=>'map_import',
			'id'=>'map_import',
			'method'=>'POST',
			'action'=>'./form_handler.php?myaction=mgt_map_import',
			'onSubmit'=>'return check_map_import();',
			'enctype'=>'multipart/form-data',
			'cols'=>'2'));
		
		$code .= $this->IMPORTFORM->initForm();
		$code .= $this->IMPORTFORM->getCatLine($this->CORE->getLang()->getText('importMap'));
		$code .= $this->getImportFields($this->IMPORTFORM);
		$code .= $this->getSubmit($this->IMPORTFORM,$this->CORE->getLang()->getText('import'));
		
		return $code;
	}
	
	/**
	 * Gets export fields
	 *
	 * @return	Array	HTML Code
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
     */
	function getExportFields() {
		return $this->EXPORTFORM->getSelectLine($this->CORE->getLang()->getText('chooseMap'),'map_name',$this->CORE->getAvailableMaps(),'');
	}
	
	/**
	 * Gets import fields
	 *
	 * @return	Array	HTML Code
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
     */
	function getImportFields() {
		return $this->IMPORTFORM->getHiddenField('MAX_FILE_SIZE','1000000')
		      .$this->IMPORTFORM->getFileLine($this->CORE->getLang()->getText('chooseMapFile'),'map_file','');
	}
	
	/**
	 * Gets delete fields
	 *
	 * @return	Array	HTML Code
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
     */
	function getDeleteFields() {
		return $this->DELETEFORM->getSelectLine($this->CORE->getLang()->getText('chooseMap'),'map',$this->CORE->getAvailableMaps(),'')
		      .$this->DELETEFORM->getHiddenField('map','')
		      .'<script>document.map_rename.map.value=document.mapname</script>';
	}
	
	/**
	 * Gets rename fields
	 *
	 * @return	Array	HTML Code
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
     */
	function getRenameFields() {
		return $this->RENAMEFORM->getSelectLine($this->CORE->getLang()->getText('chooseMap'), 'map', $this->CORE->getAvailableMaps(), '')
		      .$this->RENAMEFORM->getInputLine($this->CORE->getLang()->getText('newMapName'), 'map_new_name', '')
		      .$this->RENAMEFORM->getHiddenField('map_current', '')
		      .'<script>document.map_rename.map_current.value = mapname;</script>';
	}
	
	/**
	 * Gets create fields
	 *
	 * @return	Array	HTML Code
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
     */
	function getCreateFields() {
		return $this->CREATEFORM->getInputLine($this->CORE->getLang()->getText('mapName'),'map','')
		      .$this->CREATEFORM->getSelectLine($this->CORE->getLang()->getText('mapIconset'),'map_iconset',$this->CORE->getAvailableIconsets(),$this->CORE->getMainCfg()->getValue('defaults','icons'))
		      .$this->CREATEFORM->getSelectLine($this->CORE->getLang()->getText('background'),'map_image',$this->CORE->getAvailableBackgroundImages(),'');
	}
	
	/**
	 * Gets submit button for the given form
	 *
	 * @param	WuiForm	$FORM
	 * @return	Array	HTML Code
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
     */
	function getSubmit($FORM, $label) {
		return $FORM->getSubmitLine($label)
		      .$FORM->closeForm();
	}
}
?>
