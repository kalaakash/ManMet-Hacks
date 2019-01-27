<?php

require_once('config.php');

class PlacesAPI {

	public function __construct($key) {
		$this->key = $key;
	}

	public function findPlaceFromText($text, $lat, $lon) {
		$key = $this->key;
		$url = "https://maps.googleapis.com/maps/api/place/findplacefromtext/json?"
			. "input=" . urlencode($text)
			. "&inputtype=textquery"
			. "&fields=name,geometry"
			. "&key=" . urlencode($key)
			. "&locationbias=" . urlencode("circle:4000@$lat,$lon");
		
		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		$data = curl_exec($ch);

		if (curl_errno($ch)) {
			echo curl_error($ch);
		}

		curl_close($ch);

		return json_decode($data);
	}

}

?>