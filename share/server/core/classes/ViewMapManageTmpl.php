<?php
/*****************************************************************************
 *
 * ViewMapManageTmpl.php - Class to render the map template management page
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

class ViewMapManageTmpl {
    private $error = null;

    private function createForm() {
        global $CORE;
        echo '<h2>'.l('Create Template').'</h2>';

        if (submitted('create'))
            $num_options = post('num_options', 1);
        else
            $num_options = 1;

        $map_name = req('show');
        if (!$map_name)
            throw new FieldInputError(null, l('Map name missing'));

        if (count($CORE->getAvailableMaps('/^'.preg_quote($map_name).'$/')) == 0)
            throw new FieldInputError(null, l('A map with this name does not exists'));

        $MAPCFG = new GlobalMapCfg($map_name);
        $MAPCFG->readMapConfig(!ONLY_GLOBAL, false, false);

        if (is_action() && post('mode') == 'create') {
            try {
                $name = post('name');
                if (!preg_match(MATCH_STRING_NO_SPACE, $name))
                    throw new FieldInputError('name', l('You need to configure an unique name.'));

                // Check if the template already exists
                // Read map config but don't resolve templates and don't use the cache
                if (count($MAPCFG->getTemplateNames('/^'.$name.'$/')) > 0)
                    throw new FieldInputError('name', l('A template with this name already exists.'));

                // Get all options from the POST vars
                $options = array('name' => $name);
                for ($i = 0; $i < $num_options; $i++) {
                    $key = post('key_'.$i);
                    $val = post('val_'.$i);
                    if ($key !== '' && $val !== '')
                        $options[$key] = $val;
                }

                // append a new object definition to the map configuration
                $MAPCFG->addElement('template', $options, true);
                success(l('The template has been created.'));
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

        js_form_start('create');
        hidden('mode', 'create');
        hidden('num_options', $num_options);

        echo '<table class="mytable">';
        echo '<tr><td class="tdlabel">'.l('Name').'</td>';
        echo '<td class="tdfield">';
        input('name');
        echo '</td></tr>';

        if (post('mode') == 'create' && post('_add_option'))
            $num_options += 1;

        echo '<tr><td>'.l('Key').'</td><td>'.l('Value').'</td></tr>';
        for ($i = 0; $i < $num_options; $i++) {
            echo '<tr><td class="tdlabel">';
            input('key_'.$i);
            echo '</td><td class="tdfield">';
            input('val_'.$i);
            echo '</td></tr>';
        }

        echo '<tr><td></td><td class="tdfield">';
        button('_add_option', l('Add Option'), 'addOption(this.form)');
        echo '</td></tr>';

        echo '</table>';

        submit(l('Create'));
        form_end();
    }

    private function editForm() {
        global $CORE;
        echo '<h2>'.l('Modify Template').'</h2>';

        $map_name = req('show');
        if (!$map_name)
            throw new NagVisException(l('Map name missing'));

        if (count($CORE->getAvailableMaps('/^'.preg_quote($map_name).'$/')) == 0)
            throw new NagVisException(l('A map with this name does not exists'));

        $MAPCFG = new GlobalMapCfg($map_name);
        $MAPCFG->readMapConfig(!ONLY_GLOBAL, false, false);

        $name = submitted('edit') ? post('name') : null;

        // Check if the template exists
        // Read map config but don't resolve templates and don't use the cache
        if ($name) {
            if (count($MAPCFG->getTemplateNames('/^'.$name.'$/')) == 0)
                throw new FieldInputError('name', l('A template with this name does not exist.'));

            $templates   = $MAPCFG->getDefinitions('template');
            $obj_id      = $MAPCFG->getTemplateIdByName($name);
            $options = array();
            foreach ($templates[$obj_id] as $key => $val)
                if ($key != 'type' && $key != 'object_id' && $key != 'name')
                    $options[$key] = $val;
            $num_options = max(post('num_options'), count($options));
        }

        if (is_action() && post('mode') == 'edit') {
            try {
                // Get all options from the POST vars
                $save_options = array('name' => $name);
                $options = array();
                for ($i = 0; $i < $num_options; $i++) {
                    $key = post('key_'.$i);
                    $val = post('val_'.$i);
                    if ($key !== '' && $val !== '') {
                        $save_options[$key] = $val;
                        $options[$key] = $val;
                    }
                }

                $MAPCFG->updateElement($obj_id, $save_options, true);
                success(l('The template has been modified.'));
            } catch (FieldInputError $e) {
                form_error($e->field, $e->msg);
            } catch (NagVisException $e) {
                form_error(null, $e->message());
            } catch (Exception $e) {
                if (isset($e->msg))
                    form_error(null, $e->msg);
                else
                    throw $e;
            }
        }
        echo $this->error;

        js_form_start('edit');
        hidden('mode', 'edit');

        echo '<table class="mytable">';
        echo '<tr><td class="tdlabel">'.l('Name').'</td>';
        echo '<td class="tdfield">';
        $choices = array('' => l('Please choose'));
        foreach (array_keys($MAPCFG->getTemplateNames()) AS $tmpl_name)
            $choices[$tmpl_name] = $tmpl_name;
        select('name', $choices, '', 'updateForm(this.form)');
        echo '</td></tr>';

        if ($name) {
            hidden('num_options', $num_options);
            if (post('mode') == 'edit' && post('_add_option'))
                $num_options += 1;

            echo '<tr><td>'.l('Key').'</td><td>'.l('Value').'</td></tr>';
            for ($i = 0; $i < $num_options; $i++) {
                if ($i < count($options)) {
                    $keys = array_keys($options);
                    $_POST['key_'.$i] = $keys[$i];
                    $values = array_values($options);
                    $_POST['val_'.$i] = $values[$i];
                } else {
                    unset($_POST['key_'.$i]);
                    unset($_POST['val_'.$i]);
                }
                echo '<tr><td class="tdlabel">';
                input('key_'.$i);
                echo '</td><td class="tdfield">';
                input('val_'.$i);
                echo '</td></tr>';
            }

            echo '<tr><td></td><td class="tdfield">';
            button('_add_option', l('Add Option'), 'addOption(this.form)');
            echo '</td></tr>';
        }

        echo '</table>';

        submit(l('Modify'));
        form_end();
    }

    private function deleteForm() {
        global $CORE;
        echo '<h2>'.l('Delete Template').'</h2>';

        $map_name = req('show');
        if (!$map_name)
            throw new NagVisException(l('Map name missing'));

        if (count($CORE->getAvailableMaps('/^'.preg_quote($map_name).'$/')) == 0)
            throw new NagVisException(l('A map with this name does not exists'));

        $MAPCFG = new GlobalMapCfg($map_name);
        $MAPCFG->readMapConfig(!ONLY_GLOBAL, false, false);

        if (is_action() && post('mode') == 'delete') {
            try {
                $name = post('name');
                if (count($MAPCFG->getTemplateNames('/^'.$name.'$/')) == 0)
                    throw new FieldInputError('name', l('A template with this name does not exist.'));

                $obj_id = $MAPCFG->getTemplateIdByName($name);
                $MAPCFG->deleteElement($obj_id, true);
                success(l('The template has been deleted.'));
            } catch (FieldInputError $e) {
                form_error($e->field, $e->msg);
            } catch (NagVisException $e) {
                form_error(null, $e->message());
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
        echo '<tr><td class="tdlabel">'.l('Name').'</td>';
        echo '<td class="tdfield">';
        $choices = array('' => l('Please choose'));
        foreach (array_keys($MAPCFG->getTemplateNames()) AS $tmpl_name)
            $choices[$tmpl_name] = $tmpl_name;
        select('name', $choices, '', 'updateForm(this.form)');
        echo '</td></tr>';
        echo '</table>';

        submit(l('Delete'));
        form_end();
    }

    public function parse() {
        ob_start();
        $this->createForm();
        $this->editForm();
        $this->deleteForm();
        return ob_get_clean();
    }
}

?>
