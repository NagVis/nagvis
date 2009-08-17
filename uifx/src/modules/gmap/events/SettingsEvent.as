package modules.gmap.events
{
	import flash.events.Event;

	import modules.gmap.domain.Settings;

	public class SettingsEvent extends Event
	{
		public static const CHANGE : String = "SettingsChanged";
		public static const RELOAD : String = "GoogleMapsKeyChanged";

		public var settings : Settings;

		public function SettingsEvent(type : String, settings : Settings,
			bubbles : Boolean = true, cancelable : Boolean = false)
		{
			super(type, bubbles, cancelable);
			this.settings = settings;
		}
	}
}
