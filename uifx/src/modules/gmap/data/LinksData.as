package modules.gmap.data
{
	import modules.gmap.domain.Link;
	
	import mx.collections.ArrayCollection;
	import mx.events.CollectionEvent;

	public class LinksData extends ArrayCollection
	{
		public function LinksData(source:Array=null)
		{
			super(source);
		}
		
		public function fill(data : Array, locations:LocationsData) : void
		{
			this.source = data;
			
			for each(var link:Link in this)
			{
				link.location1 = locations.getItemById(link.id1);
				link.location2 = locations.getItemById(link.id2);
			}
		}
	}
}