<?php

namespace Statamic\Addons\Statamify\Modifiers;

use Statamic\Extend\Modifier;
use Statamic\Addons\Statamify\Statamify;

class AttrsModifier extends Modifier
{
  /**
   * Modify a value
   *
   * @param mixed  $value    The value to be modified
   * @param array  $params   Any parameters used in the modifier
   * @param array  $context  Contextual values
   * @return mixed
   */
  public function index($value, $params, $context) {

    $values = explode('|', $value);

    if (class_exists('\Statamic\Addons\T\TAPI')) {
      $values = array_map(function($string) {
        return app(\Statamic\Addons\T\TAPI::class)->api('T')->string($string);
      }, $values);
    }
    
    return join($values, ', '); 
    
  }

}
