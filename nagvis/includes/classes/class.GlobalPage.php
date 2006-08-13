<?php
/** 
 * Class for managing the common page layout
 * Should be used by ALL pages of NagVis and NagVisWui
 *
 * @author 	Lars Michelsen <larsi@nagios-wiki.de>
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
	
	/**
	 * Class Constructor
	 *
	 * @param 	GlobalMainCfg 	$MAINCFG
	 * @param 	Array			$prop		Array('name'=>'myform','id'=>'myform','method'=>'POST','action'=>'','onSubmit'=>'','cols'=>'2','enctype'=>''
	 * @author 	Lars Michelsen <larsi@nagios-wiki.de>
	 */
	function GlobalPage(&$MAINCFG,$prop=Array('title'=>'NagVis Page','cssIncludes'=>Array('../nagvis/includes/css/style.css'),'jsIncludes'=>Array(),'extHeader'=>Array(),'allowedUsers'=>Array('EVERYONE'))) {
		$this->body = Array();
		
		$this->MAINCFG = &$MAINCFG;
		$this->title = $prop['title'];
		$this->cssIncludes = $prop['cssIncludes'];
		$this->jsIncludes = $prop['jsIncludes'];
		$this->extHeader = array_merge(Array("<title>".$prop['title']."</title>\n"),$prop['extHeader']);
		$this->allowedUsers = $prop['allowedUsers'];
		
		$this->user = $this->getUser();
		$this->MAINCFG->setRuntimeValue('user',$this->user);
		
		$this->checkPreflight();
	}
	
	/**
	 * Gets the User
	 *
	 * @return	String	String with Username
	 * @author 	Lars Michelsen <larsi@nagios-wiki.de>
	 */
	function getUser() {
		if(isset($_SERVER['PHP_AUTH_USER'])) {
			return $_SERVER['PHP_AUTH_USER'];
		} elseif(isset($_SERVER['REMOTE_USER'])) {
			return $_SERVER['REMOTE_USER'];
		}
	}
	
	/**
	 * Checks for logged in Users
	 *
	 * @param 	Boolean	$printErr
	 * @return	Boolean	Is Check Successful?
	 * @author 	Lars Michelsen <larsi@nagios-wiki.de>
     */
	function checkUser($printErr) {
		if($this->user != '') {
        	return TRUE;
        } else {
        	if($printErr) {
	            $this->messageToUser('ERROR',"14", "");
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
	 * @author 	Lars Michelsen <larsi@nagios-wiki.de>
     */
	function checkPermissions($allowed,$printErr) {
		if(isset($allowed) && !in_array('EVERYONE', $allowed) && !in_array($this->MAINCFG->getRuntimeValue('user'),$allowed)) {
        	if($printErr) {
				$this->messageToUser('ERROR',"4", "USER~".$this->MAINCFG->getRuntimeValue('user'));
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
	 * @author 	Lars Michelsen <larsi@nagios-wiki.de>
     */
	function checkPreflight() {
		$ret = TRUE;
		$ret = $ret & $this->checkUser(TRUE);
		$ret = $ret & $this->checkPermissions($this->allowedUsers,TRUE);
		
		return $ret;
	}
	
	/**
	 * Writes a Message to message array and does what to do...
	 * serverity: ERROR, WARNING, INFORMATION
	 *
	 * @param	String	$serverity	Serverity of the message (ERROR|WARNING|INFORMATION)
	 * @param	Integer	$nr			Message Nr./Key in the language file
	 * @param	String	$vars		String to replace
	 * @author	Lars Michelsen <larsi@nagios-wiki.de>
     */
	function messageToUser($serverity='WARNING', $nr, $vars) {
		switch($serverity) {
			case 'ERROR':
				// print the message box and kill the script
				$this->body = array_merge($this->body,$this->messageBox($nr,$vars));
				$this->printPage();
				// end of script
			break;
			case 'WARNING':
			case 'INFORMATION':
				// add the message to message Array - the printing will be done later, the message array has to be superglobal, not a class variable
				if(is_array($this->MAINCFG->getRuntimeValue('userMessages'))) {
					$this->MAINCFG->setRuntimeValue('userMessages',array_merge($this->MAINCFG->getRuntimeValue('userMessages'),Array('serverity' => $serverity, 'nr' => $nr, 'vars' => $vars)));
				} else {
					$this->MAINCFG->setRuntimeValue('userMessages',Array(Array('serverity' => $serverity, 'nr' => $nr, 'vars' => $vars)));
				}
			break;
		}
	}
	
	/**
	 * Gets the messages to be printed to the user
	 *
	 * @return 	Array	HTML Code
     * @author	Lars Michelsen <larsi@nagios-wiki.de>
     */
	function getUserMessages() {
		$ret = Array();
		
		if(is_array($this->MAINCFG->getRuntimeValue('userMessages'))) {
			foreach($this->MAINCFG->getRuntimeValue('userMessages') AS $message) {
				$ret = array_merge($ret,messageBox($message['nr'], $message['vars']));
			}
		}
		
		return $ret;
	}
	
	/**
	 * Creates a Messagebox for informations and errors
	 *
	 * @param	String	$messagnr	Number of the error messages
	 * @param	String	$vars		Strings to replace
	 * @return 	Array	HTML Code
     * @author	Michael Luebben <michael_luebben@web.de>
     */
	function messageBox($messagenr, $vars) {
		$ret = Array();
		
		$LANG = new GlobalLanguage($this->MAINCFG,'errors');
		$LANG->readLanguageFile();

		$vars = str_replace('~','=',$vars);
        $msg = $LANG->getTextReplace($messagenr,$vars);
        $msg = explode("~",$msg);
        
		$messageNr = $messagenr;
		// FIXME: Message Icon not in language files, better by serverity
		$messageIcon = $msg[0];
		$messageHead = $msg[1];
		$message = $msg[2];
		
		$ret[] = '<style type="text/css">.main { background-color: yellow }</style>';
		$ret[] = '<table width="100%" height="100%" border="0" cellpadding="0" cellspacing="0">';
		$ret[] = '<tr><td align="center" valign="middle">';
		$ret[] = "<table class=\"messageBox\" width=\"50%\" align=\"center\">";
		$ret[] = "\t<tr>";
		$ret[] = "\t\t<td class=\"messageBoxHead\" width=\"40\">";
		$ret[] = "\t\t\t<img src=\"".$this->MAINCFG->getValue('paths','htmlimages')."internal/".$messageIcon."\" align=\"left\" />";
		$ret[] = "\t\t</td>";
		$ret[] = "\t\t<td class=\"messageBoxHead\" align=\"center\">".$messagenr.":".$messageHead."</td>";
		$ret[] = "\t</tr>";
		$ret[] = "\t<tr>";
		$ret[] = "\t\t<td class=\"messageBoxMessage\" align=\"center\" colspan=\"2\">".$message."</td>";
		$ret[] = "\t</tr>";
		$ret[] = "</table>";
		$ret[] = "</td></tr></table>";
		
		return $ret;
	}
	
	/**
	 * Adds an Element (line) to the Body Array
	 *
	 * @param	String	$line	HTML Code
	 * @return 	Boolean	TRUE
	 * @author	Lars Michelsen <larsi@nagios-wiki.de>
     * @deprecated
     */
	function addBodyLine($line) {
		return addBodyLines($lines);
	}
	
	/**
	 * Adds on ore more elements (lines) to the Body Array
	 *
	 * @param	String/Array	$lines	String or Array with HTML Code
	 * @return 	Boolean	TRUE
	 * @author	Lars Michelsen <larsi@nagios-wiki.de>
     */
	function addBodyLines($lines) {
		if(is_string($lines)) {
			$lines = Array($lines);	
		}
		$this->body = array_merge($this->body,$lines);
		
		return TRUE;
	}
	
	/**
	 * Gets the Header of the HTML Page
	 *
	 * @return 	Array	HTML Code of the Header
	 * @author	Lars Michelsen <larsi@nagios-wiki.de>
     */
	function getHeader() {
		return array_merge($this->getJsIncludes(),$this->getCssIncludes(),$this->getExtHeader());
	}
	
	/**
	 * Gets the Body of the HTML Page
	 *
	 * @return 	Array	HTML Code of the Header
	 * @author	Lars Michelsen <larsi@nagios-wiki.de>
     */
	function getBody() {
		return $this->body;
	}
	
	/**
	 * Gets the formated lines of an array (Body/Head)
	 *
	 * @param	Array	HTML Code
	 * @return 	Array	Formated HTML Code
	 * @author	Lars Michelsen <larsi@nagios-wiki.de>
     */
	function getLines($arr) {
		$ret = '';
		
		foreach($arr AS $line) {
			$ret .= "\t\t".$line."\n";
		}
		
		return $ret;
	}
	
	/**
	 * Gets the lines of extended header informations
	 *
	 * @return 	Array	HTML Code
	 * @author	Lars Michelsen <larsi@nagios-wiki.de>
     */
	function getExtHeader() {
		$ret = Array();
		
		foreach($this->extHeader AS $var => $val) {
			$ret[] = $val;
		}
		
		return $ret;
	}
	
	/**
	 * Gets the lines of javscript inclusions
	 *
	 * @return 	Array	HTML Code
	 * @author	Lars Michelsen <larsi@nagios-wiki.de>
     */
	function getJsIncludes() {
		$ret = Array();
		
		if(count($this->jsIncludes) > 0) {
			foreach($this->jsIncludes AS $var => $val) {
				$ret[] = "<script type=\"text/javascript\" src=\"".$val."\"></script>";
			}
		}
		
		return $ret;
	}
	
	/**
	 * Gets the lines of css file inclusions
	 *
	 * @return 	Array	HTML Code
	 * @author	Lars Michelsen <larsi@nagios-wiki.de>
     */
	function getCssIncludes() {
		$ret = Array();
		
		if(count($this->cssIncludes) > 0) {
			foreach($this->cssIncludes AS $var => $val) {
				$ret[] = "<link href=\"".$val."\" rel=\"stylesheet\" type=\"text/css\" />";
			}
		}
		
		return $ret;
	}
	
	/**
	 * Builds the HTML Page
	 *
	 * @return 	String	HTML Code
	 * @author	Lars Michelsen <larsi@nagios-wiki.de>
     */
	function buildPage() {
		$ret = '';
		
		$ret  = "<html>\n";
		$ret .= "\t<head>\n";
		$ret .= $this->getLines($this->getHeader());
		$ret .= "\t</head>\n";
		$ret .= "\t<body class=\"main\" marginwidth=\"0\" marginheight=\"0\" topmargin=\"0\" leftmargin=\"0\">\n";
		$ret .= $this->getLines($this->getBody());
		$ret .= "\t</body>\n";
		$ret .= "</html>\n";
		
		return $ret;
	}
	
	/**
	 * Prints the complete HTML Page
	 *
	 * @author	Lars Michelsen <larsi@nagios-wiki.de>
     */
	function printPage() {
		echo $this->buildPage();
		// printing the page, is the end of everything other - good bye! ;-)
		exit;
	}
	
	/**
	 * Gets the complete HTML Page
	 *
	 * @return	String	HTML Code
	 * @author	Lars Michelsen <larsi@nagios-wiki.de>
     */
	function getPage() {
		return $this->buildPage();
	}
}