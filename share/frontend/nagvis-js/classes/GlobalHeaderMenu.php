<?php
/*****************************************************************************
 *
 * GlobalHeaderMenu.php - Class for handling the header menu
 *
 * Copyright (c) 2004-2009 NagVis Project (Contact: info@nagvis.org)
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
	private $TMPL;
	private $TMPLSYS;
	
	private $templateName;
	private $pathHtmlBase;
	private $pathTemplateFile;
	
	private $aMacros = Array();
	private $bRotation = false;
	
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
		$this->pathTemplateFile = $this->CORE->getMainCfg()->getValue('paths','pagetemplate').$this->templateName.'.header.html';
		
		// Initialize template system
		$this->TMPL = New FrontendTemplateSystem($this->CORE);
		$this->TMPLSYS = $this->TMPL->getTmplSys();
		
		// Read the contents of the template file
		$this->checkTemplateReadable(1);
	}
	
	/**
	 * PUBLIC setRotationEnabled()
	 *
	 * Tells the header menu that the current view is rotating
	 *
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	public function setRotationEnabled() {
		$this->bRotation = true;
	}
	
	/**
	 * Print the HTML code
	 *
	 * return   String  HTML Code
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	public function __toString() {
		// Get all macros
		$this->getMacros();
		
		// Build page based on the template file and the data array
		return $this->TMPLSYS->get($this->TMPL->getTmplFile('header'), $this->aMacros);
	}
	
	/**
	 * ORIVATE getMacros()
	 *
	 * Returns all macros for the header template
	 *
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	private function getMacros() {
		// First get all static macros
		$this->aMacros = $this->getStaticMacros();
		
		$objPageClass = get_class($this->OBJPAGE);
		
		if($objPageClass == 'NagVisMapCfg') {
			$this->aMacros['view_type'] = 'Map';
		} elseif($objPageClass == 'NagVisAutomapCfg') {
			$this->aMacros['view_type'] = 'Automap';
		}
		
		// In rotation?
		$this->aMacros['bRotation'] = $this->bRotation;
		
		// Check if the user is permitted to edit the current map/automap
		if(isset($this->aMacros['view_type']) && $this->CORE->getAuthorization() !== null && $this->CORE->getAuthorization()->isPermitted($this->aMacros['view_type'], 'edit', $this->OBJPAGE->getName())) {
			$this->aMacros['permitted_edit'] = true;
		} else {
			$this->aMacros['permitted_edit'] = false;
		}
		
		$this->aMacros['current_user'] = $this->AUTHORISATION->getAuthentication()->getUser();
		
		
		// Replace some special macros
		if($this->OBJPAGE !== null && ($objPageClass == 'NagVisMapCfg' || $objPageClass == 'NagVisAutomapCfg')) {
			$this->aMacros['current_map'] = $this->OBJPAGE->getName();
			$this->aMacros['current_map_alias'] = $this->OBJPAGE->getValue('global', '0', 'alias');
		} else {
			$this->aMacros['current_map'] = '';
			$this->aMacros['current_map_alias'] = '';
		}
		
		
		// Build map list
		$aMaps = Array();
		foreach($this->CORE->getAvailableMaps() AS $mapName) {
			$MAPCFG1 = new NagVisMapCfg($this->CORE, $mapName);
			$MAPCFG1->readMapConfig(1);
			
			// Only show maps which should be shown
			if($MAPCFG1->getValue('global', 0, 'show_in_lists') == 1) {
				// Only proceed permited objects
				if($this->CORE->getAuthorization() !== null && $this->CORE->getAuthorization()->isPermitted('Map', 'view', $mapName)) {
					$aMaps[$mapName] = Array();
					$aMaps[$mapName]['map_name'] = $MAPCFG1->getName();
					$aMaps[$mapName]['map_alias'] = $MAPCFG1->getValue('global', '0', 'alias');
					$aMaps[$mapName]['url_params'] = '';
					
					// auto select current map
					if($objPageClass == 'NagVisMapCfg' && $mapName == $this->OBJPAGE->getName()) {
						$aMaps[$mapName]['selected'] = true;
					} else {
						$aMaps[$mapName]['selected'] = false;
					}
				}
			}
		}
		$this->aMacros['maps'] = $aMaps;
		
		// Build automap list
		$aAutomaps = Array();
		$aAutomap = $this->CORE->getAvailableAutomaps();
		$numAutomaps = count($aAutomap);
		$i = 1;
		foreach($aAutomap AS $mapName) {
			$MAPCFG1 = new NagVisAutomapCfg($this->CORE, $mapName);
			$MAPCFG1->readMapConfig(1);
			
			// Only show maps which should be shown
			if($MAPCFG1->getValue('global',0, 'show_in_lists') == 1 && ($mapName != '__automap' || ($mapName == '__automap' && $this->CORE->getMainCfg()->getValue('automap', 'showinlists')))) {
				// Only proceed permited objects
				if($this->CORE->getAuthorization() !== null && $this->CORE->getAuthorization()->isPermitted('AutoMap', 'view', $mapName)) {
					$aAutomaps[$mapName] = Array();
					$aAutomaps[$mapName]['map_name'] = 'automap='.$MAPCFG1->getName();
					$aAutomaps[$mapName]['map_alias'] = $MAPCFG1->getValue('global', '0', 'alias');
					
					// Add defaultparams to map selection
					$aAutomaps[$mapName]['url_params'] = str_replace('&', '&amp;', $this->CORE->getMainCfg()->getValue('automap', 'defaultparams'));
					
					// auto select current map
					if($objPageClass == 'NagVisAutomapCfg' && $mapName == $this->OBJPAGE->getName()) {
						$aAutomaps[$mapName]['selected'] = true;
					} else {
						$aAutomaps[$mapName]['selected'] = false;
					}
					
					// Underline last element
					if($i == $numAutomaps) {
						$aAutomaps[$mapName]['class_underline'] = true;
					} else {
						$aAutomaps[$mapName]['class_underline'] = false;
					}
				}
			}
					
			$i++;
		}
		$this->aMacros['automaps'] = $aAutomaps;
		
		// Build language list
		$aLang = $this->CORE->getAvailableAndEnabledLanguages();
		$numLang = count($aLang);
		$i = 1;
		foreach($aLang AS $lang) {
			$aLangs[$lang] = Array();
			$aLangs[$lang]['language'] = $lang;
			
			// Underline last element
			if($i == $numLang) {
				$aLangs[$lang]['class_underline'] = true;
			} else {
				$aLangs[$lang]['class_underline'] = false;
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
			
			$aLangs[$lang]['lang_language_located'] = $languageLocated;
			
			$i++;
		}
		$this->aMacros['langs'] = $aLangs;
		
		// Select overview in header menu when no map shown
		if($objPageClass != 'NagVisMapCfg' && $objPageClass != 'NagVisAutomapCfg') {
			$this->aMacros['selected'] = true;
		}
	}
	
	/**
	 * PRIVATE getStaticMacros()
	 *
	 * Get all static macros for the template code
	 *
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	private function getStaticMacros() {
		// Replace paths and language macros
		$aReturn = Array('html_base' => $this->pathHtmlBase,
			'langSearch' => $this->CORE->getLang()->getText('Search'),
			'langUserAdd' => $this->CORE->getLang()->getText('Create User'),
			'html_images' => $this->CORE->getMainCfg()->getValue('paths','htmlimages'), 
			'html_templates' => $this->CORE->getMainCfg()->getValue('paths','htmlpagetemplates'), 
			'html_template_images' => $this->CORE->getMainCfg()->getValue('paths','htmlheadertemplateimages'),
			'current_language' => $this->CORE->getLang()->getCurrentLanguage(),
			'lang_choose_language' => $this->CORE->getLang()->getText('Choose Language'),
			'lang_user' => $this->CORE->getLang()->getText('User menu'),
			'lang_actions' => $this->CORE->getLang()->getText('Actions'),
			'lang_logged_in' => $this->CORE->getLang()->getText('Logged in'),
			'lang_change_password' => $this->CORE->getLang()->getText('Change password'),
			'lang_select_map' => $this->CORE->getLang()->getText('selectMap'),
			'lang_edit_map' => $this->CORE->getLang()->getText('editMap'),
			'lang_need_help' => $this->CORE->getLang()->getText('needHelp'),
			'lang_online_doc' => $this->CORE->getLang()->getText('onlineDoc'),
			'lang_forum' => $this->CORE->getLang()->getText('forum'),
			'lang_support_info' => $this->CORE->getLang()->getText('supportInfo'),
			'lang_overview' => $this->CORE->getLang()->getText('overview'),
			'lang_instance' => $this->CORE->getLang()->getText('instance'),
			'lang_logout' => $this->CORE->getLang()->getText('Logout'),
			'lang_rotation_start' => $this->CORE->getLang()->getText('rotationStart'),
			'lang_rotation_stop' => $this->CORE->getLang()->getText('rotationStop'),
			'authChangePasswordSupported' => $this->AUTHORISATION->getAuthentication()->checkFeature('changePassword'),
			'authCreateUserSupported' => $this->AUTHORISATION->getAuthentication()->checkFeature('createUser'));
		
		return $aReturn;
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
				new GlobalMessage('WARNING', $this->CORE->getLang()->getText('headerTemplateNotExists', Array('PATH' => $this->pathTemplateFile)));
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
				new GlobalMessage('WARNING', $this->CORE->getLang()->getText('headerTemplateNotReadable', Array('FILE' => $this->pathTemplateFile)));
			}
			return FALSE;
		}
	}
}
?>
