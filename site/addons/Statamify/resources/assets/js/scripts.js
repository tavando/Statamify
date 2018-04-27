Statamify = {

	init: function() {

		this.removeNavCollections()
		this.orderRefund()

	},

	removeNavCollections: function() {

		if ($('[class*="statamify"].active').length) {
			$('.nav-collections').removeClass('visible active')
		}

		$.each($('[class*="statamify"].active'), function() {
			if ($(this).parents('li').length) {
				$(this).parents('li').addClass('visible active')
			}
		})

	},

	convertListingCells: function() {

		$.each($('[class^="column-listing_"]'), function() {
			$(this).attr('data-title', $(this).text().replace('Listing ', '').trim())
		})

		$.each($('[class^="cell-listing_"]'), function() {
			$(this).html($(this).text())
		})
		
	},

	changeSectionsToTabs: function() {

		condition = Statamic.urlPath.indexOf('store_products') != -1 || Statamic.urlPath.indexOf('statamify/settings') != -1 || Statamic.urlPath.indexOf('store_customers') != -1

		if (
			$(".section-fieldtype").length 
			&& !$('#publish-fields').is('.naked') 
			&& (condition)
			) {

			$('#publish-fields').addClass('naked')

			// Code from "Section" by Rudy Affandi ### https://github.com/lesaff/statamic-sections
			$(".form-group.section-fieldtype").addClass("section-header");

			$(".section-header").each(function() {
					$(this).nextUntil(".section-header").wrapAll('<div class="tab-pane fade" />')
			});

			$(".tab-pane").each(function() {
					$(this).children('.form-group').wrapAll('<div class="tab-flex" />')
			});

			$(".section-header").wrapAll('<ul id="section-tabs" class="nav nav-tabs" role="tablist" />')

			$(".section-header").each(function() {
					$(this).replaceWith($('<li role="presentation">' + this.innerHTML + '</li>'));
			});

			$(".nav-tabs li > div").each(function(index, element) {
					$(this).replaceWith($('<a href="#panel' + index + '" role="tab" data-toggle="tab">' + this.innerHTML + '</a>'));
			});

			$(".nav-tabs li").first().addClass("first active");

			$(".tab-pane").each(function(index, element){
					$(this).attr({
							'id': 'panel' + index,
							'role': 'tabpanel',
					});
					$(this).addClass('tab-' + $('[href="#panel' + index + '"] label').text().trim().toLowerCase())
			});

			var tabContainer = $('<div>', {'class': 'tab-content'});
			var panes = $(".tab-pane");

			panes.sort(function(a,b){
					var an = a.getAttribute('id'),
							bn = b.getAttribute('id');

					if(an > bn) {
							return 1;
					}

					if(an < bn) {
							return -1;
					}

					return 0;

			});

			$(".publish-fields").append(tabContainer);
			panes.detach().appendTo(tabContainer);

			tabContainer.parent().addClass('tabs-wrapper')

			$(".tab-content .tab-pane").first().addClass("first active").removeClass('fade');

			$(".nav-tabs li a").each(function(index, element){
					$(this).find('small').detach().prependTo('#panel' + index);
			});

		}

	},

	orderPreview: function() {

		$('body').on('click', '.statamify-orders .cell-title a', function() {

			$(this).find('span').remove()
			title = $(this).text().trim()

			if ($('.dossier .order-item[data-title="' + title + '"]').length) {
				$('.dossier .order-item[data-title="' + title + '"]').remove()
			} else {
				$('.dossier .order-item').remove()
				originalOrder = $('#orders-details .order-item[data-title="' + title + '"]')
				label = originalOrder.find('[name="order-status"]').parent().attr('data-selected')
				originalOrder.find('[name="order-status"]').val(label)
				originalOrder.find('[name="order-status"]').parent().attr('data-content', originalOrder.find('[name="order-status"] :selected').text())
				clone = $('#orders-details .order-item[data-title="' + title + '"]').clone(true, true)
				$(this).parent().parent().after(clone)
			}
		})

		$('body').on('change', '[name="order-status"]', function() {
			$(this).parent().attr('data-content', $(this).find(':selected').text())
			$(this).parents('.field-inner').next().show()
		})

		$('body').on('change', '[name="tracking"]', function() {
			$(this).parents('.field-inner').next().show()
		})

		$('body').on('click', '#save-order-status', function() {
			_btn = $(this)
			_btn.text('Processing')
			status = $(this).prev().find('[name="order-status"]').val()
			label = $(this).prev().find('[name="order-status"] :selected').text()

			title = $(this).parents('.order-item').attr('data-title')
			id = $(this).parents('.order-item').attr('data-id')

			$.post(Statamic.urlPath + '/status', { status: status, id: id, _token: $('meta#csrf-token').attr("value") }, function(res) {
				_btn.hide()
				_btn.text('Save')

				if (res.success) {

					originalOrderStatus = $('#orders-details .order-item[data-title="' + title + '"] [name="order-status"]')
					originalOrderStatus.val(status)
					originalOrderStatus.parent().attr('data-content', originalOrderStatus.find(':selected').text())
					originalOrderStatus.parent().attr('data-selected', status)

					$('.dossier .order-item[data-title="' + title + '"]').prev()
						.find('.cell-listing_status')
						.html('<span class="order-status ' + status + '">' + label + '</span>')
				}

			})
		})

		$('body').on('click', '#save-tracking', function() {
			_btn = $(this)
			_btn.text('Processing')
			url = $(this).prev().find('[name="tracking"]').val()

			title = $(this).parents('.order-item').attr('data-title')
			id = $(this).parents('.order-item').attr('data-id')

			$.post(Statamic.urlPath + '/tracking', { url: url, id: id, _token: $('meta#csrf-token').attr("value") }, function(res) {
				_btn.hide()
				_btn.text('Save')

				if (res.success) {

					$('#orders-details .order-item[data-title="' + title + '"] [name="tracking"]').val(url)

				}

			})
		})

	},

	orderRefund: function() {

		$('body').on('click', '.btn.refund:not(.btn-primary)', function() {

			order = $(this).parents('.order-item')

			if (order.find('.refund-form').is('.on')) {
				$(this).text('Refund')
				order.find('.refund-form').removeClass('on')
			} else {
				$(this).text('Cancel')
				order.find('.refund-form').addClass('on')
			}

			return false
		})

		$('body').on('click', '.refund-form .refund', function() {

			_btn = $(this)
			form = _btn.parents('.refund-form')
			_btn_text = _btn.text()
			_btn.text('Processing')
			id = _btn.parents('.order-item').attr('data-id')
			amount = form.find('[name="refund_amount"]').val()
			reason = form.find('[name="refund_reason"]').val()

			if (amount != '') {

				$.post(Statamic.urlPath + '/refund', { amount: amount, reason: reason, id: id, _token: $('meta#csrf-token').attr("value") }, function(res) {
					
					_btn.text(_btn_text)

					if (res.success) {

						$('.order-item[data-id="' + id + '"] .refund-form').removeClass('on')
						$('.order-item[data-id="' + id + '"] .totals.refunded .item-totals').text(money(res.amount_refunded))
						$('.order-item[data-id="' + id + '"] .totals.refunded').show()

						$('.dossier .order-item[data-id="' + id + '"]').prev().find('.cell-listing_status').html(res.listing_status)
						$('.order-item[data-id="' + id + '"] [name="order-status"]').val(res.status)
						statusLabel = $('.dossier .order-item[data-id="' + id + '"] [name="order-status"]').find(':selected').text()
						$('.order-item[data-id="' + id + '"] [name="order-status"]').parent().attr('data-content', statusLabel).attr('data-selected', res.status)

						$('.btn.refund').text('Refund')

						if (res.status == 'refunded') {
							$('.order-item[data-id="' + id + '"] .btn.refund, .order-item[data-id="' + id + '"] .refund-form').remove()
						}

						if (reason != '') {
							$('.order-item[data-id="' + id + '"] .refund-reason').show().find('span').text(reason)
						}

					} else {

						alert(res.errors)

					}

				})

			}

			return false
		})

	}

}

$(document).ready(function() { Statamify.init() })

// Listen to AJAX calls
function newXHR() {
	var realXHR = new oldXHR();
	realXHR.addEventListener("readystatechange", function() {
		if(realXHR.readyState==4){

			if (realXHR.responseURL.indexOf('statamify/orders/status') == -1 && realXHR.responseURL.indexOf('statamify/orders/tracking') == -1
				&& realXHR.responseURL.indexOf('statamify/orders/refund') == -1) {

				setTimeout(function(){
					Statamify.changeSectionsToTabs()
					Statamify.convertListingCells()
					Statamify.orderPreview()
				}, 0)

			}
		}
	}, false);
	return realXHR;
}

var oldXHR = window.XMLHttpRequest;
window.XMLHttpRequest = newXHR;

function number_format (number, decimals, decPoint, thousandsSep) { // eslint-disable-line camelcase

  number = (number + '').replace(/[^0-9+\-Ee.]/g, '')
  var n = !isFinite(+number) ? 0 : +number
  var prec = !isFinite(+decimals) ? 0 : Math.abs(decimals)
  var sep = (typeof thousandsSep === 'undefined') ? ',' : thousandsSep
  var dec = (typeof decPoint === 'undefined') ? '.' : decPoint
  var s = ''

  var toFixedFix = function (n, prec) {
    var k = Math.pow(10, prec)
    return '' + (Math.round(n * k) / k)
      .toFixed(prec)
  }

  // @todo: for IE parseFloat(0.55).toFixed(0) = 0;
  s = (prec ? toFixedFix(n, prec) : '' + Math.round(n)).split('.')
  if (s[0].length > 3) {
    s[0] = s[0].replace(/\B(?=(?:\d{3})+(?!\d))/g, sep)
  }
  if ((s[1] || '').length < prec) {
    s[1] = s[1] || ''
    s[1] += new Array(prec - s[1].length + 1).join('0')
  }

  return s.join(dec)
}

// ANALYTICS

function camelize(str) {
	return str.replace(/(?:^\w|[A-Z]|\b\w)/g, function(letter, index) {
		return index == 0 ? letter.toLowerCase() : letter.toUpperCase();
	}).replace(/\s+/g, '');
}

Vue.component('line-chart', {
	props: ['name'],

	template: '<div :id="name"></div>',

	ready: function() {
		
		this.createChart()

	},

	methods: {
		createChart: function() {

			$('#' + this.name).html('')

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
			.on('customMouseOver', tooltip.show)
			.on('customMouseMove', tooltip.update)
			.on('customMouseOut', tooltip.hide);

			if (this.$parent.split == 'perday') {
				lineChart.xAxisFormat('custom').xTicks(10)
			} else {
				lineChart.xAxisFormat(britecharts.line().axisTimeCombinations.MINUTE_HOUR)
			}

			tooltip.title('Date');

			if (this.$parent.split == 'perhour') {
				tooltip.dateFormat(tooltip.axisTimeCombinations.MINUTE_HOUR)
			}

			d3.select('#' + this.name).datum(lineData).call(lineChart);
			d3.select('#' + this.name + ' .metadata-group .vertical-marker-container').datum(lineData).call(tooltip)

		}
	}
})

Vue.component('donut-chart', {
	props: ['name'],

	template: '<div :id="name"></div><div class="legend"></div>',

	ready: function() {
		
		this.createChart()

	},

	methods: {
		createChart: function() {

			$('#' + this.name).html('')
			$('#' + this.name).next().html('')

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
	}
})

Vue.component('statamify-analytics', {
	data: function() {
		return initialData
	},
	computed: {
		totalOrdersSum: function() {
			return _.reduce(this.totalOrders, function(sum, item) { return sum + item.value }, 0)
		},
		totalSalesSum: function() {
			total = _.reduce(this.totalSales, function(sum, item) { return sum + item.value }, 0)
			return money(total)
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
		},
		reportOrders: function() {
			split = this.split
			type = this.popup.type

			orders = JSON.parse(JSON.stringify(this.allOrders))

			return _.map(orders, function(item) {
				if (split == 'perhour') {
					item.date = moment(item.date).format('H:mm')
				}

				if (type == 'totalOrders') {
					item.sub = money(_.reduce(item.value, function(sum, order) { return sum + order.summary.total.sub }, 0))
					item.discount = money(_.reduce(item.value, function(sum, order) { return sum + order.summary.total.discount }, 0))
					item.returns = money(_.reduce(item.value, function(sum, order) { return sum + (order.summary.total.returns ? order.summary.total.returns : 0) }, 0))
					item.net = money(_.reduce(item.value, function(sum, order) { return sum + order.summary.total.grand - parseFloat(order.summary.total.shipping) }, 0))
					item.shipping = money(_.reduce(item.value, function(sum, order) { return sum + parseFloat(order.summary.total.shipping) }, 0))
				}

				grand = _.reduce(item.value, function(sum, order) { return sum + order.summary.total.grand }, 0)
				item.grand = money(grand)
				item.value = item.value ? item.value.length : item.value

				if (type == 'averageOrderValue') {
					item.avg = money(grand/item.value)
				}

				return item
			})
		}
	},
	ready: function() {
		vm = this

		$j('input[name="daterange"]').daterangepicker({
			locale: {
				format: 'MMMM D, YYYY'
			},
			ranges: {
				'Today': [moment(), moment()],
				'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
				'Last 7 Days': [moment().subtract(6, 'days'), moment()],
				'Last 30 Days': [moment().subtract(29, 'days'), moment()],
				'This Month': [moment().startOf('month'), moment().endOf('month')],
				'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
			},
			"alwaysShowCalendars": true,
			"startDate": moment(),
			"endDate": moment(),
			"opens": "left",
			"applyClass": "btn-primary"
		}, function(start, end, label) {
			this.split = start.diff(end, 'hours') == -23 ? 'perhour' : 'perday'
			if (label == "Custom Range") {
				vm.popup.range = start.format('MMMM D, YYYY') + ' - ' + end.format('MMMM D, YYYY')
			} else {
				vm.popup.range = label
			}
			vm.changeDate(start.format('YYYY-MM-DD H:mm:ss'), end.format('YYYY-MM-DD H:mm:ss'), this.split)
		});

	},
	methods: {
		changeDate: function(start, end, split) {
			this.$http.post(cp_url('/addons/statamify/analytics'), {start:start, end:end, split:split}, function(res) {

				this.$set('split', res.split)
				this.$set('allOrders', res.all_orders)
				this.$set('totalOrders', res.total_orders)
				this.$set('totalSales', res.total_sales)
				this.$set('averageOrderValue', res.avg_order_value)
				this.$set('repeatRate', res.repeat_rate)

				_.each(this.$children, function(c) {
					c.createChart()
				})

			});
		},
		reportTotalOrders: function() {
			this.popup.active = true
			this.popup.title = 'Total Orders'
			this.popup.type = 'totalOrders'

		},
		reportAvgOrderVal: function() {
			this.popup.active = true
			this.popup.title = 'Average Order Value'
			this.popup.type = 'averageOrderValue'

		},
		closeReport: function() {
			this.popup.active = false
		}
	}
})