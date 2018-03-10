<?php

namespace Statamic\Addons\Statamify;

use Statamic\Extend\API;
use Statamic\API\Helper;
use Statamic\API\Entry;
use Statamic\API\Collection;
use Statamic\API\Fieldset;
use Statamic\API\File;
use Statamic\API\YAML;
use Statamic\API\Stache;
use Statamic\API\User;
use Statamic\API\Role;

class StatamifyAPI extends API
{

	public function t($string, $space = 'statamify', $params = []) {

		return app('translator')->trans('addons.Statamify::' . $space . '.' . $string, $params);

	}

	/*************
	
	CART API 

	*************/

	public function cartInit($instance = 'cart', $recalculated = false) {

		$session = session('statamify.' . $instance);

		if ($session) {
			if ($recalculated) {

				return $this->cartRecalculated($session);

			}

			return $session;

		} else {

			return [
				'cart_id' => Helper::makeUuid(),
				'items' => [],
				'coupons' => [],
				'shipping' => false,
				'total' => [
					'sub' => 0,
					'discount' => 0,
					'shipping' => 0,
					'tax' => 0,
					'grand' => 0,
					'weight' => 0
				]
			];

		}

	}

	private function removeExtraValues($entry) {

		$blacklist = [
			'columns', 'products', 'is_entry', 'order', 'order_type',
			'content', 'content_raw',
			'listing_image', 'listing_type', 'listing_vendor', 'listing_inventory',
			'edit_url', 'uri', 'url_path'
		];

		return array_diff_key($entry, array_flip($blacklist));

	}

	private function cartRecalculated($cart) {

		$fieldset = Fieldset::get(Collection::whereHandle('products')->get('fieldset'));
		$fieldset_data = $fieldset->toArray();

		$cart['total'] = [
			'sub' => 0,
			'discount' => 0,
			'shipping' => 0,
			'tax' => 0,
			'grand' => 0,
			'weight' => 0
		];

		foreach ($cart['items'] as $key => $item) {
			
			$product = Entry::find($item['product']);

			if ($product) {

				/********** REPLACE PRODUCT ID WITH DATA  ***/
				$product = $product->toArray();
				$cart['items'][$key]['product'] = $this->removeExtraValues($product);

				/********** REPLACE RELATIONS' ID WITH DATA  ***/

				foreach ($fieldset_data['fields'] as $fkey => $field) {
					
					if ($field['type'] == 'collection') {

						$type = $field['name'];

						if (isset($product[$type])) {

							if (isset($field['max_items']) && $field['max_items'] == '1') {

								$relation = Entry::find($product[$type]);

								if ($relation) {

									$relation = $relation->toArray();
									$cart['items'][$key]['product'][$type] = $this->removeExtraValues($relation);

								}

							} else {

								foreach ($product[$type] as $ckey => $id) {

									$relation = Entry::find($id);

									if ($relation) {

										$relation = $relation->toArray();
										$cart['items'][$key]['product'][$type][$ckey] = $this->removeExtraValues($relation);

									}

								}

							}

						}

					}

				}

				if ($item['variant']) {

					/********** FIND VARIANT KEY IN PRODUCT'S VARIANTS  ***/
					$vkey = array_search($item['variant'], array_column($product['variants'], 'id'));

					/********** REPLACE VARIANT ID WITH DATA  ***/
					if (!is_bool($vkey)) $cart['items'][$key]['variant'] = $product['variants'][$vkey];

					$price = @$product['variants'][$vkey]['price'] ? (float) $product['variants'][$vkey]['price'] : 0;

				} else {

					if (@$product['price']) {

						$price = (float) $product['price'];

					} else {

						$price = 0;

					}

				}

				$cart['total']['sub'] += $price * $item['quantity'];
				$cart['total']['weight'] += @$product['weight'] ? ((float) $product['weight']) * $item['quantity'] : 0;

			}

		}


		/********** USER IS LOGGED IN BUT DEFAULT ADDRESS IS NOT SET - RESET SHIPPING SET BEFORE LOGIN  ***/

		if (!session('statamify.default_address')) {

			$this->setDefaultAddress('default');
			$cart['shipping'] = $this->cartSetShipping();

		}

		if (!$cart['shipping'] && session('statamify.shipping_country')) {

			$cart['shipping'] = $this->cartSetShipping();

		}

		if ($cart['shipping']) {

			$shipping_methods = [];
			$shipping_zones = $this->getConfig('shipping_zones');
			$shipping_zone = $shipping_zones[$cart['shipping']['zone']];

			$bases = ['price_rates', 'weight_rates'];

			foreach ($bases as $base) {
				
				if (isset($shipping_zone[$base])) {

					$compare = $base == 'price_rates' ? 'sub' : 'weight';

					foreach ($shipping_zone[$base] as $key => $method) {

						$condition = true;

						if (isset($method['min']) && $method['min']) {

							if ($cart['total'][$compare] < $method['min']) {

								$condition = false;

							}

						}

						if (isset($method['max']) && $method['max']) {

							if ($cart['total'][$compare] > $method['max']) {

								$condition = false;

							}

						}

						if ($condition) {

							$shipping_methods[slugify($method['name'])] = $method;

						}

					}

				}

			}

			if (count($shipping_methods)) {

				$shipping_method = session('statamify.shipping_method');

				if (!$shipping_method || !isset($shipping_methods[$shipping_method])) {

					$keys = array_keys($shipping_methods);
					$first = reset($keys);
					$shipping_method = $first;
					session(['statamify.shipping_method' => $first]);

				}

				$cart['shipping']['methods'] = $shipping_methods;
				$cart['shipping']['methods'][$shipping_method]['active'] = true;
				$cart['total']['shipping'] = isset($shipping_methods[$shipping_method]['rate']) ? $shipping_methods[$shipping_method]['rate'] : 0;

			}

		}

		$cart['total']['grand'] = $cart['total']['sub'] + $cart['total']['discount'] + $cart['total']['shipping'] + $cart['total']['tax'];

		return $cart;

	}

	public function cartGet($instance = 'cart') {

		return $this->cartInit($instance, true);

	}

	public function cartAdd($item, $instance = 'cart') {

		$cart = $this->cartInit();
		$found = false;

		/********** CHECK IF PRODUCT'S ALREADY IN CART  ***/
		foreach ($cart['items'] as $key => $cartItem) {

			if ($item['variant']) {

				if ($cartItem['product'] == $item['product'] && $cartItem['variant'] == $item['variant']) $found = $key;

			} else {

				if ($cartItem['product'] == $item['product']) $found = $key;

			}

		}

		if ( is_bool($found) ) {

			$product = Entry::find($item['product']);

			if ($product) {

				/********** CHECK IF VARIANT EXISTS  ***/
				if ($product->get('class') == 'complex') {

					if (!isset($item['variant']) || !$item['variant']) {

						throw new \Exception($this->t('variant_required', 'errors'));

					} else {

						$key = array_search($item['variant'], array_column($product->get('variants'), 'id'));

						if (is_bool($key)) throw new \Exception($this->t('variant_not_found', 'errors'));

					}

				}

				/********** CHECK IF ENOUGH IN INVENTORY  ***/
				$this->checkInventory($product, $item);

				$item = [
					'item_id' => Helper::makeUuid(),
					'quantity' => $item['quantity'],
					'product' => $item['product'],
					'variant' => $item['variant'] ?: false,
					'custom' => isset($item['custom']) && $item['custom'] ? $item['custom'] : null
				];

				$cart['items'][] = $item;
				session(['statamify.' . $instance => $cart]);

				return $this->cartGet($instance);

			} else {

				throw new \Exception($this->t('product_not_found', 'errors'));

			}


		} else {

			$item['item_id'] = $cart['items'][$found]['item_id'];
			$item['quantity'] += $cart['items'][$found]['quantity'];

			return $this->cartUpdate($item, $instance);

		}

	}

	public function cartUpdate($item, $instance = 'cart') {

		$cart = $this->cartInit();
		$key = array_search($item['item_id'], array_column($cart['items'], 'item_id'));

		if (!is_bool($key)) {

			if ($item['quantity'] == 0) {

				unset( $cart['items'][ $key ] );
				$cart['items'] = array_values($cart['items']);

				if (!count($cart['items'])) {

					$cart['shipping'] = false;
					session()->forget('statamify.shipping_method');

				}

				session(['statamify.' . $instance => $cart]);

			} else {

				$cartItem = $cart['items'][$key];
				$product = Entry::find($cartItem['product']);

				if ($product) {

					/********** CHECK IF ENOUGH IN INVENTORY, IF NOT - THROW ERROR  ***/
					$this->checkInventory($product, $item, $cartItem['quantity']);

					$cart['items'][ $key ]['quantity'] = $item['quantity'];
					session(['statamify.' . $instance => $cart]);

					return $this->cartGet($instance);

				} else {

					throw new \Exception($this->t('product_not_found', 'errors'));

				}

			}

		}

		return $this->cartGet();

	}

	private function checkInventory($product, $item, $init = 0) {

		if ($product->get('track_inventory')) {

			if ($product->get('class') == 'simple') {

				if ($product->get('inventory') < $item['quantity'] + $init) throw new \Exception($this->t('product_too_many', 'errors'));

			} elseif ($product->get('class') == 'complex') {

				foreach ($product->get('variants') as $key => $variant) {

					if ($key != 'settings' && $variant['id'] == $item['variant']) {

						if (!$variant['inventory'] || $variant['inventory'] < $item['quantity'] + $init) throw new \Exception($this->t('product_too_many', 'errors'));

					}

				}

			}

		}

	}

	public function cartSetShipping() {

		$shipping_country = session('statamify.shipping_country');
		$cart = $this->cartInit();

		if ($shipping_country) {

			$zones = $this->getConfig('shipping_zones');
			$shipping_zone = array_search('rest', array_column($zones, 'type'));

			foreach ($zones as $key => $zone) {
				
				if (isset($zone['countries']) && in_array($shipping_country, $zone['countries'])) {

					$shipping_zone = $key;

					break;
				}

			}

			if (!is_bool($shipping_zone)) {

				$cart['shipping'] = ['zone' => $shipping_zone];

			} else {

				$cart['shipping'] = false;

			}

		} else {

			$cart['shipping'] = false;

		}

		session()->forget('statamify.shipping_method');
		session(['statamify.cart' => $cart]);

		return $cart['shipping'];

	}

	public function cartClear($instance = 'cart') {

		session()->forget('statamify.' . $instance);

		if ($instance == 'cart') {

			session()->forget('statamify.shipping_method');

		}

	}

	/*************
	
	ORDER API

	*************/

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

	public function orderCreate($data) {

		$cart = $this->cartGet();

		/********** REFORMAT DATA FOR SHIPPING AND BILLING COUNTRY/REGION  ***/

		if (isset($data['shipping'][0]['region']) && $data['shipping'][0]['region'] != '') {

			$data['shipping'][0]['country'] = $data['shipping'][0]['country'] . '|' . $data['shipping'][0]['region'];
			unset($data['shipping'][0]['region']);

		}

		if (isset($data['billing'][0]['region']) && $data['billing'][0]['region'] != '') {

			$data['billing'][0]['country'] = $data['billing'][0]['country'] . '|' . $data['billing'][0]['region'];
			unset($data['billing'][0]['region']);

		}

		if (isset($data['billing_diff']) && $data['billing_diff'] == '1') {

			$data['billing_diff'] = true;

		}

		unset($data['saved_addresses']);

		/********** CREATE USER IF DOESN'T EXIST  ***/
		if (!$data['user']) {

			$roles = collect(Role::all()->toArray());

			$user_data = [
				'first_name' => $data['shipping'][0]['first_name'],
				'last_name' => $data['shipping'][0]['last_name'],
				'roles' => [$roles->where('slug', 'customer')->keys()->first()],
				'password' => $data['password']
			];

			$user = User::create()
			->with($user_data)
			->email($data['email'])
			->get();

			$user->save();
			$data['user'] = $user->get('id');
			unset($data['password'], $data['password_confirmation']);

		}

		$customer = Entry::whereSlug($data['email'], 'customers');

		if (!$customer) {

			$address = $data['shipping'];
			$address[0]['default'] = true;

			$customer_data = [
				'user' => $data['user'],
				'title' => $user->get('first_name') . ' ' . $user->get('last_name'),
				'listing_orders' => 1,
				'listing_spent' => '<span data-total="' . $cart['total']['grand'] . '">' . $this->money($cart['total']['grand']) . '</span>',
				'addresses' => $address,
				'orders' => []
			];

			$customer = Entry::create($data['email'])
			->collection('customers')
			->with($customer_data)
			->published(true)
			->get();

		} else {

			$customer->set('listing_orders', $customer->get('listing_orders') + 1);
			$spent = explode('"', $customer->get('listing_spent'));
			$spent[1] = $spent[1] + $cart['total']['grand'];
			$spent[2] = '>' . $this->money($spent[1]) . '</span>';
			$customer->set('listing_spent', join($spent, '"'));

		}

		$order_next_id = $this->getConfigInt('order_next_id', 1000);
		$order_id_format = $this->getConfig('order_id_format', '#[id]');
		$shipping_zones = $this->getConfig('shipping_zones');

		$data['title'] = str_replace('[id]', $order_next_id, $order_id_format);

		$shipping_method = explode('|', $data['shipping_method']);
		$zone = $shipping_zones[$shipping_method[0]];
		$payment_method = $data['payment_method'];

		$data['shipping_method'] = ['zone' => $zone['name']];

		foreach (@$zone['price_rates'] as $rate) {
			
			if (slugify($rate['name']) == $shipping_method[1]) {

				$data['shipping_method']['name'] = $rate['name'];
				$data['shipping_method']['rate'] = @$rate['rate'] ?: 0;

			}

		}

		if (!isset($data['shipping_method']['name'])) {

			foreach (@$zone['weight_rates'] as $rate) {

				if (slugify($rate['name']) == $shipping_method[1]) {

					$data['shipping_method']['name'] = $rate['name'];
					$data['shipping_method']['rate'] = @$rate['rate'] ?: 0;

				}

			}

		}

		$data['payment_method'] = [];

		switch ($payment_method) {
			case 'cheque':
			$data['status'] = 'awaiting_payment';
			$data['payment_method'] = ['name' => 'Cheque', 'fee' => 0];
			break;
			case 'stripe':
			$data['status'] = 'pending';
			$data['payment_method'] = ['name' => 'Stripe', 'fee' => 0];
			break;
			
			default:
			$data['status'] = 'pending';
			$data['payment_method'] = ['name' => 'Default', 'fee' => 0];
			break;
		}

		$data['listing_status'] = '<span class="order-status ' . $data['status'] . '">' . $this->t('status.' . $data['status']) . '</span>';

		$data['summary'] = [
			'items' => [],
			'total' => $cart['total']
		];

		foreach ($cart['items'] as $item) {
			
			$data['summary']['items'][] = [
				'id' => $item['item_id'],
				'name' => $item['product']['title'],
				'variant' => $item['variant'] ? $item['variant']['attrs'] : false,
				'sku' => $item['variant'] ? @$item['variant']['sku'] : @$item['product']['sku'],
				'price' => $item['variant'] ? @$item['variant']['price'] : @$item['product']['price'],
				'quantity' => $item['quantity'],
				'custom' => isset($item['custom']) && $item['custom'] ? $item['custom'] : null,
				'image' => $item['product']['image'],
				'edit_url' => '/cp/collections/entries/products/' . $item['product']['slug']
			];

		}

		$data['listing_total'] = $this->money($data['summary']['total']['grand']);
		$data['listing_customer'] = $customer->get('title') . ' <a href="/cp/collections/entries/customers/' . $data['email'] . '" class="statamify-link"><span class="icon icon-forward"></span></a>';

		$data['listing_email'] = $data['email'];
		unset($data['email']);

		$order = Entry::create(slugify($data['title']))
		->collection('orders')
		->with($data)
		->published(true)
		->date(date('Y-m-d H:i'))
		->get();

		$settings_file = File::get('site/settings/addons/statamify.yaml');
		$settings = YAML::parse($settings_file);
		$settings['order_next_id'] = $order_next_id + 1;
		File::put('site/settings/addons/statamify.yaml', YAML::dump($settings));

		$order->save();

		$customers_orders = $customer->get('orders');
		$customers_orders[] = $order->get('id');
		$customer->set('orders', $customers_orders);
		$customer->save();

		Stache::update();

		return $order;

	}

	public function setDefaultAddress($key) {

		$user = User::getCurrent();

		if ($user) {

			$customer = Entry::whereSlug($user->email(), 'customers');

			if ($customer) {

				$addresses = $customer->get('addresses');

				if (!isset($addresses[$key])) {

					$key = array_search(true, array_column($addresses, 'default'));

				}

				if (!is_bool($key) && isset($addresses[$key])) {

					$address = $addresses[$key];

					$parts = explode('|', $address['country']);
					$address['country'] = $parts[0];
					$address['region'] = $parts[1];

					session(['statamify.default_address' => [
						'defaultKey' => $key,
						'default' => $address
					]]);
					session(['statamify.shipping_country' => $address['country']]);
					$this->cartSetShipping();

				}

			}

		}

	}

}
