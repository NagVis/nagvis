<?php
/*****************************************************************************
 *
 * ViewUserMgmt.php - User management dialog
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

class ViewUserMgmt {
    private $error = null;

    private function addForm() {
        global $AUTH;
        echo '<h2>'.l('Create User').'</h2>';

        if (is_action() && post('mode') == 'create') {
            try {
                $name = post('name');
                if (!$name)
                    throw new FieldInputError('name', l('Please specify a name.'));

                if (count($name) > AUTH_MAX_USERNAME_LENGTH)
                    throw new FieldInputError('name', l('This name is too long.'));

                if (!preg_match(MATCH_USER_NAME, $name))
                    throw new FieldInputError('name', l('Invalid value provided. Needs to match: [P].',
                                                                          array('P' => MATCH_USER_NAME)));

                if ($AUTH->checkUserExists($name))
                    throw new FieldInputError('name', l('A user with this name already exists.'));

                $password1 = post('password1');
                if (!$password1)
                    throw new FieldInputError('password1', l('Please specify a password.'));

                if (count($password1) > AUTH_MAX_PASSWORD_LENGTH)
                    throw new FieldInputError('password1', l('This password is too long.'));

                $password2 = post('password2');
                if (!$password2)
                    throw new FieldInputError('password2', l('Please confirm your password.'));

                if ($password1 != $password2)
                    throw new FieldInputError('password2', l('The two passwords are not equal.'));

                if ($AUTH->createUser($name, $password1))
                    success(l('The user has been created.'));
                else
                    throw new NagVisException('Failed to create the user.');
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
        echo '<tr><td class="tdlabel">'.l('Username').'</td>';
        echo '<td class="tdfield">';
        input('name');
        echo '</td></tr>';
        echo '<tr><td class="tdlabel">'.l('Password').'</td>';
        echo '<td class="tdfield">';
        input('password1');
        echo '</td></tr>';
        echo '<tr><td class="tdlabel">'.l('Password Confirm').'</td>';
        echo '<td class="tdfield">';
        input('password2');
        echo '</td></tr>';
        echo '</table>';

        submit(l('Create'));
        form_end();
    }

    private function editForm() {
        global $AUTH, $AUTHORISATION;
        if (!$AUTHORISATION->rolesConfigurable())
            return;
        echo '<h2>'.l('Modify User').'</h2>';

        $user_id = post('user_id');

        if (is_action() && post('mode') == 'edit') {
            try {
                if ($user_id === null || $user_id === '')
                    throw new FieldInputError('user_id', l('Please choose a user to edit.'));
                if (!is_numeric($user_id))
                    throw new FieldInputError('user_id', l('Invalid value provided.'));
                $user_id = intval($user_id);

                $roles = explode(',', post('user_roles'));
                if ($AUTHORISATION->updateUserRoles($user_id, $roles))
                    success(l('The roles for this user have been updated.'));
                else
                    throw new NagVisException(l('Problem while updating user roles.'));
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
        echo '<tr><td class="tdlabel">'.l('Select User').'</td>';
        echo '<td class="tdfield">';
        $choices = array('' => l('Please choose'));
        foreach ($AUTH->getAllUsers() AS $user)
            $choices[$user['userId']] = $user['name'];
        select('user_id', $choices, '', 'updateForm(this.form)');
        echo '</td></tr>';
        echo '</table>';

        if ($user_id) {
            $user_roles = array();
            foreach ($AUTHORISATION->getUserRoles($user_id) as $role)
                $user_roles[$role['roleId']] = $role['name'];
                
            $available_roles = array();
            foreach ($AUTHORISATION->getAllRoles() AS $role)
                if (!isset($user_roles[$role['roleId']]))
                    $available_roles[$role['roleId']] = $role['name'];

            hidden('user_roles', implode(',', array_keys($user_roles)));
            echo '<table class="mytable">';
            echo '<tr><td>'.l('Available Roles').'</td>';
            echo '<td style="width:30px"></td>';
            echo '<td>'.l('Selected Roles').'</td></tr>';
            echo '<tr><td>';
            select('roles_available', $available_roles, '', '', 'width:100%', 5);
            echo '</td><td style="vertical-align:middle">';
            button('add', '&gt;', 'updateUserRoles(true)');
            button('remove', '&lt;', 'updateUserRoles()');
            echo '</td><td>';
            select('roles_selected', $user_roles, '', '', 'width:100%', 5);
            echo '</td></tr></table>';
        }

        submit(l('Save'));
        form_end();
    }

    private function deleteForm() {
        global $AUTH, $AUTHORISATION;
        echo '<h2>'.l('Delete User').'</h2>';

        if (is_action() && post('mode') == 'delete') {
            try {
                $user_id = post('user_id');
                if ($user_id === null || $user_id === '')
                    throw new FieldInputError('user_id', l('Please choose a user to delete.'));
                if (!is_numeric($user_id))
                    throw new FieldInputError('user_id', l('Invalid value provided.'));
                $user_id = intval($user_id);

                // Don't delete own user
                if ($AUTH->getUserId() == $user_id)
                    throw new FieldInputError('user_id', l('Unable to delete your own user.'));

                if ($AUTHORISATION->deleteUser($user_id))
                    success(l('The user has been deleted.'));
                else
                    throw new NagVisException(l('Problem while deleting the user.'));
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
        foreach ($AUTH->getAllUsers() AS $user)
            $choices[$user['userId']] = $user['name'];
        select('user_id', $choices);
        echo '</td></tr>';
        echo '</table>';

        submit(l('Delete'));
        form_end();
    }

    public function parse() {
        ob_start();
        $this->addForm();
        $this->editForm();
        $this->deleteForm();
        return ob_get_clean();
    }
}

    /*
        $aData = Array(
            'langUserPwReset'    => l('Reset Password'),
            // Supported by backend and not using trusted auth
            'supportedChangePassword' => $AUTH->checkFeature('changePassword') && !$AUTH->authedTrusted()
        );

/*

{if $supportedChangePassword}<div id="userPwReset">
    <h2>{$langUserPwReset}</h2>
    <form name="userPwReset" id="userPwResetForm" action="#" onsubmit="submitFrontendForm('{$formTargetPwReset}', 'userPwResetForm', true);return false" method="post">
        <table class="mytable">
            <tr>
                <td class="tdlabel">{$langSelectUser}</td>
                <td class="tdfield">
                <select type="text" name="userId" id="userId" tabindex="130" required />
                    <option value="" selected="selected"></option>
                    {foreach $users user}<option value="{$user.userId}">{$user.name}</option>{/foreach}
                </select>
                </td>
            </tr>
            <tr>
                <td class="tdlabel">{$langPassword1}</td>
                <td class="tdfield"><input type="password" name="password1" id="password1" class="input" value="" size="{$maxPasswordLength}" tabindex="140" /></td>
            </tr>
            <tr>
                <td class="tdlabel">{$langPassword2}</td>
                <td class="tdfield"><input type="password" name="password2" id="password2" class="input" value="" size="{$maxPasswordLength}" tabindex="150" /></td>
            </tr>
            <tr>
                <td colspan="2"><input class="submit" type="submit" name="submit" value="{$langUserPwReset}" tabindex="160" /></td>
            </tr>
        </table>
    </form>
{/if}
</div>

                    $sReturn = json_encode($AUTHORISATION->getAllRoles());
                case 'doPwReset':
                    $this->handleResponse('handleResponseDoPwReset', 'doPwReset',
                                            l('The password has been reset.'),
                                                                l('The password could not be reset.'));
                break;

    protected function doPwReset($a) {
        global $AUTH;
        if($AUTH->authedTrusted())
            return false;
        return $AUTH->resetPassword($a['userId'], $a['password1']);
    }

    protected function handleResponseDoPwReset() {
        global $AUTH;
        $bValid = true;

        $FHANDLER = new CoreRequestHandler($_POST);
        $attr = Array('userId'     => MATCH_INTEGER,
                      'password1'  => MATCH_STRING,
                      'password2'  => MATCH_STRING);
        $this->verifyValuesSet($FHANDLER,   $attr);
        $this->verifyValuesMatch($FHANDLER, $attr);

        // Check length limits
        if($bValid && $this->FHANDLER->isLongerThan('password1', AUTH_MAX_PASSWORD_LENGTH))
            $bValid = false;
        if($bValid && $this->FHANDLER->isLongerThan('password2', AUTH_MAX_PASSWORD_LENGTH))
            $bValid = false;

        // Check if new passwords are equal
        if($bValid && $this->FHANDLER->get('password1') !== $this->FHANDLER->get('password2'))
            throw new NagVisException(l('The two passwords are not equal.'));

        // Don't change own users password
        if($AUTH->getUserId() == $FHANDLER->get('userId'))
            throw new NagVisException(l('Unable to reset the password for your own user.'));

        // Store response data
        if($bValid === true)
          return Array('userId'    => $FHANDLER->get('userId'),
                         'password1' => $FHANDLER->get('password1'),
                         'password2' => $FHANDLER->get('password2'));
        else
            return false;
    }
*/

?>
