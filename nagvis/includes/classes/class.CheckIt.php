<?php
/**
* This Class makes some checks
*/

class checkit extends frontend {
	var $MAINCFG;
	var $MAPCFG;
	
	/**
	* Constructor
	*
	* @param config $MAINCFG
	* @param config $MAPCFG
	*
	* @author Lars Michelsen <larsi@nagios-wiki.de>
	*/
	function checkit(&$MAINCFG,&$MAPCFG) {
		$this->MAINCFG = $MAINCFG;
		$this->MAPCFG = $MAPCFG;
		parent::frontend($this->MAINCFG,$this->MAPCFG);
	}
	
	/**
	* Dummy-Check
	*
	* @author ML: FIXME!
	*/
	function check_dummy() {
		echo "create some checks!";
	}

	/**
	* Check the logged in User.
	*
	* @author FIXME!
	*/
    function check_user($printErr) {
        if(isset($_SERVER['PHP_AUTH_USER'])) {
        	$this->MAINCFG->setRuntimeValue('user',$_SERVER['PHP_AUTH_USER']);
        	
        	return TRUE;
        } elseif(isset($_SERVER['REMOTE_USER'])) {
			$MAINCFG->setRuntimeValue('user',$_SERVER['REMOTE_USER']);
			
			return TRUE;
        } else {
        	if($printErr) {
	            $this->openSite();
	            $this->messageBox("14", "");
	            $this->closeSite();
	            $this->printSite();
		            
            	exit;
            }
            return FALSE;
        }
    }
    
	/**
	* Check the permission from a loggin User (to view the Map).
	*
	* @author FIXME!
	* @author Michael Luebben <michael_luebben@web.de>
	*/
    function check_permissions($allowed,$printErr) {
        if(isset($allowed) && !in_array('EVERYONE', $allowed) && !in_array($this->MAINCFG->getRuntimeValue('user'),$allowed)) {
        	if($printErr) {
				$this->openSite();
				$this->messageBox("4", "USER~".$this->MAINCFG->getRuntimeValue('user'));
				$this->closeSite();
				$this->printSite();
			}
			return FALSE;
        } else {
        	return TRUE;
    	}
    }
		
	/**
	* Check is Rotate-Mode enable and create this
	*
	* @author Michael Luebben <michael_luebben@web.de>
	* @author Lars Michelsen <larsi@nagios-wiki.de>
	*/
    function check_rotate() {
        $maps = explode(",", $this->MAINCFG->getValue('global', 'maps'));
        if($this->MAINCFG->getValue('global', 'rotatemaps') == "1") {
            $Index = array_search($this->MAPCFG->getName(),$maps);
			if (($Index + 1) >= sizeof($maps)) {
            	// if end of array reached, go to the beginning...
				$Index = 0;
			} else {
				$Index++;
			}
            $map = $maps[$Index];
            
        	return " URL=index.php?map=".$map;
        }
    }

	/**
	* Check is GD-Libs installed, when GD-Libs are enabled.
	*
	* @author FIXME!
	* @author Michael Luebben <michael_luebben@web.de>
	*/
    function check_gd($printErr) {
		if ($this->MAINCFG->getValue('global', 'usegdlibs') == "1") {
        	if(!extension_loaded('gd')) {
        		if($printErr) {
	                $this->openSite();
	                $this->messageBox("15", "");
	                $this->closeSite();
	                $this->printSite();
	            }
	            return FALSE;
            } else {
            	return TRUE;
        	}
        }
    }
	
	/**
	* Check is the Language-File readable.
	*
	* @author FIXME!
	*/
    function check_langfile($printErr) {
        if(!is_readable($this->MAINCFG->getValue('paths', 'cfg').'languages/'.$this->MAINCFG->getValue('global', 'language').'.txt')) {
        	if($printErr) {
	            $this->openSite();
	            $this->messageBox("18", "LANGFILE~".$this->MAINCFG->getValue('paths', 'cfg').'languages/wui_'.$this->MAINCFG->getValue('global', 'language').'.txt');
	            $this->closeSite();
	            $this->printSite();
	        }
	        return FALSE;
        } else {
        	return TRUE;
        }
    }

	/**
	* Check is the Wui-Language-File readable.
	*
	* @author FIXME!
	*/
	function check_wuilangfile($printErr) {
        if(!is_readable($this->MAINCFG->getValue('paths', 'cfg').'languages/wui_'.$this->MAINCFG->getValue('global', 'language').'.txt')) {
        	if($printErr) {
	            $this->openSite();
	            $this->messageBox("18", "LANGFILE~".$this->MAINCFG->getValue('paths', 'cfg').'languages/wui_'.$this->MAINCFG->getValue('global', 'language').'.txt');
	            $this->closeSite();
	            $this->printSite();
	        }
	        return FALSE;
	    } else {
	    	return TRUE;
	    }
	}
}
?>
