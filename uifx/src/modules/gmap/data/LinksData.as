/*****************************************************************************
 *
 * Copyright (C) 2009 NagVis Project
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

package modules.gmap.data
{
	import modules.gmap.domain.Link;

	import mx.collections.ArrayCollection;
	import mx.events.CollectionEvent;

	public class LinksData extends ArrayCollection
	{
		public function LinksData(source : Array = null)
		{
			super(source);
		}

		public function fill(data : Array, locations:LocationsData) : void
		{
			this.source = data;

			for each(var link:Link in this)
			{
				link.location1 = locations.getItemById(link.id1);
				link.location2 = locations.getItemById(link.id2);
			}
		}
	}
}
