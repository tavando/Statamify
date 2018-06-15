@extends('layout')

@section('content')

<statamify-analytics inline-template>
<div class="dashboard statamify-analytics" id="statamify-analytics" :data-type="split">
	<div class="flexy mb-24">
		<h1 class="fill">{{ $t['analytics'] }}</h1>
		<input name="daterange">
	</div>

	<div class="widgets">

		<div class="widget">
			<div class="card flush">
				<div class="card-body pad-16">
					<a class="btn btn-primary" @click.prevent="reportTotalOrders">{{ $t['report'] }}</a>
					<h2>{{ $t['total_orders'] }}</h2>
					<h1>@{{ totalOrdersSum }}</h1>
					<line-chart name="total-orders"></line-chart>
				</div>
			</div>    		
		</div>

		<div class="widget">
			<div class="card flush">
				<div class="card-body pad-16">
					<h2>{{ $t['total_sales'] }}</h2>
					<h1>@{{ totalSalesSum }}</h1>
					<line-chart name="total-sales"></line-chart>
				</div>
			</div>    		
		</div>

		<div class="widget">
			<div class="card flush">
				<div class="card-body pad-16">
					<a href="" class="btn btn-primary" @click.prevent="reportAvgOrderVal">{{ $t['report'] }}</a>
					<h2>{{ $t['total_avg'] }}</h2>
					<h1>@{{ totalAvgOrderValueSum }}</h1>
					<line-chart name="average-order-value"></line-chart>
				</div>
			</div>    		
		</div>

		<div class="widget">
			<div class="card flush">
				<div class="card-body pad-16">
					<h2>{{ $t['total_repeat'] }}</h2>
					<h1>@{{ totalRepeatRate }}%</h1>
					<donut-chart name="repeat-rate"></donut-chart>
				</div>
			</div>    		
		</div>

	</div>

	<div class="asset-selector-modal" view-mode="grid" v-show="popup.active" style="display:none">
		<div class="asset-selector">
			<div class="asset-browser card">
				<div class="asset-browser-main">
					<div class="asset-browser-header">
						<h1 class="mb-24">{{ $t['report'] }}. @{{ popup.range }}</h1>
						<div class="asset-browser-actions flexy wrap">
							<button type="button" class="btn action mb-24" @click="closeReport">
								Close
							</button>
						</div>
					</div>
					<div class="asset-browser-content">
						<div class="asset-table-listing">
							<table>
								<thead>
									<tr>
										<th class="pl-24">{{ $t['date'] }}</th>
										<th class="pr-24 text-right" v-if="popup.type == 'averageOrderValue'">{{ $t['total'] }}</th>
										<th class="pr-16 text-right">{{ $t['orders'] }}</th>
										<th class="pr-16 text-right" v-if="popup.type == 'averageOrderValue'">{{ $t['total_avg'] }}</th>
										<th class="pr-16 text-right" v-if="popup.type == 'totalOrders'">{{ $t['gross_sales'] }}</th>
										<th class="pr-16 text-right" v-if="popup.type == 'totalOrders'">{{ $t['discounts'] }}</th>
										<th class="pr-16 text-right" v-if="popup.type == 'totalOrders'">{{ $t['refunds'] }}</th>
										<th class="pr-16 text-right" v-if="popup.type == 'totalOrders'">{{ $t['net_sales'] }}</th>
										<th class="pr-16 text-right" v-if="popup.type == 'totalOrders'">{{ $t['shipping'] }}</th>
										<th class="pr-24 text-right" v-if="popup.type == 'totalOrders'">{{ $t['total'] }}</th>
									</tr>
								</thead>
								<tbody>
									<tr v-for="order in reportOrders">
										<td class="pl-24">@{{ order.date }}</td>
										<td class="pr-24 text-right" v-if="popup.type == 'averageOrderValue'">@{{ order.grand }}</td>
										<td class="pr-16 text-right">@{{ order.value }}</td>
										<td class="pr-24 text-right" v-if="popup.type == 'averageOrderValue'">@{{ order.avg }}</td>
										<td class="pr-16 text-right" v-if="popup.type == 'totalOrders'">@{{ order.sub }}</td>
										<td class="pr-16 text-right" v-if="popup.type == 'totalOrders'">@{{ order.discount }}</td>
										<td class="pr-16 text-right" v-if="popup.type == 'totalOrders'">@{{ order.refunded }}</td>
										<td class="pr-16 text-right" v-if="popup.type == 'totalOrders'">@{{ order.net }}</td>
										<td class="pr-16 text-right" v-if="popup.type == 'totalOrders'">@{{ order.shipping }}</td>
										<td class="pr-24 text-right" v-if="popup.type == 'totalOrders'">@{{ order.grand }}</td>
									</tr>
								</tbody>
							</table>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>

</div>

</statamify-analytics>

<script src="//code.jquery.com/jquery-1.12.4.min.js"></script>
<script>$j = jQuery.noConflict();</script>
<script type="text/javascript" src="//cdn.jsdelivr.net/momentjs/latest/moment.min.js"></script>
<script type="text/javascript" src="/_resources/addons/Statamify/js/daterangepicker.js"></script>

<script src="//cdnjs.cloudflare.com/ajax/libs/d3/4.7.4/d3.js"></script>
<script src="//cdn.jsdelivr.net/npm/britecharts@2/dist/bundled/britecharts.min.js"></script>

<link rel="stylesheet" href="//cdn.jsdelivr.net/npm/britecharts/dist/css/britecharts.min.css" type="text/css" />
<link rel="stylesheet" type="text/css" href="/_resources/addons/Statamify/css/daterangepicker.css" />

<script>

	function money(price) {

		minus = false
    if (price < 0) {
      price *= -1
      minus = true
    }

    price = parseFloat(price).toFixed(2)
    price = {!! $moneyFormatFn !!}
    formatMoney = '{{ $moneyFormat }}'

    return (minus ? '-' : '') + formatMoney.replace('[symbol]', '{!! $moneySymbol !!}').replace('[price]', price)

	}

</script>

<script>

	initialData = {
		split: '{{ $split }}',
		allOrders: {!! $all_orders !!},
		totalOrders: {!! $total_orders !!},
		totalSales: {!! $total_sales !!},
		averageOrderValue: {!! $avg_order_value !!},
		repeatRate: {!! $repeat_rate !!},
		popup: {
			active: 0,
			title: '{{ $t['total_orders'] }}',
			type: 'totalOrders',
			range: 'Today'
		}
	}

</script>

@stop