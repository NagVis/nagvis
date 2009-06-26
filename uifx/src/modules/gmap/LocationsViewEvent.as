package modules.gmap
{
	import flash.events.Event;

	public class LocationsViewEvent extends Event
	{
		public static const SELECT_LOCATION : String = "selectLocation";
		public var location : Location;

		public function LocationsViewEvent(type : String, location : Location = null)
		{
			super(type);

			this.location = location;
		}

        override public function clone() : Event
        {
			return new LocationsViewEvent(type, this.location);
        }
	}
}
