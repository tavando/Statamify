<?php

namespace Statamic\Addons\Statamify;

use Statamic\Extend\Tags;
use Statamic\API\URL;
use Statamic\API\User;
use Statamic\API\Entry;

class StatamifyTags extends Tags
{

	public function index()
	{
		
		return '';

	}

	public function url()
	{
		
		$url = URL::getCurrent();
		$uri = explode('?', $_SERVER['REQUEST_URI']);

		if (isset($uri[1])) {

			parse_str($uri[1], $query);

		} else {

			$query = [];

		}

		$type = $this->get('type') ?: 'replace';
		$key = $this->get('key');
		$value = $this->get('value');
		$logic = $this->get('logic') ?: 'OR';
		$arg = $this->get('arg') ? $this->get('arg') : false;

		switch ($type) {

			case 'get':

			if (isset($query[$key])) {

				if (!$value) {

					return $query[$key];

				} else {

					$fields = explode(';', $query[$key]);
					$arg_index = 0;

					foreach ($fields as $k => $field) {

						$field = explode(':', $field);
						$value = str_replace('@', ':', $value);			

						$condition = 	$field[0] == $value 
						|| join($field, ':') == $value 
						|| (in_array(@explode(':', $value)[1], explode('|', $field[1]))  && explode(':', $value)[0] == $field[0])
						|| (in_array(@explode(':', $value)[1], explode(',', $field[1]))  && explode(':', $value)[0] == $field[0]);

						if ($condition) {

							if (is_bool($arg)) {

								return $field[1];

							} else {

								if ($arg_index == $arg) {

									return $field[1];

								} else {

									$arg_index++;

								}

							}

						}

					}

					return false;

				}

			} else {

				return false;

			}

			break;

			case 'add':

			if (isset($query[$key])) {

				$sign = $logic == 'OR' ? '|' : ',';
				$fields = explode(';', $query[$key]);
				$value = explode(':', $value);

				$found = false;

				foreach ($fields as $k => $field) {

					$field = explode(':', $field);

					if ($field[0] == $value[0]) {

						$found = true;
						$values = explode($sign, $field[1]);

						if (!in_array($value[1], $values)) {

							$values[] = $value[1];

						}

						$field[1] = join($values, $sign);
						$fields[$k] = join($field, ':');

					}

				}

				if (!$found) {

					$field = array_shift($value);
					$fields[] = $field . ':' . join($value, $sign);
					$query[$key] = join($fields, ';');

				}

				$query[$key] = join($fields, ';');

			} else {

				$query[$key] = $value;

			}

			break;

			case 'remove':

			if ($value) {

				if (isset($query[$key])) {

					$sign = $logic == 'OR' ? '|' : ',';
					$fields = explode(';', $query[$key]);

					foreach ($fields as $k => $field) {

						if (strpos($value, ':')) {

							$field = explode(':', $field);
							$val = explode(':', $value);
							$values = explode($sign, $field[1]);

							if (in_array($val[1], $values)) {

								$values = array_diff($values, [$val[1]]);

							}

							if ($values) {

								$field[1] = join($values, $sign);
								$fields[$k] = join($field, ':');

							} else {

								unset($fields[$k]);

							}

						} else {

							if (strpos($field, $value . ':') !== false) {

								unset($fields[$k]);

							}

						}

					}

					if (count($fields)) {

						$query[$key] = join($fields, ';');

					} else {

						unset($query[$key]);

					}

				}

			} else {

				unset($query[$key]);

			}

			break;
			
			default:

			if (!$arg) {

				$query[$key] = $value;

			} else {

				if (!isset($query[$key])) {

					$query[$key] = $value;

				} else {

					$fields = explode(';', $query[$key]);

					foreach ($fields as $k => $field) {

						$field = explode(':', $field);

						if ($field[0] == $arg) {

							unset($fields[$k]);

						}

					}

					$fields[] = $value;
					$query[$key] = join($fields, ';');

				}

			}

			break;
		}

		return $uri[0] . ($query ? '?' . urldecode(http_build_query($query)) : '');

	}

	public function money() {

		$get = $this->get('get');
		$currencies = $this->getConfig('currency');

		if (count($currencies)) {

			$key = array_search('1', array_column($currencies, 'rate'));

			if (!is_bool($key)) {

				$currency = $currencies[$key];

				if ($get) {

					if ($get == 'zero') {

						/********** TO RETURN ZERO FORMATED WHEN VAR UNDEFINED  ***/

						$priceFormat = $currency['formatPrice'];

						switch ($priceFormat) {
							case 1: $price = number_format(0, 0, '', ','); break;
							case 2: $price = number_format(0, 0, '', ' '); break;
							case 3: $price = number_format(0, 2, '.', ','); break;
							case 4: $price = number_format(0, 2, '.', ' '); break;
							case 5: $price = number_format(0, 2, ',', ' '); break;
							case 6: $price = number_format(0, 0, '', ''); break;
							case 7: $price = number_format(0, 2, '.', ''); break;
							case 8: $price = number_format(0, 2, ',', ''); break;

							default:
							$price = number_format(0, 2, '.', ' '); break;
							break;
						}

						return str_replace('[symbol]', $currency['symbol'], str_replace('[price]', $price, $currency['format']));

					} else {

						if ($get == 'formatPriceJS') {

							switch($currency['formatPrice']) {
								case 1:
									return "number_format(price, 0, '', ',')";
									break;
								case 2:
									return "number_format(price, 0, '', ' ')";
									break;
								case 3:
									return "number_format(price, 2, '.', ',')";
									break;
								case 4:
									return "number_format(price, 2, '.', ' ')";
									break;
								case 5:
									return "number_format(price, 2, ',', ' ')";
									break;
								case 6:
									return "number_format(price, 0, '', '')";
									break;
								case 7:
									return "number_format(price, 2, '.', '')";
									break;
								default:
									return "number_format(price, 2, ',', '')";
							} 

						}

						return isset($currency[$get]) ? $currency[$get] : '';

					}

				} else {
					
					return '';

				}

			}

		}

	}

	public function cart() {

		//session()->forget('statamify.cart');
		return $this->api('Statamify')->cartGet( $this->get('instance') ?: 'cart' );

	}

	public function location() {
		
		$countries = $this->api('Statamify')->countries();
		$regions = $this->api('Statamify')->regions();
		
		$is_part = strpos($this->get('country'), ';') !== false;

		if ($this->get('country') && $is_part) {

			$country = $this->get('country');

		} else {

			$country = session('statamify.shipping_country');

		}

		if ($country) {

			if ($is_part) {

				$parts = explode(';', $country);
				$country = $parts[0];
				$region = $parts[1];

			}

			$regions = reset($regions);

			if (isset($regions[$country])) {

				$regions = $regions[$country];

			} else {

				$regions = false;

			}

		} else {

			$regions = false;

		}

		$countries = reset($countries);

		return [ 
			'countries' => $countries, 
			'regions' => $regions,
			'country' => $is_part ? $countries[$country] : $country,
			'region' => $is_part ? (isset($regions[$region]) ? $regions[$region] : $region) : null,
			'country_code' => $country,
			'region_code' => @$region
		];

	}

	public function addresses() {

		$user = User::getCurrent();

		if ($user) {

			$customer = Entry::whereSlug($user->id(), 'customers');

			if ($customer) {

				$default_address = session('statamify.default_address');
				$addresses = $customer->get('addresses');

				foreach ($addresses as $key => $address) {
					$parts = explode(';', $address['country']);
					$address['country'] = $parts[0];
					$address['region'] = @$parts[1];

					if (!$default_address && $address['default']) {

						$default_address = $key;
						session(['statamify.default_address' => [
							'defaultKey' => $key,
							'default' => $address
						]]);
						session(['statamify.shipping_country' => $address['country']]);
						$this->api('Statamify')->cartSetShipping();

					}

				}

				return $this->parseLoop($customer->get('addresses'));

			} else {

				return ['no_results' => true];

			}

		} else {

			return ['no_results' => true];

		}

	}

	public function defaultAddress() {

		return session('statamify.default_address') ?: ['no_results' => true];

	}

	public function customer() {

		$user = User::getCurrent();

		if ($user) {

			$customer = Entry::whereSlug($user->get('id'), 'customers');

			if ($customer) {

				return $customer->toArray();

			} else {

				return ['no_results' => true];

			}

		} else {

			return redirect('/account/login');

		}

	}

	public function cheque() {

		return [

			'active' => $this->getConfig('gateway_cheque_active'), 
			'info' => $this->getConfig('gateway_cheque_info')

		];

	}

}
