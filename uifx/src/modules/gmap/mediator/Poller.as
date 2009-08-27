/*****************************************************************************
 *
 * Copyright (C) 2009 NagVis Project
 *
 * License:
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2 as
 * published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 675 Mass Ave, Cambridge, MA 02139, USA.
 *
 *****************************************************************************/

package modules.gmap.mediator
{
	import flash.events.Event;
	import flash.events.IEventDispatcher;
	import flash.events.TimerEvent;
	import flash.utils.Timer;

	public class Poller
	{
		public static const TIMEOUT : String = "PollerTimeout";

		private var _semaphor : int = 2;
		private var _dispatcher : IEventDispatcher;
		private var _timer : Timer;

		public function Poller(dispatcher : IEventDispatcher)
		{
			_dispatcher = dispatcher;

			_timer = new Timer(30000);
			_timer.addEventListener(TimerEvent.TIMER, onTimer);
		}

		public function resourceReady():void
		{
			_semaphor--;

			if (_semaphor === 0)
				_timer.start();
		}

		protected function onTimer(event : TimerEvent) : void
		{
			_dispatcher.dispatchEvent(new Event(TIMEOUT));
		}
	}
}
