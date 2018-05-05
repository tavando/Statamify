<?php

namespace Statamic\Addons\Statamify\Validators;

use Statamic\Addons\Statamify\Statamify;
use Statamic\API\Storage;
use Statamic\API\User;
use Statamic\Addons\Statamify\Validators\CartValidator as Validate;
use Validator;

class OrderValidator
{

  public static function create($data, $cart)
  {

    $messages = [
      'email.required' => 'Email address is required',
      'email.email' => 'Email address is not valid',

      'shipping.first_name.required' => 'Shipping: First name is required',
      'shipping.last_name.required' => 'Shipping: Last name is required',
      'shipping.address.required' => 'Shipping: Address is required',
      'shipping.city.required' => 'Shipping: City is required',
      'shipping.postal.required' => 'Shipping: Postal is required',
      'shipping.country.required' => 'Shipping: Country is required',

      'billing.first_name.required_if' => 'Billing: First name is required',
      'billing.last_name.required_if' => 'Billing: Last name is required',
      'billing.address.required_if' => 'Billing: Address is required',
      'billing.city.required_if' => 'Billing: City is required',
      'billing.postal.required_if' => 'Billing: Postal is required',
      'billing.country.required_if' => 'Billing: Country is required',

      'password.required_if' => 'Password is required',
      'password_confirmation.required_if' => 'Password Confirmation is required',
    ];

    $validator = Validator::make($data, [
      'addresso' => 'max:0',
      'email' => 'required|email',

      'shipping' => 'required|array',
      'shipping.first_name' => 'required',
      'shipping.last_name' => 'required',
      'shipping.address' => 'required',
      'shipping.city' => 'required',
      'shipping.postal' => 'required',
      'shipping.country' => 'required',

      'billing' => 'array|required_if:billing_diff,1',
      'billing.first_name' => 'required_if:billing_diff,1',
      'billing.last_name' => 'required_if:billing_diff,1',
      'billing.address' => 'required_if:billing_diff,1',
      'billing.city' => 'required_if:billing_diff,1',
      'billing.postal' => 'required_if:billing_diff,1',
      'billing.country' => 'required_if:billing_diff,1',

      'shipping_method' => 'required',
      'payment_method' => 'required',

      'password' => Statamify::config('guest_checkout') ? '' : 'confirmed|required_if:user,',
      'password_confirmation' => Statamify::config('guest_checkout') ? '' : 'required_if:user,',

      'payment_token' => 'required_if:payment_method,stripe',

    ], $messages);

    if ($validator->fails()) {

      return $validator->errors()->all();

    }

    if (!$data['user'] && User::whereEmail($data['email'])) {

      return [Statamify::t('customer_exists', 'errors')];

    }

    // Check coupons again

    $coupons = $cart['coupons'];

    if (count($coupons)) {

      foreach ($coupons as $coupon) {

        $valid = Validate::coupon(['coupon' => $coupon, 'email' => $data['email']], $cart);

        if (!is_bool($valid)) {

          return [$valid];

        }

      }

    }

    return true;

  }

  public static function refund($data)
  {

    $validator = Validator::make($data, [
      'amount' => 'required|numeric',
      'id' => 'required',
    ]);

    if ($validator->fails()) {

      Statamify::response(400, (Statamify::t('somethings_wrong', 'errors')));

    }

    $order = Storage::getYAML('statamify/orders/' . $data['id']);
    $old_refund = isset($order['summary']['total']['refunded']) ? (float) $order['summary']['total']['refunded'] : 0;

    if ($order['summary']['total']['grand'] < ($data['amount'] + ($old_refund*-1))) {

      Statamify::response(400, Statamify::t('somethings_wrong', 'errors'));
      
    }

  }

}