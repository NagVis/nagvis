<?php
/*******************************************************************************
 *
 * CoreModGeneral.php - Core module to handle general ajax requests
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
class CoreModGeneral extends CoreModule {

    public function __construct(GlobalCore $CORE) {
        $this->sName = 'General';
        $this->CORE = $CORE;

        $this->aActions = Array(
            'getHoverTemplate'   => REQUIRES_AUTHORISATION,
            'getContextTemplate' => REQUIRES_AUTHORISATION,
            'getHoverUrl'        => REQUIRES_AUTHORISATION,
        );
    }

    public function handleAction() {
        $sReturn = '';

        if($this->offersAction($this->sAction)) {
            switch($this->sAction) {
                case 'getHoverTemplate':
                    $sReturn = $this->getTemplate('hover');
                break;
                case 'getContextTemplate':
                    $sReturn = $this->getTemplate('context');
                break;
                case 'getHoverUrl':
                    $sReturn = $this->getHoverUrl();
                break;
            }
        }

        return $sReturn;
    }

    private function getTemplate($type) {
        $arrReturn = Array();

        // Parse view specific uri params
        $aOpts = $this->getCustomOptions(Array('name' => MATCH_STRING_NO_SPACE));

        foreach($aOpts['name'] AS $sName) {
            if($type == 'hover')
                $OBJ = new NagVisHoverMenu($this->CORE, $sName);
            else
                $OBJ = new NagVisContextMenu($this->CORE, $sName);

            $arrReturn[] = Array('name'     => $sName,
                                 'css_file' => $OBJ->getCssFile(),
                                 'code'     => str_replace("\r\n", "", str_replace("\n", "", $OBJ->__toString())));
        }

        return json_encode($arrReturn);
    }

    private function getHoverUrl() {
        $arrReturn = Array();

        // Parse view specific uri params
        $aOpts = $this->getCustomOptions(Array('url' => MATCH_STRING_URL));

        foreach($aOpts['url'] AS $sUrl) {
            $OBJ = new NagVisHoverUrl($this->CORE, $sUrl);
            $arrReturn[] = Array('url' => $sUrl, 'code' => $OBJ->__toString());
        }

        $result = json_encode($arrReturn);
        if ($result === false)
            throw new NagVisException(l('Data not parsable: [URL] ([MSG])',
                array('URL' => htmlentities($sUrl, ENT_COMPAT, 'UTF-8'), 'MSG' => json_last_error_msg())));
        return $result;
    }
}
?>
