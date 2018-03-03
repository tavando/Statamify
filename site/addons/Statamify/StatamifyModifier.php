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
				case 'attrs': return join(explode('|', $value), ', '); 
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
				$priceFormat = $currency['formatPrice'];

				switch ($priceFormat) {
					case 1: $price = number_format($value, 0, '', ','); break;
					case 2: $price = number_format($value, 0, '', ' '); break;
					case 3: $price = number_format($value, 2, '.', ','); break;
					case 4: $price = number_format($value, 2, '.', ' '); break;
					case 5: $price = number_format($value, 2, ',', ' '); break;
					case 6: $price = number_format($value, 0, '', ''); break;
					case 7: $price = number_format($value, 2, '', '.'); break;
					case 8: $price = number_format($value, 2, '', ','); break;
					
					default:
						$price = number_format($value, 2, '.', ' '); break;
						break;
				}

				return str_replace('[symbol]', $currency['symbol'], str_replace('[price]', $price, $currency['format']));

			}

		}

	}

}
