<?php

namespace Statamic\Addons\Fieldblock;

use Statamic\Extend\Listener;

class FieldblockListener extends Listener
{
	/**
	 * The events to be listened for, and the methods to call.
	 *
	 * @var array
	 */
	public $events = [
		'cp.add_to_head' => 'eventAddToHead',
	];

	public function eventAddToHead() {

		return $this->css->tag("fieldblock") . PHP_EOL;

	}
}