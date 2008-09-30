/*****************************************************************************
 *
 * NagVisHost.js - This class handles the visualisation of host objects
 *
 * Copyright (c) 2004-2008 NagVis Project
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
 
/**setLastUpdate
 * @author	Lars Michelsen <lars@vertical-visions.de>
 */

NagVisHost.prototype = new NagVisStatefulObject;
NagVisHost.prototype.constructor = NagVisHost;
function NagVisHost (oConf) {
	this.setLastUpdate();
	this.objId = getRandomLowerCaseLetter() + getRandom(1, 99999);
	this.conf = oConf;
	this.getMembers();
}
