<?php

class QuestionType {

	const SELECTION = 'SELECTION';
	const RANGE = 'RANGE';

}

abstract class Question {

	private $type;
	private $question;
	
	public function __construct($type, $question) {
		$this->type = $type;
		$this->question = $question;
	}

	public function getQuestion() {
		return $this->question;
	}

	public function toArray() {
		return [
			'type' => $this->type,
			'question' => $this->question
		];
	}

}

class SelectionQuestion extends Question {

	private $answers;

	public function __construct($question, $answers = ['Yes', 'No']) {
		parent::__construct(QuestionType::SELECTION, $question);
		$this->answers = $answers;
	}

	public function getAnswers() {
		return $this->answers;
	}

	public function toArray() {
		$arr = parent::toArray();
		$arr['answers'] = $this->answers;
		return $arr;
	}

}

class RangeQuestion extends Question {

	private $min;
	private $max;

	public function __construct($question, $min, $max) {
		parent::__construct(QuestionType::RANGE, $question);
		$this->min = $min;
		$this->max = $max;
	}

	public function getMin() {
		return $this->min;
	}

	public function getMax() {
		return $this->max;
	}

	public function toArray() {
		$arr = parent::toArray();
		$arr['min'] = $min;
		$arr['max'] = $max;
		return $arr;
	}

}

class QuestionRegistry {

	private static $questions = [];

	/**
	 * Dict of IDs to array of IDs: each index corresponds to an answer.
	 * OR dict of IDs to a single ID (if next question is independent of answer)
	 */
	private static $nextQuestions = []; 

	public static function addQuestion($id, $questionObj, $nextQuestions) {
		if (array_key_exists($id, QuestionRegistry::$questions))
			throw new Exception('Invalid question ID: already registered');

		QuestionRegistry::$questions[$id] = $questionObj;
		QuestionRegistry::$nextQuestions[$id] = $nextQuestions;
	}

	public static function addSelectionQuestion($id, $question, $answers, $nextQuestions) {
		QuestionRegistry::addQuestion($id, new SelectionQuestion($question, $answers), $nextQuestions);
	}

	public static function addRangeQuestion($id, $question, $min, $max, $nextQuestions) {
		QuestionRegistry::addQuestion($id, new RangeQuestion($question, $min, $max), $nextQuestions);
	}

	public static function getQuestion($id) {
		if (!array_key_exists($id, QuestionRegistry::$questions))
			throw new RuntimeException('Question ID not found in registry');
		
		return QuestionRegistry::$questions[$id];
	}

	public static function getNextQuestion($id, $answer) {
		// If there is no next question, then this means we have reached a terminal question.
		// In this case, we return false to indicate that the callee should deal with this
		// (i.e. work out results etc.)
		if (!array_key_exists($id, QuestionRegistry::$nextQuestions)
				|| is_null(QuestionRegistry::$nextQuestions[$id]))
			return false;

		// Element in next questions array can be ID or array of IDs, corresponding in index
		// to the (numerical) answer
		$nextId = is_array(QuestionRegistry::$nextQuestions[$id])
			? QuestionRegistry::$nextQuestions[$id][$answer]
			: QuestionRegistry::$nextQuestions[$id];

		if (!array_key_exists($id, QuestionRegistry::$questions))
			throw new Exception('Next question found but has invalid ID: not found in registry');

		return QuestionRegistry::$questions[$nextId];
	}

}

QuestionRegistry::addSelectionQuestion(0, 'Pain and anger are temporary, it will all go away. Just give it time.', ['Yes', 'No', 'I still don\'t know what an API is', 'More', 'Options'], 0);

?>