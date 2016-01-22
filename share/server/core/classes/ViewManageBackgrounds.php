<?php
/*****************************************************************************
 *
 * ViewManageBackgrounds.php - View to render manage backgrounds page
 *
 * Copyright (c) 2004-2016 NagVis Project (Contact: info@nagvis.org)
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

class ViewManageBackgrounds {
    private $error = null;

    private function uploadForm() {
        global $CORE;
        echo '<h2>'.l('Upload Background Image').'</h2>';

        if (is_action() && post('mode') == 'upload') {
            try {
                if (!isset($_FILES['image']))
                    throw new FieldInputError('image', l('You need to select an image to import.'));

                $file = $_FILES['image'];
                if (!is_uploaded_file($file['tmp_name']))
                    throw new FieldInputError('image', l('The file could not be uploaded (Error: [ERROR]).',
                      Array('ERROR' => $file['error'].': '.$CORE->getUploadErrorMsg($file['error']))));

                $file_name = $file['name'];
                $file_path = path('sys', '', 'backgrounds').$file_name;

                if (!preg_match(MATCH_PNG_GIF_JPG_FILE, $file_name))
                    throw new FieldInputError('image', l('The uploaded file is no image (png,jpg,gif) '
                                                        .'file or contains unwanted chars.'));

                $data = getimagesize($file['tmp_name']);
                if (!in_array($data[2], array(IMAGETYPE_GIF , IMAGETYPE_JPEG ,IMAGETYPE_PNG)))
                    throw new FieldInputError('image', l('The uploaded file is not an image '
                                                        .'(png, jpg and gif are allowed).'));

                move_uploaded_file($file['tmp_name'], $file_path);
                $CORE->setPerms($file_path);

                success(l('The background has been uploaded.'));
                //reload(null, 1);
            } catch (FieldInputError $e) {
                form_error($e->field, $e->msg);
            } catch (Exception $e) {
                if (isset($e->msg))
                    form_error(null, $e->msg);
                else
                    throw $e;
            }
        }
        echo $this->error;

        js_form_start('upload_background');
        hidden('mode', 'upload');
        echo '<input type="hidden" id="MAX_FILE_SIZE" name="MAX_FILE_SIZE" value="3000000" />';

        echo '<table class="mytable">';
        echo '<tr><td class="tdlabel">'.l('Choose an Image').'</td>';
        echo '<td class="tdfield">';
        upload('image');
        echo '</td></tr>';
        echo '</table>';

        submit(l('Upload'));
        form_end();
    }

    private function deleteForm() {
        global $CORE;
        echo '<h2>'.l('Delete Background').'</h2>';

        if (is_action() && post('mode') == 'delete') {
            try {
                $name = post('name');
                if (!$name)
                    throw new FieldInputError('name', l('Please choose a background'));

                if (count($CORE->getAvailableBackgroundImages('/^'.preg_quote($name).'$/')) == 0)
                    throw new FieldInputError('name', l('The background does not exist.'));

                // Check whether or not the backgroun is in use
                $using = Array();
                foreach ($CORE->getAvailableMaps() AS $map) {
                    $MAPCFG = new GlobalMapCfg($map);
                    try {
                        $MAPCFG->readMapConfig(ONLY_GLOBAL);
                    } catch (Exception $e) {
                        continue; // don't fail on broken map configs
                    }

                    $bg = $MAPCFG->getValue(0, 'map_image');
                    if (isset($bg) && $bg == $name)
                        $using[] = $MAPCFG->getName();
                }
                if ($using)
                    throw new FieldInputError('name', l('Unable to delete this background, because it is '
                                                       .'currently used by these maps: [M].',
                                                            array('M' => implode(',', $using))));

                $BACKGROUND = new GlobalBackground($name);
                $BACKGROUND->deleteImage();

                success(l('The background has been deleted.'));
                //reload(null, 1);
            } catch (FieldInputError $e) {
                form_error($e->field, $e->msg);
            } catch (Exception $e) {
                if (isset($e->msg))
                    form_error(null, $e->msg);
                else
                    throw $e;
            }
        }
        echo $this->error;

        js_form_start('delete');
        hidden('mode', 'delete');

        echo '<table class="mytable">';
        echo '<tr><td class="tdlabel">'.l('Background').'</td>';
        echo '<td class="tdfield">';
        $images = array('' => l('Choose a background'));
        foreach ($CORE->getAvailableBackgroundImages() AS $name)
            $images[$name] = $name;
        select('name', $images);
        echo '</td></tr>';

        echo '</table>';

        submit(l('Delete'));
        form_end();
    }

    public function parse() {
        ob_start();
        $this->uploadForm();
        $this->deleteForm();
        return ob_get_clean();
    }
}
?>
