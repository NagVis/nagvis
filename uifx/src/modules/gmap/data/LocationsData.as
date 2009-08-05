package modules.gmap.data
{
	import com.google.maps.LatLng;
	
	import modules.gmap.domain.Location;
	
	import mx.collections.ArrayCollection;

	public class LocationsData extends ArrayCollection
	{
		public function LocationsData(source:Array=null)
		{
			super(source);
		}
		
		public function fill(data : Array):void
		{
			this.source = data;
		}		
		
	}
}