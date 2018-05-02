<?php

namespace Statamic\Addons\Statamify;

use Statamic\Addons\Statamify\System\Helpers;
use Statamic\Addons\Statamify\System\Routes;
use Statamic\Addons\Statamify\System\System;

class Statamify
{

  public static function config($key = null, $default = null)
  {

     return System::config($key, $default);

  }

  public static function location($location = 'countries')
  {

     return Helpers::location($location);

  }

  public static function money($value, $get = null)
  {

     return Helpers::money($value, $get);

  }

  public static function response($code = 200, $msg = '')
  {

    return System::response($code, $msg);

  }

  public static function route($as = '', $type = 'routes', $add = 'routes')
  {

    return System::route($as, $type, $add);

  }

  public static function routes()
  {

    return Routes::all();

  }

  public static function wrapGlobals($data)
  {

    return System::wrapGlobals($data);

  }

  public static function t($string, $space = 'statamify', $params = [])
  {

    return System::t($string, $space, $params);

  }

}