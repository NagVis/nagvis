<?php
/*****************************************************************************
 *
 * CoreModUser.php - Manages user defined options
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
 * @author  Lars Michelsen <lars@vertical-visions.de>
 */
class CoreModUser extends CoreModule {
    protected $CORE;
    protected $FHANDLER;
    protected $SHANDLER;

    public function __construct($CORE) {
        $this->sName = 'User';
        $this->CORE = $CORE;

        $this->aActions = Array(
            'getOptions' => REQUIRES_AUTHORISATION,
            'setOption'  => REQUIRES_AUTHORISATION,
        );
    }

    public function handleAction() {
        $sReturn = '';

        if($this->offersAction($this->sAction)) {
            switch($this->sAction) {
                case 'getOptions':
                    $CFG = new CoreUserCfg();
                    $sReturn = json_encode($CFG->doGet());
                break;
                case 'setOption':
                    $this->handleResponse('handleResponseSet', 'doSet');
                break;
            }
        }

        return $sReturn;
    }

    protected function doSet($a) {
        $CFG = new CoreUserCfg();
        return $CFG->doSet($a['opts']);
    }

    protected function handleResponseSet() {
        $FHANDLER = new CoreRequestHandler($_GET);
        $this->verifyValuesSet($FHANDLER, Array('opts'));
        $opts = $FHANDLER->get('opts');

        foreach($opts as $key => $val)
            if (substr($val, 0, 1) == '{')
                $opts[$key] = json_decode($val);

        return Array('opts' => $opts);
    }
}
?>
