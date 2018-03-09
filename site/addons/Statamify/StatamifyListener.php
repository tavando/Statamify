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
		'cp.add_to_head' => 'eventAddToHead',
		'cp.nav.created' => 'eventNavCreated',
		'cp.entry.published' => 'eventPublished',
		'content.saved' => 'eventSaved',
	];

	public $cp = false;
	public $original = null;

	public function eventAddToHead() {

		return $this->css->tag("statamify") . PHP_EOL;

	}

	public function eventNavCreated($nav) {

		$store = Nav::item('store');
		$store->add(Nav::item('statamify-analytics')->title($this->api('Statamify')->t('analytics'))->icon('line-graph'));
		$store->add(Nav::item('statamify-orders')->title($this->api('Statamify')->t('orders'))->route('entries.show', 'orders')->icon('shopping-cart'));

		$products = Nav::item('statamify-products')->title($this->api('Statamify')->t('products'))->route('entries.show', 'products')->icon('shop');
		$products->add(Nav::item('statamify-all-products')->title($this->api('Statamify')->t('all'))->route('entries.show', 'products'));
		$products->add(Nav::item('statamify-collections')->title($this->api('Statamify')->t('collections'))->route('entries.show', 'collections'));
		$products->add(Nav::item('statamify-types')->title($this->api('Statamify')->t('types'))->route('entries.show', 'types'));
		$products->add(Nav::item('statamify-vendors')->title($this->api('Statamify')->t('vendors'))->route('entries.show', 'vendors'));
		$store->add($products);

		$store->add(Nav::item('statamify-customers')->title($this->api('Statamify')->t('customers'))->route('entries.show', 'customers')->icon('users'));
		$store->add(Nav::item('statamify-coupons')->title($this->api('Statamify')->t('coupons'))->route('entries.show', 'coupons')->icon('ticket'));

		$settings = Nav::item('statamify-settings')->title($this->api('Statamify')->t('settings'))->route('addon.settings', 'statamify')->icon('sound-mix');
		$store->add($settings);

		$nav->add($store);
		$nav->remove('content.collections.collections:products');
		$nav->remove('content.collections.collections:types');
		$nav->remove('content.collections.collections:vendors');
		$nav->remove('content.collections.collections:collections');
		$nav->remove('content.collections.collections:coupons');
		$nav->remove('content.collections.collections:customers');
		$nav->remove('content.collections.collections:orders');

	}

	/* EVENT PUBLISHED */

	public function eventPublished($entry) {

		$collection = $entry->toArray()['collection'];

		switch ($collection) {

			case 'products':
				$this->eventPublishedProducts($entry);
			break;

		}

	}

	private function eventPublishedProducts($entry) {

		$change = false;
		$check = [
			'listing_inventory' => $this->eventPublishedProductsInventory($entry),
			'listing_image' => $this->eventPublishedProductsImage($entry),
			'listing_type' => $this->eventPublishedProductsRelation($entry, $this->original, 'type', 'types'),
			'listing_vendor' => $this->eventPublishedProductsRelation($entry, $this->original, 'vendor', 'vendors'),
		];

		foreach ($check as $key => $value) {

			if ($entry->get($key) != $value) {

				$entry->set($key, $value);
				$change = true;

			}

		}

		$this->eventPublishedProductsCollections($entry, $this->original);

		if ($change) {

			$entry->save();
			Stache::update();

		}

	}

	private function eventPublishedProductsInventory($entry) {

		if (!$entry->get('track_inventory')) {

			$inventory = '-';

		} else {

			if ($entry->get('class') == 'simple') {

				$inventory = '<span class="inventory-quantity">' . ($entry->get('inventory') ?: '0') . '</span> ' . $this->api('Statamify')->t('instock');

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

				$inventory = '<span class="inventory-quantity">' . ($sum ?: '0') . '</span> ' . $this->api('Statamify')->t('instock_for', 'statamify', ['number' => (count($variants) - 1)]);

			}

		}

		return $inventory;

	}

	private function eventPublishedProductsImage($entry) {

		if ($entry->get('image')) {

			return '<div class="statamify-thumb" style="background-image: url(' . $entry->get('image') . ')"></div>';

		} else {

			return '';

		}

	}

	private function eventPublishedProductsRelation($entry, $original, $field, $collection) {

		if ($entry->get($field)) {

			/********** UPDATE RELATION'S PRODUCTS FIELD IF DOESN'T HAVE PRODUCT'S ID IN IT  ***/
			$relation = Entry::find($entry->get($field));

			if (!$relation->get('products') || !in_array($entry->get('id'), $relation->get('products'))) {

				$products = $relation->get('products') ?: [];
				$products[] = $entry->get('id');
				$relation->set('products', $products);
				$this->cp = true;
				$relation->save();
				Stache::update();

			}

			$this->eventPublishedProductsRelationOldCheck($entry, $original, $field);

			return $relation->get('title') . ' <a href="' . $relation->toArray()['edit_url'] . '" class="statamify-link"><span class="icon icon-forward"></span></a>';

		} else {

			$this->eventPublishedProductsRelationOldCheck($entry, $original, $field);

			return '';

		}

	}

	private function eventPublishedProductsRelationOldCheck($entry, $original, $field) {

		if (isset($original['data'])) {

			/********** REMOVE PRODUCT'S ID FROM OLD RELATION'S PRODUCTS FIELD ***/
			$original_data = reset($original['data']);

			if (isset($original_data[$field]) && $entry->get($field) != $original_data[$field]) {

				$old_relation = Entry::find($original_data[$field]);
				$old_relation_products = $old_relation->get('products') ?: [];
				$old_relation_products = array_diff($old_relation_products, [$entry->get('id')]);
				$old_relation->set('products', $old_relation_products);
				$this->cp = true;
				$old_relation->save();
				Stache::update();

			}

		}

	}

	private function eventPublishedProductsCollections($entry, $original) {

		if ($entry->get('collections')) {

			$new_collections = array_diff($entry->get('collections'), (isset($original_data['collections']) ? $original_data['collections'] : []));

			/********** UPDATE EVERY COLLECTION WITH PRODUCT ID ***/
			foreach ($new_collections as $id) {

				$collection = Entry::find($id);

				if (!$collection->get('products') || !in_array($entry->get('id'), $collection->get('products'))) {

					$products = $collection->get('products') ?: [];
					$products[] = $entry->get('id');
					$collection->set('products', $products);
					$this->cp = true;
					$collection->save();

				}

			}

			$this->eventPublishedProductsCollectionsOldCheck($entry, $original);

		} else {

			$this->eventPublishedProductsCollectionsOldCheck($entry, $original);

		}

	}

	private function eventPublishedProductsCollectionsOldCheck($entry, $original) {

		if (isset($original['data'])) {

			$original_data = reset($original['data']);

			/********** REMOVE PRODUCT'S ID FROM OLD COLLECTIONS' PRODUCTS FIELD ***/
			if (isset($original_data['collections']) && $entry->get('collections') != $original_data['collections']) {

				$old_collections = array_diff($original_data['collections'], ($entry->get('collections') ?: []));

				foreach ($old_collections as $id) {
					
					$old_collection = Entry::find($id);
					$old_collection_products = $old_collection->get('products') ?: [];
					$old_collection_products = array_diff($old_collection_products, [$entry->get('id')]);
					$old_collection->set('products', $old_collection_products);
					$this->cp = true;
					$old_collection->save();

				}

				Stache::update();

			}

		}

	}

	/* EVENT SAVED */

	public function eventSaved($entry, $original) {

		$this->original = $original;
		$data = $entry->toArray();

		if (isset($data['collection'])) {

			$collection = $data['collection'];

			switch ($collection) {

				case 'types':
				case 'vendors':
				if (!$this->cp) $this->eventSavedRelation($entry, $original, $collection);
				break;

				case 'collections':
				if (!$this->cp) $this->eventSavedCollection($entry, $original);
				break;

				case 'coupons':
				$this->eventSavedCoupons($entry, $original);
				break;

				case 'orders':
				$this->eventSavedOrders($entry, $original);
				break;
			}

		}

	}

	private function eventSavedRelation($entry, $original, $collection) {

		$data_original = reset($original['data']);

		if ($entry->get('products') != @$data_original['products']) {

			$products_original = isset($data_original['products']) ? $data_original['products'] : [];
			$products = $entry->get('products') ?: [];

			/********** UPDATE ALL FROM ARRAYS BELOW. ADD - ADD RELATION TO PRODUCT. REMOVE - REMOVE RELATION FROM PRODUCT ***/
			$add = array_diff($products, $products_original);
			$remove = array_diff($products_original, $products);

			foreach ($add as $id) {

				if ($id) {

					$type = substr($collection, 0, -1);
					$product = Entry::find($id);

					if ($product->get($type)) {
						$old_relation = Entry::find($product->get($type));
						$old_relation_products = $old_relation->get('products') ?: [];
						$old_relation_products = array_diff($old_relation_products, [$id]);
						$old_relation->set('products', $old_relation_products);
						$old_relation->save();
					}

					if ($product->get($type) != $entry->get('id')) {

						$product->set($type, $entry->get('id'));
						$product->set('listing_' . $type, $entry->get('title') . ' <a href="' . $entry->toArray()['edit_url'] . '" class="statamify-link"><span class="icon icon-forward"></span></a>');
						$product->save();

					}
					
				}

			}

			foreach ($remove as $id) {

				if ($id) {

					$product = Entry::find($id);
					$type = substr($collection, 0, -1);

					if ($product->get($type) == $entry->get('id')) {
						$product->set($type, '');
						$product->set('listing_' . $type, '');
						$product->save();
					}

				}

			}

			Stache::update();

		}

	}

	private function eventSavedCollection($entry, $original) {

		$data_original = reset($original['data']);

		if ($entry->get('products') != @$data_original['products']) {

			$products_original = isset($data_original['products']) ? $data_original['products'] : [];
			$products = $entry->get('products') ?: [];

			/********** UPDATE ALL FROM ARRAYS BELOW. ADD - ADD COLLECTION TO PRODUCT. REMOVE - REMOVE COLLECTION FROM PRODUCT ***/
			$add = array_diff($products, $products_original);
			$remove = array_diff($products_original, $products);

			foreach ($add as $id) {

				if ($id) {

					$product = Entry::find($id);

					if (!$product->get('collections') || !in_array($entry->get('id'), $product->get('collections'))) {

						$products = $product->get('collections') ?: [];
						$products[] = $entry->get('id');
						$product->set('collections', $products);
						$product->save();

					}

				}

			}

			foreach ($remove as $id) {

				if ($id) {

					$product = Entry::find($id);

					if ($product->get('collections') && in_array($entry->get('id'), $product->get('collections'))) {
						$products = array_diff($product->get('collections'), [$entry->get('id')]);
						$product->set('collections', $products);
						$product->save();
					}

				}

			}

			Stache::update();

		}

	}

	private function eventSavedCoupons($entry, $original) {

		$data = $entry->toArray();
		$data_original = reset($original['data']);

		if (isset($data['used_by'])) {

			if($data['used_by'] != @$data_original['used_by']) {

				$entry->set('listing_used', count($data['used_by']));
				$entry->save();

			}

		} else {

			if(!isset($data['listing_used']) || $data['listing_used'] != @$data_original['listing_used']) {

				$entry->set('listing_used', 0);
				$entry->save();
				
			}

		}

		Stache::update();

	}

	private function eventSavedOrders($entry, $original) {

		$data = $entry->toArray();
		$data_original = reset($original['data']);

		if (isset($data['status'])) {

			if($data['status'] != @$data_original['status']) {

				$entry->set('listing_status', '<span class="order-status ' . $data['status'] . '">' . $this->api('Statamify')->t('status.' . $data['status']) . '</span>');
				$entry->save();

			}

		}

		Stache::update();

	}

}
