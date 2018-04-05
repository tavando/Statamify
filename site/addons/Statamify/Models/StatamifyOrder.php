<?php

namespace Statamic\Addons\Statamify\Models;

use Statamic\API\Asset;
use Statamic\API\Entry;
use Statamic\API\File;
use Statamic\API\Stache;
use Statamic\API\User;
use Statamic\API\YAML;

class StatamifyOrder
{

	public function __construct($statamic) {

		$this->statamic = $statamic;
		$this->cart = $statamic->cartGet();

	}

	public function create($data) {

		$this->data = $data;

		$order_next_id = $this->statamic->getConfigInt('order_next_id', 1000);
		$order_id_format = $this->statamic->getConfig('order_id_format', '#[id]');

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

				$asset = Asset::find($item['product']['image']);

				if ($asset) {

					$image = $asset->manipulate(['w' => 50, 'h' => 50, 'fit' => 'crop']);
					$image_original = $item['product']['image'];

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
				'edit_url' => '/cp/collections/entries/products/' . $item['product']['slug']
			];

		}

		// Add data for order listing columns

		$this->data['listing_status'] = '<span class="order-status ' . $this->data['status'] . '">' . $this->statamic->t('status.' . $this->data['status']) . '</span>';
		$this->data['listing_total'] = $this->statamic->money($this->data['summary']['total']['grand']);
		$this->data['listing_customer'] = $customer->get('title') . ' <a href="/cp/collections/entries/customers/' . $this->user->get('id') . '" class="statamify-link"><span class="icon icon-forward"></span></a>';

		$this->data['listing_email'] = $this->data['email'];
		unset($this->data['email']);

		$order = Entry::create(slugify($this->data['title']))
			->collection('orders')
			->with($this->data)
			->published(false)
			->date(date('Y-m-d H:i'))
			->get();

		// Update ID of the next order in settings

		$settings_file = File::get('site/settings/addons/statamify.yaml');
		$settings = YAML::parse($settings_file);
		$settings['order_next_id'] = $order_next_id + 1;
		File::put('site/settings/addons/statamify.yaml', YAML::dump($settings));

		$order->save();

		// Add order to customer's field Orders

		$customers_orders = $customer->get('orders');
		$customers_orders[] = $order->get('id');
		$customer->set('orders', $customers_orders);
		$customer->save();

		Stache::update();

		return $order;

	}

	private function createUser() {

		$user_data = [
			'first_name' => $this->data['shipping'][0]['first_name'],
			'last_name' => $this->data['shipping'][0]['last_name'],
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

	}

	private function getCustomer() {

		$customer = Entry::whereSlug($this->user->get('id'), 'customers');

		if (!$customer) {

			// Create default address from shipping details

			$address = $this->data['shipping'];
			$address[0]['default'] = true;

			$customer_data = [
				'user' => $this->data['user'],
				'title' => $this->user->get('first_name') . ' ' . $this->user->get('last_name'),
				'listing_orders' => 1,
				'listing_spent' => '<span data-total="' . $this->cart['total']['grand'] . '">' . $this->statamic->money($this->cart['total']['grand']) . '</span>',
				'addresses' => $address,
				'orders' => []
			];

			$customer = Entry::create($this->user->get('id'))
			->collection('customers')
			->with($customer_data)
			->published(true)
			->get();

		} else {

			$customer->set('listing_orders', $customer->get('listing_orders') + 1);
			$spent = explode('"', $customer->get('listing_spent'));
			$spent[1] = $spent[1] + $this->cart['total']['grand'];
			$spent[2] = '>' . $this->statamic->money($spent[1]) . '</span>';
			$customer->set('listing_spent', join($spent, '"'));

		}

		return $customer;

	}

	private function countryFormat() {

		// Merge shipping country and region into one field

		$this->data['shipping'][0]['country'] = $this->data['shipping'][0]['country'] . ';' . @$this->data['shipping'][0]['region'];

		// Merge billing country and region into one field

		$this->data['billing'][0]['country'] = $this->data['billing'][0]['country'] . ';' . @$this->data['billing'][0]['region'];

		// billing_diff is toggle field so we convert '1' to true

		if (isset($this->data['billing_diff']) && $this->data['billing_diff'] == '1') {

			$this->data['billing_diff'] = true;

		}

		unset($this->data['shipping'][0]['region'], $this->data['billing'][0]['region'], $this->data['saved_addresses']);

	}

	private function updateShippingMethods() {

		$this->countryFormat();

		$shipping_zones = $this->statamic->getConfig('shipping_zones');

		$shipping_method = explode('|', $this->data['shipping_method']);
		$zone = $shipping_zones[$shipping_method[0]];
		$this->data['shipping_method'] = ['zone' => $zone['name']];

		// Add shipping methods details

		foreach (@$zone['price_rates'] as $rate) {
			
			if (slugify($rate['name']) == $shipping_method[1]) {

				$this->data['shipping_method']['name'] = $rate['name'];
				$this->data['shipping_method']['rate'] = @$rate['rate'] ?: 0;

			}

		}

		// If there are no shipping methods - maybe we should check weight rates

		if (!isset($this->data['shipping_method']['name'])) {

			foreach (@$zone['weight_rates'] as $rate) {

				if (slugify($rate['name']) == $shipping_method[1]) {

					$this->data['shipping_method']['name'] = $rate['name'];
					$this->data['shipping_method']['rate'] = @$rate['rate'] ?: 0;

				}

			}

		}

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