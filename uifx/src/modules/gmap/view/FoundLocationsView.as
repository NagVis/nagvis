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

package modules.gmap.view
{
	import com.google.maps.LatLng;
	import com.google.maps.Map;
	import com.google.maps.MapMouseEvent;
	import com.google.maps.overlays.Marker;
	import com.google.maps.overlays.MarkerOptions;
	
	import modules.gmap.LocationsCollection;
	import modules.gmap.domain.Location;
	import modules.gmap.events.LocationsViewEvent;
	
	import mx.controls.Alert;
	import mx.events.CollectionEvent;
	import mx.events.CollectionEventKind;

	public class FoundLocationsView extends LocationsView
	{
		public function FoundLocationsView(map : Map, locations : LocationsCollection = null)
		{
			super(map, locations);
		}

		override protected function onLocationsChange(event : CollectionEvent) : void
		{
			var location : Location;

			switch (event.kind)
			{
				case CollectionEventKind.ADD:
					for each (location in event.items)
					{
						var options : MarkerOptions = new MarkerOptions();
						// options.label = location.label; // conflicts with icon 
						options.icon = (location.id)? new okIcon() : new unknownIcon(); // TODO: change icons in the future
						options.iconAlignment = MarkerOptions.ALIGN_HORIZONTAL_CENTER || MarkerOptions.ALIGN_VERTICAL_CENTER;
						options.hasShadow = false;
						this.markers.addItem(new Marker(LatLng.fromUrlValue(location.point), options));
					}
					break;

				case CollectionEventKind.REMOVE:
					for each (location in event.items)
						this.markers.removeItemAt(event.location); // TODO: check if this works correctly
					break;
			}
		}

		override protected function onSelectLocation(event : MapMouseEvent) : void
		{
			var index : int = 0;

			// ignore duplicate clicks
			if (this.selectedMarker)
			{
				Alert.show("Location is already selected");
				return;
			}

			this.selectedMarker = event.target as Marker;

			while (this.locations.length > 1)
			{
				var location : Location = this.locations.getItemAt(index) as Location;
				if (LatLng.fromUrlValue(location.point).lat() == this.selectedMarker.getLatLng().lat()
					&& LatLng.fromUrlValue(location.point).lng() == this.selectedMarker.getLatLng().lng())
				{
					index = 1;
				}
				else
					this.locations.removeItemAt(index);
			}

			this.selectedLocation = this.locations.getItemAt(0) as Location;
			dispatchEvent(new LocationsViewEvent('selectLocation', this.selectedLocation));
		}

		public function clearLocations() : void
		{
			this.selectedMarker = null;
			this.selectedLocation = null;
			this.markers.removeAll();
			this.locations.removeAll();
			this.map.clearOverlays(); // clears links too
		}
	}
}