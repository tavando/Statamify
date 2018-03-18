<?php

namespace Statamic\Addons\Statamify;

use Statamic\Extend\Controller;
use Illuminate\Http\Request;
use Statamic\API\Entry;
use Statamic\API\User;
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

			throw new \Exception($this->api('Statamify')->t('somethings_wrong', 'errors'));

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

			throw new \Exception($this->api('Statamify')->t('somethings_wrong', 'errors'));

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

	public function postOrder(Request $request) {

		$data = $request->all();
		$user = User::getCurrent();

		if ($user) {

			$data['user'] = $user->get('id');
			$data['email'] = $user->email();

		} else {

			$data['user'] = null;

		}

		$valid = $this->validateOrder($data);

		if (is_bool($valid)) {

			unset($data['_token'], $data['addresso']);

			$order = $this->api('Statamify')->orderCreate($data);
			$this->api('Statamify')->cartClear();

			$whitelist = ['title', 'listing_email', 'shipping', 'billing', 'billing_diff', 'summary',
			'shipping_method', 'payment_method', 'status', 'id', 'slug', 'url', 'last_modified'];
			$data = array_intersect_key($order->toArray(), array_flip($whitelist));

			return redirect('/store/summary')->withInput($data);

		} else {

			return redirect('/store/checkout')->withInput([
				'errors' => $valid,
				'data' => $data
			]);

		}

	}

	private function validateOrder($data) {

		$messages = [
			'email.required' => 'Email address is required',
			'email.email' => 'Email address is not valid',

			'shipping.0.first_name.required' => 'Shipping: First name is required',
			'shipping.0.last_name.required' => 'Shipping: Last name is required',
			'shipping.0.address.required' => 'Shipping: Address is required',
			'shipping.0.city.required' => 'Shipping: City is required',
			'shipping.0.postal.required' => 'Shipping: Postal is required',
			'shipping.0.country.required' => 'Shipping: Country is required',

			'billing.0.first_name.required_if' => 'Billing: First name is required',
			'billing.0.last_name.required_if' => 'Billing: Last name is required',
			'billing.0.address.required_if' => 'Billing: Address is required',
			'billing.0.city.required_if' => 'Billing: City is required',
			'billing.0.postal.required_if' => 'Billing: Postal is required',
			'billing.0.country.required_if' => 'Billing: Country is required',

			'password.required_if' => 'Password is required',
			'password_confirmation.required_if' => 'Password Confirmation is required',
		];

		$validator = Validator::make($data, [
			'addresso' => 'max:0',
			'email' => 'required|email',

			'shipping' => 'required|array',
			'shipping.0.first_name' => 'required',
			'shipping.0.last_name' => 'required',
			'shipping.0.address' => 'required',
			'shipping.0.city' => 'required',
			'shipping.0.postal' => 'required',
			'shipping.0.country' => 'required',

			'billing' => 'array|required_if:billing_diff,1',
			'billing.0.first_name' => 'required_if:billing_diff,1',
			'billing.0.last_name' => 'required_if:billing_diff,1',
			'billing.0.address' => 'required_if:billing_diff,1',
			'billing.0.city' => 'required_if:billing_diff,1',
			'billing.0.postal' => 'required_if:billing_diff,1',
			'billing.0.country' => 'required_if:billing_diff,1',

			'shipping_method' => 'required',
			'payment_method' => 'required',

			'password' => 'confirmed|required_if:user,',
			'password_confirmation' => 'required_if:user,',
		], $messages);

		if ($validator->fails()) {

			return $validator->errors()->all();

		}

		if (!$data['user'] && User::whereEmail($data['email'])) {

			return [$this->api('Statamify')->t('customer_exists', 'errors')];

		}

		return true;

	}

	public function postSetDefaultAddress(Request $request) {

		$data = $request->all();

		$validator = Validator::make($data, [
			'address' => 'required|numeric',
		]);

		if ($validator->fails()) {

			throw new \Exception($this->api('Statamify')->t('somethings_wrong', 'errors'));

		}

		$this->api('Statamify')->setDefaultAddress($data['address']);

	}

}
