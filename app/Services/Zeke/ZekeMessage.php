<?php namespace App\Services\Zeke;

use App\Services\Zeke\Voice;

/**
 * Class ZekeMessage
 * @package App\Services\Zeke
 */
class ZekeMessage
{
  protected $messages;

  public function __construct()
  {
    
  }

    /**
     * @param $message
     * @param string $type
     * @param string $author
     * @param null $answer_id
     * @param null $questionInfo
     */
  public function add($message, $type = 'text', $author = 'them', $answer_id = null, $questionInfo = null)
  {
    $this->messages[] = compact('message', 'type', 'author', 'answer_id', 'questionInfo');
  }

    /**
     * @param null $question
     * @return array
     */
  public function output($question = null)
  {
    $reply = [];

    (new Voice($this->messages))->saveVoiceText();
    foreach ($this->messages as $message) {
      $reply[] = [
        'question' => $question,
        'type' => $message['type'],
        'author' => $message['author'],
        'answer_id' => $message['answer_id'],
        'data' => [
          'text' => $message['message'],
          'voice' => !empty($message['voice']) ? $message['voice'] : '',
        ],
        'questionInfo' => $message['questionInfo'],
      ];
    }
		return $reply;
  }

}