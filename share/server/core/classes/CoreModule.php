<?php
/*******************************************************************************
 *
 * CoreModule.php - Abstract definition of a core module
 *
 * Copyright (c) 2004-2015 NagVis Project (Contact: info@nagvis.org)
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
 ******************************************************************************/

/**
 * @author Lars Michelsen <lars@vertical-visions.de>
 */
abstract class CoreModule {
    protected $UHANDLER = null;
    protected $FHANDLER = null;

    protected $aActions = Array();
    protected $aObjects = Array();
    protected $sName = '';
    protected $sAction = '';
    protected $sObject = null;

    /**
     * Tells if the module offers the requested action
     *
     * @author  Lars Michelsen <lars@vertical-visions.de>
     */
    public function offersAction($sAction) {
        return isset($this->aActions[$sAction]);
    }

    /**
     * Stores the requested action in the module
     *
     * @author  Lars Michelsen <lars@vertical-visions.de>
     */
    public function setAction($sAction) {
        if(!$this->offersAction($sAction))
            return false;

        $this->sAction = $sAction;
        return true;
    }

    /**
     * Tells wether the requested action requires the users autorisation
     *
     * @author  Lars Michelsen <lars@vertical-visions.de>
     */
    public function actionRequiresAuthorisation() {
        return isset($this->aActions[$this->sAction]) && $this->aActions[$this->sAction] !== !REQUIRES_AUTHORISATION;
    }

    /**
     * Tells wether the requested object is available
     *
     * @author  Lars Michelsen <lars@vertical-visions.de>
     */
    public function offersObject($sObject) {
        return isset($this->aObjects[$sObject]);
    }

    /**
     * Stores the requested object name in the module
     * when it is supported
     *
     * @author  Lars Michelsen <lars@vertical-visions.de>
     */
    public function setObject($sObject) {
        if(!$this->offersObject($sObject)) {
            // Set sObject to an empty string. This tells the isPermitted() check that
            // this module uses object based authorisation checks. In that case it
            // won't pass the object authorisation check.
            $this->sObject = '';
            return false;
        }

        $this->sObject = $sObject;
        return true;
    }

    /**
     *  Returns the object string
     *
     * @author  Lars Michelsen <lars@vertical-visions.de>
     */
    public function getObject() {
        return $this->sObject;
    }

    /**
     * Checks if the user is permitted to perform the requested action
     *
     * @author  Lars Michelsen <lars@vertical-visions.de>
     */
    public function isPermitted() {
        global $AUTHORISATION;
        $authorized = true;
        if(!isset($AUTHORISATION) || $AUTHORISATION === null)
            $authorized = false;

        // Maybe the requested action is summarized by some other
        $action = !is_bool($this->aActions[$this->sAction]) ? $this->aActions[$this->sAction] : $this->sAction;

        if(!$AUTHORISATION->isPermitted($this->sName, $action, $this->sObject))
            $authorized = false;

        if(!$authorized)
            throw new NagVisException(l('You are not permitted to access this page ([PAGE]).',
                                        Array('PAGE' => $this->sName.'/'.$action.'/'.$this->sObject)));
    }

    /**
     * Initializes the URI handler object
     *
     * @author  Lars Michelsen <lars@vertical-visions.de>
     */
    protected function initUriHandler() {
        $this->UHANDLER = new CoreUriHandler();
    }

    /**
     * Returns all _GET+_POST vars. Supports optional array of attributes to
     * exclude where the keys are the var names. Always excludes mod/act params
     */
    protected function getAllOptions($exclude = Array()) {
        if(!isset($this->FHANDLER))
            $this->FHANDLER = new CoreRequestHandler(array_merge($_GET, $_POST));
        $exclude['mod'] = true;
        $exclude['act'] = true;
        return $this->FHANDLER->getAll($exclude);
    }

    /**
     * Reads a list of custom variables from the request
     *
     * @author  Lars Michelsen <lars@vertical-visions.de>
     */
    protected function getCustomOptions($aKeys, $aDefaults = Array(), $mixed = false) {
        if($mixed) {
            if(!isset($this->FHANDLER))
                $this->FHANDLER = new CoreRequestHandler(array_merge($_GET, $_POST));

            $aReturn = Array();
            foreach($aKeys AS $key => $val)
                if($this->FHANDLER->match($key, $val))
                    $aReturn[$key] = $this->FHANDLER->get($key);

            return $aReturn;
        }

        // Initialize on first call
        if($this->UHANDLER === null)
            $this->initUriHandler();

        // Load the specific params to the UriHandler
        $this->UHANDLER->parseModSpecificUri($aKeys, $aDefaults);

        // Now get those params
        $aReturn = Array();
        foreach($aKeys AS $key => $val)
            $aReturn[$key] = $this->UHANDLER->get($key);

        return $aReturn;
    }

    /**
     * Is a dummy at this place. Some special modules like
     * CoreModMap have no general way of fetching the called
     * "object" because the value might come in different vars
     * when using different actions. So these special modules
     * can implement that by overriding this method.
     *
     * @author  Lars Michelsen <lars@vertical-visions.de>
     */
    public function initObject() {}

    /**
     * This method needs to be implemented by each module
     * to handle the user called action
     *
     * @author  Lars Michelsen <lars@vertical-visions.de>
     */
    abstract public function handleAction();

    /**
     * Helper function to handle default form responses
     *
     * @author  Lars Michelsen <lars@vertical-visions.de>
     */
    protected function handleResponse($validationHandler, $action, $successMsg = null,
                                        $failMessage = null, $reload = null, $redirectUrl = null) {
        $aReturn = $this->{$validationHandler}();

        $type = 'ok';
        $msg = null;
        if($aReturn !== false) {
            $ret = $this->{$action}($aReturn);
            if($ret && $successMsg) {
                $msg = $successMsg;
            } elseif(!$ret && $failMessage) {
                $type = 'error';
                $msg = $failMessage;
            }
        } else {
            $type = 'error';
            $msg = l('You entered invalid information.');
        }

        if($msg && $type == 'error')
            throw new NagVisException($msg, null, $reload, $redirectUrl);
        elseif($msg && $type == 'ok')
            throw new Success($msg, null, $reload, $redirectUrl);
        else
            return $ret;
    }

    /**
     * Checks if the listed values are set. Otherwise it raises and error message
     *
     * @author  Lars Michelsen <lars@vertical-visions.de>
     */
    protected function verifyValuesSet($HANDLER, $list) {
        // Check if the array is assoc. When it isn't re-format it.
        if(array_keys($list) === range(0, count($list) - 1)) {
            $assoc = Array();
            foreach($list AS $value)
                $assoc[$value] = true;
            $list = $assoc;
        }

        foreach($list AS $key => $value)
            if(!$HANDLER->isSetAndNotEmpty($key))
                throw new UserInputError(l('mustValueNotSet1', Array('ATTRIBUTE' => $key)));
    }

    /**
     * Checks if the listes values match the given patterns. Otherwise it raises
     * an error message.
     *
     * @author  Lars Michelsen <lars@vertical-visions.de>
     */
    protected function verifyValuesMatch($HANDLER, $list) {
        foreach($list AS $key => $pattern)
            if($pattern && !$HANDLER->match($key, $pattern))
                throw new UserInputError(l('The value of option "[ATTRIBUTE]" does not match the valid format.',
                                           Array('ATTRIBUTE' => $key)));

    }

    /**
     * Is called with an array of files and timestamps to check if the file ages
     * have changed since these timestamps.
     *
     * Returns null when nothing changed or a structure of the changed objects
     */
    protected function checkFilesChanged($files) {
        global $AUTHORISATION, $CORE;
        $changed = array();

        foreach($files AS $file) {
            $parts = explode(',', $file);
            // Skip invalid requested files
            if(count($parts) != 3)
                continue;
            list($ty, $name, $age) = $parts;
            $age = (int) $age;

            // Try to fetch the current age of the requested file
            $cur_age = null;
            if($ty == 'maincfg') {
                $cur_age = $CORE->getMainCfg()->getConfigFileAge();

            } elseif($ty == 'map') {
                if($AUTHORISATION->isPermitted('Map', 'view', $name)) {
                    $MAPCFG  = new GlobalMapCfg($name);
                    $MAPCFG->readMapConfig();
                    $cur_age = $MAPCFG->getFileModificationTime($age);
                }
            }

            // Check if the file has changed; Reply with the changed timestamps
            if($cur_age !== null && $cur_age > $age) {
                $changed[$name] = $cur_age;
            }
        }

        if(count($changed) > 0) {
            return json_encode(array(
                'status' => 'CHANGED',
                'data'   => $changed,
            ));
        } else {
            return null;
        }
    }
}
?>
