<?php

namespace html {
	session_start();
	$start = microtime(true);
	ob_start();
	require_once("../setup.php");
	$storage = new \helpers\Storage($settings);
	$sandbox = new \helpers\Sandbox($storage);
	$sandbox->setGlobalStorage($storage);
	$controller = new \base\Controller($sandbox);
	$latency = (microtime(true) - $start)*1000;
	$controller->log($latency);
	ob_flush();
}

?>