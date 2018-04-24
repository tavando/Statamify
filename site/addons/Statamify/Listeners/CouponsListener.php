<?php

namespace Statamic\Addons\Statamify\Listeners;

use Statamic\Extend\Listener;
use Statamic\API\Stache;

class CouponsListener extends Listener
{

  private $cp = false;
  private $original = null;

  public $events = [
    'content.saved' => 'saved',
  ];

  public function saved($entry, $original)
  {

    $this->original = $original;
    $data = $entry->toArray();

    if (isset($data['collection'])) {

      if ($data['collection'] == 'store_coupons') {

        $data_original = reset($original['data']);

        if (isset($data['used_by'])) {

          if($data['used_by'] != @$data_original['used_by']) {

            $entry->set('listing_used', count(explode(';', preg_replace('/\s+/', '', $data['used_by']))));
            $entry->save();

          }

        } else {

          if(!isset($data['listing_used']) || $data['listing_used'] != @$data_original['listing_used']) {

            $entry->set('listing_used', 0);
            $entry->save();

          }

        }

        Stache::update();

      }

    }

  }

}