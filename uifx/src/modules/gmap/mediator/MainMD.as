package modules.gmap.mediator
{
	import flash.events.IEventDispatcher;
	import flash.system.Security;
	
	import modules.gmap.domain.Location;
	import modules.gmap.view.MainView;
	
	import mx.controls.Alert; 
	
	public class MainMD
	{
		public static const MODE_DEFAULT : int = 0;
		public static const MODE_LOCATION_EDIT : int = 1;
		
		private var _mode:int = 0;
		 
		private var _view : MainView;
		private var _dispatcher : IEventDispatcher;
		
		public function MainMD(view : MainView, dispatcher : IEventDispatcher)
		{
			this._view = view;
			this._dispatcher = dispatcher;
		}
		
		public function init():void
		{
			Security.allowInsecureDomain("*");
		}
		
		public function get mode():int
		{
			return _mode;
		}
		
		public function set mode(value:int):void
		{
			_mode = value;
		}
		
		public function reconsiderMode():void
		{
			switch(_view.ebg.current)
			{
				case _view.locationBox:
					mode = MODE_LOCATION_EDIT;
					break;
				default:
					mode = MODE_DEFAULT;
			}
		}
		
		public function selectLocation(location : Location):void
		{
			switch(_mode)
			{
				case MODE_DEFAULT:
					Alert.show(location.label);
					break;
				case MODE_LOCATION_EDIT:
					_view.locationBox.update(location);
					break; 
			}
		}	
	}
}