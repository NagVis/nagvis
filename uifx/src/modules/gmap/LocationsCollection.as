package modules.gmap
{
	import com.google.maps.LatLng;
	
	import mx.collections.ArrayCollection;

	public class LocationsCollection extends ArrayCollection
	{
		public function LocationsCollection(source : Array = null)
		{
			super(source);
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
	}
}