package modules.gmap.mediator
{
	import flash.events.IEventDispatcher;
	import flash.external.ExternalInterface;
	import flash.system.Security;
	
	import modules.gmap.domain.Location;
	import modules.gmap.domain.Settings;
	import modules.gmap.events.ModeEvent;
	import modules.gmap.view.MainView; 
	
	public class MainMD
	{
		public static const MODE_DEFAULT : int = 0;
		public static const MODE_LOCATION_EDIT : int = 1;
		public static const MODE_LOCATION_SEARCH : int = 2;
		
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
			if(_mode !== value)
			{
				_dispatcher.dispatchEvent(
					new ModeEvent(ModeEvent.CHANGED, _mode, value)
				);
				
				_mode = value;
			}
		}
		
		public function reconsiderMode():void
		{
			switch(_view.ebg.current)
			{
				case _view.locationBox:
					mode = MODE_LOCATION_EDIT;
					break;
				case _view.searchBox:
					mode = MODE_LOCATION_SEARCH;
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
					//Alert.show(location.label);
					break;
				case MODE_LOCATION_EDIT:
					//_view.locationBox.update(location);
					break;
				case MODE_LOCATION_SEARCH:
					if(location.id && location.id.length > 0)
						break;
					//_view.locationBox.update(location);
					_view.locationBox.setCurrentState('right-expanded'); 
					break;
			}
		}
		
		public function activateLocation(location:Location, settings:Settings):void
		{
			var slices1:Array = location.action.split(':', 2);
			switch(slices1[0])
			{
				case 'nagios':
					gotoURL('/nagios', settings.openLinksInNewWindow);
					return;
				case 'nagvis':
					gotoURL('/nagvis', settings.openLinksInNewWindow);
					return;
				case 'http':
				case 'https':
					gotoURL(location.action, settings.openLinksInNewWindow);
					return;
			}				

			var slices2:Array = settings.defaultLocationAction.split(':', 2);
			switch(slices2[0])
			{
				case 'nagios':
					gotoURL('/nagios', settings.openLinksInNewWindow);
					return;
			}				
		}
		
		public function gotoURL(url:String, newWindow:Boolean):void
		{
			if(newWindow)
				ExternalInterface.call('window.open("' + url + '")');
			else
				ExternalInterface.call('window.location.assign("' + url + '")');
		}
	}
}