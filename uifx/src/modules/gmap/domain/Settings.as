package modules.gmap.domain
{
	[Bindable]
	[RemoteClass(alias="Settings")]
	public class Settings
	{
		public var googleMapsKey : String;
		public var defaultLocationAction : String;
		public var openLinksInNewWindow : Boolean;
		public var hosturl : String;
		public var hostgroupurl : String;
		public var serviceurl : String;
		public var servicegroupurl : String;
		public var mapurl : String;

		public function Settings(googleMapsKey : String = "",
			defaultLocationAction : String = "",
			openLinksInNewWindow : Boolean = false,
			hosturl : String = "",
			hostgroupurl : String = "",
			serviceurl : String = "",
			servicegroupurl : String = "",
			mapurl : String = "") : void
		{
			this.googleMapsKey = googleMapsKey;
			this.defaultLocationAction = defaultLocationAction;
			this.openLinksInNewWindow = openLinksInNewWindow;
			this.hosturl = hosturl;
			this.hostgroupurl = hostgroupurl;
			this.serviceurl = serviceurl;
			this.servicegroupurl = servicegroupurl;
			this.mapurl = mapurl;
		}
	}
}
