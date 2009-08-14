<?php

	define('DOCROOT', rtrim(dirname(__FILE__), '\\/'));
	define('DOMAIN', rtrim(rtrim($_SERVER['HTTP_HOST'], '\\/') . dirname($_SERVER['PHP_SELF']), '\\/'));

	require(DOCROOT . '/symphony/lib/boot/bundle.php');
	
	function renderer($mode='frontend'){
		require_once(CORE . "/class.{$mode}.php");
		return ($mode == 'administration' ? Administration::instance() : Frontend::instance());
	}
	
	$renderer = (isset($_GET['mode']) ? strtolower($_GET['mode']) : 'frontend');
	$output = renderer($renderer)->display(getCurrentPage());
	
	header(sprintf('Content-Length: %d', strlen($output)));
	echo $output;

	exit();
