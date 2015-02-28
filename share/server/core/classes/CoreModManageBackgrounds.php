<?php
/*******************************************************************************
 *
 * CoreModManageBackgrounds.php - Core module to manage backgrounds
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
class CoreModManageBackgrounds extends CoreModule {
    private $name = null;

    public function __construct(GlobalCore $CORE) {
        $this->sName = 'ManageBackgrounds';
        $this->CORE = $CORE;

        // Register valid actions
        $this->aActions = Array(
            // WUI specific actions
            'view'      => 'manage',
            'checkUsed' => 'manage',
            'doCreate'  => 'manage',
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
                    $VIEW = new ViewManageBackgrounds();
                    $sReturn = json_encode(Array('code' => $VIEW->parse()));
                break;
                case 'doCreate':
                    $this->handleResponse('handleResponseCreate', 'doCreate',
                                            l('The background has been created.'),
                                                                l('The background could not be created.'),
                                          1);
                break;
                case 'doDelete':
                    $this->handleResponse('handleResponseDelete', 'doDelete',
                                            l('The background has been deleted.'),
                                                                l('The background could not be deleted.'),
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

        $filePath = path('sys', '', 'backgrounds').$fileName;
        return move_uploaded_file($a['image_file']['tmp_name'], $filePath) && $this->CORE->setPerms($filePath);
    }

    protected function doDelete($a) {
        $BACKGROUND = new GlobalBackground($a['image']);
        $BACKGROUND->deleteImage();

        return true;
    }

    protected function handleResponseDelete() {
        $FHANDLER = new CoreRequestHandler($_POST);
        $this->verifyValuesSet($FHANDLER,   Array('map_image'));
        $this->verifyValuesMatch($FHANDLER, Array('map_image' => MATCH_PNG_GIF_JPG_FILE));

        if(!in_array($FHANDLER->get('map_image'), $this->CORE->getAvailableBackgroundImages()))
            throw new NagVisException(l('The background does not exist.'));

        return Array('image' => $FHANDLER->get('map_image'));
    }

    protected function doCreate($a) {
        $BACKGROUND = new GlobalBackground($a['name'].'.png');
        $BACKGROUND->createImage($a['color'], $a['width'], $a['height']);

        return true;
    }

    protected function handleResponseCreate() {
        $FHANDLER = new CoreRequestHandler($_POST);
        $attr = Array('image_name'   => MATCH_BACKGROUND_NAME,
                      'image_color'  => MATCH_COLOR,
                                    'image_width'  => MATCH_INTEGER,
                      'image_height' => MATCH_INTEGER);
        $this->verifyValuesSet($FHANDLER,   $attr);
        $this->verifyValuesMatch($FHANDLER, $attr);

        // Check if the background exists
        if(in_array($FHANDLER->get('image_name').'.png', $this->CORE->getAvailableBackgroundImages()))
            throw new NagVisException(l('The background does already exist.'));

        return Array('name'   => $FHANDLER->get('image_name'),
                   'color'  => $FHANDLER->get('image_color'),
                     'width'  => $FHANDLER->get('image_width'),
                     'height' => $FHANDLER->get('image_height'));
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
                $MAPCFG1->readMapConfig(ONLY_GLOBAL);
            } catch(MapCfgInvalid $e) {
                continue;
            }

            $bg = $MAPCFG1->getValue(0, 'map_image');
            if(isset($bg) && $bg == $image)
                $using[] = $MAPCFG1->getName();
        }
        return $using;
    }
}
?>
