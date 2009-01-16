<?php
/*****************************************************************************
 *
 * NagVisContextMenu.php - Class for handling the context menus
 *
 * Copyright (c) 2004-2009 NagVis Project (Contact: lars@vertical-visions.de)
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
class NagVisContextMenu {
	private $CORE;
	private $BACKEND;
	private $OBJPAGE;
	
	private $templateName;
	private $pathHtmlBase;
	private $pathContextTemplateFile;
	
	private $code;
	
	/**
	 * Class Constructor
	 *
	 * @param 	GlobalCore 	$CORE
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	public function __construct(&$CORE, $templateName, &$OBJ = NULL) {
		$this->CORE = &$CORE;
		$this->OBJPAGE = &$OBJ;
		$this->templateName = $templateName;
		
		$this->pathHtmlBase = $this->CORE->MAINCFG->getValue('paths','htmlbase');
		$this->pathTemplateFile = $this->CORE->MAINCFG->getValue('paths','contexttemplate').'tmpl.'.$this->templateName.'.html';
		
		// Read the contents of the template file
		$this->readTemplate();
	}
	
	
	/**
	 * readTemplate
	 *
	 * Reads the contents of the template file
	 *
	 * @return	String		HTML Code for the menu
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	function readTemplate() {
		// Read cache contents
		$arrContextCache = $this->CORE->MAINCFG->getRuntimeValue('context_cache');
		if(isset($arrContextCache[$this->templateName])) {
			$this->code = $arrContextCache[$this->templateName];
		} else {
			if($this->checkTemplateReadable(1)) {
				$this->code = file_get_contents($this->pathTemplateFile);
				
				// The static macros should be replaced before caching
				$this->replaceStaticMacros();
				
				// Build cache for the template
				if(!isset($arrContextCache[$this->templateName])) {
					$this->CORE->MAINCFG->setRuntimeValue('context_cache', Array($this->templateName => $this->code));
				} else {
					$arrCoverCache[$this->templateName] = $this->code;
					$this->CORE->MAINCFG->setRuntimeValue('context_cache', $arrContextCache);
				}
			}
		}
	}
	
	private function replaceStaticMacros() {
		// Replace the static macros (language, paths)
		/*if(strpos($this->code,'[lang_state_duration]') !== FALSE) {
			$this->code = str_replace('[lang_state_duration]',$this->CORE->LANG->getText('stateDuration'),$this->code);
		}*/
		
		if(strpos($this->code,'[html_cgi]') !== FALSE) {
			$this->code = str_replace('[html_cgi]',$this->CORE->MAINCFG->getValue('paths','htmlcgi'),$this->code);
		}
		
		if(strpos($this->code,'[html_base]') !== FALSE) {
			$this->code = str_replace('[html_base]',$this->CORE->MAINCFG->getValue('paths','htmlbase'),$this->code);
		}
		
		if(strpos($this->code,'[html_templates]') !== FALSE) {
			$this->code = str_replace('[html_templates]',$this->CORE->MAINCFG->getValue('paths','htmlcontexttemplates'),$this->code);
		}
		
		if(strpos($this->code,'[html_template_images]') !== FALSE) {
			$this->code = str_replace('[html_template_images]',$this->CORE->MAINCFG->getValue('paths','htmlcontexttemplateimages'),$this->code);
		}
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
	 * PRIVATE checkTemplateReadable()
	 *
	 * Checks if the requested template file is readable
	 *
	 * @param		Boolean		Switch for enabling/disabling error messages
	 * @return	Boolean		Check Result
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	private function checkTemplateReadable($printErr) {
		if($this->checkTemplateExists($printErr) && is_readable($this->pathTemplateFile)) {
			return TRUE;
		} else {
			if($printErr == 1) {
				new GlobalFrontendMessage('ERROR', $this->CORE->LANG->getText('contextTemplateNotReadable', 'FILE~'.$this->pathTemplateFile));
			}
			return FALSE;
		}
	}
	
	/**
	 * PRIVATE checkTemplateExists()
	 *
	 * Checks if the requested template file exists
	 *
	 * @param		Boolean		Switch for enabling/disabling error messages
	 * @return	Boolean		Check Result
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	private function checkTemplateExists($printErr) {
		if(file_exists($this->pathTemplateFile)) {
			return TRUE;
		} else {
			if($printErr == 1) {
				new GlobalFrontendMessage('ERROR', $this->CORE->LANG->getText('contextTemplateNotExists','FILE~'.$this->pathTemplateFile));
			}
			return FALSE;
		}
	}
}
?>
