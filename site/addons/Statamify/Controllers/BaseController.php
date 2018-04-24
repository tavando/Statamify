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

}