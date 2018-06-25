<?php

namespace Statamic\Addons\Statamify\SuggestModes;

use Statamic\Addons\Suggest\Modes\AbstractMode;
use Statamic\Addons\Statamify\Statamify;

class CountrySuggestMode extends AbstractMode
{

  public function suggestions()
  {

    switch ($this->request->input('name')) {

      case 'countries':
        
        $countries = Statamify::location();

        return array_map(function($code, $name) {
          return [ 'value' => $code, 'text' => $name ]; 
        }, array_keys(reset($countries)), reset($countries));

      break;
    }
  }
  
}
