<?php

namespace Statamic\Addons\Statamify\Tags;

use Statamic\API\Entry;
use Statamic\API\User;

class Customer
{

  public static function tag($s)
  {

    $user = User::getCurrent();

    if ($user) {

      $customer = Entry::whereSlug($user->get('id'), 'store_customers');

      if ($customer) {

        return $customer->toArray();

      } else {

        return ['no_results' => true];

      }

    } else {

      return redirect(Statamify::route('statamify.account.login'));

    }

  }

}