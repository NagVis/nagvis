<?php
/**
 * Class for managing the backgrounds in WUI
 *
 * @author 	Lars Michelsen <lars@vertical-visions.de>
 */
class WuiBackgroundManagement extends GlobalPage {
	var $MAINCFG;
	var $LANG;
	var $ADDFORM;
	var $DELFORM;
	var $propCount;
	
	/**
	 * Class Constructor
	 *
	 * @param	GlobalMainCfg	$MAINCFG
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	function WuiBackgroundManagement(&$MAINCFG) {
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method WuiBackgroundManagement::WuiBackgroundManagement(&$MAINCFG)');
		$this->MAINCFG = &$MAINCFG;
		$this->propCount = 0;
		
		// load the language file
		$this->LANG = new GlobalLanguage($MAINCFG,'wui:backgroundManagement');
		
		$prop = Array('title'=>$MAINCFG->getValue('internal', 'title'),
					  'cssIncludes'=>Array('./includes/css/wui.css'),
					  'jsIncludes'=>Array('./includes/js/BackgroundManagement.js',
					  						'./includes/js/ajax.js',
					  						'./includes/js/wui.js'),
					  'extHeader'=>Array(''),
					  'allowedUsers' => $this->MAINCFG->getValue('wui','allowedforconfig'),
					  'languageRoot' => 'wui:backgroundManagement');
		parent::GlobalPage($MAINCFG,$prop);
		if (DEBUG&&DEBUGLEVEL&1) debug('End method WuiBackgroundManagement::WuiBackgroundManagement()');
	}
	
	/**
	* If enabled, the form is added to the page
	*
	* @author Lars Michelsen <lars@vertical-visions.de>
	*/
	function getForm() {
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method WuiBackgroundManagement::getForm()');
		// Inititalize language for JS
		$this->addBodyLines($this->parseJs($this->getJsLang()));
		
		$this->CREATEFORM = new GlobalForm(Array('name'=>'create_image',
			'id'=>'create_image',
			'method'=>'POST',
			'action'=>'./form_handler.php?myaction=mgt_image_create',
			'onSubmit'=>'return check_image_create();',
			'cols'=>'2'));
		$this->addBodyLines($this->CREATEFORM->initForm());
		$this->addBodyLines($this->CREATEFORM->getCatLine(strtoupper($this->LANG->getLabel('createBackground'))));
		$this->propCount++;
		$this->addBodyLines($this->getCreateFields());
		$this->propCount++;
		$this->addBodyLines($this->CREATEFORM->getSubmitLine($this->LANG->getLabel('create')));
		$this->addBodyLines($this->CREATEFORM->closeForm());
		
		$this->ADDFORM = new GlobalForm(Array('name'=>'new_image',
			'id'=>'new_image',
			'method'=>'POST',
			'action'=>'./form_handler.php?myaction=mgt_new_image',
			'onSubmit'=>'return check_image_add();',
			'enctype'=>'multipart/form-data',
			'cols'=>'2'));
		$this->addBodyLines($this->ADDFORM->initForm());
		$this->addBodyLines($this->ADDFORM->getCatLine(strtoupper($this->LANG->getLabel('uploadBackground'))));
		$this->propCount++;
		$this->addBodyLines($this->getAddFields());
		$this->propCount++;
		$this->addBodyLines($this->ADDFORM->getSubmitLine($this->LANG->getLabel('upload')));
		$this->addBodyLines($this->ADDFORM->closeForm());
		
		$this->DELFORM = new GlobalForm(Array('name'=>'image_delete',
			'id'=>'image_delete',
			'method'=>'POST',
			'action'=>'./form_handler.php?myaction=mgt_image_delete',
			'onSubmit'=>'return check_image_delete();',
			'cols'=>'2'));
		$this->addBodyLines($this->DELFORM->initForm());
		$this->addBodyLines($this->DELFORM->getCatLine(strtoupper($this->LANG->getLabel('deleteBackground'))));
		$this->propCount++;
		$this->addBodyLines($this->getDelFields());
		$this->propCount++;
		$this->addBodyLines($this->ADDFORM->getSubmitLine($this->LANG->getLabel('delete')));
		$this->addBodyLines($this->ADDFORM->closeForm());
		
		// Resize the window
		$this->addBodyLines($this->parseJs($this->resizeWindow(540,$this->propCount*30+90)));
		if (DEBUG&&DEBUGLEVEL&1) debug('End method WuiBackgroundManagement::getForm()');
	}
	
	/**
	 * Gets delete fields
	 *
	 * @return	Array	HTML Code
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
     */
	function getDelFields() {
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method WuiBackgroundManagement::getDelFields()');
		$ret = Array();
		$ret = array_merge($ret,$this->DELFORM->getSelectLine($this->LANG->getLabel('choosePngImage'),'map_image',$this->getMapImages(),''));
		$this->propCount++;
		
		if (DEBUG&&DEBUGLEVEL&1) debug('End method WuiBackgroundManagement::getDelFields(): Array(HTML)');
		return $ret;
	}
	
	/**
	 * Gets new image fields
	 *
	 * @return	Array	HTML Code
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
     */
	function getAddFields() {
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method WuiBackgroundManagement::getAddFields()');
		$ret = Array();
		$ret = array_merge($ret,$this->ADDFORM->getHiddenField('MAX_FILE_SIZE','3000000'));
		$ret = array_merge($ret,$this->ADDFORM->getFileLine($this->LANG->getLabel('choosePngImage'),'image_file',''));
		$this->propCount++;
		
		if (DEBUG&&DEBUGLEVEL&1) debug('End method WuiBackgroundManagement::getAddFields(): Array(HTML)');
		return $ret;
	}
	
	/**
	 * Gets create image fields
	 *
	 * @return	Array	HTML Code
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
     */
	function getCreateFields() {
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method WuiBackgroundManagement::getCreateFields()');
		$ret = Array();
		$ret = array_merge($ret,$this->CREATEFORM->getInputLine($this->LANG->getLabel('backgroundName'),'image_name','',TRUE));
		$this->propCount++;
		$ret = array_merge($ret,$this->CREATEFORM->getInputLine($this->LANG->getLabel('backgroundColor'),'image_color','#',TRUE));
		$this->propCount++;
		$ret = array_merge($ret,$this->CREATEFORM->getInputLine($this->LANG->getLabel('backgroundWidth'),'image_width','',TRUE));
		$this->propCount++;
		$ret = array_merge($ret,$this->CREATEFORM->getInputLine($this->LANG->getLabel('backgroundHeight'),'image_height','',TRUE));
		$this->propCount++;
		
		if (DEBUG&&DEBUGLEVEL&1) debug('End method WuiBackgroundManagement::getCreateFields(): Array(HTML)');
		return $ret;
	}
	
	/**
	 * Reads all map images in map path
	 *
	 * @return	Array map images
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	function getMapImages() {
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method WuiBackgroundManagement::getMapImages()');
		$files = Array();
		
		if ($handle = opendir($this->MAINCFG->getValue('paths', 'map'))) {
 			while (false !== ($file = readdir($handle))) {
				if ($file != "." && $file != ".." && substr($file,strlen($file)-4,4) == ".png") {
					$files[] = $file;
				}				
			}
			
			if ($files) {
				natcasesort($files);
			}
		}
		closedir($handle);
		
		if (DEBUG&&DEBUGLEVEL&1) debug('End method WuiBackgroundManagement::getMapImages(): Array(...)');
		return $files;
	}
	
	/**
	 * Gets all needed messages
	 *
	 * @return	Array JS
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	function getJsLang() {
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method WuiBackgroundManagement::getJsLang()');
		$ret = Array();
		$ret[] = 'var lang = Array();';
		$ret[] = 'lang[\'firstMustChoosePngImage\'] = \''.$this->LANG->getMessageText('firstMustChoosePngImage').'\';';
		$ret[] = 'lang[\'mustChoosePngImage\'] = \''.$this->LANG->getMessageText('mustChoosePngImage').'\';';
		$ret[] = 'lang[\'foundNoBackgroundToDelete\'] = \''.$this->LANG->getMessageText('foundNoBackgroundToDelete').'\';';
		$ret[] = 'lang[\'confirmBackgroundDeletion\'] = \''.$this->LANG->getMessageText('confirmBackgroundDeletion').'\';';
		$ret[] = 'lang[\'unableToDeleteBackground\'] = \''.$this->LANG->getMessageText('unableToDeleteBackground').'\';';
		$ret[] = 'lang[\'mustValueNotSet1\'] = \''.$this->LANG->getMessageText('mustValueNotSet1').'\';';
		
		if (DEBUG&&DEBUGLEVEL&1) debug('End method WuiBackgroundManagement::getJsLang(): Array(JS)');
		return $ret;	
	}
}
?>
