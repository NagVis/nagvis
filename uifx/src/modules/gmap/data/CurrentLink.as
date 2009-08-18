package modules.gmap.data
{
	import modules.gmap.domain.Link;

	public class CurrentLink
	{
		private var _link : Link;

		[Bindable]
		public function set link(value : Link) : void
		{
			if(_link !== value)
				_link = value;
		}

		public function get link() : Link
		{
			return _link;
		}
	}
}
