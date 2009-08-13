package modules.gmap.events
{
	import flash.events.Event;
	
	public class GoogleMapsKeyEvent extends Event
	{
		public static const SAVE : String = "GoogleMapKeySave";

		public var key : String;

		public function GoogleMapsKeyEvent(type : String, key : String = "",
			bubbles : Boolean = true, cancelable : Boolean = false)
		{
			super(type, bubbles, cancelable);
			this.key = key;
		}
	}
}