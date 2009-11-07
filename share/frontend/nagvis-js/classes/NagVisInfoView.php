<?php
/*****************************************************************************
 *
 * NagVisInfoView.php - Class for handling the rendering of the support
 *                      information page
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
class NagVisInfoView {
	private $CORE;
	
	/**
	 * Class Constructor
	 *
	 * @param 	GlobalCore 	$CORE
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	public function __construct($CORE) {
		$this->CORE = $CORE;
	}
	
	/**
	 * Parses the information in html format
	 *
	 * @return	String 	String with Html Code
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	public function parse() {
		// Initialize template system
		$TMPL = New FrontendTemplateSystem($this->CORE);
		$TMPLSYS = $TMPL->getTmplSys();
		
		$aData = Array(
			'pageTitle' => $this->CORE->getMainCfg()->getValue('internal', 'title') . ' &rsaquo; '.$this->CORE->getLang()->getText('supportInfo'),
			'htmlBase' => $this->CORE->getMainCfg()->getValue('paths', 'htmlbase'),
			'htmlTemplates' => $this->CORE->getMainCfg()->getValue('paths','htmlpagetemplates'), 
			'nagvisVersion' => CONST_VERSION,
			'phpVersion' => PHP_VERSION,
			'mysqlVersion' => shell_exec('mysql --version'),
			'os' => shell_exec('uname -a'),
			'serverSoftware' => $_SERVER['SERVER_SOFTWARE'],
			'remoteUser' => (isset($_SERVER['REMOTE_USER']) ? $_SERVER['REMOTE_USER'] : ''),
			'scriptFilename' => $_SERVER['SCRIPT_FILENAME'],
			'scriptName' => $_SERVER['SCRIPT_NAME'],
			'requestTime' => $_SERVER['REQUEST_TIME'].' (gmdate(): '.gmdate('r',$_SERVER['REQUEST_TIME']).')',
			'phpErrorReporting' => ini_get('error_reporting'),
			'phpSafeMode' => (ini_get('safe_mode')?"yes":"no"),
			'phpMaxExecTime' => ini_get('max_execution_time'),
			'phpMemoryLimit' => ini_get('memory_limit'),
			'phpLoadedExtensions' => implode(", ",get_loaded_extensions()),
			'userAgent' => $_SERVER['HTTP_USER_AGENT'],
		);
		
		// Build page based on the template file and the data array
		return $TMPLSYS->get($TMPL->getTmplFile('info'), $aData);
	}
}
?>
