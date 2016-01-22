<?php
/*****************************************************************************
 *
 * ViewManageBackends.php - View to render manage backends page
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

class ViewManageBackends {
    private $error = null;

    public function __construct() {
        global $CORE;
        $this->editable_backends  = $CORE->getDefinedBackends(ONLY_USERCFG);
        $this->defined_backends   = $CORE->getDefinedBackends();
        $this->available_backends = $CORE->getAvailableBackends();
    }

    private function backend_attributes($type) {
        global $_MAINCFG;
        // Loop all options for this backend type
        $backend_opts = $_MAINCFG->getValidObjectType('backend');

        // Merge global backend options with type specific options
        $opts = $backend_opts['options'][$type];
        foreach ($backend_opts AS $key => $opt)
            if ($key !== 'backendid' && $key !== 'options')
                $opts[$key] = $opt;
        return $opts;
    }

    private function backend_options($backend_id) {
        $ret = Array();
        $backend_type = cfg('backend_'.$backend_id, 'backendtype');

        foreach ($this->backend_attributes($backend_type) AS $key => $opt)
            if (cfg('backend_'.$backend_id, $key, true) !== false)
                $ret[$key] = cfg('backend_'.$backend_id, $key, true);
            else
                $ret[$key] = '';

        return $ret;
    }

    private function defaultForm() {
        global $CORE;
        echo '<h2>'.l('Default Backend').'</h2>';

        if (is_action() && post('mode') == 'default') {
            try {
                $default = post('default');
                if (!$default || !isset($this->defined_backends[$default]))
                    throw new FieldInputError('default', l('You need to choose a default backend.'));

                $CORE->getUserMainCfg()->setValue('defaults', 'backend', $default);
                $CORE->getUserMainCfg()->writeConfig();

                success(l('The default backend has been changed.'));
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

        js_form_start('default');
        hidden('mode', 'default');
        echo '<table name="mytable" class="mytable">';
        echo '<tr><td class="tdlabel">'.l('Default Backend').'</td>';
        echo '<td class="tdfield">';
        $backends = array('' => l('Please choose'));
        foreach ($this->defined_backends AS $backend)
            $backends[$backend] = $backend;
        $default_backends = cfg('defaults', 'backend', true);
        select('default', $backends, $default_backends[0]);
        echo '</td></tr>';
        echo '</table>';
        submit(l('Save'));
        form_end();

        if ($this->editable_backends != $this->defined_backends)
            echo '<p>'.l('Some backends are not editable by using the web gui. They can only be '
                         .'configured by modifying the file in the NagVis conf.d directory.').'</p>';
    }

    private function editForm($mode = 'add') {
        global $CORE;

        if ($mode == 'add')
            echo '<h2>'.l('Add Backend').'</h2>';
        else
            echo '<h2>'.l('Edit Backend').'</h2>';

        $backend_type = submitted($mode) ? post('backend_type') : null;
        $backend_id   = submitted($mode) ? post('backend_id') : null;
        if (is_action() && post('mode') == $mode) {
            try {
                if ($mode == 'add' && (!$backend_type || !in_array($backend_type, $this->available_backends)))
                    throw new FieldInputError('backend_type', l('You need to choose a backend type.'));

                if (!$backend_id || !preg_match(MATCH_BACKEND_ID, $backend_id))
                    throw new FieldInputError('backend_id', l('You need to specify a identifier for the backend.'));

                if ($mode == 'add' && isset($this->defined_backends[$backend_id]))
                    throw new FieldInputError('backend_id', l('This ID is already used by another backend.'));
                elseif ($mode == 'edit' && !isset($this->editable_backends[$backend_id]))
                    throw new FieldInputError('backend_id', l('The choosen backend does not exist.'));

                if ($mode == 'add') {
                    // Set standard values
                    $CORE->getUserMainCfg()->setSection('backend_'.$backend_id);
                    $CORE->getUserMainCfg()->setValue('backend_'.$backend_id, 'backendtype', $backend_type);
                } else {
                    $backend_type = cfg('backend_'.$backend_id, 'backendtype');
                }

                $found_option = false;
                foreach ($this->backend_attributes($backend_type) as $key => $opt) {
                    if ($key == 'backendtype')
                        continue;

                    // If there is a value for this option, set it
                    $val = post($key);
                    if ($opt['must'] && !$val)
                        throw new FieldInputError($key, l('You need to configure this option.'));

                    elseif ($val != null) {
                        if (!preg_match($opt['match'], $val))
                            throw new FieldInputError($key, l('Invalid value provided. Needs to match: [P].',
                                                                            array('P' => $opt['match'])));
                        $CORE->getUserMainCfg()->setValue('backend_'.$backend_id, $key, $val);
                    }
                }

                // Persist the changes
                $CORE->getUserMainCfg()->writeConfig();

                if ($mode == 'add')
                    success(l('The new backend has been added.'));
                else
                    success(l('The changes have been saved.'));
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

        js_form_start($mode);
        hidden('mode', $mode);
        echo '<table name="mytable" class="mytable">';
        if ($mode == 'add') {
            echo '<tr><td class="tdlabel">'.l('Backend ID').'</td>';
            echo '<td class="tdfield">';
            input('backend_id');
            echo '</td></tr>';
            echo '<tr><td class="tdlabel">'.l('Backend Type').'</td>';
            echo '<td class="tdfield">';
            $choices = array('' => l('Please choose'));
            foreach ($this->available_backends as $choice)
                $choices[$choice] = $choice;
            select('backend_type', $choices, '', 'updateForm(this.form)');
        } else {
            echo '<tr><td class="tdlabel">'.l('Backend ID').'</td>';
            echo '<td class="tdfield">';
            $choices = array('' => l('Please choose'));
            foreach ($this->editable_backends as $choice)
                $choices[$choice] = $choice;
            select('backend_id', $choices, '', 'updateForm(this.form)');
            echo '</td></tr>';
        }

        if (($mode == 'add' && $backend_type) || ($mode == 'edit' && $backend_id)) {
            if ($mode == 'edit') {
                $opts = $this->backend_options($backend_id);
                $backend_type = $opts['backendtype'];
            } else {
                $opts = array();
            }

            foreach ($this->backend_attributes($backend_type) as $key => $opt) {
                if ($key == 'backendtype')
                    continue;
                $val = isset($opts[$key]) ? $opts[$key] : null;
                echo '<tr><td class="tdlabel">'.$key.'</td>';
                // FIXME: Add checkbox for selecting the option, show default values
                echo '<td class="tdfield">';
                input($key, $val);
                echo '</td></tr>';
            }
        }

        echo '</td></tr>';
        echo '</table>';
        submit(l('Save'));
        form_end();
    }

    private function delForm() {
        global $CORE;
        echo '<h2>'.l('Delete Backend').'</h2>';

        if (is_action() && post('mode') == 'delete') {
            try {
                $backend_id = post('backend_id');
                if (!isset($this->editable_backends[$backend_id]))
                    throw new FieldInputError('backend_id', l('The choosen backend does not exist.'));

                // FIXME: Check whether or not the backend is used anywhere

                $CORE->getUserMainCfg()->delSection('backend_'.$backend_id);
                $CORE->getUserMainCfg()->writeConfig();

                success(l('The backend has been deleted.'));
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
        echo '<tr><td class="tdlabel">'.l('Backend ID').'</td>';
        echo '<td class="tdfield">';
        $choices = array('' => l('Please choose'));
        foreach ($this->editable_backends AS $choice)
            $choices[$choice] = $choice;
        select('backend_id', $choices);
        echo '</td></tr>';

        echo '</table>';

        submit(l('Delete'));
        form_end();
    }

    public function parse() {
        ob_start();
        $this->defaultForm();
        $this->editForm('add');
        $this->editForm('edit');
        $this->delForm();
        return ob_get_clean();
    }
}
?>
