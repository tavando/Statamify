<?php

namespace Statamic\Addons\Statamify\Modifiers;

use Statamic\Extend\Modifier;
use Statamic\Addons\Statamify\Statamify;

class LocationModifier extends Modifier
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

		$countries = Statamify::location();
		$countries = reset($countries);

		if (!array_get($params, 0)) {

			return $countries[$value];

		} else {

			$country = array_get($context, array_get($params, 0));

			$regions = Statamify::location('regions');
			$regions = reset($regions);

			if (isset($regions[$country])) {

				return $regions[$country][$value];

			} else {

				return $value;

			}

		}
		
	}

}
