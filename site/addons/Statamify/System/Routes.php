<?php

namespace Statamic\Addons\Statamify\System;

class Routes
{

  const account_ctrl = '\Statamic\Addons\Statamify\Controllers\AccountController';
  const base_ctrl = '\Statamic\Addons\Statamify\Controllers\BaseController';
  const cart_ctrl = '\Statamic\Addons\Statamify\Controllers\CartController';
  const order_ctrl = '\Statamic\Addons\Statamify\Controllers\OrderController';

  public static function all()
  {

    return [

      // Account Routes

      'POST@statamify/account/address' => self::account_ctrl . '@address',
      'GET@statamify/account/address-remove/{index}' => self::account_ctrl . '@addressRemove',

      // Base Routes

      'GET@statamify/countries' => self::base_ctrl . '@countries',

      // Cart Routes

      'POST@statamify/cart/add' => self::cart_ctrl . '@add',
      'POST@statamify/cart/coupon' => self::cart_ctrl . '@coupon',
      'POST@statamify/cart/coupon-remove' => self::cart_ctrl . '@couponRemove',
      'POST@statamify/cart/default-address' => self::cart_ctrl . '@defaultAddress',
      'GET@statamify/cart/get' => self::cart_ctrl . '@get',
      'POST@statamify/cart/set-shipping' => self::cart_ctrl . '@setShipping',
      'POST@statamify/cart/set-shipping-method' => self::cart_ctrl . '@setShippingMethod',
      'POST@statamify/cart/update' => self::cart_ctrl . '@update',

      // Order Routes

      'POST@statamify/order/create' => self::order_ctrl . '@create',

    ];

  }

}