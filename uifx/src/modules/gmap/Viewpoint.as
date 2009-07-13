package modules.gmap
{
	[Bindable]
	[RemoteClass(alias="Viewpoint")]
	public class Viewpoint
	{
		public var label : String;
		public var center : String;
		public var zoom : int;
	}
}
