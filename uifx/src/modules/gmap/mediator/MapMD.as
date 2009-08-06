package modules.gmap.mediator
{
	import com.google.maps.LatLng;
	import com.google.maps.controls.ZoomControl;
	
	import flash.events.IEventDispatcher;
	
	import modules.gmap.domain.Viewpoint;
	import modules.gmap.view.controls.GMapControl;

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
		}
		
		public function focusViewpoint(where : Viewpoint):void
		{
			_view.map.setCenter(LatLng.fromUrlValue(where.center));
			_view.map.setZoom(where.zoom);
		}
		
		public function extractViewpoint(name : String):Viewpoint
		{
			var vp : Viewpoint = new Viewpoint;
			vp.label = name;
			vp.center = _view.map.getCenter().toUrlValue(16);
			vp.zoom = _view.map.getZoom();
			
			return vp;
		}
		
		public function onModeChanged(oldMode:int, newMode:int):void
		{
			if(MainMD.MODE_LOCATION_SEARCH == oldMode)
				_view.locationsExtControl.visible = false;
				
			if(MainMD.MODE_LOCATION_SEARCH == newMode)
				_view.locationsExtControl.visible = true;
		}
	}
}