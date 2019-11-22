<?php namespace App\Services\Zeke;

use App\Services\Market;

/**
 * Class ZekeCommand
 * @package App\Services\Zeke
 */
class ZekeCommand
{
  protected $command;
  protected $zeke;

  public function __construct(&$zeke)
  {
    $this->zeke = $zeke;
  }

  public function help()
  {
    $this->zeke->messages->add([
      'sites' => 'list of our wonderful sites that you like',
      'venrate' => 'current rate Ven',
    ], 'commands');
  }

  public function venrate()
  {
    $markets = Market::getMarket();
		$message = [];
		foreach ($markets as $market) {
			$message[] = "<b>{$market['currency']}:</b> " . number_format($market['rate'], 6);
    }
    $message = implode('<br>', $message);
    $this->zeke->messages->add($message);
  }

  public function sites()
  {
    $sites = [];
    $sites[] = '1. <a href="https://hubculture.com">HubCulture</a>';
    $sites[] = '2. <a href="https://ven.vc">Ven.vc</a>';
    $sites[] = '3. <a href="https://ultra.exchange">UltraExchange</a>';
    $sites = implode('<br>', $sites);

    $this->zeke->messages->add($sites);
  }

  public function welcome()
  {
    $this->zeke->messages->add($this->zeke->user ? "Hi {$this->zeke->user->name}" : 'Welcome, glad to see you here');
  }

}