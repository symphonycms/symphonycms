<?php

	define('DOCROOT', rtrim(dirname(__FILE__), '\\/'));
	define('PATH_INFO', isset($_SERVER['PATH_INFO']) ? $_SERVER['PATH_INFO'] : NULL);
	define('DOMAIN_PATH', dirname(rtrim($_SERVER['PHP_SELF'], PATH_INFO)));
	define('DOMAIN', rtrim(rtrim($_SERVER['HTTP_HOST'], '\\/') . DOMAIN_PATH, '\\/'));

	require(DOCROOT . '/symphony/lib/boot/bundle.php');

	function renderer($mode='frontend'){
		if(!in_array($mode, array('frontend', 'administration'))){
			throw new Exception('Invalid Symphony Renderer mode specified. Must be either "frontend" or "administration".');
		}
		require_once(CORE . "/class.{$mode}.php");
		return ($mode == 'administration' ? Administration::instance() : Frontend::instance());
	}

	$renderer = (isset($_GET['mode']) && strtolower($_GET['mode']) == 'administration'
			? 'administration'
			: 'frontend');

	$output = renderer($renderer)->display(getCurrentPage());

	// #1808
	if(isset($_SERVER['HTTP_MOD_REWRITE'])) {
		$output = file_get_contents(GenericExceptionHandler::getTemplate('fatalerror.rewrite'));
		$output = str_replace('{SYMPHONY_URL}', SYMPHONY_URL, $output);
		$output = str_replace('{URL}', URL, $output);
		echo $output;
		exit;
	}

	cleanup_session_cookies();

	echo $output;

	exit;
