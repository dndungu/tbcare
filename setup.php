<?php

namespace html {
	
	date_default_timezone_set('Africa/Nairobi');
	error_reporting(E_ALL);
	ini_set('display_errors', 1);
	ini_set('log_errors', 1);
	$base = str_replace('/html', '', getcwd());
	ini_set('error_log', "$base/logs/messages");
	
	$settings = array(
			'host' => 'localhost',
			'user' => 'tangazo',
			'password' => 'QdsUz6SPLRLRMBHv',
			'schema' => 'tangazo'
	);
	require_once "$base/helpers/HelperException.php";
	require_once "$base/helpers/Sandbox.php";
	require_once "$base/helpers/Storage.php";
	require_once "$base/base/BaseException.php";
	require_once "$base/base/Response.php";
	require_once "$base/base/Controller.php";
	
}