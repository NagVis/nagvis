<?php
/*****************************************************************************
 *
 * WuiEditMainCfg.php - Class editing main configuration file in WUI
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
class WuiEditMainCfg extends GlobalPage {
	var $CORE;
	var $MAINCFG;
	var $LANG;
	var $FORM;
	
	/**
	 * Class Constructor
	 *
	 * @param 	$MAINCFG GlobalMainCfg
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	function WuiEditMainCfg(&$CORE) {
		$this->CORE = &$CORE;
		$this->MAINCFG = &$CORE->MAINCFG;
		$this->LANG = &$CORE->LANG;
		
		$prop = Array('title' => $this->CORE->MAINCFG->getValue('internal', 'title'),
					  'cssIncludes'=>Array('./includes/css/wui.css'),
					  'jsIncludes'=>Array('./includes/js/EditMainCfg.js'),
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
		
		$this->FORM = new GlobalForm(Array('name' => 'edit_config',
			'id' => 'edit_config',
			'action' => 'javascript:(validateMainCfgForm()) ? formSubmit(\'edit_config\', \'updateMainCfg\') : alert(\'\');',
			'method' => '',
			'cols' => '3'));
		
		$code .= $this->getJsIncludes();
		$code .= $this->FORM->initForm();
		
		$code .= $this->getFields();
		$code .= $this->FORM->getSubmitLine($this->LANG->getText('save'));
		$code .= $this->FORM->closeForm();
		
		return $code;
	}
	
	/**
	 * Parses the Form fields
	 *
	 * @return	Array Html
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	function getFields() {
		$ret = '';
		
		foreach($this->MAINCFG->getValidConfig() AS $cat => $arr) {
			// don't display backend,rotation and internal options
			if(!preg_match("/^backend/i",$cat) && !preg_match("/^internal$/i",$cat) && !preg_match("/^rotation/i",$cat)) {
				$ret .= $this->FORM->getCatLine($cat);
				
				foreach($arr AS $key2 => $prop) {
					// ignore some vars
					if(isset($arr[$key2]['editable']) && $arr[$key2]['editable']) {
						//FIXME!!!!
						$val2 = $this->MAINCFG->getValue($cat, $key2, TRUE);
						
						# we add a line in the form
						$ret .= "<tr>";
						$ret .= "\t<td class=\"tdlabel\">".$key2."</td>";
						
						if(preg_match('/^TranslationNotFound:/',$this->LANG->getText($key2)) > 0) {
							$ret .= "\t<td class=\"tdfield\"></td>";
						} else {
							$ret .= "\t<td class=\"tdfield\">";
							$ret .= "\t\t<img style=\"cursor:help\" src=\"./images/internal/help_icon.png\" onclick=\"javascript:alert('".$this->LANG->getText($key2)." (".$this->LANG->getText('defaultValue').": ".$arr[$key2]['default'].")')\" />";
							$ret .= "\t</td>";
						}
						
						$ret .= "\t<td class=\"tdfield\">";
						switch($key2) {
							case 'language':
							case 'backend':
							case 'icons':
							case 'rotatemaps':
							case 'headermenu':
							case 'recognizeservices':
							case 'onlyhardstates':
							case 'usegdlibs':
							case 'autoupdatefreq':
							case 'headertemplate':
							case 'showinlists':
							case 'hovermenu':
							case 'hoverchildsshow':
								switch($key2) {
									case 'language':
										$arrOpts = $this->CORE->getAvailableLanguages();
									break;
									case 'backend':
										$arrOpts = $this->CORE->getDefinedBackends();
									break;
									case 'icons':
										$arrOpts = $this->CORE->getAvailableIconsets();
									break;
									case 'headertemplate':
										$arrOpts = $this->CORE->getAvailableHeaderTemplates();
									break;
									case 'rotatemaps':
									case 'headermenu':
									case 'recognizeservices':
									case 'onlyhardstates':
									case 'usegdlibs':
									case 'showinlists':
									case 'hovermenu':
									case 'hoverchildsshow':
										$arrOpts = Array(Array('value'=>'1','label'=>$this->LANG->getText('yes')),
														 Array('value'=>'0','label'=>$this->LANG->getText('no')));
									break;
									case 'autoupdatefreq':
										$arrOpts = Array(Array('value'=>'0','label'=>$this->LANG->getText('disabled')),
														 Array('value'=>'2','label'=>'2'),
														 Array('value'=>'5','label'=>'5'),
														 Array('value'=>'10','label'=>'10'),
														 Array('value'=>'25','label'=>'25'),
														 Array('value'=>'50','label'=>'50'));
									break;
								}
								
								$ret .= $this->FORM->getSelectField($cat."_".$key2, $arrOpts, '', '' , 'validateMainConfigFieldValue(this)');
							break;
							default:
								$ret .= $this->FORM->getInputField($cat."_".$key2, $val2, 'validateMainConfigFieldValue(this)');
								
								if(isset($prop['locked']) && $prop['locked'] == 1) {
									$ret .= "<script>document.edit_config.elements['".$cat."_".$key2."'].disabled=true;</script>";
								}
								
								if(is_array($val2)) {
									$val2 = implode(',',$val2);
								}
							break;
						}
						$ret .= "\t\t<script>document.edit_config.elements['".$cat."_".$key2."'].value='".$val2."';</script>";
						$ret .= "\t</td>";
						$ret .= "</tr>";
					}
				}
			}
		}
		return $ret;
	}
}
?>
