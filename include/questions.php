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

QuestionRegistry::addRangeQuestion(0, 'On a scale of 0 to 10, how emotionally stable do you think you are? (0 being least stable and 10 being extremely stable)', 0, 10,[2,2,2,2,2,2,2,2,2,2,2]);
QuestionRegistry::addSelectionQuestion(1, 'Have you been bothered by moving or speaking so slowly that other people could have noticed,<br/>or the opposite - being so fidgety or restless that you have been moving around a lot more than usual?', ['Yes', 'No'], [3,3]);
QuestionRegistry::addSelectionQuestion(2, 'How are you feeling today?', ['Not too good','Alright','Good','Very Good','Amazing'], [1,1,3,3,3]);
QuestionRegistry::addSelectionQuestion(3, 'How many liters of water have you had today?', ['<1', '1-2', '2-3', '3-4', '>4'], [4,4,5,5,5]);
QuestionRegistry::addSelectionQuestion(4, 'Do you think you should have more water to avoid dehydration?', ['Yes', 'No'], [5,5]);
QuestionRegistry::addSelectionQuestion(5, 'How many hours of sleep did you get in the past 24 hours?', ['0-3', '4-6', '7-9', '10-12', '12+'],[6,6,8,7,7]);
QuestionRegistry::addSelectionQuestion(6, 'Do you think that having more sleep would be healthier, as sleep-deprivation has increased chances of obiesity and high heart rate?', ['Yes', 'No'], [8,8]);
QuestionRegistry::addSelectionQuestion(7, 'Do you think that having less sleep would be healthier, as excess sleep could put on weight?', ['Yes', 'No'], [8,8]);
QuestionRegistry::addSelectionQuestion(8, 'What do you see in the following image?<br/><img src="images/duck_or_rabbit.jpg"/>', ['Duck', 'Rabbit'], [9,10]);
QuestionRegistry::addSelectionQuestion(9, ' As you chose the duck, it describes a person who has several emotional impulses, has rapid mood swings & makes abrupt decisions. <br/>Do you spend time focusing on yourself and following your passions and hobbies?', ['Yes', 'No'], [11,11]);
QuestionRegistry::addSelectionQuestion(10, 'As you chose rabbit, it describes a person who considers the possibility of each outcome, mostly logical, however not cold or insensitive.<br/>Do you spend time focusing on yourself and following your passions and hobbies?', ['Yes', 'No'], [11,11]);
QuestionRegistry::addSelectionQuestion(11, 'Have you been bothered by worrying about any of the following?<br/>Your health, weight, little or no desire for pleasure or sex, difficulties with partner,<br/>stress at school, work or outside home, financial troubles, no one to turn to, something bad has happened recently.', ['Yes', 'No'], [12,13]);
QuestionRegistry::addSelectionQuestion(12, 'Don\'t worry. It\'s just a matter of time and being patient. Everything will be okay eventually. <br/>How would you best describe your mood today ?', ['Angry', 'Fearful','Sad','Ashamed','Disgusted','Jealous','Happy','Loving'], [14,17,21,24,27,30,34,37]);
QuestionRegistry::addSelectionQuestion(13, 'How would you best describe your mood today ?', ['Angry', 'Fearful','Sad','Ashamed','Disgusted','Jealous','Happy','Loving'],  [14,17,21,24,27,30,34,37]);

//Anger

QuestionRegistry::addSelectionQuestion(14, 'After calmly reflecting upon yourself, has something recently gone wrong which is causing you pain or anger?', ['Yes', 'No'], [15,15]);
QuestionRegistry::addSelectionQuestion(15, 'After calmly reflecting upon yourself, have you been speaking loudly, shouting or abusing and losing concentration?', ['Yes', 'No'], [16,16]);
QuestionRegistry::addSelectionQuestion(16, 'After calmly reflecting upon yourself, has something recently gone wrong which is causing you pain or anger?', ['Yes', 'No'], null);

//Fear

QuestionRegistry::addSelectionQuestion(17, 'Have you recently come across something that caused you to be insecure or afraid?', ['Yes', 'No'], [18,18]);
QuestionRegistry::addSelectionQuestion(18, 'Is there a sense of agitation or anxiety because of an immediate imminent danger?', ['Yes', 'No'], [19,19]);
QuestionRegistry::addSelectionQuestion(19, 'Do you feel like you can\'t sleep and have restless nights and feel like you\'re losing control?', ['Yes', 'No'], [20,20]);
QuestionRegistry::addSelectionQuestion(20, 'After calmly reflecting upon yourself, has something recently gone wrong which is causing you pain or anger?', ['Yes', 'No'], null);

//Sadness

QuestionRegistry::addSelectionQuestion(21, 'After calmly reflecting upon yourself, has something recently gone wrong which is causing you pain or anger?', ['Yes', 'No'], [15,15]);
QuestionRegistry::addSelectionQuestion(22, 'After calmly reflecting upon yourself, has something recently gone wrong which is causing you pain or anger?', ['Yes', 'No'], [15,15]);
QuestionRegistry::addSelectionQuestion(23, 'After calmly reflecting upon yourself, has something recently gone wrong which is causing you pain or anger?', ['Yes', 'No'], null);

//Shame

QuestionRegistry::addSelectionQuestion(24, 'After calmly reflecting upon yourself, has something recently gone wrong which is causing you pain or anger?', ['Yes', 'No'], [15,15]);
QuestionRegistry::addSelectionQuestion(25, 'After calmly reflecting upon yourself, has something recently gone wrong which is causing you pain or anger?', ['Yes', 'No'], [15,15]);
QuestionRegistry::addSelectionQuestion(26, 'After calmly reflecting upon yourself, has something recently gone wrong which is causing you pain or anger?', ['Yes', 'No'], [15,15]);

//Disgust

QuestionRegistry::addSelectionQuestion(27, 'After calmly reflecting upon yourself, has something recently gone wrong which is causing you pain or anger?', ['Yes', 'No'], [15,15]);
QuestionRegistry::addSelectionQuestion(28, 'After calmly reflecting upon yourself, has something recently gone wrong which is causing you pain or anger?', ['Yes', 'No'], [15,15]);
QuestionRegistry::addSelectionQuestion(29, 'After calmly reflecting upon yourself, has something recently gone wrong which is causing you pain or anger?', ['Yes', 'No'], [15,15]);

//Jealous

QuestionRegistry::addSelectionQuestion(30, 'After calmly reflecting upon yourself, has something recently gone wrong which is causing you pain or anger?', ['Yes', 'No'], [15,15]);
QuestionRegistry::addSelectionQuestion(31, 'After calmly reflecting upon yourself, has something recently gone wrong which is causing you pain or anger?', ['Yes', 'No'], [15,15]);
QuestionRegistry::addSelectionQuestion(32, 'After calmly reflecting upon yourself, has something recently gone wrong which is causing you pain or anger?', ['Yes', 'No'], [15,15]);
QuestionRegistry::addSelectionQuestion(33, 'After calmly reflecting upon yourself, has something recently gone wrong which is causing you pain or anger?', ['Yes', 'No'], [15,15]);

//Happiness

QuestionRegistry::addSelectionQuestion(34, 'After calmly reflecting upon yourself, has something recently gone wrong which is causing you pain or anger?', ['Yes', 'No'], [15,15]);
QuestionRegistry::addSelectionQuestion(35, 'After calmly reflecting upon yourself, has something recently gone wrong which is causing you pain or anger?', ['Yes', 'No'], [15,15]);
QuestionRegistry::addSelectionQuestion(36, 'After calmly reflecting upon yourself, has something recently gone wrong which is causing you pain or anger?', ['Yes', 'No'], [15,15]);

//Love

QuestionRegistry::addSelectionQuestion(37, 'After calmly reflecting upon yourself, has something recently gone wrong which is causing you pain or anger?', ['Yes', 'No'], [15,15]);
QuestionRegistry::addSelectionQuestion(38, 'After calmly reflecting upon yourself, has something recently gone wrong which is causing you pain or anger?', ['Yes', 'No'], [15,15]);
QuestionRegistry::addSelectionQuestion(39, 'After calmly reflecting upon yourself, has something recently gone wrong which is causing you pain or anger?', ['Yes', 'No'], [15,15]);

?>
