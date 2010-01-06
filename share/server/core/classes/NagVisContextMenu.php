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
		
		$this->pathHtmlBase = $this->CORE->getMainCfg()->getValue('paths','htmlbase');
		$this->pathTemplateFile = $this->CORE->getMainCfg()->getValue('paths','templates').$this->templateName.'.context.html';
		
		$this->CACHE = new GlobalFileCache($this->CORE, $this->pathTemplateFile, $this->CORE->getMainCfg()->getValue('paths','var').'context-'.$this->templateName.'-'.$this->CORE->getLang()->getCurrentLanguage().'.cache');
		
		// Only use cache when there is
		// a) Some valid cache file
		// b) Some valid main configuration cache file
		// c) This cache file newer than main configuration cache file
		if($this->CACHE->isCached() !== -1
		  && $this->CORE->getMainCfg()->isCached() !== -1
		  && $this->CACHE->isCached() >= $this->CORE->getMainCfg()->isCached()) {
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
			$this->code = str_replace('[lang_connect_by_ssh]',$this->CORE->getLang()->getText('contextConnectBySsh'),$this->code);
		}
		
		if(strpos($this->code,'[lang_refresh_status]') !== FALSE) {
			$this->code = str_replace('[lang_refresh_status]',$this->CORE->getLang()->getText('contextRefreshStatus'),$this->code);
		}
		
		if(strpos($this->code,'[lang_reschedule_next_check]') !== FALSE) {
			$this->code = str_replace('[lang_reschedule_next_check]',$this->CORE->getLang()->getText('contextRescheduleNextCheck'),$this->code);
		}
		
		if(strpos($this->code,'[lang_schedule_downtime]') !== FALSE) {
			$this->code = str_replace('[lang_schedule_downtime]',$this->CORE->getLang()->getText('contextScheduleDowntime'),$this->code);
		}
		
		if(strpos($this->code,'[html_cgi]') !== FALSE) {
			$this->code = str_replace('[html_cgi]',$this->CORE->getMainCfg()->getValue('paths','htmlcgi'),$this->code);
		}
		
		if(strpos($this->code,'[html_base]') !== FALSE) {
			$this->code = str_replace('[html_base]',$this->CORE->getMainCfg()->getValue('paths','htmlbase'),$this->code);
		}
		
		if(strpos($this->code,'[html_templates]') !== FALSE) {
			$this->code = str_replace('[html_templates]',$this->CORE->getMainCfg()->getValue('paths','htmltemplates'),$this->code);
		}
		
		if(strpos($this->code,'[html_template_images]') !== FALSE) {
			$this->code = str_replace('[html_template_images]',$this->CORE->getMainCfg()->getValue('paths','htmltemplateimages'),$this->code);
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
				new GlobalMessage('ERROR', $this->CORE->getLang()->getText('contextTemplateNotReadable', 'FILE~'.$this->pathTemplateFile));
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
				new GlobalMessage('ERROR', $this->CORE->getLang()->getText('contextTemplateNotExists','FILE~'.$this->pathTemplateFile));
			}
			return FALSE;
		}
	}
}
?>
