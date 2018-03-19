<?php

namespace Statamic\Addons\StatamifyStripe;

use Statamic\Extend\Tags;

class StatamifyStripeTags extends Tags
{

	public function index() {

		return [ 
			'active' => $this->getConfig('active'),
			'name' => $this->getConfig('name', 'Stripe'),
			'public_key' => $this->api('StatamifyStripe')->key()
		];

	}

}
