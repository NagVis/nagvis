package modules.gmap.mediator
{
	import com.google.maps.LatLng;
	import com.google.maps.Map;
	import com.google.maps.controls.ZoomControl;
	
	import flash.events.IEventDispatcher;
	
	import modules.gmap.domain.Viewpoint;
	import modules.gmap.events.ViewpointEvent;

	public class MapMD
	{
		private var view : Map;
		private var dispatcher : IEventDispatcher;
		
		public function MapMD(view : Map, dispatcher : IEventDispatcher)
		{
			this.view = view;
			this.dispatcher = dispatcher;
		}
		
		public function init():void
		{
		  	view.enableScrollWheelZoom();
			view.enableContinuousZoom();
			view.addControl(new ZoomControl());
			view.setZoom(2);			
		}
		
		public function focusViewpoint(where : Viewpoint):void
		{
			view.setCenter(LatLng.fromUrlValue(where.center));
			view.setZoom(where.zoom);
		}
		
		public function extractViewpoint(name : String):Viewpoint
		{
			var vp : Viewpoint = new Viewpoint;
			vp.label = name;
			vp.center = view.getCenter().toUrlValue(16);
			vp.zoom = view.getZoom();
			
			return vp;
		}
	}
}