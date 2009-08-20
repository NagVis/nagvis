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
	import com.google.maps.LatLng;
	import com.google.maps.MapMouseEvent;
	import com.google.maps.overlays.Marker;
	import com.google.maps.overlays.MarkerOptions;

	import flash.display.DisplayObject;

	import modules.gmap.domain.Location;
	import modules.gmap.events.LocationEvent;

	public class LocationMarker extends Marker
	{
		[Embed(source="modules/gmap/img/std_small_ok.png")]
		protected static var okIcon : Class;

		[Embed(source="modules/gmap/img/std_small_warning.png")]
		protected static var warningIcon : Class;

		[Embed(source="modules/gmap/img/std_small_critical.png")]
		protected static var errorIcon : Class;

		[Embed(source="modules/gmap/img/std_small_unknown.png")]
		protected static var unknownIcon : Class;

		private var _lastTimeClicked:Number = 0;

		private var _location : Location;

		public function LocationMarker(location : Location)
		{
			var point : LatLng = LatLng.fromUrlValue(location.point);

			var options : MarkerOptions;

			if (location.id && location.id.length > 0)
			{
				options = new MarkerOptions();
				options.icon = chooseIcon(location.state);
				options.iconAlignment = MarkerOptions.ALIGN_HORIZONTAL_CENTER | MarkerOptions.ALIGN_VERTICAL_CENTER;
				options.hasShadow = false;
			}

			super(point, options);

			// Note: the event gets redispatched here from GMapLocationsControl
			this.addEventListener(MapMouseEvent.CLICK, this.onClick);

			_location = location;
			_location.addEventListener('change', this.onChange);
		}

		/***
		 * Handles click & double click events
		 * because of the bug in Google Maps API
		 * http://code.google.com/p/gmaps-api-issues/issues/detail?id=394
		 ***/
		protected function onClick(event : *):void
		{
			var date : Date = new Date;

			if (date.time - _lastTimeClicked < 350)
			{
				dispatchEvent(
					new LocationEvent(LocationEvent.ACTIVATE, _location, true)
				);
				return;
			}

			_lastTimeClicked = date.time;
			dispatchEvent(
				new LocationEvent(LocationEvent.SELECTED, _location, true)
			);
		}

		private static function chooseIcon(state : Number) : DisplayObject
		{
			switch (state)
			{
				case Location.STATE_OK:
					return new okIcon();

				case Location.STATE_WARNING:
					return new warningIcon();

				case Location.STATE_ERROR:
					return new errorIcon();

				case Location.STATE_UNKNOWN:
				default:
					return new unknownIcon();
			}
		}

		protected function onChange(event : *) : void
		{
			var options : MarkerOptions = this.getOptions();
			options.icon = chooseIcon(_location.state);
			this.setOptions(options);
		}

		public function get location() : Location
		{
			return _location;
		}
	}
}
