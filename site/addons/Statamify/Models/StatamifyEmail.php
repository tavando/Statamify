<?php

namespace Statamic\Addons\Statamify\Models;

use Statamic\API\Email;
use Statamic\API\Entry;
use Statamic\API\Stache;

class StatamifyEmail
{

	public function __construct($template, $data, $to) {

		$this->template = $template;
		$this->data = $data;
		$this->to = $to;

	}

	public function create() {

		// Create Email entry - it will be sent later with Cron
		
		$email = Entry::create(date('Y-m-d-H-i-s') . '_' . $this->template)
		->collection('emails')
		->with(['title' => $this->template, 'data' => serialize($this->data), 'email' => $this->to])
		->published(true)
		->get();

		$email->save();

		Stache::update();

	}

	public function send() {

		$email = Email::create();
		$attrs = $this->attrs();

		$email
			->to($this->to)
			->subject($attrs['subject'])
			->in('/site/addons/Statamify/resources/emails')
			->with($this->data)
			->template($this->template);

		return $email->send();

	}

	private function attrs() {

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
			
			default:
				
				break;
		}

		return $attrs;

	}

}