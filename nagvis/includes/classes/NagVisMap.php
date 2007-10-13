<?php
/**
 * Class for printing the map in NagVis
 */
class NagVisMap extends GlobalMap {
	var $MAINCFG;
	var $MAPCFG;
	var $BACKEND;
	var $GRAPHIC;
	
	var $objects;
	
	/**
	 * Class Constructor
	 *
	 * @param 	GlobalMainCfg 	$MAINCFG
	 * @param 	GlobalMapCfg 	$MAPCFG
	 * @param 	GlobalBackend 	$BACKEND
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	function NagVisMap(&$MAINCFG,&$MAPCFG,&$LANG,&$BACKEND,$getState=1) {
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method NagVisMap::NagVisMap($MAINCFG,$MAPCFG,$LANG,$BACKEND)');
		$this->MAINCFG = &$MAINCFG;
		$this->MAPCFG = &$MAPCFG;
		$this->LANG = &$LANG;
		$this->BACKEND = &$BACKEND;
		
		$this->GRAPHIC = new GlobalGraphic();
		
		parent::GlobalMap($MAINCFG,$MAPCFG);
		
		$this->objects = $this->getMapObjects($getState,1);
		if (DEBUG&&DEBUGLEVEL&1) debug('End method NagVisMap::NagVisMap()');
	}
	
	/**
	 * Parses the Map and the Objects
	 *
	 * @return	Array 	Array with Html Code
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	function parseMap() {
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method NagVisMap::parseMap()');
		$ret = Array();
		$ret = array_merge($ret,$this->getBackground());
		$ret = array_merge($ret,$this->parseObjects());
		// Dynamicaly set favicon
		$ret[] = $this->getFavicon();
		// Change title (add map alias and map state)
		$ret[] = '<script type="text/javascript" language="JavaScript">document.title=\''.$this->MAPCFG->getValue('global', 0, 'alias').' ('.$this->getMapState($this->getMapObjects(1,1)).') :: \'+document.title;</script>';
		
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method NagVisMap::parseMap(): Array(...)');
		return $ret;
	}
	
	/**
	 * Gets the background of the map
	 *
	 * @return	Array	HTML Code
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
     */
	function getBackground() {
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method NagVisMap::getBackground()');
		
		if($this->MAPCFG->getValue('global', 0,'usegdlibs') == '1' && $this->checkGd(1)) {
			$src = './draw.php?map='.$this->MAPCFG->getName();
		} else {
			$src = $this->MAINCFG->getValue('paths', 'htmlmap').$this->MAPCFG->BACKGROUND->getFileName();
		}
		
		if (DEBUG&&DEBUGLEVEL&1) debug('End method NagVisMap::getBackground(): Array(...)');
		return $this->getBackgroundHtml($src);
	}
	
	/**
	 * Gets the favicon of the page representation the state of the map
	 *
	 * @return	String	HTML Code
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
     */
	function getFavicon() {
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method NagVisMap::getFavicon()');
		if(file_exists('./images/internal/favicon_'.strtolower($this->getMapState($this->getMapObjects(1,1))).'.png')) {
			$favicon = './images/internal/favicon_'.strtolower($this->getMapState($this->getMapObjects(1,1))).'.png';
		} else {
			$favicon = './images/internal/favicon.png';
		}
		if (DEBUG&&DEBUGLEVEL&1) debug('End method NagVisMap::getFavicon()');
		return '<script type="text/javascript" language="JavaScript">favicon.change(\''.$favicon.'\'); </script>';
	}
	
	/**
	 * Parses the Objects
	 *
	 * @return	Array 	Array with Html Code
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	function parseObjects() {
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method NagVisMap::parseObjects()');
		$ret = Array();
		foreach($this->objects AS $obj) {
			switch($obj['type']) {
				case 'textbox':
					// css class of the textbox
					$obj['class'] = 'box';
					
					// default background color
					if(!isset($obj['background_color']) || $obj['background_color'] == '') {
						$obj['background_color'] = '#CCCCCC';
					}
					
					$ret = array_merge($ret,$this->textBox($obj));
				break;
				case 'shape':
					if(isset($obj['url']) && $obj['url'] != '') {
						$link = 1;
					} else {
						$link = 0;
					}
					
					if(isset($obj['hover_url']) && $obj['hover_url'] != '') {
						$hoverMenu = 1;
					} else {
						$hoverMenu = 0;
					}
					
					$obj = $this->fixIcon($obj);
					$ret[] = $this->parseIcon($obj,$link,$hoverMenu);
				break;
				default:
					// replace macros in url/hover_url/label_text
					$obj = $this->replaceMacros($obj);
					
                    // Some specials for lines
					if(isset($obj['line_type'])) {
                        $obj = $this->getLineHoverArea($obj);
					}
                    
                    $obj = $this->fixIcon($obj);
					$ret[] = $this->parseIcon($obj);
                    
					if($obj['label_show'] == '1') {
						$ret[] = $this->parseLabel($obj);
					}
				break;	
			}
		}
		
		if (DEBUG&&DEBUGLEVEL&1) debug('End method NagVisMap::parseObjects(): Array(...)');
		return $ret;
	}
	
	/**
	 * Adds paths to the icon
	 *
	 * @param	Array	$obj	Array with object informations
	 * @return	Array	Array with object informations
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	function fixIcon(&$obj) {
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method NagVisMap::fixIcon(&$obj)');
		$return = parent::fixIcon($this->getIconPaths($obj));
		if (DEBUG&&DEBUGLEVEL&1) debug('End method NagVisMap::fixIcon(): '.$return);
		return $return;
	}
	
	/**
	 * Calculates the position of the line hover area
	 *
	 * @param	Array	$obj	Array with object informations
	 * @return	Array	Array with HTML Code
	 */
	function getLineHoverArea(&$obj) {
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method NagVisMap::getLineHoverArea(&$obj)');
		
		list($xFrom,$xTo) = explode(',', $obj['x']);
		list($yFrom,$yTo) = explode(',', $obj['y']);
        
		$obj['x'] = $this->GRAPHIC->middle($xFrom,$xTo) - 10;
		$obj['y'] = $this->GRAPHIC->middle($yFrom,$yTo) - 10;
		$obj['icon'] = '20x20.gif';

		if (DEBUG&&DEBUGLEVEL&1) debug('End method NagVisMap::getLineHoverArea(): Array(...)');
		return $obj;
	}
	
	/**
	 * Parses the HTML-Code of an icon
	 *
	 * @param	Array	$obj		Array with object informations
	 * @param	String	$base		Array with object informations
	 * @param	Boolean	$link		Add a link to the icon
	 * @param	Boolean	$hoverMenu	Add a hover menu to the icon
	 * @return	String	String with Html Code
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	function parseIcon(&$obj,$link=1,$hoverMenu=1) {
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method NagVisMap::parseIcon(&$obj,'.$link.','.$hoverMenu.')');
		
		if($obj['type'] == 'shape') {
			if(preg_match('/^\[(.*)\]$/',$obj['icon'],$match) > 0) {
				$imgPath = $match[1];
			} else {
				$imgPath = $this->MAINCFG->getValue('paths', 'htmlshape').$obj['icon'];
			}
			$alt = $obj['icon'];
		} else {
			$imgPath = $this->MAINCFG->getValue('paths', 'htmlicon').$obj['icon'];
			
			if($obj['type'] == 'service') {
				$name = 'host_name';
				$alt = $obj['host_name'].'-'.$obj['service_description'];
			} else {
				$alt = $obj[$obj['type'].'_name'];
			}
			
		}
		
		
		$ret = '<div class="icon" style="left:'.$obj['x'].'px;top:'.$obj['y'].'px;z-index:'.$obj['z'].';">';
		
		if($link) {
			$ret .= $this->createLink($obj);
		}
		
		if($hoverMenu) {
			$menu = $this->getHoverMenu($obj);
		} else {
			$menu = '';
		}
		
		$ret .= '<img src="'.$imgPath.'" '.$menu.' alt="'.$obj['type'].'-'.$alt.'">';
		
		if($link) {
			$ret .= '</a>';
		}
		
		$ret .= '</div>';
		
		if (DEBUG&&DEBUGLEVEL&1) debug('End method NagVisMap::parseIcon(): Array(...)');
		return $ret;
	}
	
	/**
	 * Replaces macros of urls and hover_urls
	 *
	 * @param	Array	$obj	Array with object informations
	 * @return	Array	$obj	Modified array
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	function replaceMacros(&$obj) {
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method NagVisMap::replaceMacros(&$obj)');
		if($obj['type'] == 'service') {
			$name = 'host_name';
		} else {
			$name = $obj['type'] . '_name';
		}
		
		if(isset($obj['url']) && $obj['url'] != '') {
			$obj['url'] = str_replace('['.$name.']',$obj[$name],$obj['url']);
			if($obj['type'] == 'service') {
				$obj['url'] = str_replace('[service_description]',$obj['service_description'],$obj['url']);
			}
		}
		
		if(isset($obj['hover_url']) && $obj['hover_url'] != '') {
			$obj['hover_url'] = str_replace('[name]',$obj[$name],$obj['hover_url']);
			if($obj['type'] == 'service') {
				$obj['hover_url'] = str_replace('[service_description]',$obj['service_description'],$obj['hover_url']);
			}
		}
		
		if(isset($obj['label_text']) && $obj['label_text'] != '') {
		    // For maps use the alias as display string
		    if($obj['type'] == 'map') {
		        $name = 'alias';   
		    }
		    
			$obj['label_text'] = str_replace('[name]',$obj[$name],$obj['label_text']);
			$obj['label_text'] = str_replace('[output]',$obj['stateOutput'],$obj['label_text']);
			if($obj['type'] == 'service') {
				$obj['label_text'] = str_replace('[service_description]',$obj['service_description'],$obj['label_text']);
			}
		}
		
		if (DEBUG&&DEBUGLEVEL&1) debug('End method NagVisMap::replaceMacros(): Array(...)');
		return $obj;
	}
	
	/**
	 * Creates a link to Nagios, when this is not set in the Config-File
	 *
	 * @param	Array	$obj	Array with object informations
	 * @return	String	The Link
	 * @author 	Michael Luebben <michael_luebben@web.de>
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	function createLink(&$obj) {
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method NagVisMap::createLink(&$obj)');
		if($obj['type'] == 'service') {
			$name = 'host_name';
		} else {
			$name = $obj['type'] . '_name';
		}
		
		if(isset($obj['url']) && $obj['url'] != '') {
			$link = '<a href="'.$obj['url'].'" target="'.$obj['url_target'].'">';
    	} else {
    		switch($obj['type']) {
    			case 'map':
    				$link = '<a href="'.$this->MAINCFG->getValue('paths', 'htmlbase').'/index.php?map='.$obj[$name].'" target="'.$obj['url_target'].'">';
    			break;
    			case 'host':
    				$link = '<a href="'.$this->MAINCFG->getValue('backend_'.$obj['backend_id'], 'htmlcgi').'/status.cgi?host='.$obj[$name].'" target="'.$obj['url_target'].'">';
    			break;
    			case 'service':
    				$link = '<a href="'.$this->MAINCFG->getValue('backend_'.$obj['backend_id'], 'htmlcgi').'/extinfo.cgi?type=2&amp;host='.$obj[$name].'&amp;service='.$obj['service_description'].'" target="'.$obj['url_target'].'">';
    			break;
    			case 'hostgroup':
    				$link = '<a href="'.$this->MAINCFG->getValue('backend_'.$obj['backend_id'], 'htmlcgi').'/status.cgi?hostgroup='.$obj[$name].'&amp;style=detail" target="'.$obj['url_target'].'">';
    			break;
    			case 'servicegroup':
    				$link = '<a href="'.$this->MAINCFG->getValue('backend_'.$obj['backend_id'], 'htmlcgi').'/status.cgi?servicegroup='.$obj[$name].'&amp;style=detail" target="'.$obj['url_target'].'">';
    			break;
    		}
    	}
		if (DEBUG&&DEBUGLEVEL&1) debug('End method NagVisMap::createLink(): '.$link);
    	return $link;
	}
	
	/**
	 * Create a Comment-Textbox
	 *
	 * @param	Array	$obj	Array with object informations
	 * @return	String	Array with HTML Code
	 * @author	Joerg Linge
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	function textBox(&$obj) {
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method NagVisMap::textBox(&$obj)');
		$ret = Array();
		$ret[] = '<div class="'.$obj['class'].'" style="background:'.$obj['background_color'].';left:'.$obj['x'].'px;top:'.$obj['y'].'px;width:'.$obj['w'].'px;overflow:visible;">';	
		$ret[] = "\t".'<span>'.$obj['text'].'</span>';
		$ret[] = '</div>';
		if (DEBUG&&DEBUGLEVEL&1) debug('End method NagVisMap::textBox(): Array(...)');
		return $ret;	
	}
	
	/**
	 * Creates a hover box for objects
	 *
	 * @param	Array	$obj	Array with object informations
	 * @return	String	Code for the hover box
	 * @author	Lars Michelsen <lars@vertical-visions.de>
     */
	function getHoverMenu(&$obj) {
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method NagVisMap::getHoverMenu(&$obj)');
		$ret = '';
		// FIXME 1.1: check if this is an object, where a menu should be displayed
		if(1) {
			$ret .= 'onmouseover="return overlib(\'';
			if(isset($obj['hover_url']) && $obj['hover_url'] != '') {
				$ret .= $this->readHoverUrl($obj);
			} else {
				$ret .= $this->createInfoBox($obj);
			}
			
			$ret .= '\', WRAP, VAUTO, DELAY, '.($obj['hover_delay']*1000).');" onmouseout="return nd();"';
			
			if (DEBUG&&DEBUGLEVEL&1) debug('End method NagVisMap::getHoverMenu(): Array(...)');
			return $ret;
		}
	}
	
	/**
	 * Reads the given hover url form an object and forms it to a readable format for the hover box
	 *
	 * @param	Array	$obj	Array with object informations
	 * @return	String	Code for the hover box
	 * @author	Lars Michelsen <lars@vertical-visions.de>
     */
	function readHoverUrl(&$obj) {
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method NagVisMap::readHoverUrl(&$obj)');
		/* FIXME: Context is supported in php >= 5.0
		* $http_opts = array(
		*      'http'=>array(
		*      'method'=>"GET",
		*      'header'=>"Accept-language: en\r\n" .
		*                "Authorization: Basic ".base64_encode("user:pw"),
		*      'request_fulluri'=>true  ,
		*      'proxy'=>"tcp://proxy.url.de"
		*   )
		* );
		* $context = stream_context_create($http_opts);
		* $content = file_get_contents($obj['hover_url'],FALSE,$context);
		*/
		if(!$content = @file_get_contents($obj['hover_url'])) {
			$FRONTEND = new GlobalPage($this->MAINCFG,Array('languageRoot'=>'nagvis:global'));
	        $FRONTEND->messageToUser('WARNING','couldNotGetHoverUrl','URL~'.$obj['hover_url']);
		}
		
		if (DEBUG&&DEBUGLEVEL&1) debug('End method NagVisMap::readHoverUrl(): HTML');
		return str_replace('"','\\\'',str_replace('\'','\\\'',str_replace("\t",'',str_replace("\n",'',str_replace("\r\n",'',$content)))));
	}
	
	/**
	 * Creates a JavaScript hover menu
	 *
	 * @param	Array	$obj	Array with object informations
	 * @return	String	Code for the Hover-Box
	 * @author	Lars Michelsen <lars@vertical-visions.de>
     */
	function createInfoBox(&$obj) {
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method NagVisMap::createInfoBox(&$obj)');
		
		if(!isset($obj['stateCount'])) {
			$obj['stateCount'] = 0;
		}
		
		if(!isset($obj['stateHost'])) {
			$obj['stateHost'] = '';
		}
		
		$Count = $obj['stateCount'];
		$obj['stateCount'] = str_replace('"','',$obj['stateCount']);
		$obj['stateCount'] = str_replace("'",'',$obj['stateCount']);
		$ServiceHostState = $obj['stateHost'];
		
		$ret = $this->getHoverTemplate($obj);
		
		if (DEBUG&&DEBUGLEVEL&1) debug('End method NagVisMap::createInfoBox(): Array(...)');
		return $ret;
	}
	
	/**
	 * Gets the hover template
	 *
	 * @param	Array	$obj	Array with object informations
	 * @return	String	HTML	HTML Code for the hover menu
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	function getHoverTemplate(&$obj) {
		if($this->checkHoverTemplateReadable($obj,1)) {
            $ret = file_get_contents($this->MAINCFG->getValue('paths','hovertemplate').'tmpl.'.$obj['hover_template'].'.html');
            
            if($obj['type'] == 'service') {
				$name = 'host_name';
			} else {
				$name = $obj['type'] . '_name';
			}
			
			// For maps use the alias as display string
			if($obj['type'] == 'map') {
				$displayName = 'alias';
			} else {
				$displayName = $name;
			}
			
            // Replace the macros
			$ret = str_replace('[obj_type]',$obj['type'],$ret);
			$ret = str_replace('[obj_name]',$obj[$displayName],$ret);
			$ret = str_replace('[obj_state]',$obj['state'],$ret);
			if($obj['type'] != 'map') {
				$ret = str_replace('[obj_backendid]',$obj['backend_id'],$ret);
	            if($this->MAINCFG->getValue('backend_'.$obj['backend_id'],'backendtype') == 'ndomy') {
				    $ret = str_replace('[obj_backend_instancename]',$this->MAINCFG->getValue('backend_'.$obj['backend_id'],'dbinstancename'),$ret);
	            } else {
	                $ret = str_replace('[obj_backend_instancename]','',$ret);
	            }
	        }
			$ret = str_replace('[obj_output]',strtr($obj['stateOutput'], Array("\r" => '<br />', "\n" => '<br />')),$ret);
			$ret = str_replace('[pnp_hostname]',str_replace(' ','%20',$obj[$name]),$ret);
			$ret = str_replace('[lang_name]',$this->LANG->getLabel(str_replace('_','',$name)),$ret);
			$ret = str_replace('[lang_state]',$this->LANG->getLabel('state'),$ret);
			$ret = str_replace('[lang_output]',$this->LANG->getLabel('output'),$ret);
			$ret = str_replace('[lang_obj_type]',$this->LANG->getLabel($obj['type']),$ret);
			$ret = str_replace('[lang_overview]',$this->LANG->getLabel('overview'),$ret);
			$ret = str_replace('[lang_instance]',$this->LANG->getLabel('instance'),$ret);
			$ret = str_replace('[html_base]',$this->MAINCFG->getValue('paths','htmlbase'),$ret);
			$ret = str_replace('[html_templates]',$this->MAINCFG->getValue('paths','htmlhovertemplates'),$ret);
			$ret = str_replace('[html_template_images]',$this->MAINCFG->getValue('paths','htmlhovertemplateimages'),$ret);
			if($obj['type'] == 'service') {
				$ret = str_replace('[service_description]',$obj['service_description'],$ret);
				$ret = str_replace('[pnp_service_description]',str_replace(' ','%20',$obj['service_description']),$ret);
			}
            // Escape chars which could make problems
            $ret = strtr(addslashes($ret),Array('"' => '\'', "\r" => '', "\n" => ''));
		}
		
		return $ret;
	}
	
	/**
	 * Checks if the requested hover template file is readable
	 *
	 * @param	Array	$obj	Array with object informations
	 * @return	Boolean Check Result
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	function checkHoverTemplateReadable(&$obj,$printErr) {
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method NagVisMap::checkHoverTemplateReadable(&$obj,'.$printErr.')');
		if($this->checkHoverTemplateExists($obj,$printErr) && is_readable($this->MAINCFG->getValue('paths','hovertemplate').'tmpl.'.$obj['hover_template'].'.html')) {
			if (DEBUG&&DEBUGLEVEL&1) debug('End method NagVisMap::checkHoverTemplateReadable(): TRUE');
			return TRUE;
		} else {
			if($printErr == 1) {
				$FRONTEND = new GlobalPage($this->MAINCFG,Array('languageRoot'=>'nagvis:global'));
	            $FRONTEND->messageToUser('ERROR','hoverTemplateNotReadable','FILE~'.$this->MAINCFG->getValue('paths','hovertemplate').'tmpl.'.$obj['hover_template'].'.html');
			}
			if (DEBUG&&DEBUGLEVEL&1) debug('End method NagVisMap::checkHoverTemplateReadable(): FALSE');
			return FALSE;
		}
	}
	
	/**
	 * Checks if the requested hover template file exists
	 *
	 * @param	Array	$obj	Array with object informations
	 * @return	Boolean Check Result
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	function checkHoverTemplateExists(&$obj,$printErr) {
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method NagVisMap::checkHoverTemplateExists(&$obj,'.$printErr.')');
		if(file_exists($this->MAINCFG->getValue('paths','hovertemplate').'tmpl.'.$obj['hover_template'].'.html')) {
			if (DEBUG&&DEBUGLEVEL&1) debug('End method NagVisMap::checkHoverTemplateExists(): TRUE');
			return TRUE;
		} else {
			if($printErr == 1) {
				$FRONTEND = new GlobalPage($this->MAINCFG,Array('languageRoot'=>'nagvis:global'));
	            $FRONTEND->messageToUser('ERROR','hoverTemplateNotExists','FILE~'.$this->MAINCFG->getValue('paths', 'hovertemplate').'tmpl.'.$obj['hover_template'].'.html');
			}
			if (DEBUG&&DEBUGLEVEL&1) debug('End method NagVisMap::checkHoverTemplateExists(): FALSE');
			return FALSE;
		}
	}
	
	/**
	 * Gets the summary state of all objects on the map
	 *
	 * @param	Array	$arr	Array with states
	 * @return	String	Summary state of the map
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
     */
	function getMapState(&$arr) {
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method NagVisMap::getMapState(Array(...))');
		$ret = Array();
		foreach($arr AS $obj) {
			$ret[] = $obj['state'];
		}
		
		$sRet = $this->wrapState($ret);
		if (DEBUG&&DEBUGLEVEL&1) debug('End method NagVisMap::getMapState(): '.$sRet);
		return $sRet;
	}
	
	/**
	 * Gets the state of an object
	 *
	 * @param	Array	$obj	Array with object properties
	 * @return	Array	Array with state of the object
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
     */
	function getState(&$obj) {
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method NagVisMap::getState(&$obj)');
		$state = Array('State'=>'','Output'=>'');
		if($obj['type'] == 'service') {
			$name = 'host_name';
		} else {
			$name = $obj['type'] . '_name';
		}
		
		switch($obj['type']) {
			case 'map':
				// prevent direct loops (map including itselfes as map icon)
				if($this->MAPCFG->getName() == $obj[$name]) {
					$FRONTEND = new GlobalPage($this->MAINCFG,Array('languageRoot'=>'global:global'));
		            $FRONTEND->messageToUser('WARNING','loopInMapRecursion');
					
					$LANG = new GlobalLanguage($this->MAINCFG,'global:global');
					$state = Array('State' => 'UNKNOWN','Output' => $LANG->getMessageText('loopInMapRecursion'));
				} else {
					// save mapName in linkedMaps array
					$this->linkedMaps[] = $this->MAPCFG->getName();
					
					$SUBMAPCFG = new NagVisMapCfg($this->MAINCFG,$obj[$name]);
					$SUBMAPCFG->readMapConfig();
					// Start map wihtout getting the state
					$SUBMAP = new NagVisMap($this->MAINCFG,$SUBMAPCFG,$this->LANG,$this->BACKEND,FALSE);
					$SUBMAP->linkedMaps = $this->linkedMaps;
					
					if($this->checkPermissions($SUBMAPCFG->getValue('global',0, 'allowed_user'),FALSE)) {
						// prevent loops in recursion
						if(in_array($SUBMAPCFG->getName(),$this->linkedMaps)) {
			                $FRONTEND = new GlobalPage($this->MAINCFG,Array('languageRoot'=>'global:global'));
				            $FRONTEND->messageToUser('WARNING','loopInMapRecursion');
							
							$LANG = new GlobalLanguage($this->MAINCFG,'global:global');
							$state = Array('State' => 'UNKNOWN','Output' => $LANG->getMessageText('loopInMapRecursion'));
						} else {
							$state = $SUBMAP->getMapState($SUBMAP->getMapObjects(1,1));
							// FIXME: Language entry
							$state = Array('State' => $state,'Output'=>'State of child map is '.$state);
						}
					} else {
					    // FIXME: Language entry
						$state = Array('State' => 'UNKNOWN','Output'=>'Error: You are not permited to view the state of this map.');
					}
				}
			break;
			case 'textbox':
			break;
			default:
				if(!isset($obj['service_description'])) {
					$obj['service_description'] = '';
				}
				if(!isset($obj['recognize_services'])) {
					$obj['recognize_services'] = '';	
				}
				
				if($this->BACKEND->checkBackendInitialized($obj['backend_id'],TRUE)) {
					$state = $this->BACKEND->BACKENDS[$obj['backend_id']]->checkStates($obj['type'],$obj[$name],$obj['recognize_services'],$obj['service_description'],$obj['only_hard_states']);
				}
			break;	
		}
		
		if (DEBUG&&DEBUGLEVEL&1) debug('End method NagVisMap::getState(): Array()');
		return Array('state' => $state['State'],'stateOutput' => $state['Output']);
	}
	
	/**
	 * Gets all objects of the map
	 *
	 * @param	Boolean	$getState	With state?
	 * @return	Array	Array of Objects of this map
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
     */
	function getMapObjects($getState=1,$mergeWithGlobals=1) {
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method GlobalMap::getMapObjects('.$getState.','.$mergeWithGlobals.')');
		$objects = Array();
		
		$objects = array_merge($objects,$this->getObjectsOfType('map',$getState,$mergeWithGlobals));
		$objects = array_merge($objects,$this->getObjectsOfType('host',$getState,$mergeWithGlobals));
		$objects = array_merge($objects,$this->getObjectsOfType('service',$getState,$mergeWithGlobals));
		$objects = array_merge($objects,$this->getObjectsOfType('hostgroup',$getState,$mergeWithGlobals));
		$objects = array_merge($objects,$this->getObjectsOfType('servicegroup',$getState,$mergeWithGlobals));
		$objects = array_merge($objects,$this->getObjectsOfType('textbox',$getState,$mergeWithGlobals));
		$objects = array_merge($objects,$this->getObjectsOfType('shape',0,$mergeWithGlobals));
		
		if (DEBUG&&DEBUGLEVEL&1) debug('End method GlobalMap::getMapObjects(): Array(...)');
		return $objects;
	}
	
	/**
	 * Gets all objects of the defined type from a map and return an array with states
	 *
	 * @param	String	$type		Type of objects
	 * @param	Boolean	$getState	With state?
	 * @return	Array	Array of Objects of this type on the map
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
     */
	function getObjectsOfType($type,$getState=1,$mergeWithGlobals=1) {
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method GlobalMap::getObjectsOfType('.$type.','.$getState.','.$mergeWithGlobals.')');
		// object array
		$objects = Array();
		
		// Default object state
		if($type == 'host' || $type == 'hostgroup') {
			$objState = Array('state'=>'UP','stateOutput'=>'Default State');
		} else {
			$objState = Array('state'=>'OK','stateOutput'=>'Default State');
		}
		
		if(is_array($objs = $this->MAPCFG->getDefinitions($type))){
			foreach($objs AS $index => $obj) {
				if (DEBUG&&DEBUGLEVEL&2) debug('Start object of type: '.$type);
				// workaround
				$obj['id'] = $index;
				
				if($mergeWithGlobals) {
					// merge with "global" settings
					foreach($this->MAPCFG->validConfig[$type] AS $key => $values) {
						if((!isset($obj[$key]) || $obj[$key] == '') && isset($values['default'])) {
							$obj[$key] = $values['default'];
						}
					}
				}
				
				// add default state to the object
				$obj = array_merge($obj,$objState);
				
				if($getState) {
					$obj = array_merge($obj,$this->getState($obj));
				}
				
				// The map alias only stands in the global section of the child map, get it
				if($obj['type'] == 'map') {
				    $obj = $this->getChildMapAlias($obj);
				}
				
				if($obj['type'] != 'textbox' && $obj['type'] != 'shape') {
					$obj['icon'] = $this->getIcon($obj);
				}
				
				// add object to array of objects
				$objects[] = $obj;
				if (DEBUG&&DEBUGLEVEL&2) debug('End object of type: '.$type);
			}
			
			if (DEBUG&&DEBUGLEVEL&1) debug('End method GlobalMap::getObjectsOfType(): Array(...)');
			return $objects;
		}
	}
	
	/**
	 * The map alias only stands in the global section of the child map, get it
	 *
	 * @param	Array	Array with object informations
	 * @return	Array	Array with object informations
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	function getChildMapAlias(&$obj) {
	    $MAPCFG = new NagVisMapCfg($this->MAINCFG,$obj['map_name']);
		$MAPCFG->readMapConfig(1);
		$obj['alias'] = $MAPCFG->getValue('global', 0, 'alias');
		
		return $obj;
	}
	
	/**
	 * Wraps all states in an Array to a summary state
	 *
	 * @param	Array	Array with objects states
	 * @return	String	Object state (DOWN|CRITICAL|WARNING|UNKNOWN|ERROR)
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	function wrapState(&$objStates) {
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method GlobalMap::wrapState(Array(...))');
		if(in_array('DOWN', $objStates) || in_array('CRITICAL', $objStates)) {
			if (DEBUG&&DEBUGLEVEL&1) debug('End method GlobalMap::wrapState(): CRITICAL');
			return 'CRITICAL';
		} elseif(in_array('WARNING', $objStates)) {
			if (DEBUG&&DEBUGLEVEL&1) debug('End method GlobalMap::wrapState(): WARNING');
			return 'WARNING';
		} elseif(in_array('UNKNOWN', $objStates)) {
			if (DEBUG&&DEBUGLEVEL&1) debug('End method GlobalMap::wrapState(): UNKNOWN');
			return 'UNKNOWN';
		} elseif(in_array('ERROR', $objStates)) {
			if (DEBUG&&DEBUGLEVEL&1) debug('End method GlobalMap::wrapState(): ERROR');
			return 'ERROR';
		} else {
			if (DEBUG&&DEBUGLEVEL&1) debug('End method GlobalMap::wrapState(): OK');
			return 'OK';
		}
	}
}
