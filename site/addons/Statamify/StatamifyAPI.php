<?php

namespace Statamic\Addons\Statamify;

use Statamic\Extend\API;
use Statamic\API\Config;
use Statamic\API\GlobalSet;
use Statamic\Addons\Statamify\Models\StatamifyAnalytics as Analytics;
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
				$minus = false;

				if ($value < 0) { $value *= -1; $minus = true; }

				switch ($priceFormat) {
					case 1: $price = number_format($value, 0, '', ','); break;
					case 2: $price = number_format($value, 0, '', ' '); break;
					case 3: $price = number_format($value, 2, '.', ','); break;
					case 4: $price = number_format($value, 2, '.', ' '); break;
					case 5: $price = number_format($value, 2, ',', ' '); break;
					case 6: $price = number_format($value, 0, '', ''); break;
					case 7: $price = number_format($value, 2, '.', ''); break;
					case 8: $price = number_format($value, 2, ',', ''); break;
					
					default:
					$price = number_format($value, 2, '.', ' '); break;
					break;
				}

				return ($minus ? '- ' : '') . str_replace('[symbol]', $currency['symbol'], str_replace('[price]', $price, $currency['format']));

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

	public function cartSetDefaultAddress($data) {

		$cart = new Cart($this);

		$cart->setDefaultAddress($data);

	}

	public function cartAddCoupon($coupon, $instance = 'cart') {

		$cart = new Cart($this, $instance);

		return $cart->addCoupon($coupon);

	}

	public function cartRemoveCoupon($id, $instance = 'cart') {

		$cart = new Cart($this, $instance);

		return $cart->removeCoupon($id);

	}

	public function cartCheckCoupon($cart, $coupon_entry, $coupon, $email) {

		// Check if minimum purches in met

		if ($coupon_entry->get('min')) {

			if ((float) $cart['total']['sub'] < (float) $coupon_entry->get('min')) {

				return ['status' => 'error', 'error' => 'min'];

			}

		}

		// Check if shipping country is in selected countries

		if ($coupon_entry->get('countries')) {

			$shipping_country = session('statamify.shipping_country');

			if (!in_array($shipping_country, $coupon_entry->get('countries'))) {

				return ['status' => 'error', 'error' => 'countries'];

			}

		}

		// Check if logged in customer's email is in selected emails

		if ($coupon_entry->get('customers') && array_filter($coupon_entry->get('customers'))) {

			if (!$email || ($email && !in_array($email, $coupon_entry->get('customers')))) {

			return ['status' => 'error', 'error' => 'customers'];

			}

		}

		// Check if total number of coupons are used

		if ($coupon_entry->get('total')) {

			if ($coupon_entry->get('used_by') && $coupon_entry->get('total') == count()) {

				return ['status' => 'error', 'error' => 'total'];

			}

		}

		// Check if total number of coupons per user is used

		if ($coupon_entry->get('per_user')) {

			if (!$email) {

				return ['status' => 'error', 'error' => 'per_user'];

			}

			if ($coupon_entry->get('used_by')) {

				$emails_collection = collect(explode(';', preg_replace('/\s+/', '', $coupon_entry->get('used_by'))));

				$filtered = $emails_collection->reject(function ($value) use($email) {
					return $value != $email;
				});

				if ($coupon_entry->get('per_user') == $filtered->count()) {

					return ['status' => 'error', 'error' => 'per_user'];

				}

			}

		}

		// Check dates

		if ($coupon_entry->get('start_date')) {

			if( strtotime($coupon_entry->get('start_date')) > strtotime('now') ) {

				return ['status' => 'error', 'error' => 'start_date'];

			}

		}

		if ($coupon_entry->get('end_date')) {

			if( strtotime($coupon_entry->get('end_date')) < strtotime('now') ) {

				return ['status' => 'error', 'error' => 'end_date'];

			}

		}

		return [ 'status' => 'ok' ];

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

	public function analytics($range = [], $split = 'perday') {

		$analytics = new Analytics($this, $range, $split);

		return $analytics->get();

	}

}
