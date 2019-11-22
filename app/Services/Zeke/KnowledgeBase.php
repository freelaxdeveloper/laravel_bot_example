<?php namespace App\Services\Zeke;

use App\Models\BotZeke\Question as BotQuestion;
use App\Models\ZekeQuestion;
use Cache;

/**
 * This class contains everything that Zeke knows
 * to get new knowledge, a function is created that will return an array with the required fields, see function example()
 * the function will automatically connect, so all you need is just create it
 */

/**
 * Class KnowledgeBase
 * @package App\Services\Zeke
 */
class KnowledgeBase
{

/* 
  public function example()
  {
    //  all you need is an array containing: id, question, answer
    $questions = [];
    $questions[] = ['id' => 1, 'question' => 'The example works?', 'answer' => 'Oh sure'];
    $questions[] = ['id' => 2, 'question' => 'How are you?', 'answer' => 'All perfectly'];

    return $questions;
  }
*/


    /**
     * @param $account_id
     * @return array
     */
  public function botQuestions($account_id)
  {
    $list = Cache::rememberForever("botQuestions_{$account_id}", function () use ($account_id) {
      return BotQuestion::whereNotNull('user_id')->whereIdAccount($account_id)->with('answer')->get();
    });
		$questions = [];
		foreach ($list as $question) {
			$questions[] = [
				'id' => $question->answer->id,
				'question' => $question->question,
				'questionInfo' => $question,
				'answer' => $question->answer->answer,
			];
    }
		return $questions;
  }

    /**
     * @param $account_id
     * @return array
     */
  public function zekeQuestions($account_id)
  {
    if (1 != $account_id) {
      return [];
    }
    $questions = [];
    $list = Cache::remember('zekeQuestions', 60 * 3, function () {
      return ZekeQuestion::where('is_answered', 1)->get(['id', 'answer', 'question', 'liked']);
    });
    foreach ($list as $question) {
      $question->zekeQuestion = true;
      $questions[] = [
				'id' => $question->id,
				'question' => $question->question,
				'questionInfo' => $question,
				'answer' => $question->answer,
      ];
    }

		return $questions;
  }
}