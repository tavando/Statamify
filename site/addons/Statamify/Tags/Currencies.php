<?php

namespace Statamic\Addons\Statamify\Tags;

use Statamic\Addons\Statamify\Statamify;

class Currencies
{

  public static function tag($s)
  {

    $currencies = Statamify::config('currency');

    if ($currencies) {

      $key = array_search('1', array_column($currencies, 'rate'));

      if (!is_bool($key)) {

        if (session('statamify.currency')) {

          $currency = session('statamify.currency');

        } else {

          $currency = $currencies[$key];
          session(['statamify.currency' => $currency]);

        }

        return [
          'active' => $currency['code'],
          'currencies' => $currencies
        ];

      } else {

        return ['no_results' => true];

      }

    } else {

      return ['no_results' => true];

    }

  }

}