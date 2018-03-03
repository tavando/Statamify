<?php

namespace Statamic\Addons\Statamify;

use Statamic\Extend\API;
use Statamic\API\Helper;
use Statamic\API\Entry;
use Statamic\API\Collection;
use Statamic\API\Fieldset;

class StatamifyAPI extends API
{

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
					'coupons' => 0,
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
			'coupons' => 0,
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

				$cart['total']['sub'] += @$product['price'] ? ((float) $product['price']) * $item['quantity'] : 0;
				$cart['total']['weight'] += @$product['weight'] ? ((float) $product['weight']) * $item['quantity'] : 0;

			}

			if ($item['variant']) {

				/********** FIND VARIANT KEY IN PRODUCT'S VARIANTS  ***/
				$vkey = array_search($item['variant'], array_column($product['variants'], 'id'));

				/********** REPLACE VARIANT ID WITH DATA  ***/
				if (!is_bool($vkey)) $cart['items'][$key]['variant'] = $product['variants'][$vkey];

				$cart['total']['sub'] += @$product['variants'][$vkey]['price'] ? ((float) $product['variants'][$vkey]['price']) * $item['quantity'] : 0;

			}

		}

		$cart['total']['grand'] = $cart['total']['sub'] + $cart['total']['coupons'] + $cart['total']['shipping'] + $cart['total']['tax'];

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

						throw new \Exception('variant_required');

					} else {

						$key = array_search($item['variant'], array_column($product->get('variants'), 'id'));

						if (is_bool($key)) throw new \Exception('variant_not_found');

					}

				}

				/********** CHECK IF ENOUGH IN INVENTORY  ***/
				$this->checkInventory($product, $item);

				$item = [
					'item_id' => Helper::makeUuid(),
					'quantity' => $item['quantity'],
					'product' => $item['product'],
					'variant' => $item['variant'] ?: false,
					'custom' => null
				];

				$cart['items'][] = $item;
				session(['statamify.' . $instance => $cart]);

				return $this->cartGet($instance);

			} else {

				throw new \Exception('product_not_found');

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

					throw new \Exception('product_not_found');

				}

			}

		}

		return $this->cartGet();

	}

	private function checkInventory($product, $item, $init = 0) {

		if ($product->get('track_inventory')) {

			if ($product->get('class') == 'simple') {

				if ($product->get('inventory') < $item['quantity'] + $init) throw new \Exception('product_too_many');

			} elseif ($product->get('class') == 'complex') {

				foreach ($product->get('variants') as $key => $variant) {

					if ($key != 'settings' && $variant['id'] == $item['variant']) {

						if (!$variant['inventory'] || $variant['inventory'] < $item['quantity'] + $init) throw new \Exception('product_too_many');

					}

				}

			}

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

}
