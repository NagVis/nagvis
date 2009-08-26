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

package modules.gmap.mediator
{
	import com.google.maps.LatLng;
	import com.google.maps.MapAction;
	import com.google.maps.controls.ZoomControl;
	
	import flash.events.IEventDispatcher;
	
	import modules.gmap.domain.Link;
	import modules.gmap.domain.Viewpoint;
	import modules.gmap.view.controls.GMapControl;
	
	import mx.controls.Alert;

	public class MapMD
	{
		private var _view : GMapControl;
		private var _dispatcher : IEventDispatcher;

		public function MapMD(view : GMapControl, dispatcher : IEventDispatcher)
		{
			this._view = view;
			this._dispatcher = dispatcher;
		}

		public function showMap(key : String) : void
		{
			_view.createMap(key);
		}

		public function initMap():void
		{
		  	_view.map.enableScrollWheelZoom();
			_view.map.enableContinuousZoom();
			_view.map.addControl(new ZoomControl());
			_view.map.setZoom(2);
			_view.map.setDoubleClickMode(MapAction.ACTION_NOTHING);

			if (!_view.map.isLoaded())
			{
				Alert.show('Error initializing Google Maps.\n\n'
					+ 'Accessing the free Google Maps API requires the specification of an API key linked to a base URL.\n'
					+ 'You can obtain a free API key from Google at http://www.google.com/apis/maps/signup.html',
					'Error');
			}
		}

		public function focusViewpoint(where : Viewpoint) : void
		{
			_view.map.setCenter(LatLng.fromUrlValue(where.center));
			_view.map.setZoom(where.zoom);
		}

		public function extractViewpoint(name : String) : Viewpoint
		{
			var vp : Viewpoint = new Viewpoint;
			vp.label = name;
			vp.center = _view.map.getCenter().toUrlValue(16);
			vp.zoom = _view.map.getZoom();

			return vp;
		}

		public function onModeChanged(oldMode : int, newMode : int) : void
		{
			if (MainMD.MODE_LOCATION_SEARCH == oldMode)
				_view.locationsExtControl.visible = false;

			if (MainMD.MODE_LOCATION_SEARCH == newMode)
				_view.locationsExtControl.visible = true;
				
			if(MainMD.MODE_LINK_EDIT == oldMode)
				_view.linkTryOnControl.visible = false;

			if(MainMD.MODE_LINK_EDIT == newMode)
				_view.linkTryOnControl.visible = true;
		}
		
		public function tryOnLink(link:Link):void
		{
			_view.linkTryOnControl.link = link;		
		}
	}
}
