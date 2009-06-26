package modules.gmap
{
	import flash.events.MouseEvent;
	
	import lib.ui.EdgeBox;
	
	import mx.controls.Image;
	import mx.events.FlexEvent;

	public class Dialog extends EdgeBox
	{
		public var logoImage : Image = new Image();
		public var closeImage : Image = new Image();

		[Bindable] [Embed(source="modules/gmap/img/right1.gif")]
		private var closeClass : Class;

		private function showOnClick(event : *) : void
		{
			setCurrentState("right-expanded");
		}

		private function hideOnClick(event : *) : void
		{
			setCurrentState("right-contracted");
		}

		private function addImages(event : *) : void
		{
			logoImage.setStyle("top", 5);
			logoImage.setStyle("left", 5);
			logoImage.width=30;
			logoImage.height=30;
			logoImage.scaleContent = true;
			logoImage.addEventListener(MouseEvent.CLICK, showOnClick);
			this.addChild(logoImage);

			closeImage.setStyle("top", 10);
			closeImage.setStyle("right", 20);
			closeImage.width = 20;
			closeImage.height = 20;
			closeImage.scaleContent = true;
			closeImage.source = new closeClass();
			closeImage.addEventListener(MouseEvent.CLICK, hideOnClick);
			this.addChild(closeImage);
		}

		public function Dialog()
		{
			super();

			this.addEventListener(FlexEvent.CREATION_COMPLETE, addImages);
		}

		public function set logo(value:Class) : void
		{
			logoImage.source = new value();
		}
	}
}