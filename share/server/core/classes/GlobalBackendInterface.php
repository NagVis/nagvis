<?php
/*****************************************************************************
 *
 * GlobalBackendInterface.php - Interface for implementing a backend in NagVis
 *
 * Copyright (c) 2004-2010 NagVis Project (Contact: lars@vertical-visions.de)
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
    public function __construct($backendId);

    /**
     * Static function which returns the backend specific configuration options
     * and defines the default values for the options
     */
    public static function getValidConfig();

    /**
     * Used in WUI forms to populate the object lists when adding or modifying
     * objects in WUI.
     */
    public function getObjects($type, $name1Pattern = '', $name2Pattern = '');

    /**
     * Returns the state with detailed information of a list of hosts. Using the
     * given objects and filters.
     */
    public function getHostState($objects, $options, $filters);

    /**
     * Returns the state with detailed information of a list of services. Using
     * the given objects and filters.
     */
    public function getServiceState($objects, $options, $filters);

    /**
     * Returns the service state counts for a list of hosts. Using
     * the given objects and filters.
     */
    public function getHostMemberCounts($objects, $options, $filters);

    /**
     * Returns the host and service state counts for a list of hostgroups. Using
     * the given objects and filters.
     */
    public function getHostgroupStateCounts($objects, $options, $filters);

    /**
     * Returns the service state counts for a list of servicegroups. Using
     * the given objects and filters.
     */
    public function getServicegroupStateCounts($objects, $options, $filters);

    /**
     * Returns a list of host names which have no parent defined.
     */
    public function getHostNamesWithNoParent();

    /**
     * Returns a list of host names which are direct childs of the given host
     */
    public function getDirectChildNamesByHostName($hostName);

    /**
     * Returns a list of host names which are direct parents of the given host
     */
    public function getDirectParentNamesByHostName($hostName);

# Deprecated:
#  public function getHostsByHostgroupName($hostgroupName);
#  public function getServicesByServicegroupName($servicegroupName);
#  public function getServicegroupInformations($servicegroupName);
#  public function getHostgroupInformations($hostgroupName);
#  public function getHostgroupState($hostgroupName, $onlyHardstates);
#  public function getServicegroupState($servicegroupName, $onlyHardstates);
}
?>
