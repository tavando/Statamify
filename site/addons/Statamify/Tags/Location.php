<?php

namespace Statamic\Addons\Statamify\Tags;

use Statamic\Addons\Statamify\Statamify;

class Location
{

  public static function tag($s)
  {

    $countries = Statamify::location();
    $regions = Statamify::location('regions');

    if (isset($s->context['country'])) {

      $country = $s->context['country'];
      $region = $s->context['region'];

    }

    if (isset($s->context['defaultKey'])) {

      $country = $s->context['default']['country'];
      $region = $s->context['default']['region'];

      if (session('statamify.shipping_country')) {

        $country = session('statamify.shipping_country');
        $region = '';

      }

    }

    if (isset($s->context['old']['data'])) {

      if (isset($s->context['defaultKey'])) {

        $country = $s->context['old']['data']['shipping']['country'];
        $region = $s->context['old']['data']['shipping']['region'];

      } else {

        $country = $s->context['old']['data']['billing']['country'];
        $region = $s->context['old']['data']['billing']['region'];

      }
      
    }

    if (!isset($country)) {

      if (session('statamify.shipping_country')) {

        $country = session('statamify.shipping_country');
        $region = '';

      }
      
    }

    $countries = reset($countries);

    if (isset($country)) {

      $regions = reset($regions);

      if (isset($regions[$country])) {

        $regions = $regions[$country];

      } else {

        $regions = false;

      }

      return [ 
        'countries' => $countries, 
        'regions' => $regions,
        'country' => @$countries[$country],
        'region' => $regions ? (isset($regions[$region]) ? $regions[$region] : $region) : $region,
        'country_code' => $country,
        'region_code' => @$region
      ];

    } else {

      return [ 
        'countries' => $countries, 
        'regions' => ''
      ];

    }

  }

}