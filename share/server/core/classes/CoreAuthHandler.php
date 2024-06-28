<?php
/*******************************************************************************
 *
 * CoreAuthHandler.php - Handler for authentication modules
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

/**
 * This class handles all authorisation tasks and is the glue between the
 * application and the different authorisation modules. It loads the
 * authentication module depending on the configuration. An authentication
 * module handles the information gathered from the webservers vars or the
 * frontend.
 *
 * @author Lars Michelsen <lm@larsmichelsen.com>
 */
class CoreAuthHandler
{
    /** @var CoreSessionHandler */
    private $SESS;

    /** @var CoreAuthModule */
    private $MOD;

    /** @var string|null */
    private $sModuleName;

    /** @var bool */
    private $trustUsername  = false;

    /** @var bool */
    private $logoutPossible = true;

    public function __construct()
    {
        global $SHANDLER;
        $this->SESS = $SHANDLER;

        $this->sModuleName = cfg('global', 'authmodule');
        $this->MOD = new $this->sModuleName();
    }

    /**
     * @param $name
     * @return bool
     */
    public function checkFeature($name)
    {
        $a = $this->MOD->getSupportedFeatures();
        return isset($a[$name]) && $a[$name];
    }

    /**
     * @return string|null
     */
    public function getModule()
    {
        return $this->sModuleName;
    }

    /**
     * @param array $aData
     * @return void
     */
    public function passCredentials($aData)
    {
        $this->MOD->passCredentials($aData);
    }

    /**
     * @param array $aData
     * @return void
     * @throws NagVisException
     */
    public function passNewPassword($aData)
    {
        // FIXME: First check if the auth module supports this mechanism

        // Some simple validations
        if ($aData !== false) {
            $this->MOD->passNewPassword($aData);
        } else {
            throw new NagVisException(l('Data has an invalid format'));
        }
    }

    /**
     * @return array
     */
    private function getCredentials()
    {
        return $this->MOD->getCredentials();
    }

    /**
     * @return string
     */
    public function getUser()
    {
        return $this->MOD->getUser();
    }

    /**
     * @return int
     */
    public function getUserId()
    {
        return $this->MOD->getUserId();
    }

    /**
     * @return mixed
     */
    public function getAllUsers()
    {
        // FIXME: First check if the auth module supports this mechanism

        // Ask the module
        return $this->MOD->getAllUsers();
    }

    /**
     * @param $name
     * @return bool
     */
    public function checkUserExists($name)
    {
        return $name !== '' && $this->MOD->checkUserExists($name);
    }

    /**
     * @param string $username
     * @param string $password
     * @return int
     */
    public function createUser($username, $password)
    {
        // FIXME: First check if the auth module supports this mechanism

        // Ask the module
        return $this->MOD->createUser($username, $password);
    }

    /**
     * @return bool
     */
    public function changePassword()
    {
        // FIXME: First check if the auth module supports this mechanism

        // Ask the module
        $bChanged = $this->MOD->changePassword();

        // Save success to session
        if ($bChanged === true) {
            $this->SESS->aquire();
            $this->SESS->set('authCredentials', $this->getCredentials());
            $this->SESS->commit();
        }

        return $bChanged;
    }

    /**
     * @param int $uid
     * @param string $pw
     * @return true
     * @throws CoreAuthModNoSupport
     */
    public function resetPassword($uid, $pw)
    {
        if (!$this->checkFeature('resetPassword')) {
            throw new CoreAuthModNoSupport("Password reset not supported");
        }
        return $this->MOD->resetPassword($uid, $pw);
    }

    /**
     * Did the user authenticate using trusted auth?
     *
     * @return bool
     */
    public function authedTrusted()
    {
        return $this->trustUsername;
    }

    /**
     * @return false|mixed
     */
    public function getLogonModule()
    {
        return $this->SESS->get('logonModule');
    }

    /**
     * @return false|mixed
     */
    public function getAuthModule()
    {
        return $this->SESS->get('authModule');
    }

    /**
     * @return bool
     */
    public function isAuthenticated()
    {
        if (cfg('global', 'audit_log')) {
            $ALOG = new CoreLog(
                cfg('paths', 'var') . 'nagvis-audit.log',
                cfg('global', 'dateformat')
            );
        } else {
            $ALOG = null;
        }

        //$bAlreadyAuthed = $this->SESS->isSetAndNotEmpty('authCredentials');

        // When the user authenticated with the multisite logon module log the user
        // out if the auth_* cookie does not exist anymore. The cookie name has been
        // stored in the session var multisiteLogonCookie
        // This is a bad hacky place for this but I see no other good solution atm
        /*if ($bAlreadyAuthed && $this->SESS->isSetAndNotEmpty('multisiteLogonCookie')) {
            $cookieName = $this->SESS->get('multisiteLogonCookie');
            if (!$cookieName || !isset($_COOKIE[$cookieName])) {
                $this->logout(true);
                return false;
            }
        }*/

        // Ask the module
        $isAuthenticated = $this->MOD->isAuthenticated($this->trustUsername);

        if ($ALOG !== null) {
            if ($isAuthenticated) {
                $ALOG->l(
                    'User logged in (' . $this->getUser() . ' / ' . $this->getUserId() . '): ' . $this->sModuleName
                );
            } else {
                $ALOG->l(
                    'User login failed (' . $this->getUser() . ' / ' . $this->getUserId() . '): ' . $this->sModuleName
                );
            }
        }

        return $isAuthenticated;
    }

    /**
     * @return bool
     */
    public function logoutSupported()
    {
        return $this->logoutPossible;
    }

    /**
     * @param bool $enforce
     * @return bool
     */
    public function logout($enforce = false)
    {
        if (!$enforce && !$this->logoutSupported()) {
            return false;
        }

        if (cfg('global', 'audit_log')) {
            $ALOG = new CoreLog(cfg('paths', 'var') . 'nagvis-audit.log', cfg('global', 'dateformat'));
            $ALOG->l('User logged out (' . $this->getUser() . ' / ' . $this->getUserId() . '): ' . $this->sModuleName);
        }

        // Remove the login information
        $this->SESS->aquire();
        $this->SESS->del('logonModule');
        $this->SESS->del('authModule');
        $this->SESS->del('authCredentials');
        $this->SESS->del('authTrusted');
        $this->SESS->del('userPermissions');
        $this->SESS->del('authLogoutPossible');
        //$this->SESS->del('multisiteLogonCookie');
        $this->SESS->commit();

        return true;
    }

    /**
     * @return bool
     */
    public function isAuthenticatedSession()
    {
        // Remove logins which were performed with different logon/auth modules
        if (
            $this->SESS->get('logonModule') != cfg('global', 'logonmodule')
            || $this->SESS->get('authModule') != $this->sModuleName
        ) {
            if (DEBUG && DEBUGLEVEL & 2) {
                debug('removing different logon/auth module data');
            }
            $this->logout(true);
            return false;
        }

        //debug($_SERVER['REQUEST_URI']);
        //debug(json_encode($_SESSION));

        $this->passCredentials($this->SESS->get('authCredentials'));
        $this->setTrustUsername($this->SESS->get('authTrusted'));
        $this->setLogoutPossible($this->SESS->get('authLogoutPossible'));

        return $this->isAuthenticated();
    }

    /**
     * @return bool
     */
    public function sessionAuthPresent()
    {
        return $this->SESS->isSetAndNotEmpty('authCredentials');
    }

    /**
     * @return void
     */
    public function storeInSession()
    {
        $this->SESS->aquire();
        $this->SESS->set('logonModule',        cfg('global', 'logonmodule'));
        $this->SESS->set('authModule',         $this->sModuleName);
        $this->SESS->set('authCredentials',    $this->getCredentials());
        $this->SESS->set('authTrusted',        $this->trustUsername);
        $this->SESS->set('authLogoutPossible', $this->logoutPossible);
        $this->SESS->commit();
    }

    /**
     * @param bool $flag
     * @return void
     */
    public function setTrustUsername($flag)
    {
        $this->trustUsername = $flag;
    }

    /**
     * @param bool $flag
     * @return void
     */
    public function setLogoutPossible($flag)
    {
        $this->logoutPossible = $flag;
    }
}
