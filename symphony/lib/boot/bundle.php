<?php

	if(!defined('PHP_VERSION_ID')){
    	$version = PHP_VERSION;
    	define('PHP_VERSION_ID', ($version{0} * 10000 + $version{2} * 100 + $version{4}));
	}

	if (PHP_VERSION_ID >= 50300){
	    error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);
	} 
	else{
	    error_reporting(E_ALL ^ E_NOTICE);
	}
	
	set_magic_quotes_runtime(0);

	header('Expires: Mon, 12 Dec 1982 06:14:00 GMT');
	header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
	header('Cache-Control: no-cache, must-revalidate, max-age=0');
	header('Pragma: no-cache');
		
	require_once(DOCROOT . '/symphony/lib/boot/func.utilities.php');	
	require_once(DOCROOT . '/symphony/lib/boot/defines.php');
	require_once(BOOT . '/class.object.php');
	
	if (!file_exists(CONFIG)) {
		
		if (file_exists(DOCROOT . '/install.php')) {
			header(sprintf('Location: %s/install.php', URL));
			exit();
		}
		
		die('<h2>Error</h2><p>Could not locate Symphony configuration file. Please check <code>manifest/config.php</code> exists.</p>');
	}
	
	include(CONFIG);