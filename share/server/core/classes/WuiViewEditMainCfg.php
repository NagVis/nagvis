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
		
		$i = 1;
		foreach($this->CORE->getMainCfg()->getValidConfig() AS $cat => $arr) {
			// don't display backend,rotation and internal options
			if(!preg_match("/^backend/i", $cat) && !preg_match("/^internal$/i", $cat) && !preg_match("/^rotation/i", $cat)) {
				$ret .= '<tr><th class="cat" colspan="3"><h2>'.$cat.'</h2></th></tr>';
				
				foreach($arr AS $propname => $prop) {
					$class = '';
					$style = '';
					$isDefaultValue = false;
					
					// Set field type to show
					$fieldType = 'text';
					if(isset($prop['field_type'])) {
						$fieldType = $prop['field_type'];
					}
					
					// Don't show anything for hidden options
					if($fieldType !== 'hidden') {
						
						// Only get the really set value
						$val2 = $this->CORE->getMainCfg()->getValue($cat, $propname, true);
						
						// Check if depends_on and depends_value are defined and if the value
						// is equal. If not equal hide the field
						if(isset($prop['depends_on']) && isset($prop['depends_value'])
							&& $this->CORE->getMainCfg()->getValue($cat, $prop['depends_on'], false) != $prop['depends_value']) {
							
							$class = ' class="child-row"';
							$style = ' style="display:none;"';
						} elseif(isset($prop['depends_on']) && isset($prop['depends_value'])
							&& $this->CORE->getMainCfg()->getValue($cat, $prop['depends_on'], false) == $prop['depends_value']) {
							
							//$style .= 'display:;';
							$class = ' class="child-row"';
						}
						
						// Create a "helper" field which contains the real applied value
						if($val2 === false) {
							$defaultValue = $this->CORE->getMainCfg()->getValue($cat, $propname, false);
							
							if(is_array($defaultValue)) {
								$defaultValue = implode(',', $defaultValue);
							}
							
							$ret .= '<input type="hidden" id="_'.$cat.'_'.$propname.'" name="_'.$cat.'_'.$propname.'" value="'.$defaultValue.'" />';
						} else {
							$ret .= '<input type="hidden" id="_'.$cat.'_'.$propname.'" name="_'.$cat.'_'.$propname.'" value="" />';
						}
						
						# we add a line in the form
						$ret .= '<tr'.$class.$style.'>';
						$ret .= '<td class="tdlabel">'.$propname.'</td>';
						
						if(preg_match('/^TranslationNotFound:/', $this->CORE->getLang()->getText($propname)) > 0) {
							$ret .= '<td class="tdfield"></td>';
						} else {
							$ret .= '<td class="tdfield">';
							$ret .= "<img style=\"cursor:help\" src=\"./images/internal/help_icon.png\" onclick=\"javascript:alert('".$this->CORE->getLang()->getText($propname)." (".$this->CORE->getLang()->getText('defaultValue').": ".$arr[$propname]['default'].")')\" />";
							$ret .= '</td>';
						}
						
						$ret .= '<td class="tdfield">';
						switch($fieldType) {
							case 'dropdown':
								switch($propname) {
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
									case 'autoupdatefreq':
										$arrOpts = Array(Array('value'=>'0','label'=>$this->CORE->getLang()->getText('disabled')),
														 Array('value'=>'2','label'=>'2'),
														 Array('value'=>'5','label'=>'5'),
														 Array('value'=>'10','label'=>'10'),
														 Array('value'=>'25','label'=>'25'),
														 Array('value'=>'50','label'=>'50'));
									break;
								}
								
								$ret .= '<select id="'.$cat.'_'.$propname.'" name="'.$cat.'_'.$propname.'" onBlur="validateMainConfigFieldValue(this)">';
								$ret .= '<option value=""></option>';
								
								foreach($arrOpts AS $val) {
									if(is_array($val)) {
										$ret .= '<option value="'.$val['value'].'">'.$val['label'].'</option>';
									} else {
										$ret .= '<option value="'.$val.'">'.$val.'</option>';
									}
								}
								
								$ret .= '</select>';
								
								$ret .= '<script>document.edit_config.elements[\''.$cat.'_'.$propname.'\'].value = \''.$val2.'\';</script>';
							break;
							case 'boolean':
								$ret .= '<select id="'.$cat.'_'.$propname.'" name="'.$cat.'_'.$propname.'" onBlur="validateMainConfigFieldValue(this)">';
								$ret .= '<option value=""></option>';
								$ret .= '<option value="1">'.$this->CORE->getLang()->getText('yes').'</option>';
								$ret .= '<option value="0">'.$this->CORE->getLang()->getText('no').'</option>';
								$ret .= '</select>';
								
								$ret .= '<script>document.edit_config.elements[\''.$cat.'_'.$propname.'\'].value = \''.$val2.'\';</script>';
							break;
							case 'text':
								if(is_array($val2)) {
									$val2 = implode(',', $val2);
								}
								
								$ret .= '<input id="'.$cat.'_'.$propname.'" type="text" name="'.$cat.'_'.$propname.'" value="'.$val2.'" onBlur="validateMainConfigFieldValue(this)" />';
								
								if(isset($prop['locked']) && $prop['locked'] == 1) {
									$ret .= "<script>document.edit_config.elements['".$cat."_".$propname."'].disabled=true;</script>";
								}
							break;
						}
						
						// Initially toggle the depending fields
						$ret .= '<script>validateMainConfigFieldValue(document.getElementById("'.$cat.'_'.$propname.'"));</script>';
					
						$ret .= '</td>';
						$ret .= '</tr>';
					}
				}
	
				if($i % 3 == 0) {
					$ret .= '</table><table class="mytable" style="width:300px;float:left">';
				}			
			
				$i++;

			}
		}
		
		return $ret;
	}
}
?>
