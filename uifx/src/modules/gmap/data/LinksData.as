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
	import mx.logging.LogEvent;
	import mx.logging.LogEventLevel;

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
			for (var i:int = data.length - 1; i >= 0; i--)
				if(!resolveLink(data[i] as Link))
					data.splice(i, 1);

			this.source = data;
		}
		
		protected function resolveLink(link:Link):Boolean
		{
			link.location1 = _locations.getItemById(link.id1);
			link.location2 = _locations.getItemById(link.id2);
				
			if(!link.location1 || !link.location2)
				return false;
			
			return true
		}

		public function getItemById(id : String) : Link
		{
			for each(var link : Link in this)
				if (link.id == id)
					return link;

			return null;
		}

		public function addUpdateItem(item : Link) : Link
		{
			var link : Link = this.getItemById(item.id);

			if (link)
			{
				resolveLink(item);
				link.update(item);
				return link;
			}

			if (resolveLink(item))
			{
				addItem(item);
				return item;
			}

			return null;
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
