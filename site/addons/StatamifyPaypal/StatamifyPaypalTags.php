<?php

namespace Statamic\Addons\StatamifyPaypal;

use Statamic\Extend\Tags;

class StatamifyPaypalTags extends Tags
{

	public function index() {

		return [ 
			'active' => $this->getConfig('active'),
		];

	}

}
