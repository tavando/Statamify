<?php

namespace Statamic\Addons\Statamify\Models;

use Statamic\API\Config;
use Statamic\API\Email;
use Statamic\API\File;
use Statamic\Addons\Statamify\Statamify;
use Statamic\API\Storage;

class Emails
{

	public function __construct($template, $data, $to = null)
	{

		$this->template = $template;
		$this->data = Statamify::wrapGlobals($data);
		$this->to = !$to ? Statamify::config('owner_email') : $to;

	}

	private function attrs()
	{

		$attrs = [];

		switch ($this->template) {

			case 'admin-order-new':
				
				$attrs['subject'] = 'New Order ' . $this->data['title'] . ' has been placed'; 

				break;

			case 'order-new':
				
				$attrs['subject'] = 'Order ' . $this->data['title'] . ' confirmed'; 

				break;

			case 'order-status':
				
				$attrs['subject'] = 'Order ' . $this->data['title'] . ' is now ' . strtolower(strip_tags($this->data['listing_status'])); 

				break;

			case 'order-refund':
				
				$attrs['subject'] = 'Order ' . $this->data['title'] . ' has been refunded'; 

				break;
			
			default:

				$attrs['subject'] = 'New message from the store'; 
				
				break;
		}

		return $attrs;

	}

	public function create()
	{

		// Create Email entry - it will be sent later with Cron

		$email_id = date('Y-m-d_H-i-s') . '.' . $this->template;
		$email_data = [
			'title' => $this->template, 
			'data' => serialize($this->data), 
			'email' => $this->to,
			'id' => $email_id,
			'date' => date('Y-m-d H:i:s')
		];

		Storage::putYAML('statamify/emails/' . $email_id, $email_data);

	}

	public function send()
	{

		$email = Email::create();
		$attrs = $this->attrs();

		if (File::disk('theme')->exists('emails/' . $this->template . '.html')) {

			$path = '/site/themes/' . Config::get('theming.theme') . '/emails';

		} else {

			$path = '/site/addons/Statamify/resources/emails';

		}

		$email
			->to($this->to)
			->subject($attrs['subject'])
			->in($path)
			->with($this->data)
			->template($this->template);

		return $email->send();

	}

	public function sendEmail()
	{

		if (env('STATAMIFY_QUEUE_EMAILS', false)) {

			$this->create();

		} else {

			$this->send();

		}

	}

}