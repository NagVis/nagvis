<?php
/*****************************************************************************
 *
 * WuiFrontend.php - Class of NagVis frontend
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
class WuiFrontend extends GlobalPage {
	var $CORE;
	var $MAINCFG;
	var $MAPCFG;
	var $LANG;
	
	var $MAP;
	
	/**
	* Constructor
	*
	* @author Lars Michelsen <lars@vertical-visions.de>
	*/
	function WuiFrontend(&$CORE, &$MAPCFG) {
		$this->CORE = &$CORE;
		$this->MAINCFG = &$CORE->MAINCFG;
		$this->LANG = &$CORE->LANG;
		
		$this->MAPCFG = &$MAPCFG;
		
		$prop = Array('title'=>$this->CORE->MAINCFG->getValue('internal', 'title'),
					  'cssIncludes'=>Array('../nagvis/includes/css/style.css','./includes/css/wui.css','./includes/css/office_xp/office_xp.css'),
					  'jsIncludes'=>Array('../nagvis/includes/js/ajax.js',
							'../nagvis/includes/js/frontendMessage.js',
							'./includes/js/wui.js',
					  	'./includes/js/ajax.js',
						  './includes/js/jsdomenu.js',
						  './includes/js/jsdomenu.inc.js',
						  './includes/js/wz_jsgraphics.js',
						  './includes/js/wz_dragdrop.js'),
					  'extHeader'=> '<style type="text/css">body.main { background-color: '.$this->MAPCFG->getValue('global',0, 'background_color').'; }</style>',
					  'allowedUsers' => $this->MAPCFG->getValue('global', 0,'allowed_for_config'),
					  'languageRoot' => 'nagvis');
		parent::__construct($this->CORE, $prop);
	}
	
	function checkPreflight() {
		if(!$this->checkPHPMBString(1)) {
			exit;
		}
	}
	
	function checkPHPMBString($printErr=1) {
		if (!extension_loaded('mbstring')) {
			if($printErr) {
				$FRONTEND = new GlobalPage($this->CORE);
				$FRONTEND->messageToUser('ERROR', $this->CORE->LANG->getText('phpModuleNotLoaded','MODULE~mbstring'));
			}
			return FALSE;
		} else {
			return TRUE;
		}
	}
	
	/**
	 * Writes a Message to message array and does what to do...
	 * severity: ERROR, WARNING, INFORMATION
	 *
	 * @param	String	$severity	Severity of the message (ERROR|WARNING|INFORMATION)
	 * @param	String	$text		String to display as message
	 * @author	Lars Michelsen <lars@vertical-visions.de>
     */
	function messageToUser($severity='WARNING', $text) {
		switch($severity) {
			case 'ERROR':
			case 'INFO-STOP':
				// print the message box and kill the script
				$this->body .= $this->messageBox($severity, $text);
				$this->printPage();
				// end of script
			break;
			case 'WARNING':
			case 'INFORMATION':
				// add the message to message Array - the printing will be done later, the message array has to be superglobal, not a class variable
				$arrMessage = Array(Array('severity' => $severity, 'text' => $text));
				if(is_array($this->CORE->MAINCFG->getRuntimeValue('userMessages'))) {
					$this->CORE->MAINCFG->setRuntimeValue('userMessages',array_merge($this->CORE->MAINCFG->getRuntimeValue('userMessages'),$arrMessage));
				} else {
					$this->CORE->MAINCFG->setRuntimeValue('userMessages',$arrMessage);
				}
			break;
		}
	}
	
	/**
	* If enabled, the map is added to the page
	*
	* @author Lars Michelsen <lars@vertical-visions.de>
	*/
	function getMap() {
		$this->addBodyLines('<div id="mymap" class="map">');
		$this->MAP = new WuiMap($this->CORE, $this->MAPCFG);
		$this->addBodyLines($this->MAP->parseMap());
		$this->addBodyLines('</div>');
	}
	
	/**
	 * Adds the user messages to the page
	 *
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	function getMessages() {
		$this->addBodyLines($this->getUserMessages());	
	}
}
?>
