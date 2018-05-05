@extends('layout')
@section('content-class', 'publishing')

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
            <h1 class="flex-1 mb-8 lg_mb-0">Store: Orders</h1>
            <div class="controls flex items-center w-full lg_w-auto">
              <search :keyword.sync="searchTerm" class="w-full lg_w-auto"></search>

              <div class="btn-group ml-8" v-if="locales.length > 1">
                <select-fieldtype :data.sync="locale" :options="locales"></select-fieldtype>
              </div>
            </div>
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
                  <h2>{{ trans('cp.entries_empty_heading', ['type' => 'Store: Orders']) }}</h2>
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
                          <h2>Summary</h2>
                          <table class="order-summary">
                            <tbody>
                              <tr data-id="@{{ item.id }}" v-for="item in order.summary.items">
                                <td class="item-image"><div style="background-image: url(@{{ item.image }})"></div></td>
                                <td class="item-line">
                                  <a href="@{{ item.edit_url }}">@{{ item.name }}</a>
                                  <p v-if="item.variant">Variant: @{{ item.variant }}</p>
                                  <p>SKU: @{{ item.sku || '-' }}</p>
                                  <p v-if="item.custom">@{{ item.custom }}</p>
                                </td>
                                <td class="item-totals">@{{ item.price | money }}</td>
                                <td class="item-totals">×</td>
                                <td class="item-totals">@{{ item.quantity }}</td>
                                <td class="item-totals">@{{ item.quantity * item.price | money }}</td>
                              </tr>
                              <tr class="totals">
                                <td colspan="5" class="text-right">Subtotal</td>
                                <td class="item-totals">@{{ order.summary.total.sub | money }}</td>
                              </tr>
                              <tr class="noborder totals">
                                <td colspan="2" v-if="order.summary.total.discount">Coupon used: <strong>@{{ order.summary.coupons.join(', ') }}</strong></td>
                                <td colspan="2" v-else></td>
                                <td colspan="3" class="text-right">Discount</td>
                                <td class="item-totals">@{{ order.summary.total.discount | money }}</td>
                              </tr>
                              <tr class="noborder totals">
                                <td colspan="5" class="text-right">Shipping</td>
                                <td class="item-totals">@{{ order.summary.total.shipping | money }}</td>
                              </tr>
                              <tr class="noborder totals grand">
                                <td colspan="2"><a href="" class="btn refund" v-if="order.status != 'refunded'">Refund</a></td>
                                <td colspan="3" class="text-right"><strong>Total</strong></td>
                                <td class="item-totals"><strong>@{{ order.summary.total.grand | money }}</strong></td>
                              </tr>
                              <tr class="totals refunded" v-show="order.status == 'refunded' || order.status == 'refunded_partially'">
                                <td colspan="2"></td>
                                <td colspan="3" class="text-right">Refunded</td>
                                <td class="item-totals">@{{ order.summary.total.refunded | money }}</td>
                              </tr>
                              <tr class="noborder totals refund-form" v-if="order.status != 'refunded'">
                                <td colspan="3"><input placeholder="Amount to refund" name="refund_amount"> <input placeholder="Reason (optional)" name="refund_reason"></td>
                                <td class="item-totals text-right" colspan="3">
                                  <a href="" class="btn btn-primary refund">Refund 
                                    <span v-if="order.payment_method.name == 'Cheque'">manually</span>
                                    <span v-else>via @{{ order.payment_method.name }}</span>
                                  </a>
                                </td>
                              </tr>
                            </tbody>
                          </table>
                        </div>
                      </div>
   
                      <div class="column">
                        <div class="inside">
                          <h2>Shipping Address</h2>
                          <div class="small-text">
                            <strong>Name:</strong> @{{ order.shipping.first_name }} @{{ order.shipping.last_name }}<br>
                            <template v-if="order.shipping.company"><strong>Company: </strong>@{{ order.shipping.company }}<br></template>
                            <strong>Address:</strong> @{{ order.shipping.address }}<template v-if="order.shipping.address_2">, @{{ order.shipping.address_2 }} </template><br>
                            <strong>City:</strong> @{{ order.shipping.city }} <br/>
                            <strong>Postal:</strong> @{{ order.shipping.postal }}<template v-if="order.shipping.region">, @{{ order.shipping.region }} </template> <br>
                            <strong>Country:</strong> @{{ order.shipping.country }}</div>
                          </div>
                      </div>

                      <div class="column">
                        <div class="inside">
                          <h2>Billing Address</h2>
                          <div class="small-text" v-if="order.billing_diff">
                            <strong>Name:</strong> @{{ order.billing.first_name }} @{{ order.billing.last_name }}<br>
                            <template v-if="order.billing.company"><strong>Company: </strong>@{{ order.billing.company }}<br></template>
                            <strong>Address:</strong> @{{ order.billing.address }}<template v-if="order.billing.address_2">, @{{ order.billing.address_2 }} </template><br>
                            <strong>City:</strong> @{{ order.billing.city }} <br/>
                            <strong>Postal:</strong> @{{ order.billing.postal }}<template v-if="order.billing.region">, @{{ order.billing.region }} </template> <br>
                            <strong>Country:</strong> @{{ order.billing.country }}</div>
                            <div class="small-text" v-else>Same as Shipping</div>
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
                          <h2>Tracking URL</h2>
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
                          <h2>Shipping Method</h2>
                          <div class="small-text">
                            <strong>Zone:</strong> @{{ order.shipping_method.zone }}<br>
                            <strong>Name:</strong> @{{ order.shipping_method.name }}<br>
                            <strong>Rate:</strong> @{{ order.shipping_method.rate | money }}<br>
                          </div>
                        </div>
                      </div>

                      <div class="column">
                        <div class="inside">
                          <h2>Payment Method</h2>
                          <div class="small-text">
                            <strong>Name:</strong> @{{ order.payment_method.name }}<br>
                            <strong>Fee:</strong> @{{ order.payment_method.fee | money }}<br>
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