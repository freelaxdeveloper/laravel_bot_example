<?php namespace App\Services\Zeke;

use Storage;

/**
 * Class Voice
 * @package App\Services\Zeke
 */
class Voice 
{
  public $messages;
  const DIR_PATH = 'storage/zeke/audio';

  public function __construct(&$messages)
  {
    $this->messages = &$messages;
  }

    /**
     * @return $this
     */
  public function saveVoiceText()
  {
    
    $message = $this->getMessage();
    if (!$message) {
      return $this;
    }
    $hash = md5($message) . '.mp3';
    $audio_path = 'https://translate.google.com.vn/translate_tts?ie=UTF-8&q=' . urlencode(trim(strip_tags($message))) . '&tl=en&client=tw-ob';

    try {
      $exists = Storage::disk('zekeVoice')->exists($hash);
      if (!$exists) {
        Storage::disk('zekeVoice')->put($hash, file_get_contents($audio_path), 'public');
      }    
      $this->messages[0]['voice'] = "https://s3.amazonaws.com/zeke-voice/{$hash}";
    } catch (\Exception $e) {
      return $this;
    }

    return $this;
  }


  protected function getMessage()
  {
    if (!isset($this->messages[0]) || empty($this->messages[0]['message']) || is_array($this->messages[0]['message'])) {
      return;
    }

    return $this->messages[0]['message'];
  }

}