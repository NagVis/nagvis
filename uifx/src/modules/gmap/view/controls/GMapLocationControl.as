package modules.gmap.view.controls
{
	import com.google.maps.LatLng;
	import com.google.maps.Map;
	import com.google.maps.overlays.Marker;
	import com.google.maps.overlays.MarkerOptions;
	
	import modules.gmap.domain.Location;
	
	import mx.core.UIComponent;
	
	public class GMapLocationControl extends UIComponent
	{
		[Embed(source="modules/gmap/img/pin.png")]
		protected var _icon : Class;
		
		private var _location : Location;
		private var _map : Map;
		private var _marker : Marker;
		
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
		public function set map(value : Map):void
		{
			if(_map !== value)
			{
				_map = value;
				
				reinitMarker();
			}
		}
		
		public function get location():Location
		{
			return _location;
		}
		
		public function set location(value:Location):void
		{
			if(_location !== value)
			{		
				_location = value;
				
				reinitMarker();
			}	
		}
		
		protected function reinitMarker():void
		{
			if(_map)
			{
				if(_marker)
					_map.removeOverlay(_marker);

				if(_location)
				{		
					var  mo:MarkerOptions = new MarkerOptions();
					mo.icon = new _icon();
					mo.iconAlignment = MarkerOptions.ALIGN_RIGHT | MarkerOptions.ALIGN_BOTTOM;
					
					_marker = new Marker(
						LatLng.fromUrlValue(_location.point), mo
					);
					
					_map.addOverlay(_marker);
				}
			}
		}

	}
}