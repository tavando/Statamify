<?php

namespace Statamic\Addons\Statamify\Modifiers;

use Statamic\Extend\Modifier;
use Statamic\Addons\Statamify\Statamify;

class MoneyModifier extends Modifier
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

    if (isset($context['summary'])) {

      if (isset($context['summary']['currency'])) {

        if ($context['summary']['currency']['rate'] != '1') {

          return Statamify::money($value, 'exchange', $context['summary']['currency']); 

        } else {

          return Statamify::money($value, 'noexchange'); 

        }

      }

    }

    return Statamify::money($value); 
    
  }

}
