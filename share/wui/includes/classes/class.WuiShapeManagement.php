<?php
class WuiShapeManagement extends GlobalPage {
    var $MAINCFG;
    var $LANG;
    var $ADDFORM;
    var $DELFORM;
    var $propCount;
    
    /**
    * Class Constructor
    *
    * @param  GlobalMainCfg $MAINCFG
    * @author Lars Michelsen <lars@vertical-visions.de>
    */
    function WuiShapeManagement(&$MAINCFG) {
        if (DEBUG&&DEBUGLEVEL&1) debug('Start method WuiShapeManagement::WuiShapeManagement(&$MAINCFG)');
        $this->MAINCFG = &$MAINCFG;
        $this->propCount = 0;
        
        // load the language file
        $this->LANG = new GlobalLanguage($MAINCFG,'wui:shapeManagement');
        
        $prop = Array('title'=>$MAINCFG->getValue('internal', 'title'),
                    'cssIncludes'=>Array('./includes/css/wui.css'),
                    'jsIncludes'=>Array('./includes/js/ShapeManagement.js',
                        './includes/js/ajax.js',
                        './includes/js/wui.js'),
                    'extHeader'=>Array(''),
                    'allowedUsers' => Array('EVERYONE'),
                    'languageRoot' => 'wui:shapeManagement');
        parent::GlobalPage($MAINCFG,$prop);
        if (DEBUG&&DEBUGLEVEL&1) debug('End method WuiShapeManagement::WuiShapeManagement()');
    }
    
    /**
    * If enabled, the form is added to the page
    *
    * @author Lars Michelsen <lars@vertical-visions.de>
    */
    function getForm() {
        if (DEBUG&&DEBUGLEVEL&1) debug('Start method WuiShapeManagement::getForm()');
        // Inititalize language for JS
        $this->addBodyLines($this->parseJs($this->getJsLang()));
        
        $this->ADDFORM = new GlobalForm(Array('name'=>'shape_add',
                                                'id'=>'shape_add',
                                                'method'=>'POST',
                                                'action'=>'./wui.function.inc.php?myaction=mgt_shape_add',
                                                'onSubmit'=>'return check_image_add();',
                                                'enctype'=>'multipart/form-data',
                                                'cols'=>'2'));
        $this->addBodyLines($this->ADDFORM->initForm());
        $this->addBodyLines($this->ADDFORM->getCatLine(strtoupper($this->LANG->getLabel('uploadShape'))));
        $this->propCount++;
        $this->addBodyLines($this->getAddFields());
        $this->propCount++;
        $this->addBodyLines($this->ADDFORM->getSubmitLine($this->LANG->getLabel('upload')));
        $this->addBodyLines($this->ADDFORM->closeForm());
        
        $this->DELFORM = new GlobalForm(Array('name'=>'shape_delete',
                                                'id'=>'shape_delete',
                                                'method'=>'POST',
                                                'action'=>'./wui.function.inc.php?myaction=mgt_shape_delete',
                                                'onSubmit'=>'return check_image_delete();',
                                                'cols'=>'2'));
        $this->addBodyLines($this->DELFORM->initForm());
        $this->addBodyLines($this->DELFORM->getCatLine(strtoupper($this->LANG->getLabel('deleteShape'))));
        $this->propCount++;
        $this->addBodyLines($this->getDelFields());
        $this->propCount++;
        $this->addBodyLines($this->ADDFORM->getSubmitLine($this->LANG->getLabel('delete')));
        $this->addBodyLines($this->ADDFORM->closeForm());
        
        // Resize the window
        $this->addBodyLines($this->parseJs($this->resizeWindow(540,$this->propCount*40+10)));
        if (DEBUG&&DEBUGLEVEL&1) debug('End method WuiShapeManagement::getForm()');
    }
    
    /**
    * Gets new image fields
    *
    * @return Array HTML Code
    * @author  Lars Michelsen <lars@vertical-visions.de>
    */
    function getAddFields() {
        if (DEBUG&&DEBUGLEVEL&1) debug('Start method WuiShapeManagement::getAddFields()');
        $ret = Array();
        $ret = array_merge($ret,$this->ADDFORM->getHiddenField('MAX_FILE_SIZE','1000000'));
        $ret = array_merge($ret,$this->ADDFORM->getFileLine($this->LANG->getLabel('choosePngImage'),'shape_image',''));
        $this->propCount++;
        
        if (DEBUG&&DEBUGLEVEL&1) debug('End method WuiShapeManagement::getAddFields(): Array(HTML)');
        return $ret;
    }
    
    /**
    * Gets delete fields
    *
    * @return Array HTML Code
    * @author  Lars Michelsen <lars@vertical-visions.de>
    */
    function getDelFields() {
        if (DEBUG&&DEBUGLEVEL&1) debug('Start method WuiShapeManagement::getDelFields()');
        $ret = Array();
        $ret = array_merge($ret,$this->DELFORM->getSelectLine($this->LANG->getLabel('choosePngImage'),'shape_image',$this->getMapImages(),''));
        $this->propCount++;
        
        if (DEBUG&&DEBUGLEVEL&1) debug('End method WuiShapeManagement::getDelFields(): Array(HTML)');
        return $ret;
    }
    
    /**
    * Reads all map images in shape path
    *
    * @return Array map images
    * @author  Lars Michelsen <lars@vertical-visions.de>
    */
    function getMapImages() {
        if (DEBUG&&DEBUGLEVEL&1) debug('Start method WuiShapeManagement::getMapImages()');
        $files = Array();
        
        if ($handle = opendir($this->MAINCFG->getValue('paths', 'shape'))) {
            while (false !== ($file = readdir($handle))) {
                if($file != '.' && $file != '..' && substr($file,strlen($file)-4,4) == ".png") {
                    $files[] = $file;
                }
            }
    
            if ($files) {
                natcasesort($files);
            }
            closedir($handle);
        }
    
        if (DEBUG&&DEBUGLEVEL&1) debug('End method WuiShapeManagement::getMapImages(): Array(...)');
        return $files;
    }
    
    /**
    * Gets all needed messages
    *
    * @return Array JS
    * @author  Lars Michelsen <lars@vertical-visions.de>
    */
    function getJsLang() {
        if (DEBUG&&DEBUGLEVEL&1) debug('Start method WuiShapeManagement::getJsLang()');
        $ret = Array();
        $ret[] = 'var lang = Array();';
        $ret[] = 'lang[\'firstMustChoosePngImage\'] = \''.$this->LANG->getMessageText('firstMustChoosePngImage').'\';';
        $ret[] = 'lang[\'mustChoosePngImage\'] = \''.$this->LANG->getMessageText('mustChoosePngImage').'\';';
        $ret[] = 'lang[\'foundNoShapeToDelete\'] = \''.$this->LANG->getMessageText('foundNoShapeToDelete').'\';';
        $ret[] = 'lang[\'shapeInUse\'] = \''.$this->LANG->getMessageText('shapeInUse').'\';';
        $ret[] = 'lang[\'confirmShapeDeletion\'] = \''.$this->LANG->getMessageText('confirmShapeDeletion').'\';';
        $ret[] = 'lang[\'unableToDeleteShape\'] = \''.$this->LANG->getMessageText('unableToDeleteShape').'\';';
        
        if (DEBUG&&DEBUGLEVEL&1) debug('End method WuiShapeManagement::getJsLang(): Array(JS)');
        return $ret;
    }
}