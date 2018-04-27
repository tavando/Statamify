<?php

namespace Statamic\Addons\Statamify\Controllers;

use Statamic\Addons\Statamify\Models\Analytics;
use Statamic\Extend\Controller;
use Illuminate\Http\Request;
use Statamic\Addons\Statamify\Statamify;

class AnalyticsController extends Controller
{

  public function index()
  {

    $analytics = new Analytics([], 'perday');
    $data = $analytics->get();

    return $this->view('analytics', [
      'title' => 'Store Analytics',
      'split' => $data['split'],
      'all_orders' => json_encode($data['all_orders']),
      'total_orders' => json_encode($data['total_orders']),
      'total_sales' => json_encode($data['total_sales']),
      'avg_order_value' => json_encode($data['avg_order_value']),
      'repeat_rate' => json_encode($data['repeat_rate']),
      'moneyFormat' => $data['moneyFormat'],
      'moneyFormatFn' => $data['moneyFormatFn'],
      'moneySymbol' => $data['moneySymbol'],
    ]);

  }

  public function data(Request $request)
  {

    $this->authorize('super');

    $post = $request->all();

    $analytics = new Analytics([$post['start'], $post['end']], $post['split']);
    $data = $analytics->get();

    return json_encode([
      'split' => $data['split'],
      'all_orders' => array_values($data['all_orders']),
      'total_orders' => array_values($data['total_orders']),
      'total_sales' => array_values($data['total_sales']),
      'avg_order_value' => array_values($data['avg_order_value']),
      'repeat_rate' => $data['repeat_rate'],
      'moneyFormat' => $data['moneyFormat'],
      'moneyFormatFn' => $data['moneyFormatFn'],
      'moneySymbol' => $data['moneySymbol'],
    ]);

  }

}