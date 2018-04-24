<?php

namespace Statamic\Addons\Statamify\Modifiers;

use Statamic\Extend\Modifier;
use Statamic\Addons\Statamify\Statamify;

class MoneyModifier extends Modifier
{
	/**
	 * Modify a value
	 *
	 * @param mixed  $value    The value to be modified
	 * @param array  $params   Any parameters used in the modifier
	 * @param array  $context  Contextual values
	 * @return mixed
	 */
	public function index($value, $params, $context) {

		return Statamify::money($value); 
		
	}

}
