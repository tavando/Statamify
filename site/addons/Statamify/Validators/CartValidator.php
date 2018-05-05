<?php

namespace Statamic\Addons\Statamify\Validators;

use Statamic\API\Entry;
use Statamic\Addons\Statamify\Statamify;
use Statamic\API\User;
use Validator;

class CartValidator
{

  public static function add($data)
  {

    $validator = Validator::make($data, [
      'product' => 'required',
      'quantity' => 'required'
    ]);

    if ($validator->fails()) {

      Statamify::response(400, (Statamify::t('somethings_wrong', 'errors')));

    }

  }

  public static function coupon($data, $cart)
  {

    $validator = Validator::make($data, [
      'coupon' => 'required'
    ]);

    if ($validator->fails()) {

      return Statamify::t('somethings_wrong', 'errors');

    }

    $coupon = $data['coupon'];
    $coupon_entry = Entry::whereSlug($coupon, 'store_coupons');

    if ($coupon_entry) {

      $user = User::getCurrent();

      if ($user) {

        $email = $user->email();

      } else {

        $email = isset($data['email']) ? $data['email'] : '';

      }

      // Check if minimum purchase is met

      if ($coupon_entry->get('min')) {

        if ((float) $cart['total']['sub'] < (float) $coupon_entry->get('min')) {

          return Statamify::t('coupon_not_valid', 'errors');

        }

      }

      // Check if shipping country is in selected countries

      if ($coupon_entry->get('countries')) {

        $shipping_country = session('statamify.shipping_country');

        if (!in_array($shipping_country, $coupon_entry->get('countries'))) {

          return Statamify::t('coupon_not_valid', 'errors');

        }

      }

      // Check if logged in customer's email is in selected emails

      if ($coupon_entry->get('customers') && array_filter($coupon_entry->get('customers'))) {

        if (!$email || ($email && !in_array($email, $coupon_entry->get('customers')))) {

          return Statamify::t('coupon_not_valid', 'errors');

        }

      }

      // Check if total number of coupons are used

      if ($coupon_entry->get('total')) {

        if ($coupon_entry->get('used_by') && $coupon_entry->get('total') == count()) {

          return Statamify::t('coupon_not_valid', 'errors');

        }

      }

      // Check if total number of coupons per user is used

      if ($coupon_entry->get('per_user')) {

        if (!$email) {

          return Statamify::t('coupon_not_valid', 'errors');

        }

        if ($coupon_entry->get('used_by')) {

          $emails_collection = collect(explode(';', preg_replace('/\s+/', '', $coupon_entry->get('used_by'))));

          $filtered = $emails_collection->reject(function ($value) use($email) {
            return $value != $email;
          });

          if ($coupon_entry->get('per_user') == $filtered->count()) {

            return Statamify::t('coupon_not_valid', 'errors');

          }

        }

      }

      // Check dates

      if ($coupon_entry->get('start_date')) {

        if( strtotime($coupon_entry->get('start_date')) > strtotime('now') ) {

          return Statamify::t('coupon_not_valid', 'errors');

        }

      }

      if ($coupon_entry->get('end_date')) {

        if( strtotime($coupon_entry->get('end_date')) < strtotime('now') ) {

          return Statamify::t('coupon_not_valid', 'errors');

        }

      }


    } else {

      return Statamify::t('coupon_not_found', 'errors');

    }

    return true;

  }

  public static function couponRemove($data)
  {

    $validator = Validator::make($data, [
      'index' => 'required|numeric',
    ]);

    if ($validator->fails()) {

      Statamify::response(400, (Statamify::t('somethings_wrong', 'errors')));

    }

  }

  public static function defaultAddress($data)
  {

    $validator = Validator::make($data, [
      'address' => 'required|numeric',
    ]);

    if ($validator->fails()) {

      Statamify::response(400, (Statamify::t('somethings_wrong', 'errors')));

    }

  }

  public static function update($data)
  {

    $validator = Validator::make($data, [
      'item_id' => 'required',
      'quantity' => 'required',
    ]);

    if ($validator->fails()) {

      Statamify::response(400, (Statamify::t('somethings_wrong', 'errors')));

    }

  }

  public static function setShipping($data)
  {

    $validator = Validator::make($data, [
      'shipping_country' => 'required',
    ]);

    if ($validator->fails()) {

      Statamify::response(400, (Statamify::t('somethings_wrong', 'errors')));

    }

  }

  public static function setShippingMethod($data)
  {

    $validator = Validator::make($data, [
      'shipping' => 'required',
    ]);

    if ($validator->fails()) {

      Statamify::response(400, (Statamify::t('somethings_wrong', 'errors')));

    }

  }

}