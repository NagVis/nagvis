package modules.gmap.view.controls
{
	import com.google.maps.Map;
	
	import flash.events.Event;
	
	import modules.gmap.data.LocationsData;
	import modules.gmap.domain.Location;
	import modules.gmap.events.LocationEvent;
	
	import mx.containers.VBox;
	import mx.events.CollectionEvent;
	import mx.events.CollectionEventKind;

	public class GMapLocationsControl extends VBox
	{
		private var _dataProvider : LocationsData;
		private var _map : Map;
		private var _markers : Array;
		
		public function GMapLocationsControl()
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
				_dataProvider = value;
				_dataProvider.addEventListener(CollectionEvent.COLLECTION_CHANGE, onDataProviderChanged);
				
				reinitMarkers();
			}
		}
		
		protected function onDataProviderChanged(event : CollectionEvent):void
		{
			if(event.kind == CollectionEventKind.RESET)
				reinitMarkers();
			
			//TODO: handle items addition
			//TODO: handle items removal
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
		
		// Marker is not an UI component, so
		// we need to redispatch his events to get them into Mate.
		protected function redispatchMarkerEvent(event : Event):void
		{
			dispatchEvent(event);
		}
				
		protected function reinitMarkers():void
		{
			if(visible)
				hideMarkers();

			if(_map)
			{
				_markers = [];
				for each (var l : Location in _dataProvider)
				{
					var m : LocationMarker = new LocationMarker(l);
					m.addEventListener(LocationEvent.SELECTED, redispatchMarkerEvent);
					_markers.push(m);	
				}
			}
			
			if(visible)
				showMarkers();				

		}
		
		protected function showMarkers():void
		{
			if(_map)
			{
				for each (var m : LocationMarker in _markers)
					_map.addOverlay(m);
			}
		}
		
		protected function hideMarkers():void
		{
			if(_map)
			{ 
				for each (var m : LocationMarker in _markers)
					_map.removeOverlay(m);
			}			
		}
	}
}