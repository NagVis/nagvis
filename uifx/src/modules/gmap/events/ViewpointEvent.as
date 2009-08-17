package modules.gmap.events
{
	import flash.events.Event;

	import modules.gmap.domain.Viewpoint;

	public class ViewpointEvent extends Event
	{
		public static const SELECTED : String = "ViewpointSelected";
		public static const CREATE : String = "ViewpointCreate";
		public static const CREATED : String = "ViewpointCreated";
		public static const SAVE : String = "ViewpointSave";

		public var viewpoint : Viewpoint;

		public function ViewpointEvent(type : String, viewpoint : Viewpoint = null, bubbles : Boolean = true, cancelable : Boolean=false)
		{
			super(type, bubbles, cancelable);
			this.viewpoint = viewpoint;
		}
	}
}
