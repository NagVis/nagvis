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
					  'allowedUsers' => Array('EVERYONE'),
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
		$this->addBodyLines($this->getJsLang());
		
		$this->ADDFORM = new GlobalForm(Array('name'=>'new_image',
			'id'=>'new_image',
			'method'=>'POST',
			'action'=>'./wui.function.inc.php?myaction=mgt_new_image',
			'onSubmit'=>'return check_image_add();',
			'enctype'=>'multipart/form-data',
			'cols'=>'2'));
		$this->addBodyLines($this->ADDFORM->initForm());
		$this->addBodyLines($this->ADDFORM->getCatLine(strtoupper($this->LANG->getLabel('uploadBackground'))));
		$this->propCount++;
		$this->addBodyLines($this->getAddFields());
		$this->addBodyLines($this->getAddSubmit());
		
		$this->DELFORM = new GlobalForm(Array('name'=>'image_delete',
			'id'=>'image_delete',
			'method'=>'POST',
			'action'=>'./wui.function.inc.php?myaction=mgt_image_delete',
			'onSubmit'=>'return check_image_delete();',
			'cols'=>'2'));
		$this->addBodyLines($this->DELFORM->initForm());
		$this->addBodyLines($this->DELFORM->getCatLine(strtoupper($this->LANG->getLabel('deleteBackground'))));
		$this->propCount++;
		$this->addBodyLines($this->getDelFields());
		$this->addBodyLines($this->getDelSubmit());
		
		// Resize the window
		$this->addBodyLines($this->resizeWindow());
		if (DEBUG&&DEBUGLEVEL&1) debug('End method WuiBackgroundManagement::getForm()');
	}
	
	/**
	 * Resizes the window to individual calculated size
	 *
	 * @return	Array	HTML Code
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
     */
	function resizeWindow() {
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method WuiBackgroundManagement::resizeWindow()');
		$ret = Array();
		$ret[] = "<script type=\"text/javascript\" language=\"JavaScript\"><!--";
		$ret[] = "// resize the window (depending on the number of properties displayed)";
		$ret[] = "window.resizeTo(540,".$this->propCount."*40+10)";
		$ret[] = "//--></script>";
		
		if (DEBUG&&DEBUGLEVEL&1) debug('End method WuiBackgroundManagement::resizeWindow(): Array(HTML)');
		return $ret;
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
	 * Gets delete submit button
	 *
	 * @return	Array	HTML Code
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
     */
	function getDelSubmit() {
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method WuiBackgroundManagement::getDelSubmit()');
		$this->propCount++;
		if (DEBUG&&DEBUGLEVEL&1) debug('End method WuiBackgroundManagement::getDelSubmit(): Array(HTML)');
		return array_merge($this->DELFORM->getSubmitLine($this->LANG->getLabel('delete')),$this->DELFORM->closeForm());
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
		$ret = array_merge($ret,$this->ADDFORM->getHiddenField('MAX_FILE_SIZE','1000000'));
		$ret = array_merge($ret,$this->ADDFORM->getFileLine($this->LANG->getLabel('choosePngImage'),'image_file',''));
		$this->propCount++;
		
		if (DEBUG&&DEBUGLEVEL&1) debug('End method WuiBackgroundManagement::getAddFields(): Array(HTML)');
		return $ret;
	}
	
	/**
	 * Gets new image submit button
	 *
	 * @return	Array	HTML Code
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
     */
	function getAddSubmit() {
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method WuiBackgroundManagement::getAddSubmit()');
		$this->propCount++;
		if (DEBUG&&DEBUGLEVEL&1) debug('End method WuiBackgroundManagement::getAddSubmit(): Array(HTML)');
		return array_merge($this->ADDFORM->getSubmitLine($this->LANG->getLabel('upload')),$this->ADDFORM->closeForm());
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
	 * @return	Array Html
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	function getJsLang() {
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method WuiBackgroundManagement::getJsLang()');
		$ret = Array();
		$ret[] = '<script type="text/javascript" language="JavaScript"><!--';
		$ret[] = 'var lang = Array();';
		$ret[] = 'lang[\'firstMustChoosePngImage\'] = \''.$this->LANG->getMessageText('firstMustChoosePngImage').'\';';
		$ret[] = 'lang[\'mustChoosePngImage\'] = \''.$this->LANG->getMessageText('mustChoosePngImage').'\';';
		$ret[] = 'lang[\'foundNoBackgroundToDelete\'] = \''.$this->LANG->getMessageText('foundNoBackgroundToDelete').'\';';
		$ret[] = 'lang[\'confirmBackgroundDeletion\'] = \''.$this->LANG->getMessageText('confirmBackgroundDeletion').'\';';
		$ret[] = 'lang[\'unableToDeleteBackground\'] = \''.$this->LANG->getMessageText('unableToDeleteBackground').'\';';
		$ret[] = '//--></script>';
		
		if (DEBUG&&DEBUGLEVEL&1) debug('End method WuiBackgroundManagement::getJsLang(): Array(HTML)');
		return $ret;	
	}
}
?>