<?php

namespace Statamic\Addons\Statamify\ServiceProviders;

use Illuminate\Routing\Route;
use Illuminate\Routing\RouteCollection;
use Statamic\Extend\ServiceProvider;
use Statamic\Addons\Statamify\Statamify;
use Statamic\Addons\Statamify\Filters\StatamifyFilter;

class StatamifyServiceProvider extends ServiceProvider
{

  /**
   * Bootstrap the application services.
   *
   * @return void
   */
  public function boot()
  {

    $old_routes = app('router')->getRoutes();
    $new_routes = new RouteCollection();

    foreach ($old_routes as $i => $route) {

      if ($route->getUri() == '{segments?}') {

        foreach (Statamify::routes() as $r => $c) {

          $config = explode('@', $r);
          $new_routes->add(new Route([$config[0]], $config[1] , ['uses' => $c]));

        }

      }

      $new_routes->add($route);
      
    }

    app('router')->setRoutes($new_routes);
    
  }

}
