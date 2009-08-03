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

package modules.gmap
{
	import com.google.maps.LatLng;
	
	import modules.gmap.domain.Location;
	
	import mx.collections.ArrayCollection;

	public class LocationsCollection extends ArrayCollection
	{
		public function LocationsCollection(source : Array = null)
		{
			super(source);
		}

		public function getItemById(id : String) : Location
		{
			for each(var location : Location in this)
				if (location.id == id)
					return location;

			return null;
		}

		public function getItemByLatLng(coords : LatLng) : Location
		{
			for each(var location : Location in this)
				if (LatLng.fromUrlValue(location.point).lat() == coords.lat()
					&& LatLng.fromUrlValue(location.point).lng() == coords.lng())
				{
					return location;
				}

			return null;
		}

		public function addUpdateItem(item : Location) : void
		{
			var location : Location = this.getItemById(item.id);

			if (location == null)
				this.addItem(item);
			else
				location.update(item);
		}
	}
}