<?php

	define('DOCROOT', rtrim(dirname(__FILE__), '\\/'));
	define('PATH_INFO', isset($_SERVER['PATH_INFO']) ? $_SERVER['PATH_INFO'] : NULL);
	define('DOMAIN_PATH', dirname(rtrim($_SERVER['PHP_SELF'], PATH_INFO)));
	define('DOMAIN', rtrim(rtrim((function_exists('idn_to_utf8') ? idn_to_utf8($_SERVER['HTTP_HOST']) : $_SERVER['HTTP_HOST']), '\\/') . DOMAIN_PATH, '\\/'));

	require(DOCROOT . '/symphony/lib/boot/bundle.php');

	function renderer($mode='frontend'){
		if(!in_array($mode, array('frontend', 'administration'))){
			throw new Exception('Invalid Symphony Renderer mode specified. Must be either "frontend" or "administration".');
		}
		require_once(CORE . "/class.{$mode}.php");
		return ($mode == 'administration' ? Administration::instance() : Frontend::instance());
	}

	// #702 $Configuration is created in bundle.php
	$adminPath = $Configuration->get('admin-path', 'symphony');
	$adminPath = (is_null($adminPath)) ? 'symphony' :  $adminPath;
	if(strpos($_GET['symphony-page'], $adminPath, 0) === 0) {
		$_GET['symphony-page'] = str_replace($adminPath . '/', '', $_GET['symphony-page']);
		if($_GET['symphony-page'] == '') unset($_GET['symphony-page']);
		$_GET['mode'] = $_REQUEST['mode'] = 'administration';
	}

	$renderer = (isset($_GET['mode']) && strtolower($_GET['mode']) == 'administration'
			? 'administration'
			: 'frontend');

	$output = renderer($renderer)->display(getCurrentPage());

	// #1808
	if(isset($_SERVER['HTTP_MOD_REWRITE'])) {
		$output = file_get_contents(GenericExceptionHandler::getTemplate('fatalerror.rewrite'));
		$output = str_replace('{APPLICATION_URL}', APPLICATION_URL, $output);
		$output = str_replace('{SYMPHONY_URL}', SYMPHONY_URL, $output);
		$output = str_replace('{URL}', URL, $output);
		echo $output;
		exit;
	}

	cleanup_session_cookies();

	echo $output;

	exit;
