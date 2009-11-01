<?php
/*****************************************************************************
 *
 * GlobalHeaderMenu.php - Class for handling the header menu
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
class GlobalHeaderMenu {
	private $CORE;
	private $OBJPAGE;
	private $AUTHORISATION;
	
	private $templateName;
	private $pathHtmlBase;
	private $pathTemplateFile;
	
	private $code;
	
	/**
	 * Class Constructor
	 *
	 * @param 	GlobalCore 	$CORE
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	public function __construct($CORE, CoreAuthorisationHandler $AUTHORISATION, $templateName, $OBJ = NULL) {
		$this->CORE = $CORE;
		$this->OBJPAGE = $OBJ;
		$this->AUTHORISATION = $AUTHORISATION;
		$this->templateName = $templateName;
		
		$this->pathHtmlBase = $this->CORE->getMainCfg()->getValue('paths','htmlbase');
		$this->pathTemplateFile = $this->CORE->getMainCfg()->getValue('paths','headertemplate').'tmpl.'.$this->templateName.'.html';
		
		$this->CACHE = new GlobalFileCache($this->CORE, $this->pathTemplateFile, $this->CORE->getMainCfg()->getValue('paths','var').'header-'.$this->templateName.'-'.$this->CORE->getLang()->getCurrentLanguage().'.cache');
		
		// Only use cache when there is
		// a) Some valid cache file
		// b) Some valid main configuration cache file
		// c) This cache file newer than main configuration cache file
		if($this->CACHE->isCached() !== -1
		  && $this->CORE->getMainCfg()->isCached() !== -1
		  && $this->CACHE->isCached() >= $this->CORE->getMainCfg()->isCached()) {
			$this->code = $this->CACHE->getCache();
			
			// Replace dynamic macros after caching actions
			$this->replaceDynamicMacros();
		} else {
			// Read the contents of the template file
			if($this->readTemplate()) {
				// The static macros should be replaced before caching
				$this->replaceStaticMacros();
				
				// Build cache for the template
				$this->CACHE->writeCache($this->code, 1);
				
				// Replace dynamic macros after caching actions
				$this->replaceDynamicMacros();
			}
		}
	}
	
	/**
	 * Replace all dynamic macros in the template code
	 *
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	public function replaceDynamicMacros() {
		$arrKeys = Array();
		$arrVals = Array();
		
		// Replace current user
		$arrKeys[] = '[current_user]';
		// Get current username
		$arrVals[] = $this->AUTHORISATION->getAuthentication()->getUser();
		
		// Replace some special macros
		if($this->OBJPAGE !== null && (get_class($this->OBJPAGE) == 'NagVisMapCfg' || get_class($this->OBJPAGE) == 'NagVisAutomapCfg')) {
			$arrKeys[] = '[current_map]';
			$arrKeys[] = '[current_map_alias]';
			$arrVals[] = $this->OBJPAGE->getName();
			$arrVals[] = $this->OBJPAGE->getValue('global', '0', 'alias');
		} else {
			$arrKeys[] = '[current_map]';
			$arrKeys[] = '[current_map_alias]';
			$arrVals[] = '';
			$arrVals[] = '';
		}
		
		$this->code = str_replace($arrKeys, $arrVals, $this->code);
		
		// Replace lists
		if(preg_match_all('/<!-- BEGIN (\w+) -->/',$this->code,$matchReturn) > 0) {
			foreach($matchReturn[1] AS &$key) {
				if($key == 'maplist') {
					$sReplace = '';
					preg_match_all('/<!-- BEGIN '.$key.' -->((?s).*)<!-- END '.$key.' -->/', $this->code, $matchReturn1);
					
					foreach($this->CORE->getAvailableMaps() AS $mapName) {
						$MAPCFG1 = new NagVisMapCfg($this->CORE, $mapName);
						$MAPCFG1->readMapConfig(1);
						
						if($MAPCFG1->getValue('global', 0, 'show_in_lists') == 1) {
							// Only proceed permited objects
							if($this->CORE->getAuthorization() !== null && $this->CORE->getAuthorization()->isPermitted('Map', 'view', $mapName)) {
								$sReplaceObj = str_replace('[map_name]',$MAPCFG1->getName(), $matchReturn1[1][0]);
								$sReplaceObj = str_replace('[map_alias]',$MAPCFG1->getValue('global', '0', 'alias'), $sReplaceObj);
								
								// Add defaultparams to map selection
								$sReplaceObj = str_replace('[url_params]', '', $sReplaceObj);
								
								// auto select current map
								if(get_class($this->OBJPAGE) == 'NagVisMapCfg' && $mapName == $this->OBJPAGE->getName()) {
									$sReplaceObj = str_replace('[selected]', 'selected="selected"', $sReplaceObj);
								} else {
									$sReplaceObj = str_replace('[selected]', '', $sReplaceObj);
								}
								
								$sReplace .= $sReplaceObj;
							}
						}
					}
					
					$this->code = preg_replace('/<!-- BEGIN '.$key.' -->(?:(?s).*)<!-- END '.$key.' -->/',$sReplace,$this->code);
				} elseif($key == 'automaplist') {
					$sReplace = '';
					preg_match_all('/<!-- BEGIN '.$key.' -->((?s).*)<!-- END '.$key.' -->/', $this->code, $matchReturn1);
					
					foreach($this->CORE->getAvailableAutomaps() AS $mapName) {
						$MAPCFG1 = new NagVisAutomapCfg($this->CORE, $mapName);
						$MAPCFG1->readMapConfig(1);
						
						if($MAPCFG1->getValue('global',0, 'show_in_lists') == 1 && ($mapName != '__automap' || ($mapName == '__automap' && $this->CORE->getMainCfg()->getValue('automap', 'showinlists')))) {
							// Only proceed permited objects
							if($this->CORE->getAuthorization() !== null && $this->CORE->getAuthorization()->isPermitted('AutoMap', 'view', $mapName)) {
								$sReplaceObj = str_replace('[map_name]', 'automap='.$MAPCFG1->getName(), $matchReturn1[1][0]);
								$sReplaceObj = str_replace('[map_alias]', $MAPCFG1->getValue('global', '0', 'alias'), $sReplaceObj);
								
								// Add defaultparams to map selection
								$sReplaceObj = str_replace('[url_params]', $this->CORE->getMainCfg()->getValue('automap', 'defaultparams'), $sReplaceObj);
								
								// auto select current map
								if(get_class($this->OBJPAGE) == 'NagVisAutomapCfg' && $mapName == $this->OBJPAGE->getName()) {
									$sReplaceObj = str_replace('[selected]','selected="selected"',$sReplaceObj);
								} else {
									$sReplaceObj = str_replace('[selected]','',$sReplaceObj);
								}
								
								$sReplace .= $sReplaceObj;
							}
						}
					}
					
					$this->code = preg_replace('/<!-- BEGIN '.$key.' -->(?:(?s).*)<!-- END '.$key.' -->/',$sReplace,$this->code);
				} elseif($key == 'langlist') {
					$sReplace = '';
					preg_match_all('/<!-- BEGIN '.$key.' -->((?s).*)<!-- END '.$key.' -->/', $this->code, $matchReturn1);
					
					$aLang = $this->CORE->getAvailableAndEnabledLanguages();
					$numLang = count($aLang);
					$i = 1;
					foreach($aLang AS $lang) {
						$sReplaceObj = str_replace('[language]', $lang, $matchReturn1[1][0]);
						
						if($i == $numLang) {
							$sReplaceObj = str_replace('[class_underline]', 'class="underline"', $matchReturn1[1][0]);
						} else {
							$sReplaceObj = str_replace('[class_underline]', '', $matchReturn1[1][0]);
						}
						
						// Get translated language name
						switch($lang) {
							case 'en_US':
								$languageLocated = $this->CORE->getLang()->getText('en_US');
							break;
							case 'de_DE':
								$languageLocated = $this->CORE->getLang()->getText('de_DE');
							break;
							case 'es_ES':
								$languageLocated = $this->CORE->getLang()->getText('es_ES');
							break;
							case 'pt_BR':
								$languageLocated = $this->CORE->getLang()->getText('pt_BR');
							break;
							default:
								$languageLocated = $this->CORE->getLang()->getText($lang);
							break;
						}
						
						$sReplaceObj = str_replace('[lang_language_located]', $languageLocated, $sReplaceObj);
								
						$sReplace .= $sReplaceObj;
						$i++;
					}
					
					$this->code = preg_replace('/<!-- BEGIN '.$key.' -->(?:(?s).*)<!-- END '.$key.' -->/', $sReplace, $this->code);
				}
			}
		}
		
		// Select overview in header menu when no map shown
		if(get_class($this->OBJPAGE) != 'NagVisMapCfg') {
			$this->code = str_replace('[selected]','selected="selected"', $this->code);
		}
	}
	
	/**
	 * Replace all macros in the template code
	 *
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	public function replaceStaticMacros() {
		// Replace paths and language macros
		$arrKeys = Array('[html_base]', 
			'[html_images]', 
			'[html_templates]', 
			'[html_template_images]',
			'[current_language]',
			'[lang_choose_language]',
			'[lang_user]',
			'[lang_logged_in]',
			'[lang_select_map]',
			'[lang_edit_map]',
			'[lang_need_help]',
			'[lang_online_doc]',
			'[lang_forum]',
			'[lang_support_info]',
			'[lang_search]',
			'[lang_overview]',
			'[lang_instance]',
			'[lang_logout]',
			'[lang_rotation_start]',
			'[lang_rotation_stop]',
			'[lang_refresh_start]',
			'[lang_refresh_stop]');
		
		$arrVals = Array($this->pathHtmlBase, 
			$this->CORE->getMainCfg()->getValue('paths','htmlimages'), 
			$this->CORE->getMainCfg()->getValue('paths','htmlheadertemplates'), 
			$this->CORE->getMainCfg()->getValue('paths','htmlheadertemplateimages'),
			$this->CORE->getLang()->getCurrentLanguage(),
			$this->CORE->getLang()->getText('Choose Language'),
			$this->CORE->getLang()->getText('User menu'),
			$this->CORE->getLang()->getText('Logged in'),
			$this->CORE->getLang()->getText('selectMap'),
			$this->CORE->getLang()->getText('editMap'),
			$this->CORE->getLang()->getText('needHelp'),
			$this->CORE->getLang()->getText('onlineDoc'),
			$this->CORE->getLang()->getText('forum'),
			$this->CORE->getLang()->getText('supportInfo'),
			$this->CORE->getLang()->getText('Search'),
			$this->CORE->getLang()->getText('overview'),
			$this->CORE->getLang()->getText('instance'),
			$this->CORE->getLang()->getText('Logout'),
			$this->CORE->getLang()->getText('rotationStart'),
			$this->CORE->getLang()->getText('rotationStop'),
			$this->CORE->getLang()->getText('refreshStart'),
			$this->CORE->getLang()->getText('refreshStop'));
		
		$this->code = str_replace($arrKeys, $arrVals, $this->code);
		
		$this->code = '<div class="header">'.$this->code.'</div>';
	}
	
	/**
	 * Print the HTML code
	 *
	 * return   String  HTML Code
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	public function __toString () {
		return $this->code;
	}
	
	/**
	 * Reads the header template
	 *
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	private function readTemplate() {
		if($this->checkTemplateReadable(1)) {
			$this->code =  file_get_contents($this->pathTemplateFile);
			return TRUE;
		} else {
			return FALSE;
		}
	}
	
	/**
	 * Checks for existing header template
	 *
	 * @param 	Boolean	$printErr
	 * @return	Boolean	Is Check Successful?
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	private function checkTemplateExists($printErr) {
		if(file_exists($this->pathTemplateFile)) {
			return TRUE;
		} else {
			if($printErr == 1) {
				new GlobalMessage('WARNING', $this->CORE->getLang()->getText('headerTemplateNotExists','FILE~'.$this->pathTemplateFile));
			}
			return FALSE;
		}
	}
	
	/**
	 * Checks for readable header template
	 *
	 * @param 	Boolean	$printErr
	 * @return	Boolean	Is Check Successful?
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	private function checkTemplateReadable($printErr) {
		if($this->checkTemplateExists($printErr) && is_readable($this->pathTemplateFile)) {
			return TRUE;
		} else {
			if($printErr == 1) {
				new GlobalMessage('WARNING', $this->CORE->getLang()->getText('headerTemplateNotReadable','FILE~'.$this->pathTemplateFile));
			}
			return FALSE;
		}
	}
}
?>
