<?php

namespace Statamic\Addons\Statamify\System;

use Statamic\API\File;
use Statamic\Addons\Statamify\System\System;
use Statamic\API\YAML;

class Helpers
{

  public static function location($location)
  {

    $prefix = site_locale() != 'en' ? site_locale() : '';
    $file = File::get(__DIR__ . '/../resources/location/' . $prefix .'_' . $location . '.yaml');

    if ($file) {

      return YAML::parse($file);

    } else {

      return YAML::parse(File::get(__DIR__ . '/../resources/location/' . $location . '.yaml'));

    }

  }

  public static function money($value, $get, $exchange = null) {

    $currencies = System::config('currency');

    if (!is_null($currencies) && count($currencies)) {

      $key = array_search('1', array_column($currencies, 'rate'));

      if (!is_bool($key)) {

        if ($get == 'noexchange' || $exchange == 'noexchange') {

          $currency = $currencies[$key];

        } elseif ($get == 'exchange' && $exchange) {

          $key = array_search($exchange['code'], array_column($currencies, 'code'));
          $currency = $currencies[$key];
          $currency['rate'] = $exchange['rate'];

        } else {

          if (session('statamify.currency')) {

            $currency = session('statamify.currency');

          } else {

            $currency = $currencies[$key];
            session(['statamify.currency' => $currency]);

          }

        }

        $priceFormat = $currency['formatPrice'];
        $minus = false;

        if ($value < 0) { $value *= -1; $minus = true; }

        if ($currency['rate'] != '1') { $value *= (float) $currency['rate']; }

        switch ($priceFormat) {
          case 1: $price = number_format($value, 0, '', ','); break;
          case 2: $price = number_format($value, 0, '', ' '); break;
          case 3: $price = number_format($value, 2, '.', ','); break;
          case 4: $price = number_format($value, 2, '.', ' '); break;
          case 5: $price = number_format($value, 2, ',', ' '); break;
          case 6: $price = number_format($value, 0, '', ''); break;
          case 7: $price = number_format($value, 2, '.', ''); break;
          case 8: $price = number_format($value, 2, ',', ''); break;
          
          default:
          $price = number_format($value, 2, '.', ' '); break;
          break;
        }

        if (!$get || $get == 'zero' || $get == 'noexchange' || $get == 'exchange') {

          return ($minus ? '- ' : '') . str_replace('[symbol]', $currency['symbol'], str_replace('[price]', $price, $currency['format']));

        } else {

          if ($get == 'formatPriceJS') {

            switch($currency['formatPrice']) {
              case 1:
                return "number_format(price, 0, '', ',')";
                break;
              case 2:
                return "number_format(price, 0, '', ' ')";
                break;
              case 3:
                return "number_format(price, 2, '.', ',')";
                break;
              case 4:
                return "number_format(price, 2, '.', ' ')";
                break;
              case 5:
                return "number_format(price, 2, ',', ' ')";
                break;
              case 6:
                return "number_format(price, 0, '', '')";
                break;
              case 7:
                return "number_format(price, 2, '.', '')";
                break;
              default:
                return "number_format(price, 2, ',', '')";
            } 

          }

          return isset($currency[$get]) ? $currency[$get] : '';

        }

      }

    }

  }

}
