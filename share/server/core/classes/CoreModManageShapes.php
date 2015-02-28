<?php
/*******************************************************************************
 *
 * CoreModManageShapes.php - Core Map module to manage shapes in WUI
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
class CoreModManageShapes extends CoreModule {
    private $name = null;

    public function __construct(GlobalCore $CORE) {
        $this->sName = 'ManageShapes';
        $this->CORE = $CORE;

        // Register valid actions
        $this->aActions = Array(
            // WUI specific actions
            'view'      => 'manage',
            'checkUsed' => 'manage',
            'doUpload'  => 'manage',
            'doDelete'  => 'manage',
        );
    }

    public function handleAction() {
        $sReturn = '';

        if($this->offersAction($this->sAction)) {
            switch($this->sAction) {
                case 'checkUsed':
                    $sReturn = json_encode($this->checkUsed());
                break;
                case 'view':
                    $VIEW = new ViewManageShapes();
                    $sReturn = json_encode(Array('code' => $VIEW->parse()));
                break;
                case 'doDelete':
                    $this->handleResponse('handleResponseDelete', 'doDelete',
                                            l('The shape has been deleted.'),
                                                                l('The shape could not be deleted.'),
                                          1);
                break;
                case 'doUpload':
                    if($this->handleResponse('handleResponseDoUpload', 'doUpload'))
                        header('Location:'.$_SERVER['HTTP_REFERER']);
                break;
            }
        }

        return $sReturn;
    }

    protected function handleResponseDoUpload() {
        $FHANDLER = new CoreRequestHandler($_FILES);
        $this->verifyValuesSet($FHANDLER, Array('image_file'));
        return Array('image_file' => $FHANDLER->get('image_file'));
    }

    protected function doUpload($a) {
        if(!is_uploaded_file($a['image_file']['tmp_name']))
            throw new NagVisException(l('The file could not be uploaded (Error: [ERROR]).',
              Array('ERROR' => $a['image_file']['error'].': '.$this->CORE->getUploadErrorMsg($a['image_file']['error']))));

        $fileName = $a['image_file']['name'];

        if(!preg_match(MATCH_PNG_GIF_JPG_FILE, $fileName))
            throw new NagVisException(l('The uploaded file is no image (png,jpg,gif) file or contains unwanted chars.'));

        $filePath = path('sys', '', 'shapes').$fileName;
        return move_uploaded_file($a['image_file']['tmp_name'], $filePath) && $this->CORE->setPerms($filePath);
    }

    protected function doDelete($a) {
        $path = path('sys', '', 'shapes', $a['image']);
        if($path !== '')
            return unlink($path);
        else
            return false;
    }

    protected function handleResponseDelete() {
        $FHANDLER = new CoreRequestHandler($_POST);
        $attr = Array('image' => MATCH_PNG_GIF_JPG_FILE);
        $this->verifyValuesSet($FHANDLER,   $attr);
        $this->verifyValuesMatch($FHANDLER, $attr);

        if(!in_array($FHANDLER->get('image'), $this->CORE->getAvailableShapes()))
            throw new NagVisException(l('The shape does not exist.'));

        return Array('image' => $FHANDLER->get('image'));
    }

    protected function checkUsed() {
        $FHANDLER = new CoreRequestHandler($_GET);
        $attr = Array('image' => MATCH_PNG_GIF_JPG_FILE);
        $this->verifyValuesSet($FHANDLER,   $attr);
        $this->verifyValuesMatch($FHANDLER, $attr);
        $image = $FHANDLER->get('image');

        $using = Array();
        foreach($this->CORE->getAvailableMaps() AS $map) {
            $MAPCFG1 = new GlobalMapCfg($map);
            try {
                $MAPCFG1->readMapConfig();
            } catch(MapCfgInvalid $e) {
                continue;
            }

            foreach($MAPCFG1->getDefinitions('shape') AS $key => $obj) {
                if(isset($obj['icon']) && $obj['icon'] == $image) {
                    $using[] = $MAPCFG1->getName();
                }
            }
        }
        return $using;
    }
}
?>
