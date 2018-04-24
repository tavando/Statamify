<?php

namespace Statamic\Addons\Statamify\Fieldtypes;

use Statamic\Extend\Fieldtype;

class CountriesFieldtype extends Fieldtype
{

  public function blank() {
    return null;
  }

  public function preProcess($data)
  {
   
    return $data;
  }

  public function process($data)
  {

    return $data;

  }

}