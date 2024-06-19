<?php
/*****************************************************************************
 *
 * ViewToNewMap.php - Class for rendering the "to new map" dialog
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

class ViewToNewMap
{
    private $error = null;

    public function parse($orig_name)
    {
        global $CORE;

        ob_start();

        $view_params = [];
        $params = ltrim(req('view_params'), '&');
        if ($params) {
            $parts = explode('&', $params);
            foreach ($parts as $part) {
                list($key, $val) = explode('=', $part);
                $view_params[$key] = $val;
            }
        }

        if (is_action()) {
            try {
                $name = post('name');
                if (!$name) {
                    throw new FieldInputError('name', l('Please provide a map name.'));
                }

                if (!preg_match(MATCH_MAP_NAME, $name)) {
                    throw new FieldInputError('name', l('This is not a valid map name (need to match [M])',
                        ['M' => MATCH_MAP_NAME]));
                }

                if (count($CORE->getAvailableMaps('/^' . $name . '$/')) > 0) {
                    throw new FieldInputError('name', l('A map with this name already exists.'));
                }

                if (!isset($view_params["worldmap_center"])) {
                    throw new FieldInputError('view_params', l('Please change your viewport before saving as new map.'));
                }

                if (!preg_match(MATCH_COORDS_MULTI, $view_params["worldmap_center"])) {
                    throw new FieldInputError('view_params', l('This is not a valid worldmap center'));
                }

                if (!isset($view_params["worldmap_zoom"])) {
                    throw new FieldInputError('view_params', l('Worldmap zoom parameter missing.'));
                }

                if (!preg_match(MATCH_INTEGER, $view_params["worldmap_zoom"])) {
                    throw new FieldInputError('view_params', l('This is not a valid worldmap zoom'));
                }

                // Read the old config
                $MAPCFG = new GlobalMapCfg($orig_name);
                $MAPCFG->readMapConfig();

                // Create a new map config
                $NEW = new GlobalMapCfg($name);
                $NEW->createMapConfig();
                foreach ($MAPCFG->getMapObjects() as $object_id => $cfg) {
                    $NEW->addElement($cfg['type'], $cfg, $perm = true, $object_id);
                }

                $NEW->setValue(0, "worldmap_center", $view_params["worldmap_center"]);
                $NEW->setValue(0, "worldmap_zoom", $view_params["worldmap_zoom"]);
                $NEW->storeUpdateElement(0);

                success(l('The map has been created.'));
                reload(cfg('paths', 'htmlbase') . '/frontend/nagvis-js/index.php?mod=Map&show=' . $name, 1);
            } catch (FieldInputError $e) {
                form_error($e->field, $e->msg);
            } catch (NagVisException $e) {
                form_error(null, $e->message());
            } catch (Exception $e) {
                if (isset($e->msg)) {
                    form_error(null, $e->msg);
                } else {
                    throw $e;
                }
            }
        }
        echo $this->error;

        echo '<div class="simple_form">' . N;
        js_form_start('to_new_map');
        input('name');
        submit(l('Save'));
        focus('name');

        // Keep the view parameters the users has set
        foreach ($view_params as $key => $val) {
            hidden($key, $val);
        }

        form_end();
        echo '</div>' . N;

        return ob_get_clean();
    }
}

