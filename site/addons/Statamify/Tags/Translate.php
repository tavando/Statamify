<?php

namespace Statamic\Addons\Statamify\Tags;

use Statamic\Addons\Statamify\Statamify;

class Translate
{

  public static function tag($s)
  {

    $key = $s->get('key');

    if ($key) {

      if (class_exists('\Statamic\Addons\T\TAPI')) {

        $overwrite = $s->api('T')->string($key);

        if ($overwrite != $key) {

          return $overwrite;

        }

      }

      $string = Statamify::t($key, 'theme');

      if (strpos($string, 'addons') === false) {

        return $string;

      } else {

        return Statamify::t($key, 'statamify');

      }

    }

    return '';

  }

}