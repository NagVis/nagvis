<?php
/**
 * Abstract definition of a CoreAuthModule
 * All authentication modules should extend this class
 *
 * @author Lars Michelsen <lars@vertical-visions.de>
 */
abstract class CoreAuthModule {
	abstract public function passCredentials($aData);
	abstract public function getCredentials();
	abstract public function isAuthenticated();
	abstract public function getUser();
	abstract public function getUserId();
}
?>