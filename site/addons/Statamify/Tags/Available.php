<?php

namespace Statamic\Addons\Statamify\Tags;

class Available
{

  public static function tag($s)
  {

  	$product = $s->context;

  	if ($product['track_inventory']) {

  		if ($product['class'] == 'simple') {

  			if ($product['inventory'] < 1) {

  				return false;

  			} else {

  				return true;

  			}

  		} else {

  			return true;

  		}

  	} else {

  		return true;

  	}

  }

}