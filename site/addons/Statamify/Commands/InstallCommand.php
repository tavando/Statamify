<?php

namespace Statamic\Addons\Statamify\Commands;

use Statamic\Extend\Command;
use Statamic\API\File;
use Statamic\API\YAML;

class InstallCommand extends Command
{

  protected $signature = 'statamify:install';

  protected $description = '';

  public function __construct()
  {
    parent::__construct();
  }

  public function handle()
  {

    $this->copy(__DIR__ . '/../Installation/Fieldsets', __DIR__ . '/../../../settings/fieldsets');
    $this->copy(__DIR__ . '/../Installation/Collections/store_categories', __DIR__ . '/../../../content/collections/store_categories');
    $this->copy(__DIR__ . '/../Installation/Collections/store_coupons', __DIR__ . '/../../../content/collections/store_coupons');
    $this->copy(__DIR__ . '/../Installation/Collections/store_customers', __DIR__ . '/../../../content/collections/store_customers');
    $this->copy(__DIR__ . '/../Installation/Collections/store_products', __DIR__ . '/../../../content/collections/store_products');
    $this->copy(__DIR__ . '/../Installation/Collections/store_types', __DIR__ . '/../../../content/collections/store_types');
    $this->copy(__DIR__ . '/../Installation/Collections/store_vendors', __DIR__ . '/../../../content/collections/store_vendors');

    $store_routes = [
      '/account' => [
        'template' => 'account/account',
        'protect' => [
          'type' => 'logged_in',
          'login_url' => '/account/login'
        ]
      ],
      '/account/order/{slug}' => [
        'template' => 'account/order',
        'protect' => [
          'type' => 'logged_in',
          'login_url' => '/account/login'
        ]
      ],
      '/account/address/{address_index}' => [
        'template' => 'account/address',
        'protect' => [
          'type' => 'logged_in',
          'login_url' => '/account/login'
        ]
      ],
      '/account/login' => 'account/login',
      '/account/register' => 'account/register',
      '/account/forgotten' => 'account/forgotten',
      '/account/reset' => 'account/reset',

      '/store' => 'store/store',
      '/store/cart' => 'store/cart',
      '/store/checkout' => 'store/checkout',
      '/store/summary' => 'store/summary',
    ];

    $store_collections = [
      'store_categories' => '/store/categories/{slug}',
      'store_products' => '/store/products/{slug}',
      'store_types' => '/store/types/{slug}',
      'store_vendors' => '/store/vendors/{slug}',
    ];

    $routes = YAML::parse(File::get('site/settings/routes.yaml'));
    $routes['routes'] = array_merge($routes['routes'], $store_routes);
    $routes['collections'] = array_merge($routes['collections'], $store_collections);

    File::put('site/settings/routes.yaml', YAML::dump($routes));

  }

  private function copy($src,$dst)
  {
    $dir = opendir($src); 
    @mkdir($dst); 
    while(false !== ( $file = readdir($dir)) ) { 
      if (( $file != '.' ) && ( $file != '..' )) { 
        if ( is_dir($src . '/' . $file) ) { 
          recurse_copy($src . '/' . $file,$dst . '/' . $file); 
        } 
        else { 
          copy($src . '/' . $file,$dst . '/' . $file); 
        } 
      } 
    } 
    closedir($dir); 
  }
}
