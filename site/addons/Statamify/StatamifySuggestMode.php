<?php

namespace Statamic\Addons\Statamify;

use Statamic\Addons\Suggest\Modes\AbstractMode;

class StatamifySuggestMode extends AbstractMode
{
	public function suggestions()
	{

		switch ($this->request->input('name')) {

			case 'countries':
				
				$countries = $this->api('Statamify')->countries();

				return array_map(function($code, $name) {
					return [ 'value' => $code, 'text' => $name ];	
				}, array_keys(reset($countries)), reset($countries));

			break;
		}
	}
}
