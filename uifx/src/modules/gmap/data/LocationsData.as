package modules.gmap.data
{
	import com.google.maps.LatLng;
	
	import modules.gmap.domain.Location;
	
	import mx.collections.ArrayCollection;
	import mx.controls.Alert;

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
		
		public function getItemById(id : String) : Location
		{
			for each(var location : Location in this)
				if (location.id == id)
					return location;

			return null;
		}

		public function getItemByLatLng(coords : LatLng) : Location
		{
			for each(var location : Location in this)
				if (LatLng.fromUrlValue(location.point).lat() == coords.lat()
					&& LatLng.fromUrlValue(location.point).lng() == coords.lng())
				{
					return location;
				}

			return null;
		}

		public function addUpdateItem(item : Location) : void
		{
			var location : Location = this.getItemById(item.id);

			if (location == null)
				this.addItem(item);
			else
				location.update(item);
		}

		public function addUpdateItems(items : Array) : void
		{
			for each(var location : Location in items)
				this.addUpdateItem(location);
		}

		public function removeItemById(id:String):void
		{
			removeItemAt(getItemIndex(getItemById(id)));
		}	
		
		public function virginize():void
		{
			for each(var location : Location in this)
				location.state = Location.STATE_OK;		
		}		
	}
}