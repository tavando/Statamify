<?php

namespace Statamic\Addons\Statamify\Listeners;

use Statamic\Extend\Listener;
use Statamic\API\Nav;
use Statamic\Addons\Statamify\Statamify;

class NavCreatedListener extends Listener
{

  public $events = [
    'cp.nav.created' => 'nav'
  ];

  public function nav($nav)
  {

    $store = Nav::item('store');
		$store->add(Nav::item('statamify-analytics')->title(Statamify::t('analytics'))->route('statamify.analytics')->icon('line-graph'));
		$store->add(Nav::item('statamify-orders')->title(Statamify::t('orders'))->route('statamify.orders')->icon('shopping-cart'));

		$products = Nav::item('statamify-products')->title(Statamify::t('products'))->route('entries.show', 'store_products')->icon('shop');
		$products->add(Nav::item('statamify-all-products')->title(Statamify::t('all'))->route('entries.show', 'store_products'));
		$products->add(Nav::item('statamify-categories')->title(Statamify::t('categories'))->route('entries.show', 'store_categories'));
		$products->add(Nav::item('statamify-types')->title(Statamify::t('types'))->route('entries.show', 'store_types'));
		$products->add(Nav::item('statamify-vendors')->title(Statamify::t('vendors'))->route('entries.show', 'store_vendors'));
		$store->add($products);

		$store->add(Nav::item('statamify-customers')->title(Statamify::t('customers'))->route('entries.show', 'store_customers')->icon('users'));
		$store->add(Nav::item('statamify-coupons')->title(Statamify::t('coupons'))->route('entries.show', 'store_coupons')->icon('ticket'));

		$settings = Nav::item('statamify-settings')->title(Statamify::t('settings'))->route('addon.settings', 'statamify')->icon('sound-mix');
		$store->add($settings);

		$nav->add($store);
		$nav->remove('content.collections.collections:store_products');
		$nav->remove('content.collections.collections:store_types');
		$nav->remove('content.collections.collections:store_vendors');
		$nav->remove('content.collections.collections:store_categories');
		$nav->remove('content.collections.collections:store_coupons');
		$nav->remove('content.collections.collections:store_customers');

  }
  
}