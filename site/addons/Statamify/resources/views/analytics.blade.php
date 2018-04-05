@extends('layout')

@section('content')

<div class="dashboard statamify-analytics" id="statamify-analytics">
	<div class="flexy mb-24">
		<h1 class="fill">Analytics</h1>
	</div>

	<div class="widgets">

		<div class="widget">
			<div class="card flush">
				<div class="card-body pad-16">
					<a href="" class="btn btn-primary">Report</a>
					<h2>Total Orders</h2>
					<h1>${ totalOrdersSum }</h1>
					<line-chart name="total-orders"></line-chart>
				</div>
			</div>    		
		</div>

		<div class="widget">
			<div class="card flush">
				<div class="card-body pad-16">
					<a href="" class="btn btn-primary">Report</a>
					<h2>Total Sales</h2>
					<h1>${ totalSalesSum }</h1>
					<line-chart name="total-sales"></line-chart>
				</div>
			</div>    		
		</div>

		<div class="widget">
			<div class="card flush">
				<div class="card-body pad-16">
					<a href="" class="btn btn-primary">Report</a>
					<h2>Average Order Value</h2>
					<h1>${ totalAvgOrderValueSum }</h1>
					<line-chart name="average-order-value"></line-chart>
				</div>
			</div>    		
		</div>

		<div class="widget">
			<div class="card flush">
				<div class="card-body pad-16">
					<h2>Repeat Customer Rate</h2>
					<h1>${ totalRepeatRate }%</h1>
					<donut-chart name="repeat-rate"></donut-chart>
				</div>
			</div>    		
		</div>

	</div>

</div>

@stop

@section('scripts')

<script src="//cdnjs.cloudflare.com/ajax/libs/d3/4.7.4/d3.js"></script>
<script src="//cdn.jsdelivr.net/npm/britecharts@2/dist/bundled/britecharts.min.js"></script>

<link rel="stylesheet" href="//cdn.jsdelivr.net/npm/britecharts/dist/css/britecharts.min.css" type="text/css" />

<script>

	function camelize(str) {
		return str.replace(/(?:^\w|[A-Z]|\b\w)/g, function(letter, index) {
			return index == 0 ? letter.toLowerCase() : letter.toUpperCase();
		}).replace(/\s+/g, '');
	}

	Vue.config.delimiters = ['${', '}']

	Vue.component('line-chart', {
		props: ['name'],

		template: '<div :id="name"></div>',

		ready: function() {
			
			var lineChart = britecharts.line(), tooltip = britecharts.tooltip()

			var lineData =  {
				dataByTopic: [{
					topicName: this.name.replace(/-/g, ' ').replace(/\b\w/g, l => l.toUpperCase()),
					topic: 1,
					dates: this.$parent[camelize(this.name.replace(/-/g, ' '))]
				}]
			};

			lineChart
				.margin({
					top: 60,
					bottom: 50,
					left: 50,
					right: 20
				})
				.width($('#' + this.name).width() - 10)
				.height(300)
				.grid('full')
				.isAnimated(true)
				.xAxisFormat(this.$parent.split == 'perday' ? britecharts.line().axisTimeCombinations.HOUR_DAY : britecharts.line().axisTimeCombinations.MINUTE_HOUR)
				.on('customMouseOver', tooltip.show)
				.on('customMouseMove', tooltip.update)
				.on('customMouseOut', tooltip.hide);

			tooltip.title('Date');

			if (this.$parent.split == 'perhour') {

				tooltip.dateFormat(tooltip.axisTimeCombinations.MINUTE_HOUR)

			}

			d3.select('#' + this.name).datum(lineData).call(lineChart);
			d3.select('#' + this.name + ' .metadata-group .vertical-marker-container').datum(lineData).call(tooltip)

		}
	})

	Vue.component('donut-chart', {
		props: ['name'],

		template: '<div :id="name"></div><div class="legend"></div>',

		ready: function() {
			
			var donutChart = britecharts.donut(), legend = britecharts.legend(),

			legendContainer = d3.select('#' + this.name + ' + .legend'),

			first_time = this.$parent.repeatRate.first_time,
			returning = this.$parent.repeatRate.returning;

			var donutData = [
				{
					quantity: first_time,
					percentage: (first_time / (first_time + returning)) * 100,
					name: 'First-time',
					id: 1
				},
				{
					quantity: returning,
					percentage: (returning / (first_time + returning)) * 100,
					name: 'Returning',
					id: 2
				}
			];

			width = $('#' + this.name).width() - 10

			donutChart
				.margin({
					top: 60,
					bottom: 50,
					left: 50,
					right: 20
				})
				.width(width)
				.height(300)
				.externalRadius(width/5)
				.internalRadius(width/10)
				.isAnimated(true)
				.on('customMouseOver', function(data) {
					legendChart.highlight(data.data.id);
				})
				.on('customMouseOut', function() {
					legendChart.clearHighlight();
				});

			legend
				.isHorizontal(true)
				.width($('#' + this.name).width() - 10)
				.markerSize(8)
				.height(40)

			d3.select('#' + this.name).datum(donutData).call(donutChart)
			legendContainer.datum(donutData).call(legend)

		}
	})

	new Vue({
		el: '#statamify-analytics',
		data: {
			split: '{{ $split }}',
			totalOrders: {!! $total_orders !!},
			totalSales: {!! $total_sales !!},
			averageOrderValue: {!! $avg_order_value !!},
			repeatRate: {!! $repeat_rate !!},
		},
		computed: {
			totalOrdersSum: function() {
				return _.reduce(this.totalOrders, function(sum, item) { return sum + item.value }, 0)
			},
			totalSalesSum: function() {
				return money(_.reduce(this.totalSales, function(sum, item) { return sum + item.value }, 0))
			},
			totalAvgOrderValueSum: function() {
				count = 0
				total = _.reduce(this.averageOrderValue, function(sum, item) { if (item.value) { count++ }; return sum + item.value }, 0)
				return money(total/count)
			},
			totalRepeatRate: function() {
				first_time = this.repeatRate.first_time
				returning = this.repeatRate.returning
				if (first_time + returning) {
					return (returning / (first_time + returning)) * 100
				} else {
					return '0'
				}
			}
		}
	})

	function money(price) {

		price = parseFloat(price).toFixed(2)
		price = {!! $money['format'] !!}
		formatMoney = '{{ $money['formatMoney'] }}'

		return formatMoney.replace('[symbol]', '{{ $money['symbol'] }}').replace('[price]', price)

	}

</script>

@stop