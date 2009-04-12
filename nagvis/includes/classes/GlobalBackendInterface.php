<?php
/*****************************************************************************
 *
 * GlobalBackendInterface.php - Interface for implementing a backend in NagVis
 *
 * Copyright (c) 2004-2009 NagVis Project (Contact: lars@vertical-visions.de)
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
 
/**
 * @author	Lars Michelsen <lars@vertical-visions.de>
 */

interface GlobalBackendInterface {
	public function __construct($CORE, $backendId);
	public static function getValidConfig();
	public function getObjects($type, $name1Pattern = '', $name2Pattern = '');
	public function getHostState($hostName, $onlyHardstates);
	public function getServiceState($hostName, $serviceName, $onlyHardstates);
	public function getHostNamesWithNoParent();
	public function getDirectChildNamesByHostName($hostName);
	public function getHostsByHostgroupName($hostgroupName);
	public function getServicesByServicegroupName($servicegroupName);
	public function getServicegroupInformations($servicegroupName);
	public function getHostgroupInformations($hostgroupName);
}
?>
