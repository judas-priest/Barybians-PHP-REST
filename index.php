<?php
#exit($_SERVER['REQUEST_URI']);
//ini_set('display_errors', 1);
//ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$test = explode('/', trim($_SERVER['REQUEST_URI'], '/'));
if (!empty($test[0]) && $test[0] !== 'v1' && mb_substr($test[0], 0, 1) === 'v' && mb_substr($test[0], 1, 2) >= 2) {
	$version = strip_tags($test[0]);
	$apiGetVersion = $version;
} else {
	$version = 'v1';
	$apiGetVersion = $test[0] ?? 'v1';
}


$_GET['r'] = str_replace("$apiGetVersion/", '', $_GET['r']);
$_REQUEST['r'] = str_replace("$apiGetVersion/", '', $_REQUEST['r']);
$_SERVER['REQUEST_URI'] = str_replace("$apiGetVersion/", '', $_SERVER['REQUEST_URI']);

if ($version === 'v1' && !$_GET['r']) exit(require_once 'info.php');


$matches = [];
$match   = preg_match('/\w+/', $_GET['r'], $matches);
$target  = ucfirst($matches[0]);

if (mb_substr($test[0], 1, 2) > 2) {
	require_once "$version/Api.php";

	if (file_exists("$apiGetVersion/Controllers/{$target}Controller.php")) {
		require_once("$apiGetVersion/Controllers/{$target}Controller.php");
		$api = new $target();
		echo $api->run();
	} else {
		exit('error');
	}
} else {
	require_once "$version/api.php";
	if ($match && class_exists($target)) {
		try {
			$api = new $target();
			echo $api->run();
		} catch (Exception $e) {
			header('Content-Type: application/json');
			http_response_code($e->getCode());
			echo json_encode(['message' => $e->getMessage(), 'error' => $e->getCode()]);
		}
	} else {
		header('Content-Type: application/json');
		http_response_code(404);
		echo json_encode(['message' => 'API Not Found', 'error' => '404']);
	}
}
