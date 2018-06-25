<?php

namespace Statamic\Addons\Statamify\Tags;

use Statamic\Addons\Statamify\Models\Cart as CartModel;
use Statamic\API\Entry;
use Statamic\Addons\Statamify\Statamify;
use Statamic\API\User;

class Addresses
{

  public static function tag($s)
  {

    $user = User::getCurrent();

    if ($user) {

      $customer = Entry::whereSlug($user->id(), 'store_customers');

      if ($customer) {

        $default_address = session('statamify.default_address');
        $addresses = $customer->get('addresses');

        foreach ($addresses as $key => $address) {

          if (!$default_address && $address['default']) {

            $default_address = $key;
            session(['statamify.default_address' => [
              'defaultKey' => $key,
              'default' => $address
            ]]);
            session(['statamify.shipping_country' => $address['country']]);

            $cart = new CartModel();
            $cart->setShipping();

          }

        }

        return $customer->get('addresses');

      } else {

        return ['no_results' => true];

      }

    } else {

      return ['no_results' => true];

    }

  }

}