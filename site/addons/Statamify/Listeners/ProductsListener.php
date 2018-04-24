<?php

namespace Statamic\Addons\Statamify\Listeners;

use Statamic\API\Asset;
use Statamic\API\Entry;
use Statamic\Extend\Listener;
use Statamic\API\Stache;
use Statamic\Addons\Statamify\Statamify;

class ProductsListener extends Listener
{

  private $cp = false;
  private $original = null;

  public $events = [
    'cp.entry.published' => 'published',
    'content.saved' => 'saved',
  ];

  public function published($entry)
  {

    $collection = $entry->toArray()['collection'];

    if ($collection == 'store_products') {
      
      $change = false;
      $check = [
        'listing_image' => $this->image($entry),
        'listing_type' => $this->relation($entry, $this->original, 'type', 'store_types'),
        'listing_vendor' => $this->relation($entry, $this->original, 'vendor', 'store_vendors'),
      ];

      foreach ($check as $key => $value) {

        if ($entry->get($key) != $value) {

          $entry->set($key, $value);
          $change = true;

        }

      }

      $this->category($entry, $this->original);

      if ($change) {

        $entry->save();
        Stache::update();

      }

    }

  }

  private function image($entry)
  {

    if ($entry->get('image')) {

      $asset = Asset::find($entry->get('image'));
      $image = $asset->manipulate(['w' => 50, 'h' => 50, 'fit' => 'crop']);

      return '<div class="statamify-thumb" style="background-image: url(' . $image . ')"></div>';

    } else {

      return '';

    }

  }

  private function relation($entry, $original, $field, $collection)
  {

    if ($entry->get($field)) {

      /********** UPDATE RELATION'S PRODUCTS FIELD IF DOESN'T HAVE PRODUCT'S ID IN IT  ***/
      $relation = Entry::find($entry->get($field));

      if (!$relation->get('products') || !in_array($entry->get('id'), $relation->get('products'))) {

        $products = $relation->get('products') ?: [];
        $products[] = $entry->get('id');
        $relation->set('products', $products);
        $this->cp = true;
        $relation->save();
        Stache::update();

      }

      $this->relationOldCheck($entry, $original, $field);

      return $relation->get('title') . ' <a href="' . $relation->toArray()['edit_url'] . '" class="statamify-link"><span class="icon icon-forward"></span></a>';

    } else {

      $this->relationOldCheck($entry, $original, $field);

      return '';

    }

  }

  private function relationOldCheck($entry, $original, $field)
  {

    if (isset($original['data'])) {

      /********** REMOVE PRODUCT'S ID FROM OLD RELATION'S PRODUCTS FIELD ***/
      $original_data = reset($original['data']);

      if (isset($original_data[$field]) && $entry->get($field) != $original_data[$field]) {

        if ($original_data[$field]) {

          $old_relation = Entry::find($original_data[$field]);
          $old_relation_products = $old_relation->get('products') ?: [];
          $old_relation_products = array_diff($old_relation_products, [$entry->get('id')]);
          $old_relation->set('products', $old_relation_products);
          $this->cp = true;
          $old_relation->save();
          Stache::update();

        }

      }

    }

  }

  private function category($entry, $original)
  {

    if ($entry->get('categories')) {

      $new_categories = array_diff($entry->get('categories'), (isset($original_data['categories']) ? $original_data['categories'] : []));

      /********** UPDATE EVERY COLLECTION WITH PRODUCT ID ***/
      foreach ($new_categories as $id) {

        $collection = Entry::find($id);

        if (!$collection->get('products') || !in_array($entry->get('id'), $collection->get('products'))) {

          $products = $collection->get('products') ?: [];
          $products[] = $entry->get('id');
          $collection->set('products', $products);
          $this->cp = true;
          $collection->save();

        }

      }

      $this->categoryOldCheck($entry, $original);

    } else {

      $this->categoryOldCheck($entry, $original);

    }

  }

  private function categoryOldCheck($entry, $original)
  {

    if (isset($original['data'])) {

      $original_data = reset($original['data']);

      /********** REMOVE PRODUCT'S ID FROM OLD COLLECTIONS' PRODUCTS FIELD ***/
      if (isset($original_data['categories']) && $entry->get('categories') != $original_data['categories']) {

        $old_categories = array_diff($original_data['categories'], ($entry->get('categories') ?: []));

        foreach ($old_categories as $id) {
          
          $old_category = Entry::find($id);
          $old_category_products = $old_category->get('products') ?: [];
          $old_category_products = array_diff($old_category_products, [$entry->get('id')]);
          $old_category->set('products', $old_category_products);
          $this->cp = true;
          $old_category->save();

        }

        Stache::update();

      }

    }

  }

  public function saved($entry, $original)
  {

    $this->original = $original;
    $data = $entry->toArray();

    if (isset($data['collection'])) {

      $collection = $data['collection'];

      switch ($collection) {

        case 'store_types':
        case 'store_vendors':
        if (!$this->cp) $this->relationSaved($entry, $original, $collection);
        break;

        case 'store_categories':
        if (!$this->cp) $this->categorySaved($entry, $original);
        break;

        case 'store_products':

        $change = false;
        $check = [
          'listing_inventory' => $this->inventory($entry)
        ];

        foreach ($check as $key => $value) {

          if ($entry->get($key) != $value) {

            $entry->set($key, $value);
            $change = true;

          }

        }

        if ($change) {

          $entry->save();
          Stache::update();

        }

        break;

      }

    }

  }

  private function inventory($entry)
  {

    if (!$entry->get('track_inventory')) {

      $inventory = '-';

    } else {

      if ($entry->get('class') == 'simple') {

        $inventory = '<span class="inventory-quantity">' . ($entry->get('inventory') ?: '0') . '</span> ' . Statamify::t('instock');

      } elseif ($entry->get('class') == 'complex') {

        $variants = $entry->get('variants');
        $sum = 0;

        foreach ($variants as $key => $variant) {

          if (!is_string($key)) {

            if ($variant['inventory']) {

              $sum += (int) $variant['inventory'];

            }

          }

        }

        $inventory = '<span class="inventory-quantity">' . ($sum ?: '0') . '</span> ' . Statamify::t('instock_for', 'statamify', ['number' => (count($variants) - 1)]);

      }

    }

    return $inventory;

  }

  private function relationSaved($entry, $original, $collection) {

    $data_original = reset($original['data']);

    if ($entry->get('products') != @$data_original['products']) {

      $products_original = isset($data_original['products']) ? $data_original['products'] : [];
      $products = $entry->get('products') ?: [];

      /********** UPDATE ALL FROM ARRAYS BELOW. ADD - ADD RELATION TO PRODUCT. REMOVE - REMOVE RELATION FROM PRODUCT ***/
      $add = array_diff($products, $products_original);
      $remove = array_diff($products_original, $products);

      foreach ($add as $id) {

        if ($id) {

          $type = str_replace('store_', '', substr($collection, 0, -1));
          $product = Entry::find($id);

          if ($product->get($type)) {
            $old_relation = Entry::find($product->get($type));
            $old_relation_products = $old_relation->get('products') ?: [];
            $old_relation_products = array_diff($old_relation_products, [$id]);
            $old_relation->set('products', $old_relation_products);
            $old_relation->save();
          }

          if ($product->get($type) != $entry->get('id')) {

            $product->set($type, $entry->get('id'));
            $product->set('listing_' . $type, $entry->get('title') . ' <a href="' . $entry->toArray()['edit_url'] . '" class="statamify-link"><span class="icon icon-forward"></span></a>');
            $product->save();

          }
          
        }

      }

      foreach ($remove as $id) {

        if ($id) {

          $product = Entry::find($id);
          $type = str_replace('store_', '', substr($collection, 0, -1));

          if ($product->get($type) == $entry->get('id')) {
            $product->set($type, '');
            $product->set('listing_' . $type, '');
            $product->save();
          }

        }

      }

      Stache::update();

    }

  }

  private function categorySaved($entry, $original) {

    $data_original = reset($original['data']);

    if ($entry->get('products') != @$data_original['products']) {

      $products_original = isset($data_original['products']) ? $data_original['products'] : [];
      $products = $entry->get('products') ?: [];

      /********** UPDATE ALL FROM ARRAYS BELOW. ADD - ADD COLLECTION TO PRODUCT. REMOVE - REMOVE COLLECTION FROM PRODUCT ***/
      $add = array_diff($products, $products_original);
      $remove = array_diff($products_original, $products);

      foreach ($add as $id) {

        if ($id) {

          $product = Entry::find($id);

          if (!$product->get('categories') || !in_array($entry->get('id'), $product->get('categories'))) {

            $products = $product->get('categories') ?: [];
            $products[] = $entry->get('id');
            $product->set('categories', $products);
            $product->save();

          }

        }

      }

      foreach ($remove as $id) {

        if ($id) {

          $product = Entry::find($id);

          if ($product->get('categories') && in_array($entry->get('id'), $product->get('categories'))) {
            $products = array_diff($product->get('categories'), [$entry->get('id')]);
            $product->set('categories', $products);
            $product->save();
          }

        }

      }

      Stache::update();

    }

  }

}