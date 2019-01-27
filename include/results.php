<?php

class ResultsGenerator {

	private static $mappings = [];

	private $answers;

	public function __construct($answers) {
		$this->answers = $answers;
	}

	private function isConditionMet($cond) {
		if (is_null($cond) || empty($cond))
			return true;

		$disjunctions = explode('|', $cond);
		foreach ($disjunctions as $disjunction) {
			$conjunctions = explode('&', $disjunction);
			$met = true;

			foreach ($conjunctions as $conjunction) {
				if (!$this->checkEquality($conjunction)) {
					$met = false;
					break;
				}
			}

			if ($met)
				return true;
		}

		return false;
	}

	private function checkEquality($equalityStr) {
		$parts = explode('=', $equalityStr);
		return $this->answers[intval($parts[0])] === $parts[1];
	}

	public function generate() {
		$tips = [];
		$food = [];
		$places = [];
		$music = [];
		$videos = [];

		foreach (ResultsGenerator::$mappings as $mapping) {
			$cond = $mapping['condition'];
			
			if ($this->isConditionMet($cond)) {
				if ($mapping['type'] === 'TIPS')
					$tips = array_merge($tips, $mapping['data']);
				elseif ($mapping['type'] === 'FOOD')
					$food = array_merge($food, $mapping['data']);
				elseif ($mapping['type'] === 'PLACES')
					$places = array_merge($places, $mapping['data']);
				elseif ($mapping['type'] === 'MUSIC')
					$music = array_merge($music, $mapping['data']);
				elseif ($mapping['type'] === 'VIDEOS')
					$videos = array_merge($videos, $mapping['data']);
			}
		}

		$components = [];
		if (count($tips) > 0)
			$components[] = [
				'type' => 'TIPS',
				'tips' => $tips
			];

		if (count($food) > 0)
			$components[] = [
				'type' => 'FOOD',
				'foods' => $food
			];

		if (count($places) > 0)
			$components[] = [
				'type' => 'PLACES',
				'places' => $places
			];

		if (count($music) > 0)
			$components[] = [
				'type' => 'MUSIC',
				'music' => $music
			];

		if (count($videos) > 0)
			$components[] = [
				'type' => 'VIDEOS',
				'videos' => $videos
			];

		return [
			'title' => 'Here are your results',
			'description' => 'TODO write this message',
			'components' => $components
		];		
	}

	public static function addMapping($condStr, $type, $data) {
		ResultsGenerator::$mappings[] = [
			'condition' => $condStr,
			'type' => $type,
			'data' => $data
		];
	}

}

function add_tips($condStr, $tips) {
	if (!is_array($tips))
		$tips = [$tips];

	ResultsGenerator::addMapping($condStr, 'TIPS', $tips);
}

function add_food($condStr, $icon, $text) {
	ResultsGenerator::addMapping($condStr, 'FOOD', [[
		'icon' => $icon,
		'text' => $text
	]]);
}

function add_place($condStr, $icon, $title, $description) {
	ResultsGenerator::addMapping($condStr, 'PLACES', [[
		'icon' => $icon,
		'title' => $title,
		'description' => $description
	]]);
}

function add_music($condStr, $title, $url) {
	ResultsGenerator::addMapping($condStr, 'MUSIC', [[
		'title' => $title,
		'url' => $url
	]]);
}

function add_video($condStr, $title, $url) {
	ResultsGenerator::addMapping($condStr, 'VIDEOS', [[
		'icon' => 'play-button',
		'title' => $title,
		'url' => $url
	]]);
}

add_tips('2=1', ['Here is a tip']);
add_food('2=1', 'burger', 'mmmm burgers');
add_place('2=1', 'house', 'my house', 'hey i live here');
add_music('2=1', 'mmm tunes', 'http://tunes.biz');
add_video('2=0&0=0', 'rick', 'youtu.be/dQw');
// TODO: colors and also subtitle for emotion-specific questions

?>