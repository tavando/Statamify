<?php

namespace Statamic\Addons\Statamify\Validators;

use Statamic\Addons\Statamify\Statamify;
use Validator;

class AccountValidator
{

  public static function address($data)
  {

    $messages = [
      'addresso' => 'max:0',

      'first_name.required' => 'First name is required',
      'last_name.required' => 'Last name is required',
      'address.required' => 'Address is required',
      'city.required' => 'City is required',
      'postal.required' => 'Postal is required',
      'country.required' => 'Country is required',

    ];

    $validator = Validator::make($data, [
      'first_name' => 'required',
      'last_name' => 'required',
      'address' => 'required',
      'city' => 'required',
      'postal' => 'required',
      'country' => 'required',
    ], $messages);

    if ($validator->fails()) {

      Statamify::response(400, (Statamify::t('somethings_wrong', 'errors')));

    }

  }

}