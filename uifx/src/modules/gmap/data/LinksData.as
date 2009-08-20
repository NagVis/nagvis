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

	public class LinksData extends ArrayCollection
	{
		private var _locations : LocationsData;
		
		public function LinksData(source : Array = null)
		{
			super(source);
		}

		public function set locations(value:LocationsData):void
		{
			_locations = value;
		}

		public function fill(data : Array) : void
		{
			this.source = data;

			for each(var link : Link in this)
				resolveLink(link);
		}
		
		protected function resolveLink(link:Link):void
		{
			link.location1 = _locations.getItemById(link.id1);
			link.location2 = _locations.getItemById(link.id2);
		}

		public function getItemById(id : String) : Link
		{
			for each(var link : Link in this)
				if (link.id == id)
					return link;

			return null;
		}

		public function addUpdateItem(item : Link) : void
		{
			trace(item.id);
			var link : Link = this.getItemById(item.id);

			if (link == null)
			{
				resolveLink(item);
				this.addItem(item);
			}
			else
				link.update(item);
		}

		public function addUpdateItems(items : Array) : void
		{
			for each(var link : Link in items)
				this.addUpdateItem(link);
		}

		public function removeItemById(id : String) : void
		{
			removeItemAt(getItemIndex(getItemById(id)));
		}

		public function virginize() : void
		{
			for each(var link : Link in this)
				link.state = Link.STATE_OK;
		}
	}
}
