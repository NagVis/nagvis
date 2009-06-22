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
	protected $CORE;
	
	// arrays for the header
	private $title;
	private $cssIncludes;
	private $jsIncludes;
	private $extHeader;
	
	// String for the body
	private $body;
	
	// logged in user
	private $user;
	
	private $languageRoot;
	
	/**
	 * Class Constructor
	 *
	 * @param 	GlobalCore 	$CORE
	 * @param 	Array			$prop		Array('name'=>'myform','id'=>'myform','method'=>'POST','action'=>'','onSubmit'=>'','cols'=>'2','enctype'=>''
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	public function __construct($CORE, $givenProperties=Array()) {
		$this->CORE = $CORE;
		
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
		
		// Append additional header information
		$this->extHeader .= '<meta http-equiv="Content-Type" content="text/html;charset=utf-8">';
		$this->extHeader .= '<title>'.$prop['title'].'</title>';
		
		// Hint for iphone scaling (not verified - have no iphone)
		// http://www.nagios-portal.org/wbb/index.php?page=Thread&threadID=13885
		$this->extHeader .= '<meta name="viewport" content="width=480; initial-scale=0.6666; maximum-scale=1.0; minimum-scale=0.6666" />';
		
		$this->user = getUser();
		$this->CORE->MAINCFG->setRuntimeValue('user',$this->user);
		
		self::checkPreflight();
	}
	
	/**
	 * Checks for valid php version
	 *
	 * @param   Boolean $printErr
	 * @return  Boolean Is Check Successful?
	 * @author  Lars Michelsen <lars@vertical-visions.de>
	 */
	private function checkPHPVersion($printErr) {
		if(version_compare(PHP_VERSION, CONST_NEEDED_PHP_VERSION, ">=")) {
			return TRUE;
		} else {
			if($printErr) {
				new GlobalFrontendMessage('ERROR', $this->CORE->LANG->getText('wrongPhpVersion','CURRENT_VERSION~'.PHP_VERSION.',NEEDED_VERSION~'.CONST_NEEDED_PHP_VERSION));
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
	private function checkUser($printErr) {
		if($this->user != '') {
			return TRUE;
		} else {
			if($printErr) {
				new GlobalFrontendMessage('ERROR', $this->CORE->LANG->getText('noUser'));
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
	private function checkPermissions(&$allowed,$printErr) {
		if(isset($allowed) && !in_array('EVERYONE', $allowed) && !in_array($this->CORE->MAINCFG->getRuntimeValue('user'), $allowed)) {
			if($printErr) {
				new GlobalFrontendMessage('ERROR', $this->CORE->LANG->getText('permissionDenied','USER~'.$this->CORE->MAINCFG->getRuntimeValue('user')));
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
	private function checkPreflight() {
		$ret = TRUE;
		$ret = $ret & $this->checkUser(TRUE);
		$ret = $ret & $this->checkPHPVersion(TRUE);
		$ret = $ret & $this->checkPermissions($this->allowedUsers,TRUE);
		
		return $ret;
	}
	
	/**
	 * Adds one or more elements (lines) to the Body Array
	 *
	 * @param	String/Array	$lines	String or Array with HTML Code
	 * @return 	Boolean	TRUE
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	public function addBodyLines($lines) {
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
	private function getHeader() {
		return $this->getExtHeader().$this->getJsIncludes().$this->getCssIncludes();
	}
	
	/**
	 * Gets the Body of the HTML Page
	 *
	 * @return 	String	HTML Code of the Header
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	private function getBody() {
		$ret = $this->body;
		return $ret;
	}
	
	/**
	 * Gets the lines of extended header information
	 *
	 * @return  String	HTML Code
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	private function getExtHeader() {
		return $this->extHeader;
	}
	
	/**
	 * Gets the lines of javascript inclusions
	 *
	 * @return 	String	HTML Code
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	protected function getJsIncludes() {
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
	private function getCssIncludes() {
		$sRet = '';
		
		if(count($this->cssIncludes) > 0) {
			foreach($this->cssIncludes AS $var => &$val) {
				$sRet .= '<link rel="stylesheet" type="text/css" href="'.$val.'">';
			}
		}
		
		return $sRet;
	}
	
	/**
	 * Builds the HTML Page
	 *
	 * @return 	String	HTML Code
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	private function buildPage() {
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
	public function printPage() {
		echo $this->buildPage();
		if (DEBUG&&DEBUGLEVEL&4) debugFinalize();
		// printing the page is the end of everything else - good bye! ;-)
		exit;
	}
	
	/**
	 * Parses given Js code
	 *
	 * @param	String	$js	Javascript code to parse
	 * @return	Array 	Html
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	public function parseJs($js) {
		$ret = '';
		if($js != '') {
			
			$ret .= "<script type=\"text/javascript\"> ";
			if(is_array($js)) {
				$ret .= implode("\n", $js);
			} else {
				$ret .= $js;
			}
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
	public function resizeWindow($x,$y) {
		$ret = Array('window.resizeTo('.$x.','.$y.')');
		return $ret;
	}
}
?>
