<?php

require_once 'vendor/autoload.php';

use Firebase\JWT\JWT;

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

define('API_KEY_SID', $_ENV['API_KEY_SID']);
define('API_KEY_SECRET', $_ENV['API_KEY_SECRET']);

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
