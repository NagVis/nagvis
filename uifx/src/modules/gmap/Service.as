package modules.gmap
{
	[Bindable]
	[RemoteClass(alias="Service")]
	public class Service
	{
		public var id : String;
		public var description : String;
		public var host : String;
		public var selected : Boolean;
	}
}
