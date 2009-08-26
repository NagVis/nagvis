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
	import com.google.maps.Map;
	import com.google.maps.overlays.Polyline;
	import com.google.maps.overlays.PolylineOptions;
	import com.google.maps.styles.StrokeStyle;
	
	import flash.events.TimerEvent;
	import flash.utils.Timer;
	
	import modules.gmap.domain.Link;
	
	import mx.core.UIComponent;

	public class GMapLinkControl extends UIComponent
	{
		private var _map : Map;
		private var _link : Link;
		private var _line : Polyline;		
		private var _timer : Timer;

		public function GMapLinkControl()
		{
			super();
			
			_timer = new Timer(250, 1);
			_timer.addEventListener(TimerEvent.TIMER, onTimer);
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

				reinitLine();
			}
		}

		public function get link():Link
		{
			return _link;
		}

		public function set link(value:Link):void
		{
			if(_link !== value)
				_link = value;

			//give the user chanse to performe a doubleclick
			//then display the selection link
			_timer.start();
		}
		
		private function onTimer(event : TimerEvent) : void
		{
			reinitLine();
		}

		override public function set visible(value:Boolean):void
		{
			if(_map && _line)
			{
				if(value)
					_map.addOverlay(_line);
				else
					_map.removeOverlay(_line);
			}
			
			super.visible = value;
		} 

		protected function reinitLine():void
		{
			if(_map)
			{
				if (_line)
				{
					_map.removeOverlay(_line);
					_line = null;
				}
				
				if(_link)
				{
					var point1 : LatLng = LatLng.fromUrlValue(_link.location1.point);
					var point2 : LatLng = LatLng.fromUrlValue(_link.location2.point);

					var options : PolylineOptions = new PolylineOptions();

					if(_link.id && _link.id.length > 0)
					{										
						options.strokeStyle = new StrokeStyle({
							color: 0x002FA7,
							thickness: 2,
							alpha: 1
						});
					}
					else
					{										
						options.strokeStyle = new StrokeStyle({
							color: 0xffffff,
							thickness: 5,
							alpha: 0.7
						});
					}
					
					_line = new Polyline([point1, point2], options);

					if(visible)
						_map.addOverlay(_line);
				}
			}
		}
	}
}
