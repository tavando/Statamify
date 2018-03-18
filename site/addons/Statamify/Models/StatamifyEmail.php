<?php

namespace Statamic\Addons\Statamify\Models;

use Statamic\API\Email;

class StatamifyEmail
{

	public function __construct($template, $data, $to) {

		$this->template = $template;
		$this->data = $data;
		$this->to = $to;

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