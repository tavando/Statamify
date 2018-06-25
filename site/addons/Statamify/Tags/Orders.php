<?php

namespace Statamic\Addons\Statamify\Tags;

use Statamic\API\Entry;
use Statamic\API\Storage;
use Statamic\API\User;

class Orders
{

  public static function tag($s)
  {

    $user = User::getCurrent();

    if ($user) {

      $customer = Entry::whereSlug($user->get('id'), 'store_customers');

      if ($customer) {

        $orders_list = $customer->get('orders');
        $orders = [];

        if ($orders_list) {

          foreach ($orders_list as $order) {

            $key_elements = explode('.', $order['slug']);
            $orders[$key_elements[1]] = Storage::getYAML('statamify/orders/' . $order['slug']);

          }

          if ($orders) {

            if ($s->get('sort')) {

              $sort = explode(':', $s->get('sort'));

              if ($sort[0] == 'asc') {

                $sorted = collect($orders)->sortBy($sort[0]);

              } else {

                $sorted = collect($orders)->sortByDesc($sort[0]);

              }

              $orders = $sorted->values()->all();

            }

            return $orders;

          } else {

            return ['no_results' => true];

          }

        } else {

          return ['no_results' => true];

        }

      } else {

        return ['no_results' => true];

      }

    } else {

      return redirect(Statamify::route('statamify.account.login'));

    }

  }

}