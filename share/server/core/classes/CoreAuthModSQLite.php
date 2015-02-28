<?php
/*******************************************************************************
 *
 * CoreAuthModSQLite.php - Authentication module based on a SQLite database
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
class CoreAuthModSQLite extends CoreAuthModule {
    private $USERCFG;

    private $iUserId = -1;
    private $sUsername = '';
    private $sPassword = '';
    private $sPasswordnew = '';
    private $sPasswordHash = '';

    public function __construct() {
        parent::$aFeatures = Array(
            // General functions for authentication
            'passCredentials' => true,
            'getCredentials' => true,
            'isAuthenticated' => true,
            'getUser' => true,
            'getUserId' => true,

            // Changing passwords
            'passNewPassword' => true,
            'changePassword'  => true,
            'resetPassword'   => true,

            // Managing users
            'createUser' => true,
        );

        $this->DB = new CoreSQLiteHandler();

        // Open sqlite database
        if(!$this->DB->open(cfg('paths', 'cfg').'auth.db')) {
            throw new NagVisException(l('Unable to open auth database ([DB])',
                                      Array('DB' => cfg('paths', 'cfg').'auth.db')));
        } else {
            // Create initial db scheme if needed
            if(!$this->DB->tableExist('users')) {
                $this->DB->createInitialDb();
            } else {
                // Maybe an update is needed
                $this->DB->updateDb();
            }
        }
    }

    public function getAllUsers() {
        $aPerms = Array();

        // Get all the roles of the user
      $RES = $this->DB->query('SELECT userId, name FROM users ORDER BY name');
      while($data = $this->DB->fetchAssoc($RES)) {
      	$aPerms[] = $data;
      }

      return $aPerms;
    }

    public function checkUserExists($name) {
        return $this->DB->count('SELECT COUNT(*) AS num FROM users WHERE name='.$this->DB->escape($name));
    }

    private function checkUserAuth($bTrustUsername = AUTH_NOT_TRUST_USERNAME) {
        if($bTrustUsername === AUTH_NOT_TRUST_USERNAME) {
            $query = 'SELECT userId FROM users WHERE name='.$this->DB->escape($this->sUsername).' AND password='.$this->DB->escape($this->sPasswordHash);
        } else {
            $query = 'SELECT userId FROM users WHERE name='.$this->DB->escape($this->sUsername);
        }

        $ret = $this->DB->fetchAssoc($this->DB->query($query));

        return intval($ret['userId']);
    }

    private function updatePassword($uid, $pw) {
        if(!$this->DB->isWriteable())
            return false;
        $this->DB->query('UPDATE users SET password='.$this->DB->escape($pw).' WHERE userId='.$this->DB->escape($uid));
    }

    private function addUser($user, $hash) {
        if(!$this->DB->isWriteable())
            return false;
        $this->DB->query('INSERT INTO users (name,password) VALUES ('.$this->DB->escape($user).','.$this->DB->escape($hash).')');
    }

    public function passCredentials($aData) {
        if(isset($aData['user'])) {
            $this->sUsername = $aData['user'];
        }
        if(isset($aData['password'])) {
            $this->sPassword = $aData['password'];

            // Remove the password hash when setting a new password
            $this->sPasswordHash = '';
        }
        if(isset($aData['passwordHash'])) {
            $this->sPasswordHash = $aData['passwordHash'];
        }
    }

    public function passNewPassword($aData) {
        if(isset($aData['user'])) {
            $this->sUsername = $aData['user'];
        }
        if(isset($aData['password'])) {
            $this->sPassword = $aData['password'];

            // Remove the password hash when setting a new password
            $this->sPasswordHash = '';
        }
        if(isset($aData['passwordNew'])) {
            $this->sPasswordNew = $aData['passwordNew'];
        }
    }

    public function getCredentials() {
        return Array('user' => $this->sUsername,
                     'passwordHash' => $this->sPasswordHash,
                     'userId' => $this->iUserId);
    }

    public function createUser($user, $password) {
        $bReturn = false;

        // Compose the password hash
        $hash = $this->createHash($password);

        // Create user
        $this->addUser($user, $hash);

        // Check result
        if($this->checkUserExists($user)) {
            $bReturn = true;
        } else {
            $bReturn = false;
        }

        return $bReturn;
    }

    public function resetPassword($uid, $pw) {
        // FIXME: To be coded
        $this->updatePassword($uid, $this->createHash($pw));
        return true;
    }

    public function changePassword() {
        // Check the authentication with the old password
        if(!$this->isAuthenticated())
            return false;

        // Set new password to current one
        $this->sPassword = $this->sPasswordNew;

        // Compose the new password hash
        $this->sPasswordHash = $this->createHash($this->sPassword);

        // Update password
        $this->updatePassword($this->iUserId, $this->sPasswordHash);

        return true;
    }

    public function isAuthenticated($bTrustUsername = AUTH_NOT_TRUST_USERNAME) {
        // Only handle known users
        if($this->sUsername === '' || !$this->checkUserExists($this->sUsername))
            return false;

        // Try to calculate the passowrd hash only when no hash is known at
        // this time. For example when the user just entered the password
        // for logging in. If the user is already logged in and this is just
        // a session check don't try to rehash the password.
        if($bTrustUsername === AUTH_NOT_TRUST_USERNAME && $this->sPasswordHash === '') {
            // Compose the password hash for comparing with the stored hash
            $this->sPasswordHash = $this->createHash($this->sPassword);
        }

        // Check the password hash
        $userId = $this->checkUserAuth($bTrustUsername);
        if($userId > 0) {
            $this->iUserId = $userId;
            return true;
        }

        return false;
    }

    public function getUser() {
        return $this->sUsername;
    }

    public function getUserId() {
        return $this->iUserId;
    }

    private function createHash($password) {
        return sha1(AUTH_PASSWORD_SALT.$password);
    }
}
?>
