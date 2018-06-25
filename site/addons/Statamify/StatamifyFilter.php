<?php

namespace Statamic\Addons\Statamify;

use Statamic\Extend\Filter;
use Statamic\API\Entry;
use Statamic\API\Collection;
use Statamic\API\Fieldset;

class StatamifyFilter extends Filter
{
    /**
     * Perform filtering on a collection
     *
     * @return \Illuminate\Support\Collection
     */
    public function filter()
    {

      $filter = isset($_GET['filter']) ? $_GET['filter'] : false;
      $params = explode(';', $filter);

      if ($filter && $params) {

        $params = array_map(function($el) {

          $v = explode(':', $el);
          $param = ['field' => $v[0]];

          if (strpos($v[1], '|') !== false) {

            $param['type'] = 'OR';
            $param['values'] = explode('|', $v[1]);

          } elseif (strpos($v[1], ',') !== false) {

            $param['type'] = 'AND';
            $param['values'] = explode(',', $v[1]);

          } elseif (strpos($v[1], '>') !== false) {

            $param['type'] = '>';
            $param['values'] = str_replace('>', '', $v[1]);

          } elseif (strpos($v[1], '<') !== false) {

            $param['type'] = '<';
            $param['values'] = str_replace('<', '', $v[1]);

          } else {

            $param['type'] = 'AND';
            $param['values'] = [$v[1]];

          }

          $fieldset = Fieldset::get(Collection::whereHandle('store_products')->get('fieldset'));
          $fieldset_data = $fieldset->toArray();

          if (isset($fieldset_data['sections'])) {

            $fieldset_data_replace = [];

            foreach ($fieldset_data['sections'] as $section) {
              $fieldset_data_replace = array_merge($fieldset_data_replace, $section['fields']);
            }

            $fieldset_data['fields'] = $fieldset_data_replace;

          }

          $param['field'] = str_replace(['store_types', 'store_vendors', 'store_categories'], ['type', 'vendor', 'categories'], $param['field']);
          $key = array_search($param['field'], array_column($fieldset_data['fields'], 'name'));

          if (!is_bool($key) && $fieldset_data['fields'][$key]['type'] == 'collection') {

            $collection = reset($fieldset_data['fields'][$key]['collection']);

            $param['values'] = array_map(function($value) use ($collection) {

              $entry = Entry::whereSlug($value, $collection);

              return $entry ? $entry->get('id') : $value;

            }, $param['values']);

          }

          $param['field'] = str_replace(['store_types', 'store_vendors', 'store_categories'], ['type', 'vendor', 'categories'], $param['field']);

          return $param;

        }, $params);

        return $this->collection->filter(function($entry) use ($params) {

          $meet = true;

          foreach ($params as $param) {

            $entry_field = $entry->in(site_locale())->get($param['field'], $entry->in(default_locale())->get($param['field']));
            
            if (is_array($entry_field)) {

              switch ($param['type']) {
                case 'AND':
                  if (array_diff($param['values'], $entry_field)) { $meet = false; }
                break;

                case 'OR':
                  if (!array_intersect($param['values'], $entry_field)) { $meet = false; }
                break;
                
                default:
                  $meet = false;
                break;
              }

            } else {

              switch ($param['type']) {
                case 'AND':
                  if (reset($param['values']) != $entry_field) { $meet = false; }
                break;

                case 'OR':
                  if (!in_array($entry_field, $param['values'])) { $meet = false; }
                break;

                case '>':
                  if ($param['values'] > $entry_field) { $meet = false; }
                break;

                case '<':
                  if ($param['values'] < $entry_field) { $meet = false; }
                break;
                
                default:
                  $meet = false;
                break;
              }

            }

          }

          return $meet;

        });

      } else {

        return $this->collection;

      }
    }
}
