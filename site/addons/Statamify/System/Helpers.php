<?php

namespace Statamic\Addons\Statamify\System;

use Statamic\API\File;
use Statamic\Addons\Statamify\System\System;
use Statamic\API\YAML;

class Helpers
{

  public static function location($location)
  {

    return YAML::parse(File::get(__DIR__ . '/../resources/location/' . $location . '.yaml'));

  }

  public static function money($value, $get) {

    $currencies = System::config('currency');
    
    if (count($currencies)) {

      $key = array_search('1', array_column($currencies, 'rate'));

      if (!is_bool($key)) {

        $currency = $currencies[$key];
        $priceFormat = $currency['formatPrice'];
        $minus = false;

        if ($value < 0) { $value *= -1; $minus = true; }

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

        if (!$get || $get == 'zero') {

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