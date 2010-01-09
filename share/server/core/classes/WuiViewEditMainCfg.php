<?php
/*****************************************************************************
 *
 * WuiViewEditMainCfg.php - Class to render the main configuration edit dialog
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
class WuiViewEditMainCfg {
	private $CORE;
	private $AUTHENTICATION;
	private $AUTHORISATION;
	
	/**
	 * Class Constructor
	 *
	 * @param 	GlobalCore 	$CORE
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	public function __construct(CoreAuthHandler $AUTHENTICATION, CoreAuthorisationHandler $AUTHORISATION) {
		$this->CORE = GlobalCore::getInstance();
		$this->AUTHENTICATION = $AUTHENTICATION;
		$this->AUTHORISATION = $AUTHORISATION;
	}
	
	/**
	 * Parses the information in html format
	 *
	 * @return	String 	String with Html Code
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	public function parse() {
		// Initialize template system
		$TMPL = New CoreTemplateSystem($this->CORE);
		$TMPLSYS = $TMPL->getTmplSys();
		
		$aData = Array(
			'htmlBase' => $this->CORE->getMainCfg()->getValue('paths', 'htmlbase'),
			'formContents' => $this->getFields(),
			'langSave' => $this->CORE->getLang()->getText('save')
		);
		
		// Build page based on the template file and the data array
		return $TMPLSYS->get($TMPL->getTmplFile('default', 'wuiEditMainCfg'), $aData);
	}
	
	/**
	 * Parses the Form fields
	 *
	 * @return	Array Html
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 * FIXME: Recode to have all the HTML code in the template
	 */
	function getFields() {
		$ret = '';
		
		foreach($this->CORE->getMainCfg()->getValidConfig() AS $cat => $arr) {
			// don't display backend,rotation and internal options
			if(!preg_match("/^backend/i", $cat) && !preg_match("/^internal$/i", $cat) && !preg_match("/^rotation/i", $cat)) {
				$ret .= '<tr><th class="cat" colspan="3">'.$cat.'</th></tr>';
				
				foreach($arr AS $key2 => $prop) {
					// ignore some vars
					if(isset($arr[$key2]['editable']) && $arr[$key2]['editable']) {
						//FIXME!!!!
						$val2 = $this->CORE->getMainCfg()->getValue($cat, $key2, TRUE);
						
						# we add a line in the form
						$ret .= '<tr>';
						$ret .= '<td class="tdlabel">'.$key2.'</td>';
						
						if(preg_match('/^TranslationNotFound:/',$this->CORE->getLang()->getText($key2)) > 0) {
							$ret .= '<td class="tdfield"></td>';
						} else {
							$ret .= '<td class="tdfield">';
							$ret .= "<img style=\"cursor:help\" src=\"./images/internal/help_icon.png\" onclick=\"javascript:alert('".$this->CORE->getLang()->getText($key2)." (".$this->CORE->getLang()->getText('defaultValue').": ".$arr[$key2]['default'].")')\" />";
							$ret .= '</td>';
						}
						
						$ret .= '<td class="tdfield">';
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
										$arrOpts = Array(Array('value'=>'1','label'=>$this->CORE->getLang()->getText('yes')),
														 Array('value'=>'0','label'=>$this->CORE->getLang()->getText('no')));
									break;
									case 'autoupdatefreq':
										$arrOpts = Array(Array('value'=>'0','label'=>$this->CORE->getLang()->getText('disabled')),
														 Array('value'=>'2','label'=>'2'),
														 Array('value'=>'5','label'=>'5'),
														 Array('value'=>'10','label'=>'10'),
														 Array('value'=>'25','label'=>'25'),
														 Array('value'=>'50','label'=>'50'));
									break;
								}
								
								$ret .= '<select id="'.$cat.'_'.$key2.'" name="'.$cat.'_'.$key2.'" onBlur="validateMainConfigFieldValue(this)">';
								$ret .= '<option value=""></option>';
								
								foreach($arrOpts AS $val) {
									if(is_array($val)) {
										$ret .= '<option value="'.$val['value'].'">'.$val['label'].'</option>';
									} else {
										$ret .= '<option value="'.$val.'">'.$val.'</option>';
									}
								}
								
								$ret .= '</select>';
							break;
							default:
								$ret .= '<input id="'.$cat.'_'.$key2.'" type="text" name="'.$cat.'_'.$key2.'" value="'.$val2.'" onBlur="validateMainConfigFieldValue(this)" />';
								
								if(isset($prop['locked']) && $prop['locked'] == 1) {
									$ret .= "<script>document.edit_config.elements['".$cat."_".$key2."'].disabled=true;</script>";
								}
								
								if(is_array($val2)) {
									$val2 = implode(',',$val2);
								}
							break;
						}
						$ret .= '<script>document.edit_config.elements[\''.$cat.'_'.$key2.'\'].value = \''.$val2.'\';</script>';
						$ret .= '</td>';
						$ret .= '</tr>';
					}
				}
			}
		}
		
		return $ret;
	}
}
?>
