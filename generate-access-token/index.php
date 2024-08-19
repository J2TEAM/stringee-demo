<?php

require_once 'vendor/autoload.php';

use Firebase\JWT\JWT;

define('API_KEY_SID', 'YOUR_API_KEY');
define('API_KEY_SECRET', 'YOUR_SECRET_KEY');

if (!isset($_GET['u']) || empty($_GET['u'])) {
	$jwt = '';
} else {
	$username = $_GET['u'];
	$now = time();
	$header = array('cty' => 'stringee-api;v=1');
	$payload = array(
		'jti' => API_KEY_SID . '-' . $now,
		'iss' => API_KEY_SID,
		'exp' => $now + 3600,
		'userId' => $username
	);

	$jwt = JWT::encode($payload, API_KEY_SECRET, 'HS256', null, $header);
}

header('Access-Control-Allow-Origin: *');
echo json_encode(['access_token' => $jwt]);
