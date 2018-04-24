<?php

namespace Statamic\Addons\Statamify\System;

use Statamic\Config\Addons;
use Statamic\API\Config;
use Statamic\API\Str;
use Statamic\API\GlobalSet;

class System
{

  public static function config($key, $default = null)
  {

    $config = app(Addons::class)->get(Str::snake('Statamify')) ?: [];

    if (is_null($key)) {
      return $config;
    }

    if (! is_array($key)) {
      $keys = [$key];
    }

    foreach ($keys as $key) {
      if (array_has($config, $key)) {
        return array_get($config, $key);
      }
    }

    return $default;

  }

  public static function response($code, $msg)
  {

    header('HTTP/1.1 ' . $code . ' Internal Server Error');
    header('Content-Type: application/json; charset=UTF-8');
    die(json_encode(array('message' => $msg)));

  }

  public static function wrapGlobals($data)
  {

    // Add globals to emails

    $global = GlobalSet::whereHandle('global');

    $data['site_url'] = Config::getSiteUrl();
    $data['store_name'] = self::config('store_name');
    $data['store_address'] = self::config('store_address');
    $data['date'] = date(Config::get('system.date_format'), strtotime($data['date']));

    return $data;

  }

  public static function t($string, $space, $params)
  {

    return app('translator')->trans('addons.Statamify::' . $space . '.' . $string, $params);

  }

}