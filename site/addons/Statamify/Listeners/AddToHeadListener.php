<?php

namespace Statamic\Addons\Statamify\Listeners;

use Statamic\Extend\Listener;

class AddToHeadListener extends Listener
{

  public $events = [
    'cp.add_to_head' => 'head'
  ];

  public function head()
  {

    return $this->css->tag("statamify") . PHP_EOL;

  }
  
}