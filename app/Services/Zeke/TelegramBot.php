<?php namespace App\Services\Zeke;

use GuzzleHttp\Client;
use TelegramBot\Api\BotApi;
use TelegramBot\Api\Types\ReplyKeyboardMarkup;

/**
 * Class TelegramBot
 * @package App\Services\Zeke
 */
class TelegramBot extends BotApi {
    const BOT_NAME = 'Zeke';
    const SEARCH_MESSAGE_PERCENT = 82;
    const TAG_USERNAME = '%USERNAME%';
    public $token = '770444742:AAE00KI0vZ9BfXYWyTD_TbkJRbpq-IHv1Yk';

    protected $updateId;

    public function __construct()
    {
        parent::__construct($this->token);
    }

    /**
     * @param $method
     * @param array $parms
     * @return mixed
     */
    protected function query($method, $parms = []) 
    {
        $url = 'https://api.telegram.org/bot' . $this->token . '/' . $method;

        if (!empty($parms)) {
            $url .= '?' .  http_build_query($parms);
        }

        $client = new Client([
            'base_uri' => $url
        ]);

        $result = $client->request('GET');

        return json_decode($result->getBody());
    }

    /**
     * @param $chatId
     * @param $document
     * @param null $caption
     * @param null $replyToMessageId
     * @param null $replyMarkup
     * @param bool $disableNotification
     * @param null $parseMode
     * @return mixed
     */
    public function sendDocument($chatId, $document, $caption = NULL, $replyToMessageId = NULL, $replyMarkup = NULL, $disableNotification = false, $parseMode = NULL)
    {
        $document = new \CURLFile($document);
        $file_name = basename($document->name);
        
        $this->sendMessage($chatId, "<em>Loading...({$file_name})</em>", 'HTML');
        if ($response = parent::sendDocument($chatId, $document)) {
             $this->sendMessage($chatId, '<em>Uploaded...</em>', 'HTML');
        }
        return $response;
    }

    /**
     * @param $chatId
     * @param $message
     * @param $keyboard
     * @return mixed
     */
    public function sendMessageBot($chatId, $message, $keyboard)
    {
        $user_message = str_replace(self::BOT_NAME, '', $message->text);
        $username = !empty($message->chat->username) ? $message->chat->username : 'Guest';
        return $this->sendMessage($chatId, self::botAnswer($user_message, $username), null, false, null, $keyboard);
    }

    /**
     * @return mixed
     */
    public function getUpdatesMy()
    {
        $response = $this->query('getUpdates', [
            'offset' => $this->updateId + 1
        ]);

        if (!empty($response->result)) {
            $this->updateId = $response->result[count($response->result) - 1]->update_id;
        }

        return $response->result;
    }

    /**
     * @param $message
     * @param $username
     * @return mixed|string
     */
    private static function botAnswer($message, $username)
    {
        $files_answer = glob(storage_path('zeke/') . '*');
        $handle = fopen($files_answer[mt_rand(0, count($files_answer) - 1)], 'r');
        $bot_messages = [];
        while (!feof($handle)) {
            $buffer = fgets($handle);
            $text = explode('\\', $buffer);
        
            if (empty($text[1])) {
                continue;
            }
            similar_text($message, $text[0], $percent);
            if ($percent >= self::SEARCH_MESSAGE_PERCENT) {
                $text[1] = str_replace([self::TAG_USERNAME], ['@' . $username], $text[1]);
                $bot_messages[] = ['message' => $text[1], 'key' => $text[0], 'ver' => $percent];
            }
        }
        fclose($handle);
        if (empty($bot_messages)) {
            return 'I do not know what to answer =(';
        }
        $i = self::getRandomIndex($bot_messages);
        return !empty($bot_messages[$i]['message']) ? $bot_messages[$i]['message'] : 'I do not know what to answer =(';
    }
    /**
     * Random sample taking into account the weight of each element.
     * @param array $data The array in which the random element is searched.
     * @param string $column Array parameter containing probability weights
     * @return int Index of the item found in the array $data 
     */
    private static function getRandomIndex($data, $column = 'ver')
    {
        $rand = mt_rand(1, array_sum(array_column($data, $column)));
        $cur = $prev = 0;
        for ($i = 0, $count = count($data); $i < $count; ++$i) {
            $prev += $i != 0 ? $data[$i-1][$column] : 0;
            $cur += $data[$i][$column];
            if ($rand > $prev && $rand <= $cur) {
                return $i;
            }
        }
        return -1;
    }

}