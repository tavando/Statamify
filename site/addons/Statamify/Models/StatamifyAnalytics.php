<?php

namespace Statamic\Addons\Statamify\Models;

use Statamic\API\Collection;
use Statamic\API\Entry;

class StatamifyAnalytics
{

	public function __construct($statamic, $data = 'today', $range = []) {

		$this->statamic = $statamic;
		$this->data = $data;
		$this->range = $range;

	}

	public function get() {

		switch ($this->data) {
			
			default:

				$range = [strtotime('2018-03-27 00:00'), strtotime('2018-03-27 23:59')];

				return $this->summary($range, 'perhour');
				break;
		}

	}

	private function summary($range, $split = 'perday') {

		$orders = collect(Entry::whereCollection('orders')->toArray());

		$filtered = $orders->filter(function($order) use ($range) {

			$start = $range[0];
			$end = $range[1];

			return $order['datestamp'] >= $start && $order['datestamp'] <= $end;

		});

		if ($split == 'perhour') {

			$grouped = $filtered->groupBy(function ($order) {

				return date('H', $order['datestamp']);

			});

			$grouped = $grouped->toArray();

			$total_orders = $total_sales = $avg_order_value = $this->time_range($range[0]);

			foreach ($total_orders as $item_key => $item) {
				
				$hour = date('H', strtotime($item['date']));

				if (isset($grouped[$hour])) {

					$count = count($grouped[$hour]);

					$total_orders[$item_key]['value'] = $count;
					
					$total_sales[$item_key]['value'] = array_reduce($grouped[$hour], function($sum, $order) {
						$sum += $order['summary']['total']['grand'];
						return $sum;
					});

					$avg_order_value[$item_key]['value'] = array_reduce($grouped[$hour], function($sum, $order) use ($count) {
						$sum += $order['summary']['total']['grand']/$count;
						return $sum;
					});

				}

			}

		}

		$groupedByCustomer = $filtered->groupBy(function ($order) {

			return $order['user'];

		});

		$repeat_rate = ['first_time' => 0, 'returning' => 0];

		foreach ($groupedByCustomer->toArray() as $user_id => $orders) {
			
			$customer = Entry::whereSlug($user_id, 'customers');

			if (count($customer->get('orders')) == 1) {

				$repeat_rate['first_time'] += 1;

			} else {

				$repeat_rate['returning'] += 1;

			}

		}

		return [
			'split' => $split,
			'total_orders' => $total_orders,
			'total_sales' => $total_sales,
			'avg_order_value' => $avg_order_value,
			'repeat_rate' => $repeat_rate,
			'money' => $this->money()
		];

	}

	private function time_range($day) {
		$h = 0;
		$formatter = [];
		while ($h < 24) {
			$key = date('H:i:s', strtotime(date('Y-m-d') . ' + ' . $h . ' hours'));
			$formatter[] = ['value' => 0, 'date' => date('Y-m-d', $day) . 'T' . $key];
			$h++;
		}

		return $formatter;
	}

	private function money() {

		$currencies = $this->statamic->getConfig('currency');
		
		if (count($currencies)) {

			$key = array_search('1', array_column($currencies, 'rate'));

			if (!is_bool($key)) {

				$currency = $currencies[$key];
				$money = [
					'formatMoney' => $currency['format'],
					'symbol' => $currency['symbol'],
				];

				switch($currency['formatPrice']) {
					case 1:
						$money['format'] = "number_format(price, 0, '', ',')";
						break;
					case 2:
						$money['format'] = "number_format(price, 0, '', ' ')";
						break;
					case 3:
						$money['format'] = "number_format(price, 2, '.', ',')";
						break;
					case 4:
						$money['format'] = "number_format(price, 2, '.', ' ')";
						break;
					case 5:
						$money['format'] = "number_format(price, 2, ',', ' ')";
						break;
					case 6:
						$money['format'] = "number_format(price, 0, '', '')";
						break;
					case 7:
						$money['format'] = "number_format(price, 2, '.', '')";
						break;
					default:
						$money['format'] = "number_format(price, 2, ',', '')";
				} 

			}

		}

		if (isset($money)) {

			return $money;

		} else {

			return [
				'format' => '',
				'formatMoney' => '',
				'symbol' => '',
			];

		}

	}

}