<?php
/*****************************************************************************
 *
 * NagVisLoginView.php - Class for handling the login page
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
class NagVisLoginView {
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
			'generalProperties' => $this->CORE->getMainCfg()->parseGeneralProperties(),
			'pageTitle' => $this->CORE->getMainCfg()->getValue('internal', 'title') . ' &rsaquo; Log In',
			'htmlBase' => $this->CORE->getMainCfg()->getValue('paths', 'htmlbase'),
			'htmlJs' => $this->CORE->getMainCfg()->getValue('paths', 'htmljs'),
			'htmlCss' => $this->CORE->getMainCfg()->getValue('paths', 'htmlcss'),
			'formTarget' => $this->CORE->getMainCfg()->getValue('paths', 'htmlbase') . '/server/core/ajax_handler.php?mod=Auth&amp;act=login',
			'htmlTemplates' => $this->CORE->getMainCfg()->getValue('paths', 'htmlpagetemplates'),
			'htmlImages' => $this->CORE->getMainCfg()->getValue('paths', 'htmlimages'),
      'maxPasswordLength' => AUTH_MAX_PASSWORD_LENGTH,
      'maxUsernameLength' => AUTH_MAX_USERNAME_LENGTH,
      'langName' => $this->CORE->getLang()->getText('Name'),
      'langPassword' => $this->CORE->getLang()->getText('Password'),
      'langLogin' => $this->CORE->getLang()->getText('Login')
		);
		
		// Build page based on the template file and the data array
		return $TMPLSYS->get($TMPL->getTmplFile('login'), $aData);
	}
}
?>
