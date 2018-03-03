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
				case 'money': return $this->money($value, $params, $context); 
				break;
			}

		}
		return $value;
	}

	private function money($value, $params, $context) {

		$currencies = $this->getConfig('currency');
		
		if (count($currencies)) {

			$key = array_search('1', array_column($currencies, 'rate'));

			if (!is_bool($key)) {

				$currency = $currencies[$key];
				return str_replace('[symbol]', $currency['symbol'], str_replace('[price]', $value, $currency['format']));

			}

		}

	}

}
