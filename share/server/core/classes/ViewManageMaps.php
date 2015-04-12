<?php
/*****************************************************************************
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

class ViewManageMaps {
    private $error = null;

    private function createForm() {
        global $CORE;
        echo '<h2>'.l('Create Map').'</h2>';

        $map_types = array(
            'map'     => l('Regular map'),
            'geomap'  => l('Geographical map'),
            'automap' => l('Automap based on parent/child relations'),
            'dynmap'  => l('Dynamic map'),
        );

        if (is_action() && post('mode') == 'create') {
            try {
                $name = post('name');
                if (!$name)
                    throw new FieldInputError('name', l('You need to provide a unique map name (ID) for the map'));

                if (count($CORE->getAvailableMaps('/^'.preg_quote($name).'$/')) > 0)
                    throw new FieldInputError('name', l('A map with this name already exists'));
            
                if (!preg_match(MATCH_MAP_NAME, $name))
                    throw new FieldInputError('name', l('This is not a valid map name (need to match [M])',
                                                                    array('M' => MATCH_MAP_NAME)));

                $type = post('type');
                if (!isset($map_types[$type]))
                    throw new FieldInputError('type', l('You provided an invalid type'));

                $alias = post('alias');
                if ($alias && !preg_match(MATCH_STRING, $alias))
                    throw new FieldInputError('alias', l('This is not a valid map alias (need to match [M])',
                                                                    array('M' => MATCH_STRING)));

                $MAPCFG = new GlobalMapCfg($name);
                if (!$MAPCFG->createMapConfig())
                    throw new NagVisException(l('Failed to create the map'));

                $global = array();

                if ($alias)
                    $global['alias'] = $alias;

                if ($type != 'map')
                    $global['sources'] = array($type);

                $MAPCFG->addElement('global', $global, true, 0);

                success(l('The map has been created. Changing to the new map...'));
                reload('index.php?mod=Map&act=view&show='.$name, 1);
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

        js_form_start('create_map');
        hidden('mode', 'create');

        echo '<table class="mytable">';
        echo '<tr><td class="tdlabel">'.l('ID (Internal Name)').'</td>';
        echo '<td class="tdfield">';
        input('name');
        echo '</td></tr>';

        echo '<tr><td class="tdlabel">'.l('Alias').'</td>';
        echo '<td class="tdfield">';
        input('alias');
        echo '</td></tr>';

        echo '<tr><td class="tdlabel">'.l('Map Type').'</td>';
        echo '<td class="tdfield">';
        select('type', $map_types);
        echo '</td></tr>';

        echo '</table>';

        submit(l('Create'));
        form_end();
    }

    private function doRename($name, $new_name) {
        global $CORE, $AUTHORISATION;
        $files = Array();

        // loop all map configs to replace mapname in all map configs
        foreach($CORE->getAvailableMaps() as $mapName) {
            try {
                $MAPCFG1 = new GlobalMapCfg($mapName);
                $MAPCFG1->readMapConfig();

                $i = 0;
                // loop definitions of type map
                foreach($MAPCFG1->getDefinitions('map') AS $key => $obj) {
                    // check if old map name is linked...
                    if($obj['map_name'] == $name) {
                        $MAPCFG1->setValue('map', $i, 'map_name', $new_name);
                        $MAPCFG1->writeElement('map',$i);
                    }
                    $i++;
                }
            } catch(Exception $e) {
                // Do nothing. Siletly pass config errors here...
            }
        }

        // And also remove the permission
        $AUTHORISATION->renameMapPermissions($name, $new_name);

        // rename config file
        rename(cfg('paths', 'mapcfg').$name.'.cfg',
               cfg('paths', 'mapcfg').$new_name.'.cfg');
    }

    private function renameForm() {
        global $CORE;
        echo '<h2>'.l('Rename Map').'</h2>';

        if (is_action() && post('mode') == 'rename') {
            try {
                $name = post('name');
                if (!$name)
                    throw new FieldInputError('name', l('Please choose a map'));

                if (count($CORE->getAvailableMaps('/^'.preg_quote($name).'$/')) == 0)
                    throw new FieldInputError('name', l('The given map name is invalid'));
            
                $new_name = post('new_name');
                if (!$new_name)
                    throw new FieldInputError('new_name', l('Please provide a new name'));

                if (count($CORE->getAvailableMaps('/^'.preg_quote($new_name).'$/')) > 0)
                    throw new FieldInputError('new_name', l('A map with this name already exists'));
            
                if (!preg_match(MATCH_MAP_NAME, $new_name))
                    throw new FieldInputError('new_name', l('This is not a valid map name (need to match [M])',
                                                                    array('M' => MATCH_MAP_NAME)));

                $this->doRename($name, $new_name);
                success(l('The map has been renamed.'));

                $cur_map = post('cur_map');
                if ($cur_map && $cur_map == $name)
                    reload('index.php?mod=Map&act=view&show='.$new_name, 1);
                else
                    reload(null, 1);
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

        js_form_start('rename_map');
        hidden('mode', 'rename');

        // eventual currently open map, needed for redirecting the user if the
        // renamed map is currently open
        hidden('cur_map', '');
        js('document.getElementById(\'cur_map\').value = oPageProperties.map_name;');

        echo '<table class="mytable">';
        echo '<tr><td class="tdlabel">'.l('Map').'</td>';
        echo '<td class="tdfield">';
        $maps = array('' => l('Choose a map'));
        foreach ($CORE->getAvailableMaps() AS $map)
            $maps[$map] = $map;
        select('name', $maps);
        echo '</td></tr>';

        echo '<tr><td class="tdlabel">'.l('New name').'</td>';
        echo '<td class="tdfield">';
        input('new_name');
        echo '</td></tr>';

        echo '</table>';

        submit(l('Rename'));
        form_end();
    }

    private function deleteForm() {
        global $CORE;
        echo '<h2>'.l('Delete Map').'</h2>';

        if (is_action() && post('mode') == 'delete') {
            try {
                $name = post('name');
                if (!$name)
                    throw new FieldInputError('name', l('Please choose a map'));

                if (count($CORE->getAvailableMaps('/^'.preg_quote($name).'$/')) == 0)
                    throw new FieldInputError('name', l('The given map name is invalid'));
            
                $MAPCFG = new GlobalMapCfg($name);
                try {
                    $MAPCFG->readMapConfig();
                } catch(MapCfgInvalid $e) {}
                $MAPCFG->deleteMapConfig();

                success(l('The map has been deleted.'));

                $cur_map = post('current_map');
                if ($cur_map && $cur_map == $name)
                    reload('index.php', 1); // change to overview page when current map has been deleted
                else
                    reload(null, 1);
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

        js_form_start('delete_map');
        hidden('mode', 'delete');

        // eventual currently open map, needed for redirecting the user if the
        // renamed map is currently open
        hidden('current_map', '');
        js('document.getElementById(\'current_map\').value = oPageProperties.map_name;');

        echo '<table class="mytable">';
        echo '<tr><td class="tdlabel">'.l('Map').'</td>';
        echo '<td class="tdfield">';
        $maps = array('' => l('Choose a map'));
        foreach ($CORE->getAvailableMaps() AS $map)
            $maps[$map] = $map;
        select('name', $maps);
        echo '</td></tr>';

        echo '</table>';

        submit(l('Delete'));
        form_end();
    }

    private function exportForm() {
        global $CORE;
        echo '<h2>'.l('Export Map').'</h2>';

        if (is_action() && post('mode') == 'export') {
            try {
                $name = post('map');
                if (!$name)
                    throw new FieldInputError('map', l('Please choose a map'));

                if (count($CORE->getAvailableMaps('/^'.preg_quote($name).'$/')) == 0)
                    throw new FieldInputError('map', l('The given map name is invalid'));

                reload('../../server/core/ajax_handler.php?mod=Map&act=doExportMap&show='.$name, 1);
                success(l('The map configuration has been exported.'));
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

        js_form_start('export_map');
        hidden('mode', 'export');

        echo '<table class="mytable">';
        echo '<tr><td class="tdlabel">'.l('Map').'</td>';
        echo '<td class="tdfield">';
        $maps = array('' => l('Choose a map'));
        foreach ($CORE->getAvailableMaps() AS $map)
            $maps[$map] = $map;
        select('map', $maps);
        echo '</td></tr>';

        echo '</table>';

        submit(l('Export'));
        form_end();
    }

    private function importForm() {
        global $CORE;
        echo '<h2>'.l('Import Map').'</h2>';

        if (is_action() && post('mode') == 'import') {
            try {
                if (!isset($_FILES['map_file']))
                    throw new FieldInputError('map_file', l('You need to select a file to import.'));

                $file = $_FILES['map_file'];
                if (!is_uploaded_file($file['tmp_name']))
                    throw new FieldInputError('map_file', l('The file could not be uploaded (Error: [ERROR]).',
                      Array('ERROR' => $file['error'].': '.$CORE->getUploadErrorMsg($file['error']))));

                $file_name = $file['name'];
                $file_path = cfg('paths', 'mapcfg').$file_name;
                $map_name  = substr($file_name, 0, -4);

                if (!preg_match(MATCH_CFG_FILE, $file_name))
                    throw new FieldInputError('map_file', l('The uploaded file is no map configuration file.'));

                if (!preg_match(MATCH_MAP_NAME, $map_name))
                    throw new FieldInputError('map_file', l('This is not a valid map name (need to match [M])',
                                                                    array('M' => MATCH_MAP_NAME)));

                // FIXME: We really should validate the contents of the file

                move_uploaded_file($file['tmp_name'], $file_path);
                $CORE->setPerms($file_path);

                success(l('The map has been imported. Changing to the new map...'));
                reload('index.php?mod=Map&act=view&show='.$map_name, 1);
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

        js_form_start('import_map');
        hidden('mode', 'import');
        echo '<input type="hidden" id="MAX_FILE_SIZE" name="MAX_FILE_SIZE" value="1000000" />';

        echo '<table class="mytable">';
        echo '<tr><td class="tdlabel">'.l('Map file').'</td>';
        echo '<td class="tdfield">';
        upload('map_file');
        echo '</td></tr>';
        echo '</table>';

        submit(l('Import'));
        form_end();
    }

    public function parse() {
        global $CORE;
        ob_start();

        $this->createForm();
        $this->renameForm();
        $this->deleteForm();
        $this->exportForm();
        $this->importForm();

        return ob_get_clean();
    }
}
?>
