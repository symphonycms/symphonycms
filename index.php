<?php

	define('DOCROOT', rtrim(dirname(__FILE__), '\\/'));
	define('DOMAIN', rtrim(rtrim($_SERVER['HTTP_HOST'], '\\/') . dirname($_SERVER['PHP_SELF']), '\\/'));

	require(DOCROOT . '/symphony/lib/boot/bundle.php');
	require_once(CORE . '/class.frontend.php');
	
	$Frontend = Frontend::instance();
	
	$output = $Frontend->display(getCurrentPage());

	header(sprintf('Content-Length: %d', strlen($output)));
	echo $output;

	exit();