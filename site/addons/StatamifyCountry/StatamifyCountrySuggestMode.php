<?php namespace Statamic\Addons\StatamifyCountry;

use Statamic\Addons\Suggest\Modes\AbstractMode;

class StatamifyCountrySuggestMode extends AbstractMode
{
    public function suggestions()
    {
       
    	$countries = $this->api('Statamify')->countries();

    	return array_map(function($code, $name) {
    		return [ 'value' => $code, 'text' => $name ];	
	    }, array_keys(reset($countries)), reset($countries));

    }
}
