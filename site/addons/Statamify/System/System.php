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

  public static function response($code, $msg = '')
  {

    if ($code >= 400) {

      $data = [
        'type' => 'Error',
        'response' => ['message' => $msg] 
      ];

    } else {

      $data = [
        'type' => '',
        'response' => ['message' => $msg] 
      ];

    }

    header('HTTP/1.1 ' . $code . ' ' . $data['type']);
    header('Content-Type: application/json; charset=UTF-8');
    die(json_encode($data['response']));

  }

  public static function route($as, $type, $add)
  {

    $routes = Config::getRoutes();

    if (is_array($type)) {

      $collection = collect($routes[$add]);

    } else {

      $collection = collect($routes[$type]);

    }

    $url = $collection->search(function($route) use ($as) {
      return isset($route['as']) && $route['as'] == $as;
    });

    if ($url) {

      if (is_array($type)) {

        foreach ($type as $key => $value) {
          
          $url = str_replace('{' . $key . '}', $value, $url);

        }

      }

      return $url;

    } else {

      return $as;

    }
    
    return $url ?: $as;

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