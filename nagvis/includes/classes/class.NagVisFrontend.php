<?php
/**
 * Class for parsing the NagVis Frontend
 */
class NagVisFrontend extends GlobalPage {
	var $MAINCFG;
	var $MAPCFG;
	var $BACKEND;
	var $LANG;
	
	var $MAP;
	
	/**
	 * Class Constructor
	 *
	 * @param 	GlobalMainCfg 	$MAINCFG
	 * @param 	GlobalMapCfg 	$MAPCFG
	 * @param 	GlobalBackend 	$BACKEND
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	function NagVisFrontend(&$MAINCFG,&$MAPCFG,&$BACKEND) {
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method NagVisFrontend::NagVisFrontend($MAINCFG,$MAPCFG,$BACKEND)');
		$this->MAINCFG = &$MAINCFG;
		$this->MAPCFG = &$MAPCFG;
		$this->BACKEND = &$BACKEND;
		$this->LANG = new GlobalLanguage($MAINCFG,'nagvis:global');
		$prop = Array('title'=>$MAINCFG->getValue('internal', 'title'),
					  'cssIncludes'=>Array('./includes/css/style.css'),
					  'jsIncludes'=>Array('./includes/js/nagvis.js','./includes/js/overlib.js','./includes/js/overlib_shadow.js'),
					  'extHeader'=>Array('<META http-equiv="refresh" CONTENT="'.$this->MAINCFG->getValue('global', 'refreshtime').';'.$this->getNextRotate().'">',
					  					'<style type="text/css">.main { background-color: '.$this->MAPCFG->getValue('global',0, 'background_color').'; }</style>'),
					  'allowedUsers'=> $this->MAPCFG->getValue('global',0, 'allowed_user'),
					  'languageRoot' => 'nagvis:global');
		parent::GlobalPage($this->MAINCFG,$prop);
		if (DEBUG&&DEBUGLEVEL&1) debug('End method NagVisFrontend::NagVisFrontend()');
	}
	
	/**
	 * Reads informations from currently running Apache/PHP installation
	 *
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	function getInstInformations() {
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method NagVisFrontend::getInstInformations()');
		$ret = Array();
		
		$ret[] = '<table class="instinfo">';
		$ret[] = '<tr><th colspan="2" class="head">NagVis Debug/Support Informations</td></tr>';
		$ret[] = '</table><br />';
		
		$ret[] = '<table class="instinfo">';
		$ret[] = '<tr><th colspan="2">Version Informations</td></tr>';
		$ret[] = '<tr><td>NagVis Version</td><td>'.$this->MAINCFG->getValue('internal','version').'</td></tr>';
		$ret[] = '<tr><td>PHP Version</td><td>'.PHP_VERSION.'</td></tr>';
		$ret[] = '<tr><td>MySQL Version</td><td>'.shell_exec('mysql --version').'</td></tr>';
		$ret[] = '<tr><td>OS</td><td>'.shell_exec('uname -a').'</td></tr>';
		$ret[] = '<t><th colspan="2">Webserver Informations</th></tr>';
		$ret[] = '<tr><td>SERVER_SOFTWARE</td><td>'.$_SERVER['SERVER_SOFTWARE'].'</td></tr>';
		$ret[] = '<tr><td>REMOTE_USER</td><td>'.$_SERVER['REMOTE_USER'].'</td></tr>';
		$ret[] = '<tr><td>SCRIPT_FILENAME</td><td>'.$_SERVER['SCRIPT_FILENAME'].'</td></tr>';
		$ret[] = '<tr><td>SCRIPT_NAME</td><td>'.$_SERVER['SCRIPT_NAME'].'</td></tr>';
		$ret[] = '<tr><td>REQUEST_TIME</td><td>'.$_SERVER['REQUEST_TIME'].' ('.date('r',$_SERVER['REQUEST_TIME']).')</td></tr>';
		$ret[] = '<t><th colspan="2">PHP Informations</th></tr>';
		$ret[] = '<tr><td>safe_mode</td><td>'.ini_get('safe_mode').'</td></tr>';
		$ret[] = '<tr><td>max_execution_time</td><td>'.ini_get('max_execution_time').'</td></tr>';
		$ret[] = '<tr><td>memory_limit</td><td>'.ini_get('memory_limit').'</td></tr>';
		//FIXME: $ret[] = '<tr><td style="text-align:center;" colspan="2"><a href="">Copy to Clipboard</a></td></tr>';
		$ret[] = '</table>';
		
		$this->addBodyLines($ret);
		if (DEBUG&&DEBUGLEVEL&1) debug('End method NagVisFrontend::getInstInformations()');
	}
	
	/**
	 * If enabled, the header menu is added to the page
	 *
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	function getHeaderMenu() {
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method NagVisFrontend::getHeaderMenu()');
		if($this->MAINCFG->getValue('global', 'displayheader') == '1') {
			if($this->checkHeaderTemplateReadable(1)) {
				$ret = file_get_contents($this->MAINCFG->getValue('paths','headertemplate').'tmpl.'.$this->MAPCFG->getValue('global', 0, 'header_template').'.html');
				
				// Replace some macros
				$ret = str_replace('[current_map]',$this->MAPCFG->getName(),$ret);
				$ret = str_replace('[current_map_alias]',$this->MAPCFG->getValue('global', '0', 'alias'),$ret);
				$ret = str_replace('[tmpl_images]',$this->MAINCFG->getValue('paths','htmlimages').'/templates',$ret);
				$ret = str_replace('[html_base]',$this->MAINCFG->getValue('paths','htmlbase'),$ret);
				// Replace language macros
				$ret = str_replace('[lang_select_map]',$this->LANG->getLabel('selectMap'),$ret);
				$ret = str_replace('[lang_edit_map]',$this->LANG->getLabel('editMap'),$ret);
				$ret = str_replace('[lang_need_help]',$this->LANG->getLabel('needHelp'),$ret);
				$ret = str_replace('[lang_online_doc]',$this->LANG->getLabel('onlineDoc'),$ret);
				$ret = str_replace('[lang_forum]',$this->LANG->getLabel('forum'),$ret);
				$ret = str_replace('[lang_support_info]',$this->LANG->getLabel('supportInfo'),$ret);
				// Replace lists
				if(preg_match_all('/<!-- BEGIN (\w+) -->/',$ret,$matchReturn) > 0) {
					foreach($matchReturn[1] AS $key) {
						if($key == 'maplist') {
							$sReplace = '';
							preg_match_all('/<!-- BEGIN '.$key.' -->((?s).*)<!-- END '.$key.' -->/',$ret,$matchReturn1);
							foreach($this->getMaps() AS $mapName) {
								$MAPCFG1 = new NagVisMapCfg($this->MAINCFG,$mapName);
								$MAPCFG1->readMapConfig(1);
								
								$sReplaceObj = str_replace('[map_name]',$MAPCFG1->getName(),$matchReturn1[1][0]);
								$sReplaceObj = str_replace('[map_alias]',$MAPCFG1->getValue('global', '0', 'alias'),$sReplaceObj);
								// auto select current map
								if($mapName == $this->MAPCFG->getName()) {
									$sReplaceObj = str_replace('[selected]','selected="selected"',$sReplaceObj);
								} else {
									$sReplaceObj = str_replace('[selected]','',$sReplaceObj);
								}
								$sReplace .= $sReplaceObj;
							}
							$ret = preg_replace('/<!-- BEGIN '.$key.' -->((?s).*)<!-- END '.$key.' -->/',$sReplace,$ret);
						}
					}
				}
				
				$this->addBodyLines('<div class="header">'.$ret.'</div>');
			}
		}
		if (DEBUG&&DEBUGLEVEL&1) debug('End method NagVisFrontend::getHeaderMenu()');
	}
	
	/**
	 * Checks for existing header template
	 *
	 * @param 	Boolean	$printErr
	 * @return	Boolean	Is Check Successful?
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
     */
	function checkHeaderTemplateExists($printErr) {
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method NagVisFrontend::checkHeaderTemplateExists('.$printErr.')');
		if(file_exists($this->MAINCFG->getValue('paths', 'headertemplate').'tmpl.'.$this->MAPCFG->getValue('global', 0, 'header_template').'.html')) {
			if (DEBUG&&DEBUGLEVEL&1) debug('End method NagVisFrontend::checkHeaderTemplateExists(): TRUE');
			return TRUE;
		} else {
			if($printErr == 1) {
				$FRONTEND = new GlobalPage($this->MAINCFG,Array('languageRoot'=>'global:global'));
		    	$FRONTEND->messageToUser('WARNING','headerTemplateNotExists','FILE~'.$this->MAINCFG->getValue('paths', 'headertemplate').'tmpl.'.$this->MAPCFG->getValue('global', 0, 'header_template').'.html');
			}
			if (DEBUG&&DEBUGLEVEL&1) debug('End method NagVisFrontend::checkHeaderTemplateExists(): FALSE');
			return FALSE;
		}
	}
	
	/**
	 * Checks for readable header template
	 *
	 * @param 	Boolean	$printErr
	 * @return	Boolean	Is Check Successful?
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
     */
	function checkHeaderTemplateReadable($printErr) {
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method NagVisFrontend::checkHeaderTemplateReadable('.$printErr.')');
		if($this->checkHeaderTemplateExists($printErr) && is_readable($this->MAINCFG->getValue('paths', 'headertemplate').'tmpl.'.$this->MAPCFG->getValue('global', 0, 'header_template').'.html')) {
			if (DEBUG&&DEBUGLEVEL&1) debug('End method NagVisFrontend::checkHeaderTemplateReadable(): TRUE');
			return TRUE;
		} else {
			if($printErr == 1) {
				$FRONTEND = new GlobalPage($this->MAINCFG,Array('languageRoot'=>'global:global'));
		    	$FRONTEND->messageToUser('WARNING','headerTemplateNotReadable','FILE~'.$this->MAINCFG->getValue('paths', 'headertemplate').'tmpl.'.$this->MAPCFG->getValue('global', 0, 'header_template').'.html');
			}
			if (DEBUG&&DEBUGLEVEL&1) debug('End method NagVisFrontend::checkHeaderTemplateReadable(): FALSE');
			return FALSE;
		}
	}
	
	/**
	 * Adds the map to the page
	 *
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	function getMap() {
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method NagVisFrontend::getMap()');
		$this->addBodyLines(Array('<div id="overDiv" style="position:absolute; visibility:hidden; z-index:1000;"></div>'));
		$this->addBodyLines(Array('<div class="map">'));
		$this->MAP = new NagVisMap($this->MAINCFG,$this->MAPCFG,$this->LANG,$this->BACKEND);
		$this->addBodyLines($this->MAP->parseMap());
		$this->addBodyLines(Array('</div>'));
		if (DEBUG&&DEBUGLEVEL&1) debug('End method NagVisFrontend::getMap()');
	}
	
	/**
	 * Adds the user messages to the page
	 *
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	function getMessages() {
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method NagVisFrontend::getMessages()');
		$this->addBodyLines($this->getUserMessages());
		if (DEBUG&&DEBUGLEVEL&1) debug('End method NagVisFrontend::getMessages()');
	}
    
    /**
     * Gets the Next map to rotate to, if enabled
     * If Next map is in [ ], it will be an absolute url
     *
     * @return      String  URL to rotate to
     * @author      Lars Michelsen <lars@vertical-visions.de>
     */
    function getNextRotate() {
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method NagVisFrontend::getNextRotate()');
	    $maps = explode(',', $this->MAINCFG->getValue('global', 'maps'));
	    if(($this->MAINCFG->getValue('global', 'rotatemaps') == '1' && (!isset($_GET['rotate']) || isset($_GET['rotate']) && $_GET['rotate'] != '0')) || (isset($_GET['rotate']) && $_GET['rotate'] == '1')) {
			if(isset($_GET['url']) && $_GET['url'] != '') {
				$currentMap = '['.$_GET['url'].']';
			} else {
				$currentMap = $this->MAPCFG->getName();
			}
			
			// get position of actual map in the array
			$index = array_search($currentMap,$maps);
			if (($index + 1) >= sizeof($maps)) {
				// if end of array reached, go to the beginning...
				$index = 0;
			} else {
				$index++;
			}
			
			$nextMap = $maps[$index];
			
			if(preg_match("/^\[(.+)\]$/",$nextMap,$arrRet)) {
				if (DEBUG&&DEBUGLEVEL&1) debug('End method NagVisFrontend::getNextRotate(): URL=index.php?rotate=1&url='.$arrRet[1]);
				return ' URL=index.php?rotate=1&url='.$arrRet[1];
			} else {
				if (DEBUG&&DEBUGLEVEL&1) debug('End method NagVisFrontend::getNextRotate(): URL=index.php?map='.$nextMap.'&rotate=1');
				return ' URL=index.php?map='.$nextMap.'&rotate=1';
			}
		}
    }
    
	/**
	 * Gets all defined maps
	 *
	 * @return	Array maps
	 * @author Lars Michelsen <lars@vertical-visions.de>
     */
	function getMaps() {
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method NagVisFrontend::getMaps()');
		$files = Array();
		
		if ($handle = opendir($this->MAINCFG->getValue('paths', 'mapcfg'))) {
 			while (false !== ($file = readdir($handle))) {
				if ($file != '.' && $file != '..' && substr($file,strlen($file)-4,4) == '.cfg') {
					$files[] = substr($file,0,strlen($file)-4);
				}				
			}
			
			if ($files) {
				natcasesort($files);
			}
		}
		closedir($handle);
		
		if (DEBUG&&DEBUGLEVEL&1) debug('End method NagVisFrontend::getMaps(): Array(...)');
		return $files;
	}
}
?>
