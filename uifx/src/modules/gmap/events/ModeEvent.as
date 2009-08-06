package modules.gmap.events
{
	import flash.events.Event;

	public class ModeEvent extends Event
	{
		public static const CHANGED : String = "ModeCahnged";
		
		public var oldMode:int;
		public var newMode:int;
		
		public function ModeEvent(type:String, oldMode:int, newMode:int, bubbles:Boolean = true, cancelable:Boolean = false)
		{
			super(type, bubbles, cancelable);
			this.oldMode = oldMode;
			this.newMode = newMode;
		}
		
	}
}