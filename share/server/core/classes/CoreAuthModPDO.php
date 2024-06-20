<?php
/*******************************************************************************
 *
 * CoreAuthModPDO.php - Authentication module using the PDO database abstraction
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
 ******************************************************************************/

abstract class CoreAuthModPDO extends CoreAuthModule {
    private $USERCFG;

    /** @var CorePDOHandler */
    private $DB;

    /** @var int */
    private $iUserId = -1;

    /** @var string */
    private $sUsername = '';

    /** @var string */
    private $sPassword = '';

    /** @var string */
    private $sPasswordNew = '';

    /** @var string */
    private $sPasswordHash = '';

    /**
     * @return array
     */
    abstract public function getConfig();

    public function __construct()
    {
        parent::$aFeatures = [
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
        ];

        $this->DB = new CorePDOHandler();

        // Open the database
        $config = $this->getConfig();
        if (!$this->DB->open($config['driver'], $config['params'], $config['username'], $config['password'])) {
            throw new NagVisException(l('Unable to open auth database ([DB]): [MSG]',
                [
                    'DB' => $this->DB->getDSN(),
                    'MSG' => json_encode($this->DB->error())
                ]));
        } elseif (!$this->DB->tableExist('users')) {
            // Create initial db scheme if needed
            $this->DB->createInitialDb();
        } else {
            // Maybe an update is needed
            $this->DB->updateDb();
        }
    }

    /**
     * Get all the users in the system
     *
     * @return array
     */
    public function getAllUsers()
    {
        $aPerms = [];

        $RES = $this->DB->query('-user-get-all');
        while ($data = $RES->fetch()) {
                  $aPerms[] = $data;
        }

        return $aPerms;
    }

    /**
     * @param string $name
     * @return int
     */
    public function checkUserExists($name)
    {
        return $this->DB->count('-user-count', ['name' => $name]);
    }

    /**
     * @param bool $bTrustUsername
     * @return int
     */
    private function checkUserAuth($bTrustUsername = AUTH_NOT_TRUST_USERNAME)
    {
        if ($bTrustUsername === AUTH_NOT_TRUST_USERNAME) {
            $res = $this->DB->query(
                '-user-get-by-pass',
                ['name' => $this->sUsername, 'password' => $this->sPasswordHash]
            );
        } else {
            $res = $this->DB->query('-user-get-by-name', ['name' => $this->sUsername]);
        }

        $data = $res->fetch();

        if (!isset($data['userId'])) {
            return 0;
        }
        return intval($data['userId']);
    }

    /**
     * @param int $uid
     * @param string $pw
     * @return bool
     */
    private function updatePassword($uid, $pw)
    {
        try {
            $res = $this->DB->query('-user-update-pass', ['id' => $uid, 'password' => $pw]);
            return $res !== false && $res->rowCount() === 1;
        } catch (PDOException $e) {
            error_log("Could not update the password of user $uid: " . $e->getMessage());
            return false;
        }
    }

    /**
     * @param string $user
     * @param string $hash
     * @return void
     */
    private function addUser($user, $hash)
    {
        $this->DB->query('-user-add', ['name' => $user, 'password' => $hash]);
    }

    /**
     * @param array $aData
     * @return void
     */
    public function passCredentials($aData)
    {
        if (isset($aData['user'])) {
            $this->sUsername = $aData['user'];
        }
        if (isset($aData['password'])) {
            $this->sPassword = $aData['password'];

            // Remove the password hash when setting a new password
            $this->sPasswordHash = '';
        }
        if (isset($aData['passwordHash'])) {
            $this->sPasswordHash = $aData['passwordHash'];
        }
    }

    /**
     * @param array $aData
     * @return void
     */
    public function passNewPassword($aData)
    {
        if (isset($aData['user'])) {
            $this->sUsername = $aData['user'];
        }
        if (isset($aData['password'])) {
            $this->sPassword = $aData['password'];

            // Remove the password hash when setting a new password
            $this->sPasswordHash = '';
        }
        if (isset($aData['passwordNew'])) {
            $this->sPasswordNew = $aData['passwordNew'];
        }
    }

    /**
     * @return array
     */
    public function getCredentials()
    {
        return [
            'user' => $this->sUsername,
            'passwordHash' => $this->sPasswordHash,
            'userId' => $this->iUserId
        ];
    }

    /**
     * @param string $user
     * @param string $password
     * @return int
     */
    public function createUser($user, $password)
    {
        $bReturn = false;

        // Compose the password hash
        $hash = $this->createHash($password);

        // Create user
        $this->addUser($user, $hash);

        // Check result
        return $this->checkUserExists($user);
    }

    /**
     * @param int $uid
     * @param string $pw
     * @return true
     */
    public function resetPassword($uid, $pw)
    {
        // FIXME: To be coded
        $this->updatePassword($uid, $this->createHash($pw));
        return true;
    }

    /**
     * @return bool
     */
    public function changePassword()
    {
        // Check the authentication with the old password
        if (!$this->isAuthenticated()) {
            return false;
        }

        // Set new password to current one
        $this->sPassword = $this->sPasswordNew;

        // Compose the new password hash
        $this->sPasswordHash = $this->createHash($this->sPassword);

        // Update password
        return $this->updatePassword($this->iUserId, $this->sPasswordHash);
    }

    /**
     * @param bool $bTrustUsername
     * @return bool
     */
    public function isAuthenticated($bTrustUsername = AUTH_NOT_TRUST_USERNAME)
    {
        // Only handle known users
        if ($this->sUsername === '' || !$this->checkUserExists($this->sUsername)) {
            return false;
        }

        // Try to calculate the passowrd hash only when no hash is known at
        // this time. For example when the user just entered the password
        // for logging in. If the user is already logged in and this is just
        // a session check don't try to rehash the password.
        if ($bTrustUsername === AUTH_NOT_TRUST_USERNAME && $this->sPasswordHash === '') {
            // Compose the password hash for comparing with the stored hash
            $this->sPasswordHash = $this->createHash($this->sPassword);
        }

        // Check the password hash
        $userId = $this->checkUserAuth($bTrustUsername);
        if ($userId > 0) {
            $this->iUserId = $userId;
            return true;
        }

        return false;
    }

    /**
     * @return string
     */
    public function getUser()
    {
        return $this->sUsername;
    }

    /**
     * @return int
     */
    public function getUserId()
    {
        return $this->iUserId;
    }

    /**
     * @param string $password
     * @return string
     */
    private function createHash($password)
    {
        return sha1(AUTH_PASSWORD_SALT . $password);
    }
}
