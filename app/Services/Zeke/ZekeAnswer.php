<?php namespace App\Services\Zeke;

use App\Services\Zeke\KnowledgeBase;
use App\Services\Zeke\ZekeGoogleSearch;

/**
 * Class ZekeAnswer
 * @package App\Services\Zeke
 */
class ZekeAnswer
{
  protected $question;
  protected $zeke;

    /**
     * ZekeAnswer constructor.
     * @param $zeke
     * @param $question
     */
  public function __construct(&$zeke, $question)
  {
    $this->zeke = $zeke;
    $this->question = $question;
  }


    /**
     * @param $answers
     * @param $questions
     * @param $is_break
     */
	protected function getSimilar($answers, $questions, &$is_break)
	{
		$answer_ids = [];
		foreach ($questions as $question) {
			$keywords_user = $this->keywords($this->question);
			$keywords_zeke = $this->keywords($question['question']);
			similar_text($keywords_user, $keywords_zeke, $percent);
			if (49 >= $percent) {
				continue;
			}
			// looking for at least one keyword match
			if (count(array_intersect(explode(' ', $keywords_user), explode(' ', $keywords_zeke))) < 1) {
				continue;
			}

			if (100 == $percent) {
				$answers->push(['question' => $question, 'percent' => $percent, 'answer' => $question['answer']]);
				$is_break = true;
				return;
			}
			if (!in_array($question['id'], $answer_ids)) {
				$answer_ids[] = $question['id'];
				$answer = ['percent' => $percent, 'question' => $question, 'answer' => $question['answer']];
				$answers->push($answer);
			}
		}
	}

	/**
	 * We calculate the weight of the question based on the content of the keywords in it
	 */
	protected function calculateWeight($answers)
	{
		$newArray = collect();
		foreach ($answers as $question => $answer) {
			$questionInfo = isset($answer['questionInfo']) ? $answer['questionInfo'] : null;
			$weight = $questionInfo && $questionInfo->liked > 0 ? $questionInfo->liked : 0;
			$newArray->push([
				'string' => $question,
				'answer' => $answer['answer'],
				'weight' => $weight,
				'questionInfo' => $questionInfo,
			]);
		}

		$string = explode(' ', $this->question);
		foreach ($string as $str) {
			$newArray->transform(function ($item) use ($str) {
				if (str_contains(mb_strtolower($item['string']), mb_strtolower($str))) {
					// increase weight if an occurrence of a keyword is found
					++$item['weight'];
				}
				return $item;
			});
		}
		
		// we do not have a function collect()->max() =(
		// we twist as we can
		$first = $newArray->sortByDesc('weight')->first();
		$response = $newArray->where('weight', $first['weight']);
		
		return collect($response->all());
	}

    /**
     * @return mixed
     */
  public function send()
  {
		$answers = collect();
		$knowledgeBase = new KnowledgeBase;
		$is_break = false;
		foreach (get_class_methods($knowledgeBase) as $method) {
			$this->getSimilar($answers, $knowledgeBase->$method($this->zeke->account->id), $is_break);
			if ($is_break) {
				break;
			}
		}

    if (1 == $answers->count()) {
			// if the answer is one, then we give it back
			$answer = $answers->first();
			$answer = isset($answer[0]) ? $answer[0] : $answer;
      return $this->zeke->messages->add($answer['answer'], 'text', 'them', $answer['question']['id'], $answer['question']['questionInfo']);
    }
    if (!$answers->count()) {
			// if they did not find the answer, apologize for their stupidity
			$googleSearch = (new ZekeGoogleSearch($this->question))->search()->html();

			return $this->zeke->messages->add($googleSearch);
    }

		$questions = [];
		foreach ($answers as $answer) {
			$answer = isset($answer[0]) ? $answer[0] : $answer;
			$return = ['answer' => $answer['answer']];
			if (!empty($answer['question']['questionInfo'])) {
				$return['questionInfo'] = $answer['question']['questionInfo'];
			}
			$questions[$answer['question']['question']] = $return;
		}
		if ($questions) {
			// if there are many answers, choose the most weighty ones
			$questions = $this->calculateWeight($questions);
			if (1 == count($questions)) {
				$question = $questions->first();
				// if found more weighty, answer them
				$messages[] = $this->zeke->messages->add($question['answer'], 'text', 'them', null, $question['questionInfo']);
			} else {
				// if we have several answers, we suggest to choose
				$messages[] = $this->zeke->messages->add('Help me to know your question more precisely, I chose for you several options, choose one of them', 'system');
				$messages[] = $this->zeke->messages->add($questions->lists('answer', 'string'), 'answers');
			}
			
		}
  }

	/**
	 * split the line into key components
	 */
	protected function keywords($question)
	{
		$exceptions = ['hi'];
		$text = preg_replace('/[^a-zA-Zа-яА-Я\s]/', '', mb_strtolower($question));
		$text = explode(' ', $text);
		foreach($text as $key => $word) {
			if (2 > strlen($word) && !in_array($word, $exceptions)) {
				unset($text[$key]);
			}
		}
		return implode(' ', $text);
	}
}