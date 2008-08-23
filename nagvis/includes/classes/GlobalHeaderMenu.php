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
	private $BACKEND;
	private $OBJPAGE;
	
	private $headerTemplateName;
	private $pathHtmlBase;
	private $pathHeaderTemplateFile;
	
	private $code;
	
	/**
	 * Class Constructor
	 *
	 * @param 	GlobalCore 	$CORE
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	public function __construct(&$CORE, &$headerTemplateName, &$OBJ = NULL) {
		$this->CORE = &$CORE;
		$this->OBJPAGE = &$OBJ;
		$this->headerTemplateName = $headerTemplateName;
		
		$this->pathHtmlBase = $this->CORE->MAINCFG->getValue('paths','htmlbase');
		$this->pathHeaderTemplateFile = $this->CORE->MAINCFG->getValue('paths','headertemplate').'tmpl.'.$this->headerTemplateName.'.html';
		
		// Read the contents of the template file
		$this->readHeaderTemplate();
		// Replace all macros in the template code
		$this->replaceHeaderMenuMacros();
	}
	
	/**
	 * Replace all macros in the template code
	 *
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	public function replaceHeaderMenuMacros() {
		// Replace some macros
		if(get_class($this->OBJPAGE) == 'NagVisMapCfg') {
			$this->code = str_replace('[current_map]',$this->OBJPAGE->getName(),$this->code);
			$this->code = str_replace('[current_map_alias]',$this->OBJPAGE->getValue('global', '0', 'alias'),$this->code);
		} else {
			$this->code = str_replace('[current_map]', '',$this->code);
			$this->code = str_replace('[current_map_alias]', '',$this->code);
		}
		
		$this->code = str_replace('[html_base]',$this->pathHtmlBase,$this->code);
		$this->code = str_replace('[html_templates]',$this->CORE->MAINCFG->getValue('paths','htmlheadertemplates'),$this->code);
		$this->code = str_replace('[html_template_images]',$this->CORE->MAINCFG->getValue('paths','htmlheadertemplateimages'),$this->code);
		
		// Replace language macros
		$this->code = str_replace('[lang_select_map]',$this->CORE->LANG->getText('selectMap'),$this->code);
		$this->code = str_replace('[lang_edit_map]',$this->CORE->LANG->getText('editMap'),$this->code);
		$this->code = str_replace('[lang_need_help]',$this->CORE->LANG->getText('needHelp'),$this->code);
		$this->code = str_replace('[lang_online_doc]',$this->CORE->LANG->getText('onlineDoc'),$this->code);
		$this->code = str_replace('[lang_forum]',$this->CORE->LANG->getText('forum'),$this->code);
		$this->code = str_replace('[lang_support_info]',$this->CORE->LANG->getText('supportInfo'),$this->code);
		$this->code = str_replace('[lang_overview]',$this->CORE->LANG->getText('overview'),$this->code);
		$this->code = str_replace('[lang_instance]',$this->CORE->LANG->getText('instance'),$this->code);
		$this->code = str_replace('[lang_rotation_start]',$this->CORE->LANG->getText('rotationStart'),$this->code);
		$this->code = str_replace('[lang_rotation_stop]',$this->CORE->LANG->getText('rotationStop'),$this->code);
		$this->code = str_replace('[lang_refresh_start]',$this->CORE->LANG->getText('refreshStart'),$this->code);
		$this->code = str_replace('[lang_refresh_stop]',$this->CORE->LANG->getText('refreshStop'),$this->code);
		
		// Replace lists
		if(preg_match_all('/<!-- BEGIN (\w+) -->/',$this->code,$matchReturn) > 0) {
			foreach($matchReturn[1] AS &$key) {
				if($key == 'maplist') {
					$sReplace = '';
					preg_match_all('/<!-- BEGIN '.$key.' -->((?s).*)<!-- END '.$key.' -->/',$this->code,$matchReturn1);
					foreach($this->CORE->getAvailableMaps() AS $mapName) {
						$MAPCFG1 = new NagVisMapCfg($this->CORE, $mapName);
						$MAPCFG1->readMapConfig(1);
						
						if($MAPCFG1->getValue('global',0, 'show_in_lists') == 1 && ($mapName != '__automap' || ($mapName == '__automap' && $this->CORE->MAINCFG->getValue('automap', 'showinlists')))) {
							$sReplaceObj = str_replace('[map_name]',$MAPCFG1->getName(),$matchReturn1[1][0]);
							$sReplaceObj = str_replace('[map_alias]',$MAPCFG1->getValue('global', '0', 'alias'),$sReplaceObj);
							
							// Add defaultparams to map selection
							if($mapName == '__automap') {
								$sReplaceObj = str_replace('[url_params]', $this->CORE->MAINCFG->getValue('automap', 'defaultparams'), $sReplaceObj);
							} else {
								$sReplaceObj = str_replace('[url_params]','',$sReplaceObj);
							}
							
							// auto select current map
							if(get_class($this->OBJPAGE) == 'NagVisMapCfg' && ($mapName == $this->OBJPAGE->getName() || $mapName == '__automap' && isset($_GET['automap']))) {
								$sReplaceObj = str_replace('[selected]','selected="selected"',$sReplaceObj);
							} else {
								$sReplaceObj = str_replace('[selected]','',$sReplaceObj);
							}
							
							$sReplace .= $sReplaceObj;
						}
					}
					$this->code = preg_replace('/<!-- BEGIN '.$key.' -->(?:(?s).*)<!-- END '.$key.' -->/',$sReplace,$this->code);
				}
			}
		}
		
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
	private function readHeaderTemplate() {
		if($this->checkHeaderTemplateReadable(1)) {
			$this->code =  file_get_contents($this->pathHeaderTemplateFile);
		}
	}
	
	/**
	 * Checks for existing header template
	 *
	 * @param 	Boolean	$printErr
	 * @return	Boolean	Is Check Successful?
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	private function checkHeaderTemplateExists($printErr) {
		if(file_exists($this->pathHeaderTemplateFile)) {
			return TRUE;
		} else {
			if($printErr == 1) {
				$FRONTEND = new GlobalPage($this->CORE);
				$FRONTEND->messageToUser('WARNING', $this->CORE->LANG->getText('headerTemplateNotExists','FILE~'.$this->pathHeaderTemplateFile));
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
	private function checkHeaderTemplateReadable($printErr) {
		if($this->checkHeaderTemplateExists($printErr) && is_readable($this->pathHeaderTemplateFile)) {
			return TRUE;
		} else {
			if($printErr == 1) {
				$FRONTEND = new GlobalPage($this->CORE);
				$FRONTEND->messageToUser('WARNING', $this->CORE->LANG->getText('headerTemplateNotReadable','FILE~'.$this->pathHeaderTemplateFile));
			}
			return FALSE;
		}
	}
}
?>
