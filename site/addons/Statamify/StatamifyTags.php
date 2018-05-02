<?php

namespace Statamic\Addons\Statamify;

use Statamic\Addons\Statamify\Tags\Addresses;
use Statamic\Addons\Statamify\Tags\Cart;
use Statamic\Addons\Statamify\Tags\Customer;
use Statamic\Addons\Statamify\Tags\Location;
use Statamic\Addons\Statamify\Tags\Money;
use Statamic\Addons\Statamify\Tags\Orders;
use Statamic\Addons\Statamify\Tags\QueryUrl;
use Statamic\Extend\Tags;

class StatamifyTags extends Tags
{

  public function index()
  {

    $get = $this->get('get');

    if ($get) {
      return $get;
    }
    
    return '';

  }

  public function addresses()
  {

    $addresses = Addresses::tag($this);

    if (isset($addresses['no_results'])) {

      return $addresses;

    } else {

      return $this->parseLoop($addresses);

    }

  }

  public function cart()
  {

    return Cart::tag($this);

  }

  public function cheque() {

    return [

      'active' => $this->getConfig('gateway_cheque_active'), 
      'info' => $this->getConfig('gateway_cheque_info')

    ];

  }

  public function customer()
  {

    return Customer::tag($this);

  }

  public function defaultAddress() {

    return session('statamify.default_address') ?: ['no_results' => true];

  }

  public function gateways()
  {

     return $this->parseLoop($this->getConfig('gateways'));

  }

  public function guest()
  {

    return $this->getConfigBool('guest_checkout');

  }

  public function location()
  {

     return Location::tag($this);

  }

  public function money()
  {

    return Money::tag($this);

  }

  public function orders()
  {

    $orders = Orders::tag($this);

    if (isset($orders['no_results'])) {

      return $orders;

    } else {

      if ($this->get('slug')) {

        return $orders[$this->get('slug')];

      } else {

        return $this->parseLoop($orders);

      }

    }

  }

  public function sort()
  {

    if (isset($_GET['sort'])) {

      return $_GET['sort'];

    } else {

      return 'order:desc';

    }

  }

  public function url()
  {

  	return QueryUrl::tag($this);

  }

}