<?php
/*****************************************************************************
 *
 * CoreExceptions.php - Collection of exceptions in NagVis
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

/**
 * @author	Lars Michelsen <lars@vertical-visions.de>
 */

class NagVisException extends Exception {
    function __construct($msg, $title = null, $time = null, $url = null) {
        if($title === null)
            $title = l('ERROR');

        $this->e = Array(
            'message' => $msg,
            'title'   => $title,
            'type'    => 'error',
        );

        if($time !== null)
            $this->e['reloadTime'] = $time;
        if($url !== null)
            $this->e['reloadUrl'] = $url;

        parent::__construct($msg);
    }

    function __toString() {
        return json_encode($this->e);
    }
}

class MapInMaintenance extends NagVisException {
    function __construct($map) {
        $this->e = Array(
            'type'    => 'info',
            'message' => l('mapInMaintenance', Array('MAP' => $map)),
            'title'   => l('INFO'),
        );
    }
}

class Success extends NagVisException {
    function __construct($msg, $title = null, $time = null, $url = null) {
        parent::__construct($msg, $title, $time, $url);
        $this->e['type'] = 'ok';
        if($this->e['title'] == l('ERROR'))
            $this->e['title'] = l('OK');
    }
}

class CoreAuthModNoSupport extends NagVisException {}

class BackendException extends NagVisException {}
class BackendConnectionProblem extends BackendException {}
class BackendInvalidResponse extends BackendException {}

class MapCfgInvalid extends NagVisException {}
class MapCfgInvalidObject extends MapCfgInvalid {}
class MapSourceError extends MapCfgInvalid {}

class UserInputError extends NagVisException {}

class InputErrorRedirect extends NagVisException {}

class FieldInputError extends NagVisException {
    function __construct($field, $msg) {
        $this->field = $field;
        $this->msg   = $msg;
    }
}

// This exception is used to handle PHP errors
class NagVisErrorException extends ErrorException {
    function __toString() {
        return "Error: (".$this->getCode().") ".$this->getMessage()
             . " (".$this->getFile().":".$this->getLine().")<br /><br />\n"
             . "<code>".str_replace("\n", "<br />\n", $this->getTraceAsString())."</code>";

    }
}
