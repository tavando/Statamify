@extends('layout')

@section('content')

    <entry-listing inline-template v-cloak
        collection="{{ route('statamify.orders', 'cp')}}"
        get="{{ route('statamify.orders.get', 'cp')}}"
        delete="{{ route('statamify.orders.delete', 'cp')}}"
        reorder="/"
        search="/addons/statamify/orders/search"
        sort="date"
        sort-order="desc"
        :reorderable="false"
        :can-delete="true"
        :can-create="false"
        create-entry-route="/addons/statamify/orders/create">

        <div class="listing entry-listing statamify-orders">

          <div class="flex flex-wrap justify-between lg_flex-no-wrap items-center mb-24">
            <h1 class="flex-1 mb-8 lg_mb-0">{{ $t['store'] }}: {{ $t['orders'] }}</h1>
          </div>

          <div class="card flush">
            <template v-if="noItems">
              <div class="info-block">
                <template v-if="isSearching">
                  <span class="icon icon-magnifying-glass"></span>
                  <h2>{{ translate('cp.no_search_results') }}</h2>
                </template>
                <template v-else>
                  <span class="icon icon-documents"></span>
                  <h2>{{ trans('cp.entries_empty_heading', ['type' => $t['store'] . ': ' . $t['orders']]) }}</h2>
                  <h3>{{ trans('cp.entries_empty') }}</h3>
                  <a href="/addons/statamify/orders/create" class="btn btn-default btn-lg">{{ trans('cp.create_entry_button') }}</a>
                </template>
              </div>
            </template>

            <div class="loading" v-if="loading">
              <span class="icon icon-circular-graph animation-spin"></span> {{ translate('cp.loading') }}
            </div>

            <dossier-table v-if="hasItems" :items="items" :options="tableOptions" :is-searching="isSearching"></dossier-table>

            <div id="orders-details">
              <table>
                <tr v-for="order in items" class="order-item" :data-title="order.title" :data-id="order.id">
                  <td colspan="8">
                    <div class="summaries">
                      <div class="column">
                        <div class="inside">
                          <h2>{{ $t['summary'] }}</h2>
                          <table class="order-summary">
                            <tbody>
                              <tr data-id="@{{ item.id }}" v-for="item in order.summary.items">
                                <td class="item-image"><div style="background-image: url(@{{ item.image }})"></div></td>
                                <td class="item-line">
                                  <a href="@{{ item.edit_url }}">@{{ item.name }}</a>
                                  <p v-if="item.variant">{{ $t['variant'] }}: @{{ item.variant }}</p>
                                  <p>SKU: @{{ item.sku || '-' }}</p>
                                  <p v-if="item.custom">@{{ item.custom }}</p>
                                </td>
                                <td class="item-totals">@{{ item.price | money }}</td>
                                <td class="item-totals">×</td>
                                <td class="item-totals">@{{ item.quantity }}</td>
                                <td class="item-totals">@{{ item.quantity * item.price | money }}</td>
                              </tr>
                              <tr class="totals">
                                <td colspan="5" class="text-right">{{ $t['subtotal'] }}</td>
                                <td class="item-totals">@{{ order.summary.total.sub | money }}</td>
                              </tr>
                              <tr class="noborder totals">
                                <td colspan="2" v-if="order.summary.total.discount">{{ $t['coupon_used'] }}: <strong>@{{ order.summary.coupons.join(', ') }}</strong></td>
                                <td colspan="2" v-else></td>
                                <td colspan="3" class="text-right">{{ $t['discount'] }}</td>
                                <td class="item-totals">@{{ order.summary.total.discount | money }}</td>
                              </tr>
                              <tr class="noborder totals">
                                <td colspan="5" class="text-right">{{ $t['shipping'] }}</td>
                                <td class="item-totals">@{{ order.summary.total.shipping | money }}</td>
                              </tr>
                              <tr class="noborder totals grand">
                                <td colspan="2"><a href="" class="btn refund" v-if="order.status != 'refunded'">{{ $t['refund'] }}</a></td>
                                <td colspan="3" class="text-right"><strong>{{ $t['total'] }}</strong></td>
                                <td class="item-totals"><strong>@{{ order.summary.total.grand | money }}</strong></td>
                              </tr>
                              <tr class="totals refunded" v-show="order.status == 'refunded' || order.status == 'refunded_partially'">
                                <td colspan="2"></td>
                                <td colspan="3" class="text-right">{{ $t['refunded'] }}</td>
                                <td class="item-totals">@{{ order.summary.total.refunded | money }}</td>
                              </tr>
                              <tr class="noborder totals refund-form" v-if="order.status != 'refunded'">
                                <td colspan="3"><input placeholder="{{ $t['refund_amount'] }}" name="refund_amount"> <input placeholder="{{ $t['reason_optional'] }}" name="refund_reason"></td>
                                <td class="item-totals text-right" colspan="3">
                                  <a href="" class="btn btn-primary refund">{{ $t['refund'] }} 
                                    <span v-if="order.payment_method.name == 'Cheque'">{{ $t['manually'] }}</span>
                                    <span v-else>{{ $t['via'] }} @{{ order.payment_method.name }}</span>
                                  </a>
                                </td>
                              </tr>
                            </tbody>
                          </table>
                        </div>
                      </div>
   
                      <div class="column">
                        <div class="inside">
                          <h2>{{ $t['address_shipping'] }}</h2>
                          <div class="small-text">
                            <strong>{{ $t['address_name'] }}:</strong> @{{ order.shipping.first_name }} @{{ order.shipping.last_name }}<br>
                            <template v-if="order.shipping.company"><strong>{{ $t['address_company'] }}: </strong>@{{ order.shipping.company }}<br></template>
                            <strong>{{ $t['address'] }}:</strong> @{{ order.shipping.address }}<template v-if="order.shipping.address_2">, @{{ order.shipping.address_2 }} </template><br>
                            <strong>{{ $t['address_city'] }}:</strong> @{{ order.shipping.city }} <br/>
                            <strong>{{ $t['address_postal'] }}:</strong> @{{ order.shipping.postal }}<template v-if="order.shipping.region">, @{{ order.shipping.region }} </template> <br>
                            <strong>{{ $t['address_country'] }}:</strong> @{{ order.shipping.country }}</div>
                          </div>
                      </div>

                      <div class="column">
                        <div class="inside">
                          <h2>Billing Address</h2>
                          <div class="small-text" v-if="order.billing_diff">
                            <strong>{{ $t['address_name'] }}:</strong> @{{ order.billing.first_name }} @{{ order.billing.last_name }}<br>
                            <template v-if="order.billing.company"><strong>{{ $t['address_company'] }}: </strong>@{{ order.billing.company }}<br></template>
                            <strong>{{ $t['address'] }}:</strong> @{{ order.billing.address }}<template v-if="order.billing.address_2">, @{{ order.billing.address_2 }} </template><br>
                            <strong>{{ $t['address_city'] }}:</strong> @{{ order.billing.city }} <br/>
                            <strong>{{ $t['address_postal'] }}:</strong> @{{ order.billing.postal }}<template v-if="order.billing.region">, @{{ order.billing.region }} </template> <br>
                            <strong>{{ $t['address_country'] }}:</strong> @{{ order.billing.country }}</div>
                            <div class="small-text" v-else>{{ $t['address_same'] }}</div>
                        </div>
                      </div>

                      <div class="column">
                        <div class="inside">
                          <h2>Status</h2>
                          <div class="field-inner">
                            <div class="select select-full" data-content="" :data-selected="order.status">
                              <select tabindex="0" name="order-status">
                                @foreach ($orderStatuses as $val => $status)
                                <option value="{{ $val }}">{{ $status }}</option>
                                @endforeach
                              </select>
                            </div>
                          </div>
                          <button id="save-order-status" type="button" class="btn btn-primary mt-8" style="display:none">
                            Save
                          </button>
                        </div>
                      </div>

                      <div class="column">
                        <div class="inside">
                          <h2>{{ $t['address_tracking'] }}</h2>
                          <div class="field-inner text-fieldtype">
                            <input name="tracking" tabindex="0" class="form-control type-text" type="text" :value="order.shipping_method.tracking">
                          </div>
                          <button id="save-tracking" type="button" class="btn btn-primary mt-8" style="display:none">
                            Save
                          </button>
                        </div>
                      </div>

                      <div class="column">
                        <div class="inside">
                          <h2>{{ $t['address_shipping'] }}</h2>
                          <div class="small-text">
                            <strong>{{ $t['zone'] }}:</strong> @{{ order.shipping_method.zone }}<br>
                            <strong>{{ $t['address_name'] }}:</strong> @{{ order.shipping_method.name }}<br>
                            <strong>{{ $t['rate'] }}:</strong> @{{ order.shipping_method.rate | money }}<br>
                          </div>
                        </div>
                      </div>

                      <div class="column">
                        <div class="inside">
                          <h2>{{ $t['address_payment'] }}</h2>
                          <div class="small-text">
                            <strong>{{ $t['address_name'] }}:</strong> @{{ order.payment_method.name }}<br>
                            <strong>{{ $t['fee'] }}:</strong> @{{ order.payment_method.fee | money }}<br>
                            <strong>Id:</strong> @{{ order.payment_method.id || '-' }}<br>
                            <div class="refund-reason" v-show="order.payment_method.refund_reason"><strong>Refund Reason:</strong> <span>@{{ order.payment_method.refund_reason }}</span></div>
                          </div>
                        </div>
                      </div>

                    </div>
                  </td>
                </tr>
              </table>
            </div>

          </div>
        </div>

    </entry-listing>

@endsection

@section('scripts')

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

  Vue.filter('money', function(price) {

    return money(price)

  })

</script>

@stop