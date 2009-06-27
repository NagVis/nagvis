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
	private $OBJPAGE;
	private $CACHE;
	
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
	public function __construct($CORE, $templateName, $OBJ = NULL) {
		$this->CORE = $CORE;
		$this->OBJPAGE = $OBJ;
		$this->templateName = $templateName;
		
		$this->pathHtmlBase = $this->CORE->MAINCFG->getValue('paths','htmlbase');
		$this->pathTemplateFile = $this->CORE->MAINCFG->getValue('paths','contexttemplate').'tmpl.'.$this->templateName.'.html';
		
		$this->CACHE = new GlobalFileCache($this->CORE, $this->pathTemplateFile, $this->CORE->MAINCFG->getValue('paths','var').'context-'.$this->templateName.'-cache');
		
		// Only use cache when there is
		// a) Some valid cache file
		// b) Some valid main configuration cache file
		// c) This cache file newer than main configuration cache file
		if($this->CACHE->isCached() !== -1
		  && $this->CORE->MAINCFG->isCached() !== -1
		  && $this->CACHE->isCached() >= $this->CORE->MAINCFG->isCached()) {
			$this->code = $this->CACHE->getCache();
		} else {
			// Read the contents of the template file
			if($this->readTemplate()) {
				// The static macros should be replaced before caching
				$this->replaceStaticMacros();
				
				// Build cache for the template
				$this->CACHE->writeCache($this->code, 1);
			}
		}
	}
	
	/**
	 * readTemplate
	 *
	 * Reads the contents of the template file
	 *
	 * @return	Boolean		Result
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	function readTemplate() {
		if($this->checkTemplateReadable(1)) {
			$this->code = file_get_contents($this->pathTemplateFile);
			return TRUE;
		} else {
			return FALSE;
		}
	}
	
	/**
	 * replaceStaticMacros
	 *
	 * Replaces static macros like paths and language strings in template code
	 *
	 * @author  Lars Michelsen <lars@vertical-visions.de>
	 */
	private function replaceStaticMacros() {
		// Replace the static macros (language, paths)
		if(strpos($this->code,'[lang_connect_by_ssh]') !== FALSE) {
			$this->code = str_replace('[lang_connect_by_ssh]',$this->CORE->LANG->getText('contextConnectBySsh'),$this->code);
		}
		
		if(strpos($this->code,'[lang_refresh_status]') !== FALSE) {
			$this->code = str_replace('[lang_refresh_status]',$this->CORE->LANG->getText('contextRefreshStatus'),$this->code);
		}
		
		if(strpos($this->code,'[lang_reschedule_next_check]') !== FALSE) {
			$this->code = str_replace('[lang_reschedule_next_check]',$this->CORE->LANG->getText('contextRescheduleNextCheck'),$this->code);
		}
		
		if(strpos($this->code,'[lang_schedule_downtime]') !== FALSE) {
			$this->code = str_replace('[lang_schedule_downtime]',$this->CORE->LANG->getText('contextScheduleDowntime'),$this->code);
		}
		
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
