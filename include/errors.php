<?php

class ErrorCode {
	const NO_ACTION = 'NO_ACTION';
	const BAD_ACTION = 'BAD_ACTION';
	const INTERNAL_ERROR = 'INTERNAL_ERROR';
	const INVALID_STATE = 'INVALID_STATE';
	const INVALID_REQUEST = 'INVALID_REQUEST';
}

function make_error_response_raw($code, $message) {
	return [
		'error' => $code,
		'message' => $message
	];
}

function make_error_response($code, $message) {
	return json_encode(make_error_response_raw($code, $message));
}

?>