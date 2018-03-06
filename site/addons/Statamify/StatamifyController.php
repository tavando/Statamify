<?php

namespace Statamic\Addons\Statamify;

use Statamic\Extend\Controller;
use Illuminate\Http\Request;
use Validator;

class StatamifyController extends Controller
{

	public function getCart() {

		return $this->api('Statamify')->cartGet();

	}

	public function postCartAdd(Request $request) {

		$data = $request->all();

		$validator = Validator::make($data, [
			'product' => 'required',
			'quantity' => 'required',
		]);

		if ($validator->fails()) {

			throw new \Exception('somethings_wrong');

		}

		return $this->api('Statamify')->cartAdd($data);

	}

	public function postCartUpdate(Request $request) {

		$data = $request->all();

		$validator = Validator::make($data, [
			'item_id' => 'required',
			'quantity' => 'required',
		]);

		if ($validator->fails()) {

			throw new \Exception('somethings_wrong');

		}

		return $this->api('Statamify')->cartUpdate($data);

	}

	public function getCountries() {

		$countries = $this->api('Statamify')->countries();
		$regions = $this->api('Statamify')->regions();

		$data = [ 
			'countries' => reset($countries), 
			'regions' => reset($regions) 
		];

		return $data;

	}

	public function postSetShipping(Request $request) {

		if (isset($request->shipping_country)) {

			$data = $this->getCountries();

			if ($request->shipping_country) {
				session(['statamify.shipping_country' => $request->shipping_country]);
			} else {
				session()->forget('statamify.shipping_country');
			}
			
			$this->api('Statamify')->cartSetShipping();

			$data['cart'] = $this->api('Statamify')->cartGet();

			return $data;

		}

	}

	public function postSetShippingMethod(Request $request) {

		if (isset($request->shipping)) {

			$shipping = explode('|', $request->shipping);

			session(['statamify.shipping_method' => isset($shipping[1]) ? $shipping[1] : 0]);

			return $this->api('Statamify')->cartGet();

		}

	}

}
