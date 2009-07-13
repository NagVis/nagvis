package modules.gmap
{
	import flash.events.Event;

	public class LocationEvent extends Event
	{
		public static const CHANGE : String = "change";
		public var location : Location;

		public function LocationEvent(type : String, location : Location = null)
		{
			super(type);

			this.location = location;
		}

        override public function clone() : Event
        {
			return new LocationEvent(type, this.location);
        }
	}
}
