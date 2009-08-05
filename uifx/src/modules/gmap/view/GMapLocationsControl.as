package modules.gmap.view
{
	import com.google.maps.LatLng;
	import com.google.maps.Map;
	import com.google.maps.overlays.Marker;
	import com.google.maps.overlays.MarkerOptions;
	
	import modules.gmap.data.LocationsData;
	import modules.gmap.domain.Location;
	
	import mx.containers.VBox;
	import mx.controls.Alert;

	public class GMapLocationsControl extends VBox
	{ 
		[Embed(source="modules/gmap/img/std_small_ok.png")]
		protected var okIcon : Class;
		
		[Embed(source="modules/gmap/img/std_small_warning.png")]
		protected var warningIcon : Class;
		
		[Embed(source="modules/gmap/img/std_small_error.png")]
		protected var errorIcon : Class;
		
		[Embed(source="modules/gmap/img/std_small_critical.png")]
		protected var criticalIcon : Class;
		
		[Embed(source="modules/gmap/img/std_small_unknown.png")]
		protected var unknownIcon : Class;		
		
		private var _dataProvider : LocationsData;
		private var _map : Map;
		private var _signs : Array;
		
		public function GMapLocationsControl()
		{
			super();
		}
		
		public function get map():Map
		{
			return _map;
		}
		
		public function set map(value : Map):void
		{
			if(_map !== value)
			{
				_map = value;
				
				reinitMarkers();
				
				if(visible)
					showMarkers();
			}
		} 
				
		public function get dataProvider():LocationsData
		{
			return _dataProvider;
		}
		
		public function set dataProvider(value : LocationsData):void
		{
			if(_dataProvider !== value)
			{
				if(visible)
					hideMarkers();
					
				_dataProvider = value;
				reinitMarkers();
				
				if(visible)
					showMarkers();	
			}
		}
		
		public override function set visible(value:Boolean):void
		{
			if(super.visible != value)
			{
				if(value)
					showMarkers();
				else
					hideMarkers();
				
				super.visible = value;
			}
		}
				
		protected function reinitMarkers():void
		{
			if(_map)
			{
				_signs = [];
				for each (var l : Location in _dataProvider)
				{
					var options : MarkerOptions = new MarkerOptions();
					options.icon = new okIcon();
					options.iconAlignment = MarkerOptions.ALIGN_HORIZONTAL_CENTER || MarkerOptions.ALIGN_VERTICAL_CENTER;
					options.hasShadow = false;
					
					var point : LatLng = LatLng.fromUrlValue(l.point);
								
					var m : Marker = new Marker(point, options);
					
					_signs.push(
						{
							marker : m,
							location : l
						}
					);	
				}
			}
		}
		
		protected function showMarkers():void
		{
			if(_map)
			{
				for each (var s : Object in _signs)
					_map.addOverlay(s.marker);
			}
		}
		
		protected function hideMarkers():void
		{
			if(_map)
			{ 
				for each (var s : Object in _signs)
					_map.removeOverlay(s.marker);
			}			
		}
	}
}