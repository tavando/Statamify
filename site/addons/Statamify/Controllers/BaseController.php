<?php

namespace Statamic\Addons\Statamify\Controllers;

use Statamic\Extend\Controller;
use Illuminate\Http\Request;
use Statamic\Addons\Statamify\Statamify;

class BaseController extends Controller
{

  public function countries()
  {

    $countries = Statamify::location();
    $regions = Statamify::location('regions');

    $data = [ 
      'countries' => reset($countries), 
      'regions' => reset($regions) 
    ];

    return $data;

  }

  public function currency(Request $request)
  {

    $data = $request->all();

    if (isset($data['currency'])) {

      $currencies = Statamify::config('currency');

      if (count($currencies)) {

        $key = array_search(strtoupper($data['currency']), array_column($currencies, 'code'));

        if (!is_bool($key)) {

          session(['statamify.currency' => $currencies[$key]]);

        } else {

          return Statamify::response(404, Statamify::t('somethings_wrong', 'errors'));

        }

      } else {

        return Statamify::response(404, Statamify::t('somethings_wrong', 'errors'));

      }

    } else {

      return Statamify::response(400, Statamify::t('somethings_wrong', 'errors'));

    }

  }

}