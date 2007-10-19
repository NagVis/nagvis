<?php
/**
 * Class for building the Page for editing the MainCfg
 *
 * @author 	Lars Michelsen <lars@vertical-visions.de>
 */
class WuiEditMainCfg extends GlobalPage {
	var $MAINCFG;
	var $LANG;
	var $FORM;
	
	/**
	 * Class Constructor
	 *
	 * @param 	$MAINCFG GlobalMainCfg
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	function WuiEditMainCfg(&$MAINCFG) {
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method WuiEditMainCfg::WuiEditMainCfg(&$MAINCFG)');
		$this->MAINCFG = &$MAINCFG;
		
		# we load the language file
		$this->LANG = new GlobalLanguage($MAINCFG,'wui:editMainCfg');
		
		$prop = Array('title'=>$MAINCFG->getValue('internal', 'title'),
					  'cssIncludes'=>Array('./includes/css/wui.css'),
					  'jsIncludes'=>Array('./includes/js/wui.js'),
					  'extHeader'=>Array(''),
					  'allowedUsers' => $this->MAINCFG->getValue('wui','allowedforconfig'),
					  'languageRoot' => 'wui:editMainCfg');
		parent::GlobalPage($MAINCFG,$prop);
		if (DEBUG&&DEBUGLEVEL&1) debug('End method WuiEditMainCfg::WuiEditMainCfg()');
	}
	
	/**
	 * If enabled, the form is added to the page
	 *
	 * @author Lars Michelsen <lars@vertical-visions.de>
	 */
	function getForm() {
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method WuiEditMainCfg::getForm()');
		$this->FORM = new GlobalForm(Array('name'=>'edit_config',
									'id'=>'edit_config',
									'method'=>'POST',
									'action'=>'./form_handler.php?myaction=update_config',
									'onSubmit'=>'return update_param();',
									'cols'=>'3'));
		$this->addBodyLines($this->FORM->initForm());
		$this->addBodyLines($this->FORM->getHiddenField('properties',''));
		
		$this->addBodyLines($this->getFields());
		$this->addBodyLines($this->FORM->getSubmitLine($this->LANG->getLabel('save')));
		$this->addBodyLines($this->FORM->closeForm());
		$this->addBodyLines($this->parseJs($this->getHidden()));
		
		// Resize the window
		$this->addBodyLines($this->parseJs($this->resizeWindow(540,720)));
		if (DEBUG&&DEBUGLEVEL&1) debug('End method WuiEditMainCfg::getForm()');
	}
	
	/**
	 * Parses the Form fields
	 *
	 * @return	Array Html
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	function getFields() {
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method WuiEditMainCfg::getFields()');
		$ret = Array();
		
		foreach($this->MAINCFG->validConfig AS $cat => $arr) {
			// don't display backend,rotation and internal options
			if(!preg_match("/^backend/i",$cat) && !preg_match("/^internal$/i",$cat) && !preg_match("/^rotation/i",$cat)) {
				$ret = array_merge($ret,$this->FORM->getCatLine($cat));
				
				foreach($arr AS $key2 => $prop) {
					// ignore some vars
					if(isset($this->MAINCFG->validConfig[$cat][$key2]['editable']) && $this->MAINCFG->validConfig[$cat][$key2]['editable']) {
						$val2 = $this->MAINCFG->getValue($cat,$key2,TRUE);
						
						# we add a line in the form
						$ret[] = "<tr>";
						$ret[] = "\t<td class=\"tdlabel\">".$key2."</td>";
						
						if(preg_match('/^TranslationNotFound:/',$this->LANG->getLabel($key2,'',FALSE)) > 0) {
							$ret[] = "\t<td class=\"tdfield\"></td>";
						} else {
							$ret[] = "\t<td class=\"tdfield\">";
							$ret[] = "\t\t<img style=\"cursor:help\" src=\"./images/internal/help_icon.png\" onclick=\"javascript:alert('".$this->LANG->getLabel($key2,'',FALSE)." (".$this->LANG->getLabel('defaultValue').": ".$this->MAINCFG->validConfig[$cat][$key2]['default'].")')\" />";
							$ret[] = "\t</td>";
						}
						
						$ret[] = "\t<td class=\"tdfield\">";
						switch($key2) {
							case 'language':
							case 'backend':
							case 'icons':
							case 'rotatemaps':
							case 'displayheader':
							case 'recognizeservices':
							case 'onlyhardstates':
							case 'usegdlibs':
							case 'autoupdatefreq':
							case 'headertemplate':
								switch($key2) {
									case 'language':
										$arrOpts = $this->getLanguages();
									break;
									case 'backend':
										$arrOpts = $this->getBackends();
									break;
									case 'icons':
										$arrOpts = $this->getIconsets();
									break;
									case 'headertemplate':
										$arrOpts = $this->getHeaderTemplates();
									break;
									case 'rotatemaps':
									case 'displayheader':
									case 'recognizeservices':
									case 'onlyhardstates':
									case 'usegdlibs':
										$arrOpts = Array(Array('value'=>'1','label'=>$this->LANG->getLabel('yes')),
														 Array('value'=>'0','label'=>$this->LANG->getLabel('no')));
									break;
									case 'autoupdatefreq':
										$arrOpts = Array(Array('value'=>'0','label'=>$this->LANG->getLabel('disabled')),
														 Array('value'=>'2','label'=>'2'),
														 Array('value'=>'5','label'=>'5'),
														 Array('value'=>'10','label'=>'10'),
														 Array('value'=>'25','label'=>'25'),
														 Array('value'=>'50','label'=>'50'));
									break;
								}
								
								$ret = array_merge($ret,$this->FORM->getSelectField("conf_".$key2,$arrOpts));
							break;
							default:
								$ret = array_merge($ret,$this->FORM->getInputField("conf_".$key2,$val2));
								
								if(isset($prop['locked']) && $prop['locked'] == 1) {
									$ret[] = "<script>document.edit_config.elements['conf_".$key2."'].disabled=true;</script>";
								}
								
								if(is_array($val2)) {
									$val2 = implode(',',$val2);
								}
							break;
						}
						$ret[] = "\t\t<script>document.edit_config.elements['conf_".$key2."'].value='".$val2."';</script>";
						$ret[] = "\t</td>";
						$ret[] = "</tr>";
					}
				}
			}
		}
		if (DEBUG&&DEBUGLEVEL&1) debug('End method WuiEditMainCfg::getFields(): Array(HTML)');
		return $ret;
	}
	
	/**
	 * Reads all aviable backends
	 *
	 * @return	Array list
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	function getBackends() {
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method WuiEditMainCfg::getBackends()');
		$files = Array();
		
		if ($handle = opendir($this->MAINCFG->getValue('paths', 'class'))) {
 			while (false !== ($file = readdir($handle))) {
 				if ($file != "." && $file != ".." && preg_match('/^class.GlobalBackend-/', $file)) {
					$files[] = str_replace('class.GlobalBackend-','',str_replace('.php','',$file));
				}				
			}
			
			if ($files) {
				natcasesort($files);
			}
		}
		closedir($handle);
		
		if (DEBUG&&DEBUGLEVEL&1) debug('End method WuiEditMainCfg::getBackends(): Array(...)');
		return $files;
	}
	
	/**
	 * Reads all iconsets (that habe <iconset>_ok.png)
	 *
	 * @return	Array list
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	function getIconsets() {
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method WuiEditMainCfg::getIconsets()');
		$files = Array();
		
		if ($handle = opendir($this->MAINCFG->getValue('paths', 'icon'))) {
 			while (false !== ($file = readdir($handle))) {
				if ($file != "." && $file != ".." && preg_match('/_ok.png$/', $file)) {
					$files[] = str_replace('_ok.png','',$file);
				}				
			}
			
			if ($files) {
				natcasesort($files);
			}
		}
		closedir($handle);
		
		if (DEBUG&&DEBUGLEVEL&1) debug('End method WuiEditMainCfg::getIconsets(): Array(...)');
		return $files;
	}
	
	/**
	 * Reads all languages
	 *
	 * @return	Array list
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	function getLanguages() {
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method WuiEditMainCfg::getLanguages()');
		$files = Array();
		
		if ($handle = opendir($this->MAINCFG->getValue('paths', 'language'))) {
 			while (false !== ($file = readdir($handle))) {
				if ($file != "." && $file != ".." && preg_match('/.xml$/', $file)) {
					$files[] = str_replace('wui_','',str_replace('.xml','',$file));
				}				
			}
			
			if ($files) {
				natcasesort($files);
			}
		}
		closedir($handle);
		
		if (DEBUG&&DEBUGLEVEL&1) debug('End method WuiEditMainCfg::getLanguages(): Array(...)');
		return $files;
	}
	
	/**
	 * Reads all header templates
	 *
	 * @return	Array list
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	function getHeaderTemplates() {
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method WuiEditMainCfg::getHeaderTemplates()');
		$files = Array();
		
		if ($handle = opendir($this->MAINCFG->getValue('paths', 'headertemplate'))) {
 			while (false !== ($file = readdir($handle))) {
				if ($file != '.' && $file != '..' && preg_match('/^tmpl\..+\.html$/', $file)) {
					$files[] = str_replace('tmpl.','',str_replace('.html','',$file));
				}				
			}
			
			if ($files) {
				natcasesort($files);
			}
		}
		closedir($handle);
		
		if (DEBUG&&DEBUGLEVEL&1) debug('End method WuiEditMainCfg::getHeaderTemplates(): Array(...)');
		return $files;
	}
	
	/**
	 * Gets the hidden form
	 *
	 * @return	Array JS
	 * @author Lars Michelsen <lars@vertical-visions.de>
	 */
	function getHidden() {
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method WuiEditMainCfg::getHidden()');
		$ret = Array();
		$ret[] = "// function that builds up the list of parameters/values. There are 2 kinds of parameters values :";
		$ret[] = "//	- the \"normal value\". example : \$param=\"value\";";
		$ret[] = "//	- the other one (value computed with other ones) . example : \$param=\"part1\".\$otherparam;";
		$ret[] = "function update_param() {";
		$ret[] = "	document.edit_config.properties.value='';";
		$ret[] = "	for(i=0;i<document.edit_config.elements.length;i++) {";
		$ret[] = "		if(document.edit_config.elements[i].name.substring(0,5)=='conf_') {";
		$ret[] = "			document.edit_config.properties.value=document.edit_config.properties.value+'^'+document.edit_config.elements[i].name.substring(5,document.edit_config.elements[i].name.length)+'='+document.edit_config.elements[i].value;";
		$ret[] = "		}";
		$ret[] = "	}";
		$ret[] = "	document.edit_config.properties.value=document.edit_config.properties.value.substring(1,document.edit_config.properties.value.length);";
		$ret[] = "	return true;";
		$ret[] = "}";
		
		if (DEBUG&&DEBUGLEVEL&1) debug('End method WuiEditMainCfg::getHidden(): Array(JS)');
		return $ret;	
	}
}
?>
