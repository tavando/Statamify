<?php

namespace Statamic\Addons\Statamify\Listeners;

use Statamic\API\Entry;
use Statamic\Addons\Statamify\Models\Cart;
use Statamic\Extend\Listener;
use Statamic\API\Stache;
use Statamic\API\Storage;

class AuthListener extends Listener
{

  public $events = [
    'auth.logout' => 'logout',
    'user.registered' => 'register'
  ];

  public function logout()
  {

    session()->forget('statamify.default_address');
		session()->forget('statamify.shipping_country');
		session()->forget('statamify.shipping_method');

    $cart = new Cart();
    $cart->setShipping();

  }

  public function register($user)
  {

    $customer = Entry::whereSlug($user->email(), 'store_customers');

    if ($customer) {

      $data = $customer->toArray();
      $data['user'] = $user->id();
      $unset = ['fieldset', 'slug', 'url', 'uri', 'url_path', 'permalink', 'edit_url', 'published',
      'order', 'order_type', 'collection', 'is_entry', 'last_modified', 'content_raw'];

      foreach ($unset as $value) {
        unset($data[$value]);
      }

      $new_customer = Entry::create($user->id())
      ->collection('store_customers')
      ->with($data)
      ->published(true)
      ->get();

      $new_customer->save();
      $customer->delete();

      foreach ($data['orders'] as $order) {
        $order_data = Storage::getYAML('statamify/orders/' . $order['slug']);
        $order_data['listing_customer'] = str_replace($user->email(), $user->id(), $order_data['listing_customer']);
        Storage::putYAML('statamify/orders/' . $order['slug'], $order_data);
      }

      Stache::update();

    }

  }
  
}