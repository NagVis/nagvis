<?php
/** 
 * Class for managing the common page layout
 * Should be used by ALL pages of NagVis and NagVisWui
 *
 * @author 	Lars Michelsen <lars@vertical-visions.de>
 */
class GlobalPage {
	var $MAINCFG;
	
	// arrays for the header
	var $title;
	var $cssIncludes;
	var $jsIncludes;
	var $extHeader;
	
	// array for the body
	var $body;
	
	// logged in user
	var $user;
	
	var $languageRoot;
	
	/**
	 * Class Constructor
	 *
	 * @param 	GlobalMainCfg 	$MAINCFG
	 * @param 	Array			$prop		Array('name'=>'myform','id'=>'myform','method'=>'POST','action'=>'','onSubmit'=>'','cols'=>'2','enctype'=>''
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	function GlobalPage(&$MAINCFG,$givenProperties=Array()) {
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method GlobalPage::GlobalPage($MAINCFG,Array(...))');
		// Define default Properties here
		$defaultProperties = Array('title'=>'NagVis Page',
									'cssIncludes'=>Array('../nagvis/includes/css/style.css'),
									'jsIncludes'=>Array(),
									'extHeader'=>Array(),
									'allowedUsers'=>Array('EVERYONE'),
									'languageRoot'=>'global:global');
		$prop = array_merge($defaultProperties,$givenProperties);
		
		$this->body = Array();
		
		$this->MAINCFG = &$MAINCFG;
		$this->title = $prop['title'];
		$this->cssIncludes = $prop['cssIncludes'];
		$this->jsIncludes = $prop['jsIncludes'];
		$this->extHeader = array_merge(Array('<title>'.$prop['title'].'</title>'),$prop['extHeader']);
		$this->allowedUsers = $prop['allowedUsers'];
		$this->languageRoot = $prop['languageRoot'];
		
		$this->user = $this->getUser();
		$this->MAINCFG->setRuntimeValue('user',$this->user);
		
		$this->checkPreflight();
		if (DEBUG&&DEBUGLEVEL&1) debug('End method GlobalPage::GlobalPage()');
	}
	
	/**
	 * Gets the User
	 *
	 * @return	String	String with Username
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	function getUser() {
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method GlobalPage::getUser()');
		if(isset($_SERVER['PHP_AUTH_USER'])) {
			if (DEBUG&&DEBUGLEVEL&1) debug('End method GlobalPage::getUser(): '.$_SERVER['PHP_AUTH_USER']);
			return $_SERVER['PHP_AUTH_USER'];
		} elseif(isset($_SERVER['REMOTE_USER'])) {
			if (DEBUG&&DEBUGLEVEL&1) debug('End method GlobalPage::getUser(): '.$_SERVER['REMOTE_USER']);
			return $_SERVER['REMOTE_USER'];
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
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method GlobalPage::checkUser('.$printErr.')');
		if($this->user != '') {
			if (DEBUG&&DEBUGLEVEL&1) debug('End method GlobalPage::checkUser(): TRUE');
        	return TRUE;
        } else {
        	if($printErr) {
	            $this->messageToUser('ERROR','noUser');
            }
			if (DEBUG&&DEBUGLEVEL&1) debug('End method GlobalPage::checkUser(): FALSE');
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
	function checkPermissions($allowed,$printErr) {
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method GlobalPage::checkPermissions(Array(),'.$printErr.')');
		if(isset($allowed) && !in_array('EVERYONE', $allowed) && !in_array($this->MAINCFG->getRuntimeValue('user'),$allowed)) {
        	if($printErr) {
				$this->messageToUser('ERROR','permissionDenied','USER~'.$this->MAINCFG->getRuntimeValue('user'));
			}
			if (DEBUG&&DEBUGLEVEL&1) debug('End method GlobalPage::checkPermissions(): FALSE');
			return FALSE;
        } else {
			if (DEBUG&&DEBUGLEVEL&1) debug('End method GlobalPage::checkPermissions(): TRUE');
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
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method GlobalPage::checkPreflight()');
		$ret = TRUE;
		$ret = $ret & $this->checkUser(TRUE);
		$ret = $ret & $this->checkPermissions($this->allowedUsers,TRUE);
		
		if (DEBUG&&DEBUGLEVEL&1) debug('End method GlobalPage::checkPreflight(): '.$ret);
		return $ret;
	}
	
	/**
	 * Writes a Message to message array and does what to do...
	 * serverity: ERROR, WARNING, INFORMATION
	 *
	 * @param	String	$serverity	Serverity of the message (ERROR|WARNING|INFORMATION)
	 * @param	Integer	$id			Message Key in the language file
	 * @param	String	$vars		String to replace
	 * @author	Lars Michelsen <lars@vertical-visions.de>
     */
	function messageToUser($serverity='WARNING', $id, $vars='') {
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method GlobalPage::messageToUser('.$serverity.','.$id.','.$vars.')');
		switch($serverity) {
			case 'ERROR':
				// print the message box and kill the script
				$this->body = array_merge($this->body,$this->messageBox($serverity,$id,$vars));
				if (DEBUG&&DEBUGLEVEL&1) debug('End method GlobalPage::messageToUser()');
				$this->printPage();
				// end of script
			break;
			case 'WARNING':
			case 'INFORMATION':
				// add the message to message Array - the printing will be done later, the message array has to be superglobal, not a class variable
				$arrMessage = Array(Array('serverity' => $serverity, 'nr' => $id, 'vars' => $vars));
				if(is_array($this->MAINCFG->getRuntimeValue('userMessages'))) {
					$this->MAINCFG->setRuntimeValue('userMessages',array_merge($this->MAINCFG->getRuntimeValue('userMessages'),$arrMessage));
				} else {
					$this->MAINCFG->setRuntimeValue('userMessages',$arrMessage);
				}
			break;
		}
		if (DEBUG&&DEBUGLEVEL&1) debug('End method GlobalPage::messageToUser()');
	}
	
	/**
	 * Gets the messages to be printed to the user
	 *
	 * @return 	Array	HTML Code
     * @author	Lars Michelsen <lars@vertical-visions.de>
     */
	function getUserMessages() {
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method GlobalPage::getUserMessages()');
		$ret = Array();
		
		if(is_array($this->MAINCFG->getRuntimeValue('userMessages'))) {
			foreach($this->MAINCFG->getRuntimeValue('userMessages') AS $message) {
				$ret = array_merge($ret,$this->messageBox($message['serverity'], $message['nr'], $message['vars']));
			}
		}
		
		if (DEBUG&&DEBUGLEVEL&1) debug('End method GlobalPage::getUserMessages(): Array()');
		return $ret;
	}
	
	/**
	 * Creates a Messagebox for informations and errors
	 *
	 * @param	String	$serverity	Serverity of the message (ERROR|WARNING|INFORMATION)
	 * @param	String	$id			Number of the error messages
	 * @param	String	$vars		Strings to replace
	 * @return 	Array	HTML Code
     * @author	Michael Luebben <michael_luebben@web.de>
     * @author	Lars Michelsen <lars@vertical-visions.de>
     */
	function messageBox($serverity, $id, $vars) {
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method GlobalPage::messageBox('.$serverity.','.$id.','.$vars.')');
		$ret = Array();
		
		$LANG = new GlobalLanguage($this->MAINCFG,$this->languageRoot);
        
		switch($serverity) {
			case 'ERROR':
				$messageIcon = 'img_error.png';
			break;
			case 'WARNING':
				$messageIcon = 'img_warning.png';
			break;
			case 'INFORMATION':
				$messageIcon = 'img_information.png';
			break;
		}
		
		if($serverity == 'ERROR') {
			$ret[] = '<META http-equiv="refresh" content="60;">';
			$ret[] = '<style type="text/css">.main { background-color: yellow; }</style>';
			$ret[] = '<table width="100%" height="100%" border="0" cellpadding="0" cellspacing="0">';
			$ret[] = '<tr><td align="center">';
		}
		$ret[] = '<table class="messageBox" cellpadding="2" cellspacing="2">';
		$ret[] = '<tr><th width="40"><img src="'.$this->MAINCFG->getValue('paths','htmlimages').'internal/'.$messageIcon.'" align="left" />';
		$ret[] = '</th><th>'.$id.': '.$LANG->getMessageTitle($id,$vars).'</th></tr>';
		$ret[] = '<tr><td colspan="2">'.$LANG->getMessageText($id,$vars).'</td></tr></table>';
		if($serverity == 'ERROR') {
			$ret[] = '</td></tr></table>';
		}
		
		if (DEBUG&&DEBUGLEVEL&1) debug('End method GlobalPage::messageBox(): Array(...)');
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
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method GlobalPage::addBodyLine('.$line.')');
		$ret = addBodyLines($line);
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method GlobalPage::addBodyLine(): '.$ret);
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
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method GlobalPage::addBodyLines('.$lines.')');
		if(is_string($lines)) {
			$lines = Array($lines);	
		}
		$this->body = array_merge($this->body,$lines);
		
		if (DEBUG&&DEBUGLEVEL&1) debug('End method GlobalPage::addBodyLines(): TRUE');
		return TRUE;
	}
	
	/**
	 * Gets the Header of the HTML Page
	 *
	 * @return 	Array	HTML Code of the Header
	 * @author	Lars Michelsen <lars@vertical-visions.de>
     */
	function getHeader() {
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method GlobalPage::getHeader()');
		$ret = Array($this->getJsIncludes(),$this->getCssIncludes(),$this->getExtHeader());
		if (DEBUG&&DEBUGLEVEL&1) debug('End method GlobalPage::getHeader(): Array(...)');
		return $ret;
	}
	
	/**
	 * Gets the Body of the HTML Page
	 *
	 * @return 	Array	HTML Code of the Header
	 * @author	Lars Michelsen <lars@vertical-visions.de>
     */
	function getBody() {
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method GlobalPage::getBody()');
		$ret = $this->body;
		if (DEBUG&&DEBUGLEVEL&1) debug('End method GlobalPage::getBody()');
		return $ret;
	}
	
	/**
	 * Gets the formated lines of an array (Body/Head)
	 *
	 * @param	Array	HTML Code
	 * @return 	String	Formated HTML Code
	 * @author	Lars Michelsen <lars@vertical-visions.de>
     */
	function getLines($arr) {
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method GlobalPage::getLines(Array(...))');
		$ret = '';
		
		foreach($arr AS $line) {
			$ret .= "\t\t".$line."\n";
		}
		
		if (DEBUG&&DEBUGLEVEL&1) debug('End method GlobalPage::getLines(): HTML');
		return $ret;
	}
	
	/**
	 * Gets the lines of extended header informations
	 *
	 * @return  String	HTML Code
	 * @author	Lars Michelsen <lars@vertical-visions.de>
     */
	function getExtHeader() {
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method GlobalPage::getExtHeader()');
		$sRet = '';
		
		foreach($this->extHeader AS $var => $val) {
			$sRet .= $val;
		}
		
		if (DEBUG&&DEBUGLEVEL&1) debug('End method GlobalPage::getExtHeader(): Array(...)');
		return $sRet;
	}
	
	/**
	 * Gets the lines of javscript inclusions
	 *
	 * @return 	String	HTML Code
	 * @author	Lars Michelsen <lars@vertical-visions.de>
     */
	function getJsIncludes() {
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method GlobalPage::getJsIncludes()');
		$sRet = '';
		
		if(count($this->jsIncludes) > 0) {
			foreach($this->jsIncludes AS $var => $val) {
				$sRet .= '<script type="text/javascript" src="'.$val.'"></script>';
			}
		}
		
		if (DEBUG&&DEBUGLEVEL&1) debug('End method GlobalPage::getJsIncludes(): Array(...)');
		return $sRet;
	}
	
	/**
	 * Gets the lines of css file inclusions
	 *
	 * @return 	String	HTML Code
	 * @author	Lars Michelsen <lars@vertical-visions.de>
     */
	function getCssIncludes() {
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method GlobalPage::getCssIncludes()');
		$sRet = '';
		
		$sRet .= '<style type="text/css"><!--';
		if(count($this->cssIncludes) > 0) {
			foreach($this->cssIncludes AS $var => $val) {
				$sRet .= '@import url('.$val.'); ';
			}
		}
		$sRet .= '--></style>';
		
		if (DEBUG&&DEBUGLEVEL&1) debug('End method GlobalPage::getCssIncludes(): Array(...)');
		return $sRet;
	}
	
	/**
	 * Builds the HTML Page
	 *
	 * @return 	String	HTML Code
	 * @author	Lars Michelsen <lars@vertical-visions.de>
     */
	function buildPage() {
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method GlobalPage::buildPage()');
		$ret = '';
		
		$ret .= '<!DOCTYPE HTML SYSTEM>'."\n";
		$ret .= '<html><head>';
		$ret .= $this->getLines($this->getHeader());
		$ret .= '</head><body class="main">';
		$ret .= $this->getLines($this->getBody());
		$ret .= '</body></html>';
		
		if (DEBUG&&DEBUGLEVEL&1) debug('End method GlobalPage::buildPage(): Array(...)');
		return $ret;
	}
	
	/**
	 * Prints the complete HTML Page
	 *
	 * @author	Lars Michelsen <lars@vertical-visions.de>
     */
	function printPage() {
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method GlobalPage::printPage()');
		echo $this->buildPage();
		if (DEBUG&&DEBUGLEVEL&1) debug('End method GlobalPage::printPage()');
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
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method GlobalPage::getPage()');
		if (DEBUG&&DEBUGLEVEL&1) debug('End method GlobalPage::getPage()');
		return $this->buildPage();
	}
}
