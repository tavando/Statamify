template = `
<table class="order-summary">
	<tbody>
		<tr data-id="{{ item.id }}" v-for="item in data.items">
			<td class="item-image"><div style="background-image: url({{ item.image }})"></div></td>
			<td class="item-line">
				<a href="{{ item.edit_url }}">{{ item.name }}</a>
				<p v-if="item.variant">Variant: {{ item.variant }}</p>
				<p>SKU: {{ item.sku || '-' }}</p>
			</td>
			<td class="item-totals">{{ item.price | money }}</td>
			<td class="item-totals">×</td>
			<td class="item-totals">{{ item.quantity }}</td>
			<td class="item-totals">{{ item.quantity * item.price | money }}</td>
		</tr>
		<tr class="totals">
			<td colspan="5" class="text-right">Subtotal</td>
			<td class="item-totals">{{ data.total.sub | money }}</td>
		</tr>
		<tr class="noborder totals">
			<td colspan="5" class="text-right">Shipping</td>
			<td class="item-totals">{{ data.total.shipping | money }}</td>
		</tr>
		<tr class="noborder totals">
			<td colspan="5" class="text-right">Discount</td>
			<td class="item-totals">{{ data.total.discount | money }}</td>
		</tr>
		<tr class="noborder totals">
			<td colspan="5" class="text-right"><strong>Total</strong></td>
			<td class="item-totals"><strong>{{ data.total.grand | money }}</strong></td>
		</tr>
	</tbody>
</table>
`

Vue.component('statamify_order_summary-fieldtype', {

	mixins: [Fieldtype],

	template: template,

	filters: {
		money: function(price) {
			return this.data.config.moneyFormat.replace('[symbol]', this.data.config.currencySymbol).replace('[price]', parseFloat(price).toFixed(2))
		}
	},

	ready: function() {

		console.log(JSON.parse(JSON.stringify(this.data)))

	}


});