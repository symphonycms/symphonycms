<?php
	define('DOCROOT', rtrim(dirname(__FILE__), '\\/'));
	define('DOMAIN', rtrim(rtrim($_SERVER['HTTP_HOST'], '\\/') . dirname($_SERVER['PHP_SELF']), '\\/'));

	require(DOCROOT . '/symphony/lib/boot/bundle.php');

	function renderer($mode){
		if(!file_exists(CORE . "/class.{$mode}.php")){
			throw new Exception('Invalid Symphony Renderer mode specified.');
		}

		$classname = require_once(CORE . "/class.{$mode}.php");
		return call_user_func("{$classname}::instance");
	}

	$renderer = (isset($_GET['mode']) && strtolower($_GET['mode']) == 'administration'
		? 'administration'
		: 'frontend');

	$output = renderer($renderer)->display(getCurrentPage());

	header(sprintf('Content-Length: %d', strlen($output)));
	echo $output;

	exit();
