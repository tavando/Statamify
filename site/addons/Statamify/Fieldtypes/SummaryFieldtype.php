<?php

namespace Statamic\Addons\Statamify\Fieldtypes;

use Statamic\API\Helper;
use Statamic\CP\Fieldset;
use Statamic\Extend\Fieldtype;
use Statamic\CP\FieldtypeFactory;

class SummaryFieldtype extends Fieldtype
{

  public function blank()
  {
    return null;
  }

  public function preProcess($data)
  {

    $data['config'] = ['currencySymbol' => '', 'moneyFormat' => ''];
    $currencies = $this->getConfig('currency');

    if (count($currencies)) {

      $key = array_search('1', array_column($currencies, 'rate'));

      if (!is_bool($key)) {

        $currency = $currencies[$key];

        $data['config'] = [
          'currencySymbol' => $currency['symbol'],
          'moneyFormat' => $currency['format']
        ];

      }

    }

    return $data;
    
  }

  public function process($data)
  {
    return $data;
  }

}
