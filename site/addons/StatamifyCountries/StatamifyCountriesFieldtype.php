<?php

namespace Statamic\Addons\StatamifyCountries;

use Statamic\API\Helper;
use Statamic\CP\Fieldset;
use Statamic\Extend\Fieldtype;
use Statamic\CP\FieldtypeFactory;

class StatamifyCountriesFieldtype extends Fieldtype
{

    public function blank() {
        return null;
    }

    public function preProcess($data) {
    	
        return $data;
    }

    public function process($data) {

        return $data;

    }

}