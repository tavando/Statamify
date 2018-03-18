<?php

namespace Statamic\Addons\Statamify;

use Statamic\Extend\Tasks;
use Illuminate\Console\Scheduling\Schedule;

class StatamifyTasks extends Tasks
{
		/**
		 * Define the task schedule
		 *
		 * @param \Illuminate\Console\Scheduling\Schedule $schedule
		 */
		public function schedule(Schedule $schedule)
		{
				$schedule->command('statamify:emails')->everyMinute();
		}
}
