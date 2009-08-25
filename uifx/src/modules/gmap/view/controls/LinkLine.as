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
	import com.google.maps.overlays.Polyline;
	import com.google.maps.overlays.PolylineOptions;
	import com.google.maps.styles.StrokeStyle;

	import modules.gmap.domain.Link;
	import modules.gmap.events.LinkEvent;

	import mx.events.PropertyChangeEvent;

	public class LinkLine extends Polyline
	{
		private var _lastTimeClicked : Number = 0;
		private var _link : Link;

		public function LinkLine(link : Link)
		{
			var point1 : LatLng = LatLng.fromUrlValue(link.location1.point);
			var point2 : LatLng = LatLng.fromUrlValue(link.location2.point);

			var options : PolylineOptions = new PolylineOptions({
				strokeStyle: new StrokeStyle({
					color: chooseColor(link.state),
					thickness: 6,
					alpha: 1
				})
			});
			options.strokeStyle.color = chooseColor(link.state);

			super([point1, point2], options);

			// Note: the event gets redispatched here from GMapLinksControl
			this.addEventListener(MapMouseEvent.CLICK, this.onClick);

			_link = link;
			_link.addEventListener(mx.events.PropertyChangeEvent.PROPERTY_CHANGE, this.onChange);
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
					new LinkEvent(LinkEvent.ACTIVATE, _link, true)
				);
				return;
			}

			_lastTimeClicked = date.time;
			dispatchEvent(
				new LinkEvent(LinkEvent.SELECTED, _link, true)
			);
		}

		private static function chooseColor(state : Number) : int
		{
			switch (state)
			{
				case Link.STATE_OK:
					return 0x33FF33;

				case Link.STATE_WARNING:
					return 0xFFFF33;

				case Link.STATE_ERROR:
					return 0xFF3333;

				case Link.STATE_UNKNOWN:
				default:
					return 0xCCCCCC;
			}
		}

		protected function onChange(event : PropertyChangeEvent) : void
		{
			trace(event.property + ':' + event.oldValue + ' -> ' + event.newValue);
			switch (event.property)
			{
				case 'id1':
				case 'id2':
					dispatchEvent(
						new LinkEvent(LinkEvent.CHANGE, _link, true)
					);
					break;

				case 'state':
					var options : PolylineOptions = this.getOptions();
					options.strokeStyle.color = chooseColor(link.state);
					this.setOptions(options);
			}
		}

		public function get link() : Link
		{
			return _link;
		}
	}
}
