package modules.gmap.mediator
{
	import flash.events.Event;
	import flash.events.IEventDispatcher;
	import flash.events.TimerEvent;
	import flash.utils.Timer;

	public class Poller
	{
		public static const TIMEOUT : String = "PollerTimeout";

		private var _semaphor : int = 1;
		private var _dispatcher : IEventDispatcher;
		private var _timer : Timer;

		public function Poller(dispatcher : IEventDispatcher)
		{
			_dispatcher = dispatcher;
			
			_timer = new Timer(5000);
			_timer.addEventListener(TimerEvent.TIMER, onTimer);
		}
		
		public function resourceReady():void
		{
			_semaphor--;
			
			if(_semaphor === 0)
				_timer.start();
		}

		protected function onTimer(event : TimerEvent) : void
		{
			_dispatcher.dispatchEvent(new Event(TIMEOUT));
		}
	}
}