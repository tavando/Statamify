<?php

namespace Statamic\Addons\Statamify\Controllers;

use Statamic\Addons\Statamify\Validators\AccountValidator as Validate;
use Statamic\Extend\Controller;
use Statamic\API\Entry;
use Illuminate\Http\Request;
use Statamic\API\User;

class AccountController extends Controller
{

  public function address(Request $request) {

    $data = $request->all();

    Validate::address($data);

    $user = User::getCurrent();

    if ($user) {

      $customer = Entry::whereSlug($user->id(), 'store_customers');

      if ($customer) {

        $addresses = $customer->get('addresses');

        $data['country'] = $data['country'] . ';' . $data['region'];
        $index = $data['address_index'];

        unset($data['region'], $data['_token'], $data['address_index'], $data['addresso']);

        if ($data['default'] == 'true') {

          foreach ($addresses as $key => $addr) {
            
            $addresses[$key]['default'] = false;

          }

          $data['default'] = true;
          session()->forget('statamify.default_address');

        } else {

          $data['default'] = false;

        }

        if ($index == 'new') {

          $addresses[] = $data;

        } else {

          if (isset($addresses[$index - 1])) {

            $addresses[$index - 1] = $data;

          }

        }

        $customer->set('addresses', array_values($addresses));
        $customer->save();

        if ($index == 'new') {

          return redirect('/account');

        } else {

          return redirect('/account/address/' . $index);

        }

      }

    }

  }

  public function addressRemove() {

    $uri = explode('/', $_SERVER['REQUEST_URI']);
    $id = end($uri);

    $user = User::getCurrent();

    if ($user) {

      $customer = Entry::whereSlug($user->id(), 'store_customers');

      if ($customer) {

        $addresses = $customer->get('addresses');

        if (isset($addresses[$id - 1])) {

          unset($addresses[$id - 1]);

          $customer->set('addresses', array_values($addresses));
          $customer->save();

          return redirect('/account');

        }

      }

    }

  }

}