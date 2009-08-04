package modules.gmap.mediator
{
	import flash.events.IEventDispatcher;
	import flash.system.Security;
	
	import modules.gmap.view.GMapControl;
	import modules.gmap.view.MainView;
	
	import mx.utils.StringUtil; 
	
	public class MainMD
	{
		private var view : MainView;
		private var dispatcher : IEventDispatcher;
		
		public function MainMD(view : MainView, dispatcher : IEventDispatcher)
		{
			this.view = view;
			this.dispatcher = dispatcher;
		}
		
		public function init():void
		{
			Security.allowInsecureDomain("*");
		}
		
		public function showMap(key : String) : void
		{
			var map : GMapControl = new GMapControl();
			map.key = StringUtil.trim(key);
			view.mapContainer.addChild(map);
		}
	}
}