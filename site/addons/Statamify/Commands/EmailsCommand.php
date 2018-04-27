<?php

namespace Statamic\Addons\Statamify\Commands;

use Statamic\Extend\Command; 
use Statamic\Addons\Statamify\Models\Emails;
use Statamic\API\Entry;
use Statamic\API\File;
use Statamic\API\Folder;
use Statamic\API\Storage;

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
		
		$emails = Folder::getFiles('site/storage/statamify/emails');

		if ($emails) {

			foreach ($emails as $email) {
				
				$data = Storage::getYAML(str_replace('site/storage/', '', $email));

				$e = new Emails($data['title'], unserialize($data['data']), $data['email']);
				$e->send();

				File::delete($email);

			}

		}

	}
}
