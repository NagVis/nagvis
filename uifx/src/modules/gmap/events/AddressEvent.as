package modules.gmap.events
{
	import flash.events.Event;

	public class AddressEvent extends Event
	{
		public static const LOCATE : String = "AddressLocate";
		
		public var address:String;
		
		public function AddressEvent(type:String, address:String, bubbles:Boolean = true, cancelable:Boolean = false)
		{
			super(type, bubbles, cancelable);
			this.address = address;
		}
		
	}
}