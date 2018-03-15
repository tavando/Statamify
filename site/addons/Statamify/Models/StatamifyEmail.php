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
			->in($attrs['tmpl'])
			->with($this->data)
			->template($this->template);

		return $email->send();

	}

	private function attrs() {

		$attrs = [];

		switch ($this->template) {

			case 'order-new':
				
				$attrs['subject'] = 'Order ' . $this->data['title'] . ' confirmed'; 
				$attrs['tmpl'] = '/site/addons/Statamify/resources/emails';

				break;
			
			default:
				
				break;
		}

		return $attrs;

	}

}