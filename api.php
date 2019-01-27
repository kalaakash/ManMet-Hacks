<?php

require_once('include/actions.php');
require_once('include/errors.php');

// Get the API action from the GET parameters, so we can determine
// which action to invoke
$action = array_key_exists('action', $_GET) ? $_GET['action'] : null;
$response = null;

if ($action === null) {
	$response = make_error_response(ErrorCode::NO_ACTION, 'No action was specified in the request.');
} else {
	$response = Actions::invokeAction($action, $_POST);
}

exit($response);

?>