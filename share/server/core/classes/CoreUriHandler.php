<?php
/*******************************************************************************
 *
 * CoreUriHandler.php - Class to handle uri parsing
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
class CoreUriHandler {
    private $sRequestUri;
    private $aOpts;

    private $aAliases;

    public function __construct() {
        $this->aAliases = Array('module' => 'mod', 'action' => 'act');

        $this->sRequestUri = strip_tags($_SERVER['REQUEST_URI']);

        // Parse the URI and apply default params when neccessary
        $this->parseUri();
        $this->setDefaults();
        $this->validate();
    }

    public function getRequestUri() {
        return $this->sRequestUri;
    }

    public function set($sKey, $sVal) {
        $sReturn = false;

        // Transform parameter aliases
        if(isset($this->aAliases[$sKey]))
            $sKey = $this->aAliases[$sKey];

        if($this->isSetAndNotEmpty($sKey))
            $sReturn = $this->aOpts[$sKey];

        $this->aOpts[$sKey] = $sVal;

        // Set in superglobal array for later
        // direct access in other UHANDLER instances
        $_GET[$sKey] = $sVal;

        return $sReturn;
    }

    public function get($sKey) {
        // Transform parameter aliases
        if(isset($this->aAliases[$sKey]))
            $sKey = $this->aAliases[$sKey];

        if($this->isSetAndNotEmpty($sKey))
            return $this->aOpts[$sKey];
        else
            return false;
    }

    public function parseModSpecificUri($aKeys, $aDefaults = Array()) {
        foreach($aKeys AS $key => $sMatch) {
            // Validate the value
            $bValid = true;
            if($sMatch !== '') {
                // When param not set initialize it as empty string
                // or with the given defaults when some given.
                if(!isset($_GET[$key]))
                    if(isset($aDefaults[$key]))
                        $_GET[$key] = $aDefaults[$key];
                    else
                        $_GET[$key] = '';

                // Validate single value or multiple (array)
                if(is_array($_GET[$key]))
                    foreach($_GET[$key] AS $val)
                        $bValid = preg_match($sMatch, $val);
                else
                    $bValid = preg_match($sMatch, $_GET[$key]);
            }

            if($bValid) {
                $this->aOpts[$key] = $_GET[$key];
            } else {
                throw new NagVisException(l('The parameter "[key]" does not match the valid value format',
                                            Array('key' => htmlentities($key, ENT_COMPAT, 'UTF-8'))));
            }
        }
    }

    private function parseUri() {
        // Maybe for later use when using nice urls
        // Cleanup some bad things from the URI
        //$sRequest = str_replace(cfg('paths','htmlbase'), '', $this->sRequestUri);
        // Remove the first slash and then explode by slashes
        //$this->aOpts = explode('/', substr($sRequest,1));

        if(isset($_GET['mod']))
            $this->aOpts['mod'] = $_GET['mod'];
        if(isset($_GET['act']))
            $this->aOpts['act'] = $_GET['act'];
    }

    private function setDefaults() {
        // Handle default options when no module given
        if(!$this->isSetAndNotEmpty('mod'))
            $this->aOpts['mod'] = cfg('global', 'startmodule');

        // Handle default options when no action given
        if(!$this->isSetAndNotEmpty('act'))
            $this->aOpts['act'] = cfg('global', 'startaction');
    }

    private function validate() {
        $bValid = true;

        // Validate each param
        foreach($this->aOpts AS $val)
            if(!preg_match(MATCH_URI_PART, $val))
                $bValid = false;

        if($bValid === false)
            throw new NagVisException(l('The given url is not valid'));
    }

    public function isSetAndNotEmpty($sKey) {
        // Transform parameter aliases
        if(isset($this->aAliases[$sKey]))
            $sKey = $this->aAliases[$sKey];

        return isset($this->aOpts[$sKey]) && $this->aOpts[$sKey] != '';
    }
}

?>
