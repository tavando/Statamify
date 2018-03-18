<?php

namespace Statamic\Addons\Statamify\Commands;

use Statamic\Extend\Command;
use Statamic\API\Entry;
use Statamic\API\Stache;
use Statamic\Addons\Statamify\Models\StatamifyEmail as Email;

class EmailsCommand extends Command
{
	/**
	 * The name and signature of the console command.
	 *
	 * @var string
	 */
	protected $signature = 'statamify:emails';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = '';

	/**
	 * Create a new command instance.
	 */
	public function __construct()
	{
		parent::__construct();
	}

	/**
	 * Execute the console command.
	 *
	 * @return mixed
	 */
	public function handle()
	{
		
		$emails = Entry::whereCollection('emails');

		if ($emails) {

			foreach ($emails as $email) {
				
				$data = $email->toArray();

				$e = new Email($data['title'], unserialize($data['data']), $data['email']);
				$e->send();

				$email->delete();

			}

			Stache::update();

		}

	}
}
