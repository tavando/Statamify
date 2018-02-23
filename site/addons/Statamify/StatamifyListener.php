<?php

namespace Statamic\Addons\Statamify;

use Statamic\API\Nav;
use Statamic\API\Entry;
use Statamic\Extend\Listener;
use Statamic\API\Stache;

class StatamifyListener extends Listener
{
		/**
		 * The events to be listened for, and the methods to call.
		 *
		 * @var array
		 */
		public $events = [
			'cp.add_to_head' => 'addCss',
			'cp.nav.created' => 'addNavItems',
			'content.saved' => 'checkAdd'
		];

		public function addCss() {

			return $this->css->tag("statamify") . PHP_EOL;

		}

		public function addNavItems($nav) {

			$store = Nav::item('store');
			$store->add(Nav::item('statamify-analytics')->title('Analytics')->icon('line-graph'));
			$store->add(Nav::item('statamify-orders')->title('Orders')->icon('shopping-cart'));

			$products = Nav::item('statamify-products')->title('Products')->route('entries.show', 'products')->icon('shop');
			$products->add(Nav::item('statamify-all-products')->title('All')->route('entries.show', 'products'));
			$products->add(Nav::item('statamify-collections')->title('Collections')->route('entries.show', 'collections'));
			$products->add(Nav::item('statamify-types')->title('Types')->route('entries.show', 'types'));
			$products->add(Nav::item('statamify-vendors')->title('Vendors')->route('entries.show', 'vendors'));
			$store->add($products);

			$store->add(Nav::item('statamify-customers')->title('Customers')->icon('users'));
			$store->add(Nav::item('statamify-coupons')->title('Coupons')->icon('ticket'));

			$settings = Nav::item('statamify-settings')->title('Settings')->route('addon.settings', 'statamify')->icon('sound-mix');
			$settings->add(Nav::item('statamify-general')->title('General')->route('addon.settings', 'statamify'));
			$settings->add(Nav::item('statamify-checkout')->title('Checkout'));
			$settings->add(Nav::item('statamify-shipping')->title('Shipping'));
			$store->add($settings);

			$nav->add($store);
			$nav->remove('content.collections.collections:products');
			$nav->remove('content.collections.collections:types');
			$nav->remove('content.collections.collections:vendors');

		}

		public function checkAdd($entry, $original) {

			$collection = $original['attributes']['collection'];

			if ($collection == 'products') {

				$change = false;
				$check = [
					'listing_inventory' => $this->checkInventory($entry),
					'listing_image' => $this->checkImage($entry),
					'listing_type' => $this->checkRelation($entry, 'type'),
					'listing_vendor' => $this->checkRelation($entry, 'vendor'),
				];

				foreach ($check as $key => $value) {
					
					if ($entry->get($key) != $value) {

						$entry->set($key, $value);
						$change = true;

					}

				}

				if ($change) {

					$entry->save();
					Stache::update();

				}
			}

		}

		private function checkInventory($entry) {

			if (!$entry->get('track_inventory')) {

				$inventory = '-';

			} else {

				if ($entry->get('class') == 'simple') {

					$inventory = '<span class="inventory-quantity">' . ($entry->get('inventory') ?: '0') . '</span> in stock';

				} elseif ($entry->get('class') == 'complex') {

					$variants = $entry->get('variants');
					$sum = 0;

					foreach ($variants as $key => $variant) {

						if (!is_string($key)) {

							if ($variant['inventory']) {

								$sum += (int) $variant['inventory'];

							}

						}

					}

					$inventory = '<span class="inventory-quantity">' . ($sum ?: '0') . '</span> in stock for ' . (count($variants) - 1) . ' variants';

				}

			}

			return $inventory;

		}

		private function checkImage($entry) {

			if ($entry->get('image')) {

				return '<div class="statamify-thumb" style="background-image: url(' . $entry->get('image') . ')"></div>';

			} else {

				return '';

			}

		}

		private function checkRelation($entry, $type) {

			if ($entry->get($type)) {

				$relation = Entry::find($entry->get($type));
				$attributes = $relation->toArray();
				$url = '/cp/collections/entries/' . $attributes['collection'] . '/' . $attributes['slug'];

				return $relation->get('title') . ' <a href="' . $url . '" class="statamify-link"><span class="icon icon-forward"></span></a>';

			} else {

				return '';

			}

		}
}
