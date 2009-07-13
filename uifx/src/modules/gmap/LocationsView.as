package modules.gmap
{
	import com.google.maps.LatLng;
	import com.google.maps.Map;
	import com.google.maps.MapMouseEvent;
	import com.google.maps.overlays.Marker;
	import com.google.maps.overlays.MarkerOptions;
	
	import flash.events.EventDispatcher;
	
	import mx.events.CollectionEvent;
	import mx.events.CollectionEventKind;

	[Event(name="selectLocation", type="modules.gmap.LocationsViewEvent")]
	public class LocationsView extends EventDispatcher
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

		public var map : Map;
		public var markers : LocationsCollection;
		public var selectedMarker : Marker = null;
		public var locations : LocationsCollection;
		public var selectedLocation : Location = null;
		public var visible : Boolean = false;

		public function LocationsView(map : Map, locations : LocationsCollection = null)
		{
			this.map = map;
			this.markers = new LocationsCollection();
			this.locations = (locations)? locations : new LocationsCollection();

			this.markers.addEventListener(CollectionEvent.COLLECTION_CHANGE, this.onMarkersChange);
			this.locations.addEventListener(CollectionEvent.COLLECTION_CHANGE, this.onLocationsChange);
		}

		public function showLocations() : void
		{
			this.visible = true;
			this.selectedMarker = null;
			this.selectedLocation = null;

			for each (var marker : Marker in this.markers)
				this.map.addOverlay(marker);

			//showLinks() was here;
		}

;		public function hideLocations() : void
		{
			this.visible = false;
			this.selectedMarker = null;
			this.selectedLocation = null;
			this.map.clearOverlays(); // clears links too
		}

		public function unselectLocation() : void
		{
			this.selectedLocation = null;
		}

		private function onMarkersChange(event : CollectionEvent) : void
		{
			var marker : Marker;

			switch (event.kind)
			{
				case CollectionEventKind.ADD:
					for each (marker in event.items)
					{
						marker.addEventListener(MapMouseEvent.CLICK, this.onSelectLocation);
						if (this.visible)
							this.map.addOverlay(marker);
					}
					break;

				case CollectionEventKind.REMOVE:
					for each (marker in event.items)
					{
						marker.removeEventListener(MapMouseEvent.CLICK, this.onSelectLocation);
						if (this.visible)
							this.map.removeOverlay(marker);
					}
					break;
			}
		}

		protected function onLocationsChange(event : CollectionEvent) : void
		{
			var location : Location;

			switch (event.kind)
			{
				case CollectionEventKind.ADD:
					for each (location in event.items)
					{
						var options : MarkerOptions = new MarkerOptions();
						// options.label = location.label; // conflicts with icon 
						options.icon = new okIcon(); // TODO: change to the actual state in future
						options.iconAlignment = MarkerOptions.ALIGN_HORIZONTAL_CENTER || MarkerOptions.ALIGN_VERTICAL_CENTER;
						options.hasShadow = false;
						this.markers.addItem(new Marker(LatLng.fromUrlValue(location.point), options));
					}
					break;

				case CollectionEventKind.REMOVE:
					for each (location in event.items)
						this.markers.removeItemAt(event.location); // TODO: check if this works correctly
					break;
			}
		}

		protected function onSelectLocation(event : MapMouseEvent) : void
		{
			this.selectedMarker = event.target as Marker;

			var location : Location = this.locations.getItemByLatLng(this.selectedMarker.getLatLng());
			if (location)
			{
				this.selectedLocation = location;
				dispatchEvent(new LocationsViewEvent('selectLocation', this.selectedLocation));
			}
		}
	}
}
