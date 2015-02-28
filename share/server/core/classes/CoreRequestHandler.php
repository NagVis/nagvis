<?php
/*****************************************************************************
 *
 * CoreRequestHandler.php - Handler for requests
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
 *****************************************************************************/

class CoreRequestHandler {
    private $aOpts;

    public function __construct($aOptions) {
        $this->aOpts = $aOptions;
    }

    public function getKeys() {
        return array_keys($this->aOpts);
    }

    public function get($sKey) {
        if(isset($this->aOpts[$sKey]))
            return $this->aOpts[$sKey];
        else
            return null;
    }

    public function isLongerThan($sKey, $iLen) {
        return strlen($this->aOpts[$sKey]) > $iLen;
    }

    public function match($sKey, $regex) {
        if(!isset($this->aOpts[$sKey]))
            return false;

        // If this is an array validate the single values. When one of the values
        // is invalid return false.
        if(is_array($this->aOpts[$sKey])) {
            foreach($this->aOpts[$sKey] AS $val)
                if(!preg_match($regex, $val))
                    return false;
            return true;
        } else {
            return preg_match($regex, $this->aOpts[$sKey]);
        }
    }

    public function isSetAndNotEmpty($sKey) {
        return (isset($this->aOpts[$sKey]) && $this->aOpts[$sKey] != '');
    }

    public function getAll($exclude = Array()) {
        $ret = Array();
        foreach($this->aOpts AS $key => $val) {
            if(!isset($exclude[$key])) {
                $ret[$key] = $val;
            }
        }
        return $ret;
    }

    public static function getReferer($default) {
        if(isset($_SERVER['HTTP_REFERER']))
            return $_SERVER['HTTP_REFERER'];
        else
            return $default;
    }

    public static function getRequestUri($default) {
        if(isset($_SERVER['REQUEST_URI']))
            return $_SERVER['REQUEST_URI'];
        else
            return $default;
    }
}

?>
