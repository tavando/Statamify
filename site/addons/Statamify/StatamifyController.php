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

			try {

				$order = $this->api('Statamify')->orderCreate($data);

			} catch(\Exception $e) {

				return redirect('/store/checkout')->withInput([
					'errors' => [$e->getMessage()],
					'data' => $data
				]);

			}
			
			$this->api('Statamify')->cartClear();

			$whitelist = ['title', 'listing_email', 'shipping', 'billing', 'billing_diff', 'summary',
			'shipping_method', 'payment_method', 'status', 'id', 'slug', 'url', 'last_modified'];
			$data = array_intersect_key($order->toArray(), array_flip($whitelist));

			if ($data['payment_method']['name'] == 'PayPal') {

				return $this->api('StatamifyPaypal')->charge($data);

			}

			return redirect('/account/order/' . $data['slug'])->withInput($data);

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

			'payment_token' => 'required_if:payment_method,stripe',

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

		$this->api('Statamify')->cartSetDefaultAddress($data['address']);

	}

	public function postAddress(Request $request) {

		$data = $request->all();

		$messages = [
			'addresso' => 'max:0',

			'first_name.required' => 'First name is required',
			'last_name.required' => 'Last name is required',
			'address.required' => 'Address is required',
			'city.required' => 'City is required',
			'postal.required' => 'Postal is required',
			'country.required' => 'Country is required',

		];

		$validator = Validator::make($data, [
			'first_name' => 'required',
			'last_name' => 'required',
			'address' => 'required',
			'city' => 'required',
			'postal' => 'required',
			'country' => 'required',
		], $messages);

		if ($validator->fails()) {

			throw new \Exception($this->api('Statamify')->t('somethings_wrong', 'errors'));

		}

		$user = User::getCurrent();

		if ($user) {

			$customer = Entry::whereSlug($user->id(), 'customers');

			if ($customer) {

				$addresses = $customer->get('addresses');

				$data['country'] = $data['country'] . ';' . $data['region'];
				$index = $data['address_index'];

				unset($data['region'], $data['_token'], $data['address_index'], $data['addresso']);

				if ($data['default'] == 'true') {

					foreach ($addresses as $key => $addr) {
						
						$addresses[$key]['default'] = false;

					}

					$data['default'] = true;

				} else {

					$data['default'] = false;

				}

				if ($index == 'new') {

					$addresses[] = $data;

				} else {

					if (isset($addresses[$index - 1])) {

						$addresses[$index - 1] = $data;

					}

				}

				$customer->set('addresses', array_values($addresses));
				$customer->save();

				if ($index == 'new') {

					return redirect('/account');

				} else {

					return redirect('/account/address/' . $index);

				}

			}

		}

	}

	public function getAddressRemove() {

		$uri = explode('/', $_SERVER['REQUEST_URI']);
		$id = end($uri);

		$user = User::getCurrent();

		if ($user) {

			$customer = Entry::whereSlug($user->id(), 'customers');

			if ($customer) {

				$addresses = $customer->get('addresses');

				if (isset($addresses[$id - 1])) {

					unset($addresses[$id - 1]);

					$customer->set('addresses', array_values($addresses));
					$customer->save();

					return redirect('/account');

				}

			}

		}

	}

	public function getAnalytics() {

		$data = $this->api('Statamify')->analytics();

		return $this->view('analytics', [
			'split' => $data['split'],
			'all_orders' => json_encode($data['all_orders']),
			'total_orders' => json_encode($data['total_orders']),
			'total_sales' => json_encode($data['total_sales']),
			'avg_order_value' => json_encode($data['avg_order_value']),
			'repeat_rate' => json_encode($data['repeat_rate']),
			'money' => $data['money']
		]);

	}

	public function postAnalytics(Request $request) {

		$this->authorize('super');

		$post = $request->all();

		$data = $this->api('Statamify')->analytics([$post['start'], $post['end']], $post['split']);

		return json_encode([
			'split' => $data['split'],
			'all_orders' => array_values($data['all_orders']),
			'total_orders' => array_values($data['total_orders']),
			'total_sales' => array_values($data['total_sales']),
			'avg_order_value' => array_values($data['avg_order_value']),
			'repeat_rate' => $data['repeat_rate'],
			'money' => $data['money']
		]);

	}

}
