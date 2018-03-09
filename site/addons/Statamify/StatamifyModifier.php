<?php

namespace Statamic\Addons\Statamify;

use Statamic\Extend\Modifier;

class StatamifyModifier extends Modifier
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

		if (is_array($params)) {

			switch (reset($params)) {
				case 'money': return $this->api('Statamify')->money($value); 
				case 'attrs': return join(explode('|', $value), ', '); 
				break;
			}

		}
		return $value;
	}

}
