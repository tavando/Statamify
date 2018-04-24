<?php

namespace Statamic\Addons\Statamify\Models;

use Auth;
use Statamic\API\Asset;
use Statamic\Addons\Statamify\Models\Cart;
use Statamic\API\Entry;
use Statamic\API\File;
use Statamic\API\Stache;
use Statamic\Addons\Statamify\Statamify;
use Statamic\API\Storage;
use Statamic\API\User;
use Statamic\API\YAML;

class Order
{

	public function __construct(Cart $cart) {

		$this->cart = $cart->get();

	}

	public function create($data) {

		$this->data = $data;

		$order_next_id = (int) Statamify::config('order_next_id', 1000);
		$order_id_format = Statamify::config('order_id_format', '#[id]');

		// Create Entry / Order title based on the format in the settings

		$this->data['title'] = str_replace('[id]', $order_next_id, $order_id_format);

		// Update Shipping for Summary

		$this->updateShippingMethods();

		// Charge and Update Payment for Summary

		$this->updatePaymentMethods();

		// Create User if doesn't exist

		if ($this->data['user']) {

			$this->user = User::getCurrent();

		} else {

			$this->createUser();

		}

		// Get Customer or create one if doesn't exist

		$customer = $this->getCustomer();

		// Create summary that will be populated in StatamifyOrderSummary addon

		$this->data['summary'] = [
			'items' => [],
			'total' => $this->cart['total']
		];

		foreach ($this->cart['items'] as $item) {

			// Update inventory for products
			
			$this->updateProductInventory($item);

			// Add image based on the class and crop it with Glide

			if ($item['variant']) {

				foreach ($item['product']['gallery'] as $img) {

					$asset = Asset::find($img);

					if ($asset && $asset->get('title') == $item['variant']['sku']) {

						$image = $asset->manipulate(['w' => 50, 'h' => 50, 'fit' => 'crop']);
						$image_original = $img;

					}

				}

			}

			if (!isset($image)) {

				if (isset($item['product']['image'])) {

					$asset = Asset::find($item['product']['image']);

					if ($asset) {

						$image = $asset->manipulate(['w' => 50, 'h' => 50, 'fit' => 'crop']);
						$image_original = $item['product']['image'];

					}

				}

			}

			// Transform data to match Statamify Order Summary addon
			
			$this->data['summary']['items'][] = [
				'id' => $item['item_id'],
				'name' => $item['product']['title'],
				'variant' => $item['variant'] ? $item['variant']['attrs'] : false,
				'sku' => $item['variant'] ? @$item['variant']['sku'] : @$item['product']['sku'],
				'price' => $item['variant'] && @$item['variant']['price'] ? $item['variant']['price'] : @$item['product']['price'],
				'quantity' => $item['quantity'],
				'custom' => isset($item['custom']) && $item['custom'] ? $item['custom'] : null,
				'image' => @$image,
				'image_original' => @$image_original,
				'edit_url' => '/cp/collections/entries/store_products/' . $item['product']['slug']
			];

		}

		// Add data for order listing columns

		$this->data['listing_status'] = '<span class="order-status ' . $this->data['status'] . '">' . Statamify::t('status.' . $this->data['status']) . '</span>';
		$this->data['listing_total'] = Statamify::money($this->data['summary']['total']['grand']);
		$this->data['listing_customer'] = $customer->get('title') . ' <a href="/cp/collections/entries/store_customers/' . $this->user->get('id') . '" class="statamify-link"><span class="icon icon-forward"></span></a>';

		$this->data['listing_email'] = $this->data['email'];
		unset($this->data['email']);

		/*

		Old Entry-based

		$order = Entry::create(slugify($this->data['title']))
			->collection('orders')
			->with($this->data)
			->published(false)
			->date(date('Y-m-d H:i'))
			->get();*/

		$order_id = date('Y-m-d_H-i-s') . '.' . slugify($this->data['title']);
		$this->data['date'] = date('Y-m-d H:i:s');
		$this->data['slug'] = slugify($this->data['title']);
		$this->data['id'] = $order_id;

		Storage::putYAML('statamify/orders/' . $order_id, $this->data);

		// Update ID of the next order in settings

		$settings_file = File::get('site/settings/addons/statamify.yaml');
		$settings = YAML::parse($settings_file);
		$settings['order_next_id'] = $order_next_id + 1;
		File::put('site/settings/addons/statamify.yaml', YAML::dump($settings));

		// Add order to customer's field Orders

		$customers_orders = $customer->get('orders');
		$customers_orders[] = ['id' => $this->data['title'], 'slug' => $order_id];
		$customer->set('orders', $customers_orders);
		$customer->save();

		Stache::update();

		return $this->data;

	}

	private function createUser() {

		$user_data = [
			'first_name' => $this->data['shipping']['first_name'],
			'last_name' => $this->data['shipping']['last_name'],
			'password' => $this->data['password']
		];

		$user = User::create()
		->with($user_data)
		->email($this->data['email'])
		->get();

		$user->save();
		$this->user = $user;
		$this->data['user'] = $user->get('id');

		unset($this->data['password'], $this->data['password_confirmation']);

		Auth::loginUsingId($user->id());

	}

	private function getCustomer() {

		$customer = Entry::whereSlug($this->user->get('id'), 'store_customers');

		if (!$customer) {

			// Create default address from shipping details

			$address = $this->data['shipping'];
			$address['default'] = true;

			$customer_data = [
				'user' => $this->data['user'],
				'title' => $this->user->get('first_name') . ' ' . $this->user->get('last_name'),
				'listing_orders' => 1,
				'listing_spent' => '<span data-total="' . $this->cart['total']['grand'] . '">' . Statamify::money($this->cart['total']['grand']) . '</span>',
				'addresses' => $address,
				'orders' => []
			];

			$customer = Entry::create($this->user->get('id'))
			->collection('store_customers')
			->with($customer_data)
			->published(true)
			->get();

		} else {

			$customer->set('listing_orders', $customer->get('listing_orders') + 1);
			$spent = explode('"', $customer->get('listing_spent'));
			$spent[1] = $spent[1] + $this->cart['total']['grand'];
			$spent[2] = '>' . Statamify::money($spent[1]) . '</span>';
			$customer->set('listing_spent', join($spent, '"'));

		}

		return $customer;

	}

	private function updateShippingMethods() {

		if (isset($this->data['billing_diff']) && $this->data['billing_diff'] == '1') {

			$this->data['billing_diff'] = true;

		}

		unset($this->data['saved_addresses']);

		$shipping_zones = Statamify::config('shipping_zones');

		$shipping_method = explode('|', $this->data['shipping_method']);
		$zone = $shipping_zones[$shipping_method[0]];

		$this->data['shipping_method'] = [
			'zone' => $zone['name'],
			'name' => $shipping_method[1],
			'rate' => $this->cart['total']['shipping']
		];

	}

	private function updatePaymentMethods() {

		switch ($this->data['payment_method']) {

			case 'cheque':
				$this->data['status'] = 'awaiting_payment';
				$this->data['payment_method'] = ['name' => 'Cheque', 'fee' => 0];
			break;

			case 'paypal':
				$this->data['status'] = 'awaiting_payment';
				$this->data['payment_method'] = ['name' => 'PayPal', 'fee' => 0];
			break;

			case 'stripe':

				$charge = $this->statamic->api('StatamifyStripe')->charge($this->data, $this->cart);

				$this->data['status'] = 'pending';
				$this->data['payment_method'] = [
					'name' => $this->statamic->api('StatamifyStripe')->getConfig('name'), 
					'fee' => $charge->application_fee, 
					'id' => $charge->id
				];

			break;
			
			default:
				$this->data['status'] = 'pending';
				$this->data['payment_method'] = ['name' => 'Default', 'fee' => 0];
			break;

		}

	}

	private function updateProductInventory($item) {

		$product = Entry::find($item['product']['id']);

		if ($product->get('track_inventory')) {

			switch ($product->get('class')) {

				case 'complex':

				$variants = $product->get('variants');

				foreach ($variants as $variant_key => $variant) {

					if (isset($variant['id']) && $variant['id'] == $item['variant']['id']) {

						$variants[$variant_key]['inventory'] = $variants[$variant_key]['inventory'] - $item['quantity'];
						$product->set('variants', $variants);
						$product->save();

						break;

					}

				}

				break;

				default:

				$product->set('inventory', $product->get('inventory') - $item['quantity']);
				$product->save();

				break;
			}

		}

	}

}