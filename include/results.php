<?php

require_once('gcp.php');

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
		if (!array_key_exists(intval($parts[0]), $this->answers))
			return false;

		return $this->answers[intval($parts[0])] === $parts[1];
	}

	private function calcDistance($lat1, $lon1, $lat2, $lon2) {
		$radius = 6371;
		$dLat = deg2rad($lat2 - $lat1);
		$dLon = deg2rad($lon2 - $lon1);
		$a = sin($dLat / 2) * sin($dLat / 2)
			+ cos(deg2rad($lat1)) * cos(deg2rad($lat2))
			* sin($dLon / 2) * sin($dLon / 2);
		$c = 2 * atan2(sqrt($a), sqrt(1 - $a));
		$d = $radius * $c;
		return $d / 1.6;
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

		// Look for places using Google API if we know a location
		if (array_key_exists('pos', $_SESSION)) {
			$ourLat = explode(',', $_SESSION['pos'])[0];
			$ourLon = explode(',', $_SESSION['pos'])[1];
			$api = new PlacesAPI(GCP_API_KEY);

			foreach ($places as $i => $place) {
				$response = $api->findPlaceFromText($place['title'], $ourLat, $ourLon);
				if ($response->status !== 'OK') {
					break;
				}

				if (count($response->candidates) < 1)
					break;

				$candidate = $response->candidates[0];
				$lat = floatval($candidate->geometry->location->lat);
				$lon = floatval($candidate->geometry->location->lng);

				$distance = $this->calcDistance($ourLat, $ourLon, $lat, $lon);


				$place['subtitle'] = $candidate->name . ' (' . number_format($distance, 1) . 'mi)';
				$place['url'] = 'https://www.google.com/maps/search/?api=1&query='
					. $lat
					. ','
					. $lon;
				$places[$i] = $place;
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
			'description' => 'After computing your inputs ....',
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

// Anger results
add_tips('14=0', ['Pain and anger are often temporary emotons, and will pass with time.']);
add_tips('14=0&15=0', [
	'Try calming down by taking deep breaths and meditating.',
	'Step back from the situation and spend a moment taking care of yourself.',
	'Keep safety as a priority and do not injure yourself or anyone around you.'
]);
add_tips('14=0&16=0', [
	'Go to your happy place - mentally or physically.',
	'Take care not to damage relationships with others: personal attacks are easy to give but hard to undo.'
]);
add_tips('15=0', ['Try to resolve any issue by dealing with the situation calmly.']);
add_tips('13=0|12=0', ['Clearly state your feelings by writing or talking about the situation, as a form of release of stress and anger.']);

add_food('12=0|13=0','raspberry','Berries are useful to reduce blood pressure and is very healthy.');
add_food('12=0|13=0','tea','Sipping green tea is very helpful.');
add_food('12=0|13=0','apple','Having a combination of complex carbohydrates and healthy fat is very helpful if you are feeling cranky. For example, apples and peanut butter.');

add_place('12=0|13=0','coffee','Coffee Shop', 'Visit a coffee shop to calm down and have a nice beverage');
add_place('12=0|13=0','small-business','Supermarket', 'Visit a supermarket to get fod and relax');
add_place('12=0|13=0','forest','Park', 'Go to a park to enjoy nature and relax your senses.');

add_video('12=0|13=0','Anger Management video','https://www.youtube.com/watch?v=BsVq5R_F6RA');
add_video('12=0|13=0','Anti-anxiety video','https://youtu.be/UkM-FjfN6Mc');
add_video('12=0|13=0','Funny Memes video','https://www.youtube.com/watch?v=Je3vDBqGgL0');

add_music('12=0|13=0','Good Vibes playlist','https://open.spotify.com/user/spotify/playlist/37i9dQZF1DWYBO1MoTDhZI?si=i1QkE0XNQpKw1Xat1a0knw');
add_music('12=0|13=0','Chill Hits playlist','https://open.spotify.com/user/spotify/playlist/37i9dQZF1DX4WYpdgoIcn6?si=uex074vBS8meDRldvwAJyg');

//Fear results

add_tips(('17=0&19=0'), ['Calm yourself down by deep breathing and meditation.',
	'Try relaxing by taking your mind of the fear, by focusing on hobbies or interests.',
	'Do a meditation or breathing exercises.']);
add_tips('17=0&18=0', ['Go to your happy place and ignore the negativity around yourself.']);
add_tips('17=0&20=0', ['Go to the GP to get yourself check, after booking an appointment.']);
add_tips('17=1', ['Overcome phobias by venturing out and exploring your true self.']);
add_tips('12=1|13=1', ['Eat comforting food to relax yourself.']);

add_food('12=1|13=1','raspberry','Berries are useful to reduce blood pressure and is very healthy.');
add_food('12=1|13=1','chocolate-bar','Dark chocolate helps reduce stress and lowers blood pressure.');
add_food('12=1|13=1',' beef','Proteins are very healthy and relieves stress.');

add_place('12=1|13=1','clinic','General Practitioner', 'Visit a GP to understand your state and get treatment, if needed ');
add_place('12=1|13=1','small-business','Supermarket', 'Visit a supermarket to get fod and relax');
add_place('12=1|13=1','forest','Park', 'Go to a park to enjoy nature and relax your senses.');

add_video('12=1|13=1','Stress-releiving music video','https://www.youtube.com/watch?v=b7IrNtS2rRE');
add_video('12=1|13=1','Guided Meditation video','https://www.youtube.com/watch?v=4EaMJOo1jks');
add_video('12=1|13=1','Funny Memes video','https://www.youtube.com/watch?v=Je3vDBqGgL0');

add_music('12=1|13=1','Relax & Unwind playlist','https://open.spotify.com/user/spotify/playlist/37i9dQZF1DWU0ScTcjJBdj?si=bcmaoy64TS6NCYstqrdPj');
add_music('12=1|13=1','Lounge - Soft House playlist','https://open.spotify.com/user/spotify/playlist/37i9dQZF1DX82pCGH5USnM?si=vfm2FDC6TJegzoD1WfLOJA');

//Sadness results

add_tips('21=0|22=0', ['Calm yourself down by deep breathing and meditation.',
			'Calm yourself and meditate for sometime.',
			'Be around people who want to have a conversation with you and whom you feel happy around.']);
add_tips('21=0|22=0|23=0', ['Go to your happy place and ignore the negativity concerning yourself.']);
add_tips('22=0', ['Go and see a GP as soon as possible.']);
add_tips('22=0|23=0', ['Relax and listen to more upbeat music or watch funny videos.']);
add_tips('12=2|13=2', ['Let go of the bad memories and move on for the best.']);

add_food('12=2|13=2','raspberry','Berries are useful to reduce blood pressure and is very healthy.');
add_food('12=2|13=2','apple','Whole fruits are appatizing and healthy which helps reduce stress and sadness levels.');
add_food('12=2|13=2','tea','Sipping green tea is very helpful.');

add_place('12=2|13=2','small-business','Supermarket', 'Visit a supermarket to get fod and relax');
add_place('12=2|13=2','forest','Park', 'Go to a park to enjoy nature and relax your senses.');
add_place('12=2|13=2','clinic','General Practitioner', 'Visit a GP to understand your state and get treatment, if needed ');

add_video('12=2|13=2','Best Music of 2018','https://www.youtube.com/watch?v=KOgvA98FifU');
add_video('12=2|13=2','Funny Memes video','https://www.youtube.com/watch?v=Je3vDBqGgL0');

add_music('12=2|13=2','Today\'s Top Hits','https://open.spotify.com/user/spotify/playlist/37i9dQZF1DXcBWIGoYBM5M?si=YhrgXCsZSJSpKrguMdV9Hw');
add_music('12=2|13=2','Happy Hits playlist','https://open.spotify.com/user/spotify/playlist/37i9dQZF1DXdPec7aLTmlC?si=phPoTuiRRoaeEQQ4WD4fjg');


//Shame results

add_tips('24=0', ['It is completely normal to feel shame.']);
add_tips('24=0|25=0', ['Calm yourself down by deep breathing and meditation.']);
add_tips('25=0|26=0', ['Ignore the negativity concerning yourself and focus on your previous positive growth.']);
add_tips('26=0', ['No emotion lasts forever, you need to alter the standards, or rules and if none have been violated, then don\'t worry about shame.']);
add_tips('25=0|26=0', ['Avoid the self-blame game.']);

add_food('12=3|13=3','grain-and-cereal',' Do not over-eat due to stress. Eat whole-grain cereal with low-fat milk');
add_food('12=3|13=3','apple','Whole fruits are appatizing and healthy which helps reduce stress and sadness levels.');
add_food('12=3|13=3','natural-food','It is healthy and does not allow you to gain weight that easily.');

add_place('12=3|13=3','small-business','Supermarket', 'Visit a supermarket to get fod and relax');
add_place('12=3|13=3','forest','Park', 'Go to a park to enjoy nature and relax your senses.');
add_place('12=3|13=3','coffee','Coffee Shop', 'Visit a coffee shop to calm down and have a nice beverage');

add_video('12=3|13=3','Funny Memes video','https://www.youtube.com/watch?v=Je3vDBqGgL0');

add_music('12=3|13=3','Guilty Pleasures playlist','https://open.spotify.com/user/spotify/playlist/37i9dQZF1DX4pUKG1kS0Ac?si=yPd_Bc64RTeIqG26kgbMpw');
add_music('12=3|13=3','Confidence Booster playlist','https://open.spotify.com/user/spotify/playlist/37i9dQZF1DX4fpCWaHOned?si=rV_qu3ykQlOy1tTBP8sXeQ');


//Disgust results

add_tips('27=0&29=0', ['Calm yourself down by deep breathing and meditation.',
					'Look into going to a nearby supermarket where you could get clean & fresh food.']);
add_tips('27=0', ['Go to your happy place and ignore the negativity around yourself.',
					'Try soothing yourself by taking your mind of the issue.',
					'Focus on your hobbies and interests which will engage you in another activity.']);
add_tips('28=0&29=0', ['Look into booking an appointment with the nearest GP, who\'s name is given below.']);

add_food('12=4|13=4',' beef','Proteins are very healthy and relieves stress.');
add_food('12=4|13=4','apple','Whole fruits are appatizing and healthy which helps reduce stress and sadness levels.');
add_food('12=4|13=4','natural-food','Green, leafy vegetables are very healthy and keeps you going.');

add_place('12=4|13=4', 'small-business', 'Supermarket', 'Buy health/cleaning products, whole fresh fruit/veg');
add_place('12=4|13=4', 'clinic', 'Doctor\'s', 'If this issue is persistent');

add_video('12=4|13=4','Satisfying video on Odd Objects','https://www.youtube.com/watch?v=M3VfUmO8ehw');
add_video('12=4|13=4','Relaxing Meditation video','https://www.youtube.com/watch?v=1ZYbU82GVz4');
add_video('12=4|13=4','ASMR satisfaction video','https://www.youtube.com/watch?v=r5aD9iqdFFI');

add_music('12=4|13=4','Deep Focus playlist','https://open.spotify.com/user/spotify/playlist/37i9dQZF1DWZeKCadgRdKQ?si=DrtzWR51SZqobTth42n7nw');
add_music('12=4|13=4','Have a Great Day','https://open.spotify.com/user/spotify/playlist/37i9dQZF1DX7KNKjOK0o75?si=PdvFIxF9RIGnOWAyeK-Vog');

//Jealous results

add_tips('30=0|32=0|33=0', ['Calm yourself down by deep breathing and meditation.']);
add_tips('30=0|31=0|33=0', ['Go to your happy place and ignore the negativity concerning yourself.']);
add_tips('30=0|32=0', ['Look at yourself from an outsider\'s perspective and compare yourself with how you want to be.']);
add_tips('30=0|31=0|32=0', ['Gain self-confidence by looking at the positives in your life and how you can build on them.']);
add_tips('31=0|32=0', ['Avoid social media as an information source.']);
add_tips('33=0|32=0', ['Try to effectively communicate with the person you\'re having troubles with or try to solve the situation calmly.']);
add_tips('12=5|13=5', ['Eat healthy and filling food.']);

add_food('12=5|13=5','chocolate-bar','Dark chocolate helps reduce stress and lowers blood pressure.');
add_food('12=5|13=5','apple','Whole fruits are appatizing and healthy which helps reduce stress and sadness levels.');
add_food('12=5|13=5','tea','Sipping green tea is very helpful.');

add_place('12=5|13=5', 'forest', 'Park', 'Sit and relax. Be lost in thought.');
add_place('12=5|13=5', 'coffee', 'Coffee Shop', 'Refresh with a cup of coffee in a calm environment');

add_video('12=5|13=5','Relaxing sounds & music','https://www.youtube.com/watch?v=1ZYbU82GVz4');
add_video('12=5|13=5','Funny Memes video','https://www.youtube.com/watch?v=Je3vDBqGgL0');

add_music('12=5|13=5','Relax & Unwind playlist','https://open.spotify.com/user/spotify/playlist/37i9dQZF1DWU0ScTcjJBdj?si=bcmaoy64TS6NCYstqrdPj');
add_music('12=5|13=5','Lounge - Soft House playlist','https://open.spotify.com/user/spotify/playlist/37i9dQZF1DX82pCGH5USnM?si=vfm2FDC6TJegzoD1WfLOJA');

//Happiness results

add_tips('35=0', ['To keep your mood up, go shopping or do something you enjoy.',
					'Enjoy yourself in whatever you do.']);
add_tips('35=0', ['Go do some work and check if you were more productive than before.']);

add_food('12=6|13=6','chocolate-bar','Dark chocolate helps reduce stress and lowers blood pressure.');
add_food('12=6|13=6','apple','Whole fruits are appatizing and healthy which helps reduce stress and sadness levels.');
add_food('12=6|13=6','natural-food','Green, leafy vegetables are very healthy and keeps you going.');

add_place('12=6|13=6', 'shopping-cart', 'Shopping Centre', 'Wander round, meet people, enjoy the vibe');
add_place('12=6|13=6', 'forest', 'Park', 'Take a walk, go for a run, play some sports');
add_place('12=6|13=6', 'coffee', 'Coffee Shop', 'Enjoy a rejuvenating cup of coffee');

add_video('12=6|13=6','How to be more Productive','https://www.youtube.com/watch?v=V3WrCx3mwNo');
add_video('12=6|13=6','Funny Memes video','https://www.youtube.com/watch?v=Je3vDBqGgL0');

add_music('12=6|13=6','Intense Studying playlist','https://open.spotify.com/user/spotify/playlist/37i9dQZF1DX8NTLI2TtZa6?si=bcRoPLs9RBOJk3ezRv7oaQ');
add_music('12=6|13=6','Songs to Sing in the Shower','https://open.spotify.com/user/spotify/playlist/37i9dQZF1DWSqmBTGDYngZ?si=jowW1GAkRWy1wvtKcEYf0g');

//Love results

add_tips('38=0', ['Go out to social locations and enjoy with people around you and spread joy.']);
add_tips('39=1', ['Sit down and re-focus on yourself and evaluate where you need to improve.']);
add_tips('12=7|13=7', ['Keep looking at life from a positive perspective.']);

add_food('12=7|13=7','chocolate-bar','Dark chocolate helps reduce stress and lowers blood pressure.');
add_food('12=7|13=7','apple','Whole fruits like mangoes are appatizing and healthy which helps reduce stress and sadness levels.');
add_food('12=7|13=7','tea','Sipping green tea is very helpful.');

add_place('12=7|13=7', 'small-business', 'Supermarket', 'Buy something nice for the one you love');
add_place('12=7|13=7', 'forest', 'Park', 'Take a leisurly stroll');
add_place('12=7|13=7', 'coffee', 'Coffee Shop', 'Enjoy a cuppa');

add_video('12=7|13=7','Motivational video','https://youtu.be/T6Wufigr1p4');
add_video('12=7|13=7','Best Love songs','https://youtu.be/woSCgIhRl9s');

add_music('12=7|13=7','Good Vibes playlist','https://open.spotify.com/user/spotify/playlist/37i9dQZF1DWYBO1MoTDhZI?si=a7MNsgWQRYCKMtKukoyVoA');
add_music('12=7|13=7','Forever Mine playlist','https://open.spotify.com/user/spotify/playlist/37i9dQZF1DWWCKk94npRDB?si=pQYczBo5SWWJjHQfirdoow');

// TODO: colors and also subtitle for emotion-specific questions

?>
