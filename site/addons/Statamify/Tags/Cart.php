<?php

namespace Statamic\Addons\Statamify\Tags;

use Statamic\Addons\Statamify\Models\Cart as Model;

class Cart
{

  public static function tag($s)
  {

    $cart = new Model();
    
    return $cart->get($s->get('instance') ?: 'cart');

  }

}