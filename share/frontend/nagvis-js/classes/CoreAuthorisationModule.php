<?php
/**
 * Abstract definition of a CoreAuthorisationModule
 * All authorisaiton modules should extend this class
 *
 * @author Lars Michelsen <lars@vertical-visions.de>
 */
abstract class CoreAuthorisationModule {
	abstract public function parsePermissions();
}
?>