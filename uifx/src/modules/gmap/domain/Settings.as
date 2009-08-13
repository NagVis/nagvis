package modules.gmap.domain
{
	[Bindable]
	[RemoteClass(alias="Settings")]
	public class Settings
	{
		public var googleMapsKey : String;
		public var defaultLocationAction : String;
		public var openLinksInNewWindow : Boolean;

		public function Settings(googleMapsKey : String = "",
			defaultLocationAction : String = "",
			openLinksInNewWindow : Boolean = false) : void
		{
			this.googleMapsKey = googleMapsKey;
			this.defaultLocationAction = defaultLocationAction;
			this.openLinksInNewWindow = openLinksInNewWindow;
		}
	}
}
