<?php

namespace Statamic\Addons\StatamifyPaypal;

use Statamic\Extend\API;
use Statamic\API\Config;

class StatamifyPaypalAPI extends API
{

	public function charge($order) {

		$store = $this->api('Statamify')->wrapGlobals(['last_modified' => $order['last_modified']]);
		$order['return_url'] = $store['site_url'] . $order['url'];
		$order['notify_url'] = $store['site_url'] . '!/statamify-paypal/verify';
		$order['currency_code'] = $this->currency_code();

		if ($this->getConfig('sandbox', true)) {

			$order['business'] = $this->getConfig('business_sandbox');
			$order['action'] = 'https://www.sandbox.paypal.com/cgi-bin/webscr';

		} else {

			$order['business'] = $this->getConfig('business');
			$order['action'] = 'https://www.paypal.com/cgi-bin/webscr';

		}

		return $this->formGenerate($order);

	}

	private function currency_code() {

		$currencies = $this->api('Statamify')->currencies();

		if (count($currencies)) {

			$key = array_search('1', array_column($currencies, 'rate'));

			if (!is_bool($key)) {

				$currency = $currencies[$key];
				return $currency['code'];

			}

		} else {

			return 'USD';

		}

	}

	private function formGenerate($order) {

		$html = '
			<html>
			<head>
			<style>
			.loader {
				margin: -55px 0 0 -55px;
				background-color: transparent;
				animation: g .7s infinite linear;
				border-left: 5px solid #cbcbca;
				border-right: 5px solid #cbcbca;
				border-bottom: 5px solid #cbcbca;
				border-top: 5px solid #2380be;
				border-radius: 100%;
				height: 100px;
				width: 100px;
				top: 50%;
				left: 50%;
				position: fixed;
			}
			@keyframes g {
				0% {transform: rotate(0deg)}
				100% {transform: rotate(359deg)}
			}
			</style>
			</head>
			<body>
			<div class="loader"></div>
			<form action="' . $order['action'] . '" method="post" style="display: none" id="paypal">
			<input type="hidden" name="cmd" value="_cart">
			<input type="hidden" name="upload" value="1">
			<input type="hidden" name="custom" value="' . $order['id'] . '">
			<input type="hidden" name="business" value="' . $order['business'] . '">
			<input type="hidden" name="item_name_1" value="Order ' . $order['title'] . '">
			<input type="hidden" name="amount_1" value="' . ((float)$order['summary']['total']['grand'] - (float)$order['summary']['total']['shipping']) . '">
			<input type="hidden" name="shipping_1" value="' . $order['summary']['total']['shipping'] . '">
			<input type="hidden" name="currency_code" value="' . $order['currency_code'] . '">
			<input type="hidden" name="return" value="' . $order['return_url'] . '">
			<input type="hidden" name="email" value="' . $order['listing_email'] . '">
			';

			if (isset($order['billing_diff']) && $order['billing_diff']) {

				$html .= '
				<input type="hidden" name="first_name" value="' . @$order['billing'][0]['first_name'] . '">
				<input type="hidden" name="last_name" value="' . @$order['billing'][0]['last_name'] . '">
				<input type="hidden" name="address1" value="' . @$order['billing'][0]['address'] . '">
				<input type="hidden" name="address2" value="' . @$order['billing'][0]['address_2'] . '">
				<input type="hidden" name="city" value="' . @$order['billing'][0]['city'] . '">
				<input type="hidden" name="zip" value="' . @$order['billing'][0]['postal'] . '">
				';

				$country_region = explode(';', $order['billing'][0]['country']);

				$html .= '<input type="hidden" name="country" value="' . $country_region[0] . '">';

				if ($country_region[0] == 'US' && isset($country_region[1])) {

					$html .= '<input type="hidden" name="state" value="' . $country_region[1] . '">';

				}

			} else {

				$html .= '
				<input type="hidden" name="first_name" value="' . @$order['shipping'][0]['first_name'] . '">
				<input type="hidden" name="last_name" value="' . @$order['shipping'][0]['last_name'] . '">
				<input type="hidden" name="address1" value="' . @$order['shipping'][0]['address'] . '">
				<input type="hidden" name="address2" value="' . @$order['shipping'][0]['address_2'] . '">
				<input type="hidden" name="city" value="' . @$order['shipping'][0]['city'] . '">
				<input type="hidden" name="zip" value="' . @$order['shipping'][0]['postal'] . '">
				';

				$country_region = explode(';', $order['shipping'][0]['country']);

				$html .= '<input type="hidden" name="country" value="' . $country_region[0] . '">';

				if ($country_region[0] == 'US' && isset($country_region[1])) {

					$html .= '<input type="hidden" name="state" value="' . $country_region[1] . '">';

				}

			}

			$html .= '
			<input type="submit" value="PayPal">
			</form>
			<script type="text/javascript">
			document.getElementById("paypal").submit();
			</script>
			</body>
			</html>
			';

			return $html;

	}

}