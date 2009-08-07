package modules.gmap.view.controls
{
	import com.google.maps.LatLng;
	import com.google.maps.MapMouseEvent;
	import com.google.maps.overlays.Marker;
	import com.google.maps.overlays.MarkerOptions;
	
	import modules.gmap.domain.Location;
	import modules.gmap.events.LocationEvent;

	public class LocationMarker extends Marker
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
				
		private var _location : Location;
		
		public function LocationMarker(location:Location)
		{	
			var point : LatLng = LatLng.fromUrlValue(location.point);
			
			var options : MarkerOptions;
			
			if(location.id && location.id.length > 0)
			{
				options = new MarkerOptions();
				options.icon = new okIcon();
				options.iconAlignment = MarkerOptions.ALIGN_HORIZONTAL_CENTER || MarkerOptions.ALIGN_VERTICAL_CENTER;
				options.hasShadow = false;
			}
			
			super(point, options);
			
			_location = location;
			
			this.addEventListener(MapMouseEvent.CLICK, this.onClick);
		}
		
		protected function onClick(event : *):void
		{
			dispatchEvent(
				new LocationEvent(LocationEvent.SELECTED, _location, true)
			);
		}
		
		public function get location():Location
		{
			return _location;
		}
		
	}
}