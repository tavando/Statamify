<?php

namespace Statamic\Addons\Statamify\Validators;

use Statamic\Addons\Statamify\Statamify;
use Validator;

class CartValidator
{

  public static function add($data)
  {

    $validator = Validator::make($data, [
      'product' => 'required',
      'quantity' => 'required',
    ]);

    if ($validator->fails()) {

      throw new \Exception(Statamify::t('somethings_wrong', 'errors'));

    }

  }

  public static function defaultAddress($data)
  {

    $validator = Validator::make($data, [
      'address' => 'required|numeric',
    ]);

    if ($validator->fails()) {

      throw new \Exception(Statamify::t('somethings_wrong', 'errors'));

    }

  }

  public static function update($data)
  {

    $validator = Validator::make($data, [
      'item_id' => 'required',
      'quantity' => 'required',
    ]);

    if ($validator->fails()) {

      throw new \Exception(Statamify::t('somethings_wrong', 'errors'));

    }

  }

  public static function setShipping($data)
  {

    $validator = Validator::make($data, [
      'shipping_country' => 'required',
    ]);

    if ($validator->fails()) {

      throw new \Exception(Statamify::t('somethings_wrong', 'errors'));

    }

  }

  public static function setShippingMethod($data)
  {

    $validator = Validator::make($data, [
      'shipping' => 'required',
    ]);

    if ($validator->fails()) {

      throw new \Exception(Statamify::t('somethings_wrong', 'errors'));

    }

  }

}