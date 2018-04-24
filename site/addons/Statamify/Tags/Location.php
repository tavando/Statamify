<?php

namespace Statamic\Addons\Statamify\Tags;

use Statamic\Addons\Statamify\Statamify;

class Location
{

  public static function tag($s)
  {

    $countries = Statamify::location();
		$regions = Statamify::location('regions');
		
		$is_part = strpos($s->get('country'), ';') !== false;

		if ($s->get('country') && $is_part) {

			$country = $s->get('country');

		} else {

			$country = session('statamify.shipping_country');

		}

		if ($country) {

			if ($is_part) {

				$parts = explode(';', $country);
				$country = $parts[0];
				$region = $parts[1];

			}

			$regions = reset($regions);

			if (isset($regions[$country])) {

				$regions = $regions[$country];

			} else {

				$regions = false;

			}

		} else {

			$regions = false;

		}

		$countries = reset($countries);

		return [ 
			'countries' => $countries, 
			'regions' => $regions,
			'country' => $is_part ? $countries[$country] : $country,
			'region' => $is_part ? (isset($regions[$region]) ? $regions[$region] : $region) : null,
			'country_code' => $country,
			'region_code' => @$region
		];

  }

}