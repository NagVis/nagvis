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

package modules.gmap.view.controls
{
	import com.google.maps.Map;

	import flash.events.Event;

	import modules.gmap.data.LocationsData;
	import modules.gmap.domain.Location;
	import modules.gmap.events.LocationEvent;

	import mx.containers.VBox;
	import mx.events.CollectionEvent;
	import mx.events.CollectionEventKind;

	public class GMapLocationsControl extends VBox
	{
		private var _dataProvider : LocationsData;
		private var _map : Map;
		private var _markers : Array;

		public function GMapLocationsControl()
		{
			super();
		}

		public function get map():Map
		{
			return _map;
		}

		//Already initialized map has to be set here
		//TODO: support uninitialized map
		public function set map(value : Map) : void
		{
			if (_map !== value)
			{
				_map = value;

				reinitMarkers();

				if (visible)
					showMarkers();
			}
		}

		public function get dataProvider() : LocationsData
		{
			return _dataProvider;
		}

		public function set dataProvider(value : LocationsData) : void
		{
			if (_dataProvider !== value)
			{
				_dataProvider = value;
				_dataProvider.addEventListener(CollectionEvent.COLLECTION_CHANGE, onDataProviderChanged);

				reinitMarkers();
			}
		}

		protected function onDataProviderChanged(event : CollectionEvent) : void
		{
			if (event.kind == CollectionEventKind.RESET)
				reinitMarkers();

			if (event.kind == CollectionEventKind.ADD)
			{
				for each (var added : Location in event.items)
					createMarker(added);
			}

			if (event.kind == CollectionEventKind.REMOVE)
			{
				for each (var removed : Location in event.items)
				{
					var marked : LocationMarker;
					for (var i : int = _markers.length - 1; i >= 0; i--)
					{
						marked = _markers.shift();

						if (marked.location.id === removed.id)
						{
							if (visible && _map)
								_map.removeOverlay(marked);
							break;
						}

						_markers.push(marked);
					}
				}
			}
		}

		public override function set visible(value : Boolean) : void
		{
			if (super.visible != value)
			{
				if (value)
					showMarkers();
				else
					hideMarkers();

				super.visible = value;
			}
		}

		protected function reinitMarkers() : void
		{
			if (visible)
				hideMarkers();

			_markers = [];
			for each (var l : Location in _dataProvider)
				createMarker(l);
		}

		protected function showMarkers() : void
		{
			if (_map)
			{
				for each (var m : LocationMarker in _markers)
					_map.addOverlay(m);
			}
		}

		protected function hideMarkers() : void
		{
			if (_map)
			{
				for each (var m : LocationMarker in _markers)
					_map.removeOverlay(m);
			}
		}

		protected function createMarker(location:Location) : void
		{
			if (_map)
			{
				var m : LocationMarker = new LocationMarker(location);
				m.addEventListener(LocationEvent.SELECTED, redispatchMarkerEvent);
				m.addEventListener(LocationEvent.ACTIVATE, redispatchMarkerEvent);
				_markers.push(m);

				if (visible)
					_map.addOverlay(m);
			}
		}

		// Marker is not an UI component, so
		// we need to redispatch his events to get them into Mate.
		protected function redispatchMarkerEvent(event : Event) : void
		{
			dispatchEvent(event);
		}
	}
}
