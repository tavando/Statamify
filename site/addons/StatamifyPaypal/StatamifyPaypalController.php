<?php

namespace Statamic\Addons\StatamifyPaypal;

require('PaypalIPN.php');

use Statamic\Extend\Controller;
use Statamic\API\Entry;
use Illuminate\Http\Request;
use Statamic\API\Stache;
use PaypalIPN;

class StatamifyPaypalController extends Controller
{

	public function postVerify(Request $request) {

		$ipn = new PaypalIPN();

		if ($this->getConfig('sandbox', true)) {

				$ipn->useSandbox();

		}
		
		$verified = $ipn->verifyIPN();

		if ($verified) {

			$data = $request->all();

			$order = Entry::find($data['custom']);

			if ($order) {

				$error = false;

				// Check if paypal business email is the same as business email in settings

				$business = $this->getConfig('sandbox', true) ? $this->getConfig('business_sandbox') : $this->getConfig('business');

				if ($business != $data['business']) { $error = true; }

				// Check if paypal total is equal to total grand in order

				$summary = $order->get('summary');

				if ($summary['total']['grand'] != $data['mc_gross']) { $error = true; }

				// Check if payment status is Completed

				if ($data['payment_status'] != 'Completed') { $error = true; }

				// Check if txn_id exists

				if (!isset($data['txn_id'])) { $error = true; }

				if (!$error) {

					$payment_method = $order->get('payment_method');
					$payment_method['fee'] = $data['mc_fee'];
					$payment_method['id'] = $data['txn_id'];

					$order->set('payment_method', $payment_method);
					$order->set('status', 'pending');

					$order->save();

					Stache::update();

				} else {

					return redirect('/account/order/' . $order->slug())->withInput([
						'errors' => [$this->api('Statamify')->t('somethings_wrong', 'errors')]
					]);

				}

			} else {

				return redirect('/account');

			}

		}

		header("HTTP/1.1 200 OK");

	}

}