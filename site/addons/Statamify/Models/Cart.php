<?php

namespace Statamic\Addons\Statamify\Models;

use Statamic\API\Collection;
use Statamic\API\Entry;
use Statamic\API\Fieldset;
use Statamic\API\Helper;
use Statamic\Addons\Statamify\Statamify;
use Statamic\API\User;

class Cart
{

  public function __construct($instance = 'cart', $recalculated = true) {

    $this->instance = $instance;
    $this->recalculated = $recalculated;

    $base = [
      'cart_id' => Helper::makeUuid(),
      'items' => [],
      'coupons' => [],
      'shipping' => false,
      'total' => [
        'sub' => 0,
        'discount' => 0,
        'shipping' => 0,
        'tax' => 0,
        'grand' => 0,
        'weight' => 0,
        'items' => 0,
        'pieces' => 0
      ]
    ];

    $this->session = session('statamify.' . $instance) ?: $base;

  }

  private function removeExtraValues($entry) {

    $blacklist = [
      'columns', 'products', 'is_entry', 'order', 'order_type',
      'content', 'content_raw',
      'listing_image', 'listing_type', 'listing_vendor', 'listing_inventory',
      'edit_url', 'uri', 'url_path'
    ];

    return array_diff_key($entry, array_flip($blacklist));

  }

  private function recalculate() {

    $cart = $this->session;

    // Reset totals

    $cart['total'] = [
      'sub' => 0,
      'discount' => 0,
      'shipping' => 0,
      'tax' => 0,
      'grand' => 0,
      'weight' => 0,
      'items' => 0,
      'pieces' => 0
    ];

    // Get product's fieldset - we will transform IDs to objects of data

    $fieldset = Fieldset::get(Collection::whereHandle('store_products')->get('fieldset'));
    $fieldset_data = $fieldset->toArray();

    if (isset($fieldset_data['sections'])) {

      $fieldset_data_replace = [];

      foreach ($fieldset_data['sections'] as $section) {
        $fieldset_data_replace = array_merge($fieldset_data_replace, $section['fields']);
      }

      $fieldset_data['fields'] = $fieldset_data_replace;

    }

    foreach ($cart['items'] as $item_key => $item) {

      $cart['total']['items']++;
      $cart['total']['pieces'] += $item['quantity'];

      $product = Entry::find($item['product']);

      if ($product) {

        // Remove product data that shouldn't be visible on frontend

        $product = $product->in(site_locale())->toArray();
        $cart['items'][$item_key]['product'] = $this->removeExtraValues($product);

        // Replace all relations' IDs with objects of data

        foreach ($fieldset_data['fields'] as $field_key => $field) {

          // Replace ID with object only if field's type is Statamic Collection

          if ($field['type'] == 'collection') {

            $type = $field['name'];

            // Check if field is empty in product

            if (isset($product[$type])) {

              // Check whether field contains one or array of IDs (i.e. Type and Vendor are single choice, Collections is array)

              if (isset($field['max_items']) && $field['max_items'] == '1') {

                $relation = Entry::find($product[$type]);

                if ($relation) {

                  $relation = $relation->toArray();
                  $cart['items'][$item_key]['product'][$type] = $this->removeExtraValues($relation);

                }

              } else {

                // If field can contain more IDs, then we need to replace all elements of array

                foreach ($product[$type] as $collection_key => $id) {

                  $relation = Entry::find($id);

                  if ($relation) {

                    $relation = $relation->toArray();
                    $cart['items'][$item_key]['product'][$type][$collection_key] = $this->removeExtraValues($relation);

                  }

                }

              }

            }

          }

        }

        if ($item['variant']) {

          // Find position of selected variant in product's variants and replace id with object of data

          $variant_key = array_search($item['variant'], array_column($product['variants'], 'id'));
          
          if (!is_bool($variant_key)) {
            $var = $product['variants'][$variant_key];

            // Translate attributes if exist in T addon

            if (site_locale() != default_locale() && class_exists('\Statamic\Addons\T\TAPI')) {
              $attrs = explode('|', $var['attrs']);
              $attrs = array_map(function($attr) {
                return app(\Statamic\Addons\T\TAPI::class)->api('T')->string($attr);
              }, $attrs);
              $var['attrs'] = join($attrs, '|');
            }

            $cart['items'][$item_key]['variant'] = $var;
          }

          // Add price from variant instead of product

          $price = @$product['variants'][$variant_key]['price'] ? (float) $product['variants'][$variant_key]['price'] : (float) $product['price'];

        } else {

          // If you not set price, product will not be counted in totals

          if (@$product['price']) {

            $price = (float) $product['price'];

          } else {

            $price = 0;

          }

        }

        // Sum totals based on the quantity

        $cart['total']['sub'] += $price * $item['quantity'];
        $cart['total']['weight'] += @$product['weight'] ? ((float) $product['weight']) * $item['quantity'] : 0;

      }

    }

    // We need to set default address for logged in customer

    if (User::getCurrent() && !session('statamify.default_address')) {

      $this->setDefaultAddress('default');
      $cart['shipping'] = $this->setShipping();

    }

    // If shipping country was set but there were some circumstances that removed shipping from cart, we set it again to default

    if (!$cart['shipping'] && session('statamify.shipping_country')) {

      $cart['shipping'] = $this->setShipping();

    }

    if ($cart['shipping']) {

      $shipping_methods = [];
      $shipping_zones = Statamify::config('shipping_zones');
      $shipping_zone = $shipping_zones[$cart['shipping']['zone']];

      // We need to add all rates - if cart matches both price and weight rates, both methods will be visible

      $bases = ['price_rates', 'weight_rates'];

      foreach ($bases as $base) {

        if (isset($shipping_zone[$base])) {

          // Choose which total to compare

          $compare = $base == 'price_rates' ? 'sub' : 'weight';

          foreach ($shipping_zone[$base] as $key => $method) {

            $condition = true;

            if (isset($method['min']) && $method['min']) {

              if ($cart['total'][$compare] < $method['min']) {

                $condition = false;

              }

            }

            if (isset($method['max']) && $method['max']) {

              if ($cart['total'][$compare] > $method['max']) {

                $condition = false;

              }

            }

            // If method matches criteria, add it to cart's shipping methods

            if ($condition) {

              $shipping_methods[$method['name']] = $method;

            }

          }

        }

      }

      if (count($shipping_methods)) {

        $shipping_method = session('statamify.shipping_method');

        // Check whether shipping method was already set or selected is available. If not, set first one

        if (!$shipping_method || !isset($shipping_methods[$shipping_method])) {

          $keys = array_keys($shipping_methods);
          $first = reset($keys);
          $shipping_method = $first;

          session(['statamify.shipping_method' => $shipping_method]);
          

        }

        // Calculate totals for shipping and set shipping method

        $cart['shipping']['methods'] = $shipping_methods;
        $cart['shipping']['methods'][$shipping_method]['active'] = true;
        $cart['total']['shipping'] = isset($shipping_methods[$shipping_method]['rate']) ? (float) $shipping_methods[$shipping_method]['rate'] : 0;

        // Translate note if exists in T addon

        if (site_locale() != default_locale() && class_exists('\Statamic\Addons\T\TAPI')) {
          $cart['shipping']['methods'] = array_map(function($method) {
            $method['note'] = app(\Statamic\Addons\T\TAPI::class)->api('T')->string($method['note']);
            return $method;
          }, $cart['shipping']['methods']);
        }

      }

    }

    // Calculate discount

    if ($cart['coupons']) {

      foreach ($cart['coupons'] as $coupon) {
        
        $coupon_entry = Entry::whereSlug($coupon, 'store_coupons');

        if ($coupon_entry) {

          switch ($coupon_entry->get('type')) {
            case 'fixed':
              
              if ($coupon_entry->get('value') && $coupon_entry->get('value') != '') {

                $cart['total']['discount'] = (float) $coupon_entry->get('value') * -1;

              }
              
              break;

            case 'free_ship':

              $cart['total']['discount'] = $cart['total']['shipping'] * -1;
              
              break;
            
            default:
              
              if ($coupon_entry->get('value') && $coupon_entry->get('value') != '') {

                $cart['total']['discount'] = $cart['total']['sub'] * ((float) $coupon_entry->get('value') / 100) * -1;

              }

              break;
          }

        }

      }

    }

    // Grand total = sum of all totals

    $cart['total']['grand'] = $cart['total']['sub'] + $cart['total']['discount'] + $cart['total']['shipping'] + $cart['total']['tax'];

    return $cart;

  }

  private function checkInventory($product, $item) {

    // Check if product tracks inventory. Do nothing if not

    if ($product->get('track_inventory')) {

      if ($product->get('class') == 'simple') {

        // If there are too many items of the product in cart, throws error

        if ($product->get('inventory') < $item['quantity']) Statamify::response(400, Statamify::t('product_too_many', 'errors'));

      } elseif ($product->get('class') == 'complex') {

        if (!isset($item['variant'])) {

          foreach ($this->session['items'] as $cart_item_key => $cart_item) {

            if ($item['item_id'] == $cart_item['item_id']) $item['variant'] = $cart_item['variant'];

          }

        }

        foreach ($product->get('variants') as $variant_key => $variant) {

          if (isset($variant['id']) && $variant['id'] == $item['variant']) {

            // If there are too many items of the product's variant in cart, throws error

            if (!$variant['inventory'] || $variant['inventory'] < $item['quantity']) Statamify::response(400, Statamify::t('product_too_many', 'errors'));

          }

        }

      }

    }

  }

  public function setDefaultAddress($key) {

    $user = User::getCurrent();

    // Find customer in Customer Collection based on the slug (email is slug)

    $customer = Entry::whereSlug($user->id(), 'store_customers');

    if ($customer) {

      $addresses = $customer->get('addresses');

      if (!isset($addresses[$key])) {

        $key = array_search(true, array_column($addresses, 'default'));

      }

      // If there are addresses, but not a single one is set as default

      if (is_bool($key) && count($addresses)) {

        $key = 0;

      }

      // If we found address for logged in user, we format country / region
      // and set sessions

      if (!is_bool($key) && isset($addresses[$key])) {

        $address = $addresses[$key];

        session(['statamify.default_address' => [
          'defaultKey' => $key,
          'default' => $address
        ]]);
        session(['statamify.shipping_country' => $address['country']]);

        $this->setShipping();

      }

    }

  }

  public function get() {

    // Check if we want cart session object or full format

    if ($this->recalculated) {

      return $this->recalculate();

    }

    return $this->session;

  }

  public function add($item) {

    $found = false;

    // Check if product is already in cart - compare ID of product if simple + ID of variant if complex

    foreach ($this->session['items'] as $cart_item_key => $cart_item) {

      if ($item['variant']) {

        if ($cart_item['product'] == $item['product'] && $cart_item['variant'] == $item['variant']) $found = $cart_item_key;

      } else {

        if ($cart_item['product'] == $item['product']) $found = $cart_item_key;

      }

    }

    // is_bool returns false - we add product, returns true - we update it on position $found

    if ( is_bool($found) ) {

      $product = Entry::find($item['product']);

      // Check if product exists in store

      if ($product) {

        // Additional validation to check if variant ID was sent when product is actually complex one

        if ($product->get('class') == 'complex') {

          if (!isset($item['variant']) || !$item['variant']) {

            Statamify::response(400, Statamify::t('variant_required', 'errors'));

          } else {

            // Check if variant's ID is correct

            $variant_key = array_search($item['variant'], array_column($product->get('variants'), 'id'));

            if (is_bool($variant_key)) Statamify::response(404, Statamify::t('variant_not_found', 'errors'));

          }

        }

        // Check if there is enough quantity of the product

        $this->checkInventory($product, $item);

        $item = [
          'item_id' => Helper::makeUuid(),
          'quantity' => $item['quantity'],
          'product' => $item['product'],
          'variant' => $item['variant'] ?: false,
          'custom' => isset($item['custom']) && $item['custom'] ? $item['custom'] : null // Custom is for customization / personalization, optional
        ];

        $this->session['items'][] = $item;

        session(['statamify.' . $this->instance => $this->session]);

        return $this->get();

      } else {

        Statamify::response(404, Statamify::t('product_not_found', 'errors'));

      }

    } else {

      // Add quantity to product currently in cart

      $item['item_id'] = $this->session['items'][$found]['item_id'];
      $item['quantity'] += $this->session['items'][$found]['quantity'];

      return $this->update($item);

    }

  }

  public function update($item) {

    // Find cart item by item_id

    $item_key = array_search($item['item_id'], array_column($this->session['items'], 'item_id'));

    // Update item if it's found

    if (!is_bool($item_key)) {

      // Remove item from cart if quantity is zero

      if ($item['quantity'] == 0) {

        unset($this->session['items'][ $item_key ]);

        // Update array keys after removing the item

        $this->session['items'] = array_values($this->session['items']);

        // If cart is empty, remove shipping methods

        if (count($this->session['items']) == 0) {

          $this->session['shipping'] = false;
          session()->forget('statamify.shipping_method');

        }

        session(['statamify.' . $this->instance => $this->session]);

      } else {

        $cart_item = $this->session['items'][$item_key];
        $product = Entry::find($cart_item['product']);

        if ($product) {

          // Check if you can add more items of the product

          $this->checkInventory($product, $item);

          $this->session['items'][ $item_key ]['quantity'] = $item['quantity'];
          session(['statamify.' . $this->instance => $this->session]);

        } else {

          Statamify::response(404, Statamify::t('product_not_found', 'errors'));

        }

      }

    }

    return $this->get();

  }

  public function clear() {

    $this->session = null;

    session()->forget('statamify.' . $this->instance);

    if ($this->instance == 'cart') {

      session()->forget('statamify.shipping_method');

    }

  }

  public function setShipping() {

    $shipping_country = session('statamify.shipping_country');

    if ($shipping_country) {

      // Find zone for country. Customer can't select method if his/her country is not available

      $zones = Statamify::config('shipping_zones');
      $shipping_zone = array_search('rest', array_column($zones, 'type'));

      foreach ($zones as $zone_key => $zone) {
        
        if (isset($zone['countries']) && in_array($shipping_country, $zone['countries'])) {

          $shipping_zone = $zone_key;

          break;
        }

      }

      // If shipping country is set in one of the zones, set this zone in shipping attribute

      if (!is_bool($shipping_zone)) {

        $this->session['shipping'] = ['zone' => $shipping_zone];

      } else {

        $this->session['shipping'] = false;

      }

    } else {

      $this->session['shipping'] = false;

    }

    // Update sessions

    session()->forget('statamify.shipping_method');
    session(['statamify.' . $this->instance => $this->session]);

    return $this->session['shipping'];

  }

  public function addCoupon($coupon, $email = '')
  {

    $this->session['coupons'][] = $coupon;

    session(['statamify.' . $this->instance => $this->session]);

    return $this->get();

  }

  public function removeCoupon($index)
  {

    $coupons = $this->session['coupons'];

    unset($coupons[$index]);

    $this->session['coupons'] = array_values($coupons);

    session(['statamify.' . $this->instance => $this->session]);

    return $this->get();

  }

}