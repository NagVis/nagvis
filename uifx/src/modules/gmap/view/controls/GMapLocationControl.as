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
	import com.google.maps.InfoWindowOptions;
	import com.google.maps.LatLng;
	import com.google.maps.Map;
	import com.google.maps.interfaces.IInfoWindow;
	import com.google.maps.overlays.Marker;
	import com.google.maps.overlays.MarkerOptions;

	import flash.geom.Point;
	import flash.text.TextFormat;

	import modules.gmap.domain.Location;

	import mx.core.UIComponent;

	public class GMapLocationControl extends UIComponent
	{
		[Embed(source="modules/gmap/img/pin.png")]
		protected var _icon : Class;

		private var _location : Location;
		private var _map : Map;
		private var _info : IInfoWindow;

		public function GMapLocationControl()
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

				reinitInfo();
			}
		}

		public function get location() : Location
		{
			return _location;
		}

		public function set location(value : Location) : void
		{
			if (_location !== value)
				_location = value;

			reinitInfo();
		}

		protected function reinitInfo() : void
		{
			if (_map)
			{
				if(_location)
				{
					var title : TextFormat = new TextFormat;
					title.bold = true;
					title.underline = true

					var io : InfoWindowOptions = new InfoWindowOptions;
					io.title = _location.label;
					io.titleFormat = title;
					io.content = _location.description;
					io.hasCloseButton = false;
					io.pointOffset = new Point(-3, -5);

					_map.openInfoWindow(LatLng.fromUrlValue(_location.point), io);
				}
				else
				{
					_map.closeInfoWindow();
				}
			}
		}
	}
}
