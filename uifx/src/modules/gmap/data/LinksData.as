package modules.gmap.data
{
	import modules.gmap.domain.Link;
	
	import mx.collections.ArrayCollection;
	import mx.events.CollectionEvent;

	public class LinksData extends ArrayCollection
	{
		private var _locations:LocationsData;
		
		public function LinksData(locations:LocationsData, source:Array=null)
		{
			super(source);
			
			_locations = locations;
			_locations.addEventListener(CollectionEvent.COLLECTION_CHANGE, onLocationsChange);
		}
		
		protected function onLocationsChange(event:CollectionEvent):void
		{
			//TODO: do something
		}
		
		public function fill(data : Array) : void
		{
			this.source = data;
			
			for each(var link:Link in this)
			{
				link.location1 = _locations.getItemById(link.id1);
				link.location2 = _locations.getItemById(link.id2);
			}
		}
	}
}