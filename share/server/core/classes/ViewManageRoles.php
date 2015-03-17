<?php
/*****************************************************************************
 *
 * ViewManageRoles.php - Dialog for managing roles and permissions
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

class ViewManageRoles {
    private $error = null;

    private function addForm() {
        global $AUTHORISATION;
        echo '<h2>'.l('Create Role').'</h2>';

        if (is_action() && post('mode') == 'create') {
            try {
                $name = post('name');
                if (!$name)
                    throw new FieldInputError('name', l('Please specify a name'));

                if (count($name) > AUTH_MAX_ROLENAME_LENGTH)
                    throw new FieldInputError('name', l('This name is too long'));

                if (!preg_match(MATCH_ROLE_NAME, $name))
                    throw new FieldInputError('name', l('Invalid value provided. Needs to match: [P].',
                                                                          array('P' => MATCH_ROLE_NAME)));

                if ($AUTHORISATION->checkRoleExists($name))
                    throw new FieldInputError('name', l('A role with this name already exists.'));

                if ($AUTHORISATION->createRole($name))
                    success(l('The role has been created.'));
                else
                    throw new NagVisException('Failed to create the role');
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

        js_form_start('create');
        hidden('mode', 'create');

        echo '<table class="mytable">';
        echo '<tr><td class="tdlabel">'.l('Name').'</td>';
        echo '<td class="tdfield">';
        input('name');
        echo '</td></tr>';
        echo '</table>';

        submit(l('Create'));
        form_end();
    }

    private function modifyForm() {
        global $AUTHORISATION;
        echo '<h2>'.l('Modify Role').'</h2>';

        $role_id = post('role_id');

        if (is_action() && post('mode') == 'edit') {
            try {
                if ($role_id === null || $role_id === '')
                    throw new FieldInputError('role_id', l('Please choose a role to delete.'));
                $role_id = intval($role_id);

                $found = false;
                foreach ($AUTHORISATION->getAllRoles() AS $role)
                    if ($role['roleId'] == $role_id)
                        $found = true;
                if (!$found)
                    throw new NagVisException('Invalid role id provided');

                // Load permissions from parameters
                $perms = Array();
                foreach (array_keys($_POST) AS $key) {
                    if(strpos($key, 'perm_') !== false) {
                        $key_parts = explode('_', $key);
                        $perm_id = $key_parts[1];
                        $perms[$perm_id] = get_checkbox($key);
                    }
                }

                scroll_up(); // On success, always scroll to top of page

                if ($AUTHORISATION->updateRolePerms($role_id, $perms))
                    success(l('The permissions for this role have been updated.'));
                else
                    throw new NagVisException(l('Problem while updating role permissions.'));
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
        echo '<tr><td class="tdlabel">'.l('Select Role').'</td>';
        echo '<td class="tdfield">';
        $choices = array('' => l('Please choose'));
        foreach ($AUTHORISATION->getAllRoles() AS $role)
            $choices[$role['roleId']] = $role['name'];
        select('role_id', $choices, '', 'updateForm(this.form)');
        echo '</td></tr>';
        echo '</table>';
        
        if ($role_id) {
            echo '<h3>'.l('Set Permissions').'</h3>';
            echo '<table>';
            echo '<tr>';
            echo '<th>'.l('Module').'</th>';
            echo '<th>'.l('Action').'</th>';
            echo '<th>'.l('Object').'</th>';
            echo '<th>'.l('Permitted').'</th>';
            echo '</tr>';
            $permitted = $AUTHORISATION->getRolePerms($role_id);
            foreach ($AUTHORISATION->getAllVisiblePerms() AS $perm) {
                unset($_REQUEST['perm_'.$perm['permId']]);
                echo '<tr>';
                echo '<td>'.$perm['mod'].'</td>';
                echo '<td>'.$perm['act'].'</td>';
                echo '<td>'.$perm['obj'].'</td>';
                echo '<td>';
                checkbox('perm_'.$perm['permId'], isset($permitted[$perm['permId']]));
                echo '</td></tr>';
            }
            echo '</table>';
        }

        submit(l('Save'));
        form_end();
    }

    private function deleteForm() {
        global $AUTHORISATION;
        echo '<h2>'.l('Delete Role').'</h2>';

        if (is_action() && post('mode') == 'delete') {
            try {
                $role_id = post('role_id');
                if ($role_id === null || $role_id === '')
                    throw new FieldInputError('role_id', l('Please choose a role to delete.'));
                $role_id = intval($role_id);

                // Check not to delete any referenced role
                $used_by = $AUTHORISATION->roleUsedBy($role_id);
                if(count($used_by) > 0)
                    throw new NagVisException(l('Not deleting this role, the role is in use by the users [U].',
                                            array('U' => implode(', ', $used_by))));

                if ($AUTHORISATION->deleteRole($role_id))
                    success(l('The role has been deleted.'));
                else
                    throw new Success(l('Problem while deleting the role.'));
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
        echo '<tr><td class="tdlabel">'.l('Name').'</td>';
        echo '<td class="tdfield">';
        $choices = array('' => l('Please choose'));
        foreach ($AUTHORISATION->getAllRoles() AS $role)
            $choices[$role['roleId']] = $role['name'];
        select('role_id', $choices);
        echo '</td></tr>';

        echo '</table>';

        submit(l('Delete'));
        form_end();
    }

    public function parse() {
        global $AUTHORISATION;
        ob_start();

        // Delete permissions, which are not needed anymore when opening the
        // "manage roles" dialog. This could be done during usual page
        // processing, but would add overhead which is not really needed.
        $AUTHORISATION->cleanupPermissions();

        $this->addForm();
        $this->modifyForm();
        $this->deleteForm();
        return ob_get_clean();
    }
}
?>
