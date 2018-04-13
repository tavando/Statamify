<?php

namespace Statamic\Addons\StatamifyStripe;

use Statamic\Extend\API;
use Stripe\Stripe as Stripe;
use Stripe\Charge as Charge;

class StatamifyStripeAPI extends API
{

	public function key($type = 'public') {

		if ($this->getConfig('test')) {

			$test_keys = $this->getConfig('keys_test');

			return $test_keys && $test_keys[$type . '_key'] ? $test_keys[$type . '_key'] : '';

		} else {

			$live_keys = $this->getConfig('keys');

			return $live_keys && $live_keys[$type . '_key'] ? $live_keys[$type . '_key'] : '';

		}

	}

	public function charge($order, $cart) {

		Stripe::setApiKey($this->key('secret'));

		$token = $order['payment_token'];

		$charge = Charge::create([
			'amount' => $cart['total']['grand'] * 100,
			'currency' => 'eur',
			'description' => $order['title'],
			'source' => $token
		]);

		return $charge;

	}

}