<?php

namespace Statamic\Addons\Statamify\Models;

use Statamic\Addons\Statamify\Models\Cart;
use Statamic\Addons\Statamify\Statamify;
use Omnipay\Omnipay;

class Gateway
{

  protected $data;
  protected $redirect;
  protected $status;

  public function __construct($order, $cart)
  {

    $this->order = $order;
    $this->cart = $cart;
    $this->config = collect(Statamify::config('gateways'));

    switch ($order['payment_method']) {

      case 'stripe': return $this->stripe(); break;

      default: return $this->cheque(); break;

    }

  }

  private function cheque()
  {

    return [
      'status' => 'awaiting_payment',
      'data' => ['name' => 'Cheque', 'fee' => 0]
    ];

  }

  private function stripe()
  {

    $gateway = Omnipay::create('Stripe');
    $index = $this->config->search(function($gateway) { return $gateway['type'] == 'stripe'; });
    $config = $this->config->get($index);

    $gateway->setApiKey($config[($config['test'] ? 'test_keys' : 'keys')]['secret']);
    
    $token = $this->order['payment_token'];
    $request = $gateway->purchase([
      'amount' => $this->cart['total']['grand'],
      'currency' => 'USD',
      'description' => $this->order['title'],
      'source' => $token
    ]);

    $request_data = $request->getData();
    $request_data['expand[]'] = 'balance_transaction';

    $response = $request->sendData($request_data);

    if ($response->isSuccessful()) {

      $data = $response->getData();

      $this->status = 'pending';
      $this->data = [
        'name' => 'Stripe', 
        'fee' => isset($data['balance_transaction']['fee']) ? $data['balance_transaction']['fee']/100 : 0, 
        'id' => $data['id']
      ];

    } else {

      $this->errors = [$response->getMessage()];

    }

  }

  public function errors()
  {

    if (isset($this->errors)) {

      return $this->errors;

    } else {

      return false;

    }

  }

  public function redirect()
  {

    if (isset($this->redirect)) {

      return $this->redirect;

    } else {

      return false;

    }

  }

  public function getStatus()
  {

    return $this->status;

  }

  public function getData()
  {

    return $this->data;

  }

}