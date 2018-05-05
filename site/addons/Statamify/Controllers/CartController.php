<?php

namespace Statamic\Addons\Statamify\Controllers;

use Statamic\Addons\Statamify\Models\Cart;
use Statamic\Addons\Statamify\Validators\CartValidator as Validate;
use Statamic\Extend\Controller;
use Illuminate\Http\Request;
use Statamic\Addons\Statamify\Statamify;

class CartController extends Controller
{

  public function add(Cart $cart, Request $request)
  {

    $data = $request->all();

    Validate::add($data);

    return $cart->add($data);

  }

  public function coupon(Cart $cart,Request $request)
  {

    $data = $request->all();
    $c = $cart->get();

    if (count($c['coupons']) && Statamify::config('coupons_multiple', false)) {

      return Statamify::response(400, Statamify::t('coupon_cant_multiple', 'errors'));
      
    }

    if (!in_array($data['coupon'], $c['coupons'])) {

      $valid = Validate::coupon($data, $c);

      if (is_bool($valid)) {

        return $cart->addCoupon($data['coupon']);

      } else {

        return Statamify::response(400, $valid);

      }

    } else {

      return Statamify::response(400, Statamify::t('coupon_used', 'errors'));

    }

  }

  public function couponRemove(Cart $cart,Request $request)
  {

    $data = $request->all();

    Validate::couponRemove($data);

    return $cart->removeCoupon($data['index']);

  }

  public function defaultAddress(Cart $cart, Request $request)
  {

    $data = $request->all();

    Validate::defaultAddress($data);

    $cart->setDefaultAddress($data['address']);

  }

  public function get(Cart $cart)
  {

    return $cart->get();

  }

  public function update(Cart $cart, Request $request)
  {

    $data = $request->all();

    Validate::update($data);

    return $cart->update($data);

  }

  public function setShipping(Cart $cart, Request $request)
  {

    Validate::setShipping($request->all());

    $countries = Statamify::location();
    $regions = Statamify::location('regions');

    $data = [ 
      'countries' => reset($countries), 
      'regions' => reset($regions) 
    ];

    if ($request->shipping_country) {
      session(['statamify.shipping_country' => $request->shipping_country]);
    } else {
      session()->forget('statamify.shipping_country');
    }

    $cart->setShipping();
    $data['cart'] = $cart->get();

    return $data;

  }

  public function setShippingMethod(Request $request, Cart $cart) {

    Validate::setShippingMethod($request->all());

    $shipping = explode('|', $request->shipping);

    session(['statamify.shipping_method' => isset($shipping[1]) ? $shipping[1] : 0]);

    return $cart->get();

  }

}