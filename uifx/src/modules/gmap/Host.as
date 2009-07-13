package modules.gmap
{
	[Bindable]
	[RemoteClass(alias="Host")]
	public class Host
	{
		public var id : String;
		public var name : String;
		public var address : String;
		public var selected : Boolean;
	}
}
