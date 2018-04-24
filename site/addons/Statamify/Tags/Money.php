<?php

namespace Statamic\Addons\Statamify\Tags;

use Statamic\Addons\Statamify\Statamify;

class Money
{

  public static function tag($s)
  {

    return Statamify::money(0, $s->get('get'));

  }

}