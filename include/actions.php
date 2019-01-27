<?php

require_once('data.php');
require_once('errors.php');
require_once('questions.php');

class Actions {

	private static $actionHandlers = [];

	public static function registerActionHandler($action, $handler) {
		Actions::$actionHandlers[$action] = $handler;
	}

	public static function invokeAction($action, $data) {
		if (is_null($action)
				|| !array_key_exists($action, Actions::$actionHandlers)) {
			return make_error_response(ErrorCode::BAD_ACTION, 'Action does not exist');
		}

		$handler = Actions::$actionHandlers[$action];

		try {
			$responseData = $handler->invoke($data);
			return json_encode($responseData);
		} catch (Exception $ex) {
			return make_error_response(ErrorCode::INTERNAL_ERROR, 'An internal error occurred');
		}
	}

}

interface ActionHandler {

	public function invoke($data);

}

class StartActionHandler implements ActionHandler {

	public function __construct() {
	}

	public function invoke($data) {
		session_start();

		if (!array_key_exists('currentQuestion', $_SESSION)) {
			// We have no key for the current question, so they're starting anew
			$_SESSION['currentQuestion'] = 0;
			$_SESSION['answers'] = [];
		}

		$currentQuestion = QuestionRegistry::getQuestion($_SESSION['currentQuestion']);
		return $currentQuestion->toArray();
	}

}

class SubmitActionHandler implements ActionHandler {

	public function __construct() {}

	public function invoke($data) {
		if (!array_key_exists('answer', $data)) {
			return make_error_response_raw(ErrorCode::INVALID_REQUEST, 'No answer was specified in the request: ' . json_encode($data));
		}

		session_start();

		if (!array_key_exists('currentQuestion', $_SESSION)) {
			// We have no key for the current question, so they're starting anew
			return make_error_response_raw(ErrorCode::INVALID_STATE, 'A session must be started before submitting answers');
		}

		$currentId = $_SESSION['currentQuestion'];

		// Save their answer
		$_SESSION['answers'][$currentId] = $data['answer'];

		// Get & return next question based on answer
		$nextQuestion = QuestionRegistry::getNextQuestion($currentId, $data['answer']);
		$_SESSION['currentQuestion'] = 2; // BIG TODO

		// If we have no next question, this means we've reached a terminal question:
		// determine and return their results!
		if (!$nextQuestion) {
			$gen = new ResultsGenerator($_SESSION['answers']);
			$response = $gen->generate();
			$response['type'] = 'RESULTS';

			// Now we clear their session
			unset($_SESSION['currentQuestion']);
			unset($_SESSION['answers']);

			return $response;
		}

		return $nextQuestion->toArray();
	}

}

// Register action handlers
Actions::registerActionHandler('start', new StartActionHandler(null));
Actions::registerActionHandler('submit', new SubmitActionHandler(null));