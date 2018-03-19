<?php

namespace Statamic\Addons\Statamify;

use Statamic\Extend\API;
use Statamic\API\Config;
use Statamic\API\GlobalSet;
use Statamic\Addons\Statamify\Models\StatamifyCart as Cart;
use Statamic\Addons\Statamify\Models\StatamifyOrder as Order;
use Statamic\Addons\Statamify\Models\StatamifyEmail as Email;

class StatamifyAPI extends API
{

	public function t($string, $space = 'statamify', $params = []) {

		return app('translator')->trans('addons.Statamify::' . $space . '.' . $string, $params);

	}

	public function response($code = 200, $msg = '') {

		header('HTTP/1.1 ' . $code . ' Internal Server Error');
		header('Content-Type: application/json; charset=UTF-8');
		die(json_encode(array('message' => $msg)));

	}

	public function countries() {

		return $this->storage->getYAML('countries');
		
	}

	public function regions() {

		return $this->storage->getYAML('regions');

	}

	public function currencies() {

		return $this->getConfig('currency');

	}

	public function money($value) {

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

	public function cartGet($instance = 'cart') {

		$cart = new Cart($this, $instance);

		return $cart->get();

	}

	public function cartAdd($item, $instance = 'cart') {

		$cart = new Cart($this, $instance);

		return $cart->add($item);

	}

	public function cartUpdate($item, $instance = 'cart') {

		$cart = new Cart($this, $instance);

		return $cart->update($item);

	}

	public function cartClear($instance = 'cart') {

		$cart = new Cart($this, $instance, false);

		$cart->clear();

	}

	public function cartSetShipping() {

		$cart = new Cart($this);

		$cart->setShipping();

	}

	public function orderCreate($data) {

		$order = new Order($this);

		return $order->create($data);

	}

	public function wrapGlobals($data) {

		// Add globals to emails

		$global = GlobalSet::whereHandle('global');

		$data['site_url'] = Config::getSiteUrl();
		$data['store_name'] = $this->getConfig('store_name');
		$data['store_address'] = $this->getConfig('store_address');
		$data['date'] = date(Config::get('system.date_format'), $data['last_modified']);

		return $data;

	}

	public function sendEmail($template, $data, $to = null) {

		if (!$to) {

			$to = $this->getConfig('owner_email');

		}

		$data = $this->wrapGlobals($data);

		$email = new Email($template, $data, $to);
		$email->create();

	}

}
