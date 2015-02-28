<?php
/*******************************************************************************
 *
 * CoreSessionHandler.php - Class to handle PHP session data
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
 * Class for handlin the PHP sessions. The sessions are used to store
 * information between loading different pages.
 *
 * @author Lars Michelsen <lars@vertical-visions.de>
 */
class CoreSessionHandler {

    public function __construct() {
        $sDomain   = cfg('global', 'sesscookiedomain');
        $sPath     = cfg('global', 'sesscookiepath');
        $iDuration = cfg('global', 'sesscookieduration');
        $bSecure   = cfg('global', 'sesscookiesecure') == 1;
        $bHTTPOnly = cfg('global', 'sesscookiehttponly') == 1;

        // Set the session name (used in params/cookie names)
        session_name(SESSION_NAME);

        // Only add the domain when it is no simple hostname
        // This can be easily detected searching for a dot
        if(strpos($sDomain, '.') === false)
            $sDomain = null;

        // Opera has problems with ip addresses in domains. So skip them
        if(strpos($_SERVER['HTTP_USER_AGENT'], 'opera') !== false
           && preg_match('/\d.\d+.\d+.\d+/', $sDomain))
            $sDomain = null;

        // Set custom params for the session cookie
        if (version_compare(PHP_VERSION, '5.2') >= 0)
            session_set_cookie_params(0, $sPath, $sDomain, $bSecure, $bHTTPOnly);
        else
            session_set_cookie_params(0, $sPath, $sDomain, $bSecure);

        // Start a session for the user when not started yet
        if(!isset($_SESSION)) {
            try {
                session_start();
                // Write/close to release the lock aquired by session_start().
                // Each write to the session needs to perform session_start() again
                session_write_close();
            } catch(ErrorException $e) {
                // Catch and suppress session cleanup errors. This is a problem
                // especially on current debian/ubuntu:
                //   PHP error in ajax request handler: Error: (8) session_start():
                //   ps_files_cleanup_dir: opendir(/var/lib/php5) failed: Permission denied (13)
                if(strpos($e->getMessage(), 'ps_files_cleanup_dir') === false)
                    throw $e;
            }

            // Store the creation time of the session
            if(!$this->issetAndNotEmpty('sessionExpires'))
                $this->set('sessionExpires', time()+$iDuration);
        }

        // Reset the expiration time of the session cookie
        if(isset($_COOKIE[SESSION_NAME])) {
            // Don't reset the expiration time on every page load - only reset when
            // the half of the expiration time has passed
            if(time() >= $this->get('sessionExpires') - ($iDuration/2)) {
                $exp = time() + $iDuration;
                setcookie(SESSION_NAME, $_COOKIE[SESSION_NAME], $exp, $sPath, $sDomain, $bSecure, $bHTTPOnly);

                // Store the update time of the session cookie
                $this->set('sessionExpires', $exp);
            }
        }
    }

    public function isSetAndNotEmpty($sKey) {
        return isset($_SESSION[$sKey]) && $_SESSION[$sKey] != '';
    }

    public function get($sKey) {
        if(isset($_SESSION[$sKey])) {
            return $_SESSION[$sKey];
        } else {
            return false;
        }
    }

    public function set($sKey, $sVal) {
        if(isset($_SESSION[$sKey])) {
            $sOld = $_SESSION[$sKey];
        } else {
            $sOld = false;
        }

        if($sVal == false) {
            unset($_SESSION[$sKey]);
        } else {
            $_SESSION[$sKey] = $sVal;
        }

        return $sOld;
    }

    public function del($key) {
        unset($_SESSION[$key]);
    }

    public function aquire() {
        session_start();
    }

    public function commit() {
        session_write_close();
    }
}

?>
