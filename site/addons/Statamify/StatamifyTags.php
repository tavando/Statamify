<?php

namespace Statamic\Addons\Statamify;

use Statamic\Extend\Tags;
use Statamic\API\URL;

class StatamifyTags extends Tags
{

	public function index()
	{
		
		return '';

	}

	public function url()
	{
		
		$url = URL::getCurrent();
		$uri = explode('?', $_SERVER['REQUEST_URI']);

		if (isset($uri[1])) {

			parse_str($uri[1], $query);

		} else {

			$query = [];

		}

		$type = $this->get('type') ?: 'replace';
		$key = $this->get('key');
		$value = $this->get('value');
		$logic = $this->get('logic') ?: 'OR';

		switch ($type) {

			case 'get':

				if (isset($query[$key])) {

					if (!$value) {

						return $query[$key];

					} else {

						$fields = explode(';', $query[$key]);

						foreach ($fields as $k => $field) {

							$field = explode(':', $field);
							$value = str_replace('@', ':', $value);

							$condition = 	$field[0] == $value 
														|| join($field, ':') == $value 
														|| in_array(@explode(':', $value)[1], explode('|', $field[1])) 
														|| in_array(@explode(':', $value)[1], explode(',', $field[1]));

							if ($condition) {

								return $field[1];

							}

						}

						return false;

					}

				} else {

					return false;

				}

				break;

			case 'add':

				if (isset($query[$key])) {

					$sign = $logic == 'OR' ? '|' : ',';
					$fields = explode(';', $query[$key]);
					$value = explode(':', $value);

					$found = false;

					foreach ($fields as $k => $field) {
						
						$field = explode(':', $field);

						if ($field[0] == $value[0]) {

							$found = true;
							$values = explode($sign, $field[1]);

							if (!in_array($value[1], $values)) {

								$values[] = $value[1];

							}

							$field[1] = join($values, $sign);
							$fields[$k] = join($field, ':');

						}

					}

					if (!$found) {

						$field = array_shift($value);
						$fields[] = $field . ':' . join($value, $sign);
						$query[$key] = join($fields, ';');

					}

					$query[$key] = join($fields, ';');

				} else {

					$query[$key] = $value;

				}

				break;

			case 'remove':

				if ($value) {

					if (isset($query[$key])) {

						$sign = $logic == 'OR' ? '|' : ',';
						$fields = explode(';', $query[$key]);

						foreach ($fields as $k => $field) {

							if (strpos($value, ':')) {

								$field = explode(':', $field);
								$val = explode(':', $value);
								$values = explode($sign, $field[1]);

								if (in_array($val[1], $values)) {

									$values = array_diff($values, [$val[1]]);

								}

								if ($values) {

									$field[1] = join($values, $sign);
									$fields[$k] = join($field, ':');

								} else {

									unset($fields[$k]);

								}

							} else {

								if (strpos($field, $value . ':') !== false) {

									unset($fields[$k]);

								}

							}

						}

						if (count($fields)) {

							$query[$key] = join($fields, ';');

						} else {

							unset($query[$key]);

						}

					}

				} else {

					unset($query[$key]);

				}

				break;
			
			default:
				
				$query[$key] = $value;

				break;
		}

		return $uri[0] . ($query ? '?' . http_build_query($query) : '');

	}
}
