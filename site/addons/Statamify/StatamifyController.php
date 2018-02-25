<?php

namespace Statamic\Addons\Statamify;

use Statamic\Extend\Controller;
use Illuminate\Http\Request;
use Validator;

class StatamifyController extends Controller
{

	public function getCart() {

		return $this->api('Statamify')->cartGet();

	}

	public function postCartAdd(Request $request) {

		$data = $request->all();

		$validator = Validator::make($data, [
			'product' => 'required',
			'quantity' => 'required',
		]);

		if ($validator->fails()) {

			throw new \Exception('somethings_wrong');

		}

		return $this->api('Statamify')->cartAdd($data);

	}

	public function getCountries() {

		$countries = $this->api('Statamify')->countries();
		$regions = $this->api('Statamify')->regions();

		return [ 
			'countries' => reset($countries), 
			'regions' => reset($regions) 
		];

	}

}
