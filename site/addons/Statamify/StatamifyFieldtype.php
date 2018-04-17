<?php

namespace Statamic\Addons\Statamify;

use Statamic\Extend\Fieldtype;

class StatamifyFieldtype extends Fieldtype
{
    public function blank()
    {
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
