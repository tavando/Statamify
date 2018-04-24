<?php

namespace Statamic\Addons\Statamify\Listeners;

use Statamic\Extend\Listener;
use Statamic\Addons\Statamify\Models\Cart;

class AuthListener extends Listener
{

  public $events = [
    'auth.logout' => 'logout'
  ];

  public function logout()
  {

    session()->forget('statamify.default_address');
		session()->forget('statamify.shipping_country');
		session()->forget('statamify.shipping_method');

    $cart = new Cart();
    $cart->setShipping();

  }
  
}