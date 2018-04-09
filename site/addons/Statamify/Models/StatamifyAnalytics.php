<?php

namespace Statamic\Addons\Statamify\Models;

use Statamic\API\Collection;
use Statamic\API\Entry;

class StatamifyAnalytics
{

	public function __construct($statamic, $range = [], $split = 'perday') {

		$this->statamic = $statamic;
		$this->range = $range;
		$this->split = $split;

	}

	public function get() {

		if (count($this->range)) {

			return $this->summary(array_map(function($date) { return strtotime($date); }, $this->range), $this->split);

		} else {

			$range = [strtotime(date('Y-m-d') . ' 00:00:00'), strtotime(date('Y-m-d') . ' 23:59:59')];

			return $this->summary($range, 'perhour');

		}

	}

	private function summary($range, $split) {

		$format = $split == 'perhour' ? 'H' : 'Y-m-d';
		$orders = collect(Entry::whereCollection('orders')->toArray());

		$filtered = $orders->filter(function($order) use ($range) {

			$start = $range[0];
			$end = $range[1];

			return $order['datestamp'] >= $start && $order['datestamp'] <= $end;

		});

		$grouped = $filtered->groupBy(function ($order) use ($format) {

			return date($format, $order['datestamp']);

		});

		$grouped = $grouped->toArray();

		if ($split == 'perday') {

			$total_orders = $total_sales = $avg_order_value = $this->day_range($range[0], $range[1]);

		} else {

			$total_orders = $total_sales = $avg_order_value = $this->time_range($range[0]);

		}

		foreach ($total_orders as $item_key => $item) {

			$hour = date($format, strtotime($item['date']));

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

	private function day_range($start, $end) {
		$h = 0;
		$formatter = [];

		$end = new \DateTime(date('Y-m-d', $end));

		$period = new \DatePeriod(
			new \DateTime(date('Y-m-d', $start)),
			new \DateInterval('P1D'),
			$end->modify('+1 day')
		);

		foreach ($period as $key => $value) {
			$formatter[] = ['value' => 0, 'date' => $value->format('Y-m-d')];    
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