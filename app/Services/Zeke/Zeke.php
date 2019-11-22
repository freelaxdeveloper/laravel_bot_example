<?php namespace App\Services\Zeke;

use App\Services\Zeke\ZekeMessage as Message;
use App\Services\Zeke\ZekeCommand as Command;
use App\Services\Zeke\ZekeAnswer as Answer;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use App\Models\BotZeke\Account;
use Collection;
use JWTAuth;
use Cache;

/**
 * Class Zeke
 * @package App\Services\Zeke
 */
class Zeke
{
  public $user;
  public $account;
  protected $cache;
  protected $message;
  public $messages;
  public $commands;
  public $params;
  public $isAuthorize = false;

    /**
     * Zeke constructor.
     * @param null $message
     * @param Account|null $account
     * @param array $params
     */
  public function __construct($message = null, Account $account = null, $params = [])
  {
      $this->params = $params;
	  $this->message = $message;
	  $this->user = $this->getUser();
	  $this->account = $account;
    $this->cache = $this->cache();
    $this->messages = new Message;
    $this->commands = new Command($this);

    if ($this->message) {
      $this->actions();
    }
  }

  public function actions()
  {
    if ((!$this->cache['last_time'] || time() - $this->cache['last_time'] > 1800) && !isset($this->params['nowelcome'])) {
      // it is necessary to say hello to a person who writes for the first time or 30 minutes have passed since the last message
      $this->commands->welcome();
    }
    if ('/' == $this->message[0]) {
      // you must send the command
      $command = str_replace('/', '', $this->message);
      if (method_exists($this->commands, $command)) {
        $this->commands->$command();
      }
    } else {
      // analyze the question and the helmet answer
      $answer = new Answer($this, $this->message);
      $answer->send();
    }
  }

    /**
     * @return array
     */
  public function getMessages()
  {
    return $this->messages->output($this->message);
  }

	protected function cache()
	{
		$last_time = Cache::get('zekebot_lastTime', null);

		return compact('last_time');
  }

    /**
     * @return |null
     */
  protected function getUser()
  {
    if (JWTAuth::getToken()) {
      try {
        $this->isAuthorize = true;
        return JWTAuth::parseToken()->toUser();
      } catch(TokenExpiredException $e) {
        $this->isAuthorize = true;
        return null;
      }
    }
    return null;
  }

  public function __destruct()
	{
		Cache::add('zekebot_lastTime', time(), 30);
	}
}
