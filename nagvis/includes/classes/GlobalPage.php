<?php
/*****************************************************************************
 *
 * GlobalPage.php - Class for managing the common page layout
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
class GlobalPage {
	var $CORE;
	
	// arrays for the header
	var $title;
	var $cssIncludes;
	var $jsIncludes;
	var $extHeader;
	
	// String for the body
	var $body;
	
	// logged in user
	var $user;
	
	var $languageRoot;
	
	/**
	 * Class Constructor
	 *
	 * @param 	GlobalCore 	$CORE
	 * @param 	Array			$prop		Array('name'=>'myform','id'=>'myform','method'=>'POST','action'=>'','onSubmit'=>'','cols'=>'2','enctype'=>''
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	function GlobalPage(&$CORE, $givenProperties=Array()) {
		$this->CORE = &$CORE;
		
		// Define default Properties here
		$defaultProperties = Array('title'=>'NagVis Page',
									'cssIncludes' => Array($this->CORE->MAINCFG->getValue('paths','htmlbase').'/nagvis/includes/css/style.css'),
									'jsIncludes' => Array(),
									'extHeader' => '',
									'allowedUsers' => Array('EVERYONE'),
									'languageRoot' => 'nagvis');
		$prop = array_merge($defaultProperties,$givenProperties);
		
		$this->body = '';
		
		$this->title = $prop['title'];
		$this->cssIncludes = $prop['cssIncludes'];
		$this->jsIncludes = $prop['jsIncludes'];
		$this->allowedUsers = $prop['allowedUsers'];
		$this->languageRoot = $prop['languageRoot'];
		$this->extHeader = $prop['extHeader'];
		
		// Append additional header informations
		$this->extHeader .= '<meta http-equiv="Content-Type" content="text/html;charset=utf-8">';
		$this->extHeader .= '<title>'.$prop['title'].'</title>';
		
		$this->user = $this->getUser();
		$this->CORE->MAINCFG->setRuntimeValue('user',$this->user);
		
		self::checkPreflight();
	}
	
	/**
	 * Gets the User
	 *
	 * @return	String	String with Username
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	function getUser() {
		if(isset($_SERVER['PHP_AUTH_USER']) && $_SERVER['PHP_AUTH_USER'] != '') {
			return $_SERVER['PHP_AUTH_USER'];
		} elseif(isset($_SERVER['REMOTE_USER']) && $_SERVER['REMOTE_USER'] != '') {
			return $_SERVER['REMOTE_USER'];
		} else {
			return FALSE;
		}
	}

  /**
   * Checks for valid php version
   *
   * @param   Boolean $printErr
   * @return  Boolean Is Check Successful?
   * @author  Lars Michelsen <lars@vertical-visions.de>
   */
  function checkPHPVersion($printErr) {
		if(version_compare(PHP_VERSION, CONST_NEEDED_PHP_VERSION, ">=")) {
      return TRUE;
    } else {
			if($printErr) {
				$this->messageToUser('ERROR', $this->CORE->LANG->getText('wrongPhpVersion','CURRENT_VERSION~'.PHP_VERSION.',NEEDED_VERSION~'.CONST_NEEDED_PHP_VERSION));
      }
      return FALSE;
    }
	}
	
	/**
	 * Checks for logged in Users
	 *
	 * @param 	Boolean	$printErr
	 * @return	Boolean	Is Check Successful?
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	function checkUser($printErr) {
		if($this->user != '') {
					return TRUE;
		} else {
			if($printErr) {
				$this->messageToUser('ERROR', $this->CORE->LANG->getText('noUser'));
			}
			return FALSE;
		}
	}
	
	/**
	 * Checks for valid Permissions
	 *
	 * @param 	String 	$allowed	
	 * @param 	Boolean	$printErr
	 * @return	Boolean	Is Check Successful?
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	function checkPermissions(&$allowed,$printErr) {
		if(isset($allowed) && !in_array('EVERYONE', $allowed) && !in_array($this->CORE->MAINCFG->getRuntimeValue('user'), $allowed)) {
			if($printErr) {
				$this->messageToUser('ERROR', $this->CORE->LANG->getText('permissionDenied','USER~'.$this->CORE->MAINCFG->getRuntimeValue('user')));
			}
			return FALSE;
		} else {
			return TRUE;
		}
		return TRUE;
	}
	
	/**
	 * Does the Prflight checks before building the page
	 *
	 * @return	Boolean	Is Check Successful?
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	function checkPreflight() {
		$ret = TRUE;
		$ret = $ret & $this->checkUser(TRUE);
		$ret = $ret & $this->checkPHPVersion(TRUE);
		$ret = $ret & $this->checkPermissions($this->allowedUsers,TRUE);
		
		return $ret;
	}
	
	/**
	 * Writes a Message to message array and does what to do...
	 * serverity: ERROR, WARNING, INFORMATION
	 *
	 * @param	String	$serverity	Serverity of the message (ERROR|WARNING|INFORMATION)
	 * @param	String	$text		String to display as message
	 * @author	Lars Michelsen <lars@vertical-visions.de>
     */
	function messageToUser($serverity='WARNING', $text) {
		switch($serverity) {
			case 'ERROR':
			case 'INFO-STOP':
				// print the message box and kill the script
				$this->body .= $this->messageBox($serverity, $text);
				$this->printPage();
				// end of script
			break;
			case 'WARNING':
			case 'INFORMATION':
				// add the message to message Array - the printing will be done later, the message array has to be superglobal, not a class variable
				$arrMessage = Array(Array('serverity' => $serverity, 'text' => $text));
				if(is_array($this->CORE->MAINCFG->getRuntimeValue('userMessages'))) {
					$this->CORE->MAINCFG->setRuntimeValue('userMessages',array_merge($this->CORE->MAINCFG->getRuntimeValue('userMessages'),$arrMessage));
				} else {
					$this->CORE->MAINCFG->setRuntimeValue('userMessages',$arrMessage);
				}
			break;
		}
	}
	
	/**
	 * Gets the messages to be printed to the user
	 *
	 * @return 	String	HTML Code
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	function getUserMessages() {
		$ret = '';
		
		if(is_array($this->CORE->MAINCFG->getRuntimeValue('userMessages'))) {
			foreach($this->CORE->MAINCFG->getRuntimeValue('userMessages') AS $message) {
				$ret .= $this->messageBox($message['serverity'], $message['text']);
			}
		}
		
		return $ret;
	}
	
	/**
	 * Creates a Messagebox for informations and errors
	 *
	 * @param	String	$serverity	Serverity of the message (ERROR|WARNING|INFORMATION)
	 * @param	String	$text			Error message
	 * @return 	Array	HTML Code
	 * @author	Michael Luebben <michael_luebben@web.de>
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	function messageBox($serverity, $text) {
		$ret = '';
		
		switch($serverity) {
			case 'ERROR':
				$messageIcon = 'msg_error.png';
			break;
			case 'WARNING':
				$messageIcon = 'msg_warning.png';
			break;
			case 'INFORMATION':
			case 'INFO-STOP':
				$messageIcon = 'msg_information.png';
			break;
		}
		
		if($serverity == 'ERROR' || $serverity == 'INFO-STOP') {
			$ret .= '<META http-equiv="refresh" content="60;">';
			if($serverity == 'ERROR') {
				$ret .= '<style type="text/css">.main { background-color: yellow; }</style>';
			}
			$ret .= '<table width="100%" height="100%" border="0" cellpadding="0" cellspacing="0">';
			$ret .= '<tr><td align="center">';
		}
		$ret .= '<table class="messageBox" cellpadding="2" cellspacing="2">';
		$ret .= '<tr><th width="40"><img src="'.$this->CORE->MAINCFG->getValue('paths','htmlimages').'internal/'.$messageIcon.'" align="left" />';
		$ret .= '</th><th>'.$serverity.'</th></tr>';
		$ret .= '<tr><td colspan="2">'.$text.'</td></tr></table>';
		if($serverity == 'ERROR') {
			$ret .= '</td></tr></table>';
		}
		
		return $ret;
	}
	
	/**
	 * Adds an Element (line) to the Body Array
	 *
	 * @param	String	$line	HTML Code
	 * @return 	Boolean	TRUE
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 * @deprecated
	 */
	function addBodyLine($line) {
		$ret = addBodyLines($line);
		return $ret;
	}
	
	/**
	 * Adds on ore more elements (lines) to the Body Array
	 *
	 * @param	String/Array	$lines	String or Array with HTML Code
	 * @return 	Boolean	TRUE
	 * @author	Lars Michelsen <lars@vertical-visions.de>
     */
	function addBodyLines($lines) {
		if(is_array($lines)) {
			$lines = implode("\n", $lines);	
		}
		$this->body .= $lines;
		
		return TRUE;
	}
	
	/**
	 * Gets the Header of the HTML Page
	 *
	 * @return 	String	HTML Code of the Header
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	function getHeader() {
		return $this->getExtHeader().$this->getJsIncludes().$this->getCssIncludes();
	}
	
	/**
	 * Gets the Body of the HTML Page
	 *
	 * @return 	String	HTML Code of the Header
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	function getBody() {
		$ret = $this->body;
		return $ret;
	}
	
	/**
	 * Gets the lines of extended header informations
	 *
	 * @return  String	HTML Code
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	function getExtHeader() {
		return $this->extHeader;
	}
	
	/**
	 * Gets the lines of javscript inclusions
	 *
	 * @return 	String	HTML Code
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	function getJsIncludes() {
		$sRet = '';
		
		if(count($this->jsIncludes) > 0) {
			foreach($this->jsIncludes AS $var => &$val) {
				$sRet .= '<script type="text/javascript" src="'.$val.'"></script>';
			}
		}
		
		return $sRet;
	}
	
	/**
	 * Gets the lines of css file inclusions
	 *
	 * @return 	String	HTML Code
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	function getCssIncludes() {
		$sRet = '';
		
		if(count($this->cssIncludes) > 0) {
			$sRet .= '<style type="text/css"><!-- ';
				foreach($this->cssIncludes AS $var => &$val) {
					$sRet .= '@import url('.$val.'); ';
				}
			$sRet .= ' --></style>';
		}
		
		return $sRet;
	}
	
	/**
	 * Builds the HTML Page
	 *
	 * @return 	String	HTML Code
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	function buildPage() {
		$ret = '';
		
		$ret .= '<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">'."\n";
		$ret .= '<html><head>'."\n";
		$ret .= $this->getHeader();
		$ret .= '</head><body class="main">'."\n";
		$ret .= $this->getBody();
		$ret .= '</body></html>';
		
		return $ret;
	}
	
	/**
	 * Prints the complete HTML Page
	 *
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	function printPage() {
		echo $this->buildPage();
		if (DEBUG&&DEBUGLEVEL&4) debugFinalize();
		// printing the page, is the end of everything other - good bye! ;-)
		exit;
	}
	
	/**
	 * Gets the complete HTML Page
	 *
	 * @return	String	HTML Code
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	function getPage() {
		return $this->buildPage();
	}
	
	/**
	 * Parses given Js code
	 *
	 * @param	String	$js	Javascript code to parse
	 * @return	Array 	Html
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	function parseJs($js) {
		$ret = '';
		if($js != '') {
			
			$ret .= "<script type=\"text/javascript\" language=\"JavaScript\"> ";
			$ret .= "<!-- \n";
			if(is_array($js)) {
				$ret .= implode("\n", $js);
			} else {
				$ret .= $js;
			}
			$ret .= "\n //-->";
			$ret .= "</script>";
		}
		return $ret;
	}
	
	/**
	 * Resizes the window to individual calculated size
	 *
	 * @param	Int		$x	X-Coordinates
	 * @param	Int		$y	Y-Coordinates
	 * @return	Array	JS Code
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	function resizeWindow($x,$y) {
		$ret = Array('window.resizeTo('.$x.','.$y.')');
		return $ret;
	}
}
?>
