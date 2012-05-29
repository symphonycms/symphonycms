<?php

	header('Expires: Mon, 12 Dec 1982 06:14:00 GMT');
	header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
	header('Cache-Control: no-cache, must-revalidate, max-age=0');
	header('Pragma: no-cache');

	if(!defined('PHP_VERSION_ID')){
		$version = PHP_VERSION;
		define('PHP_VERSION_ID', ($version{0} * 10000 + $version{2} * 100 + $version{4}));
	}

	// Always show errors
	if (PHP_VERSION_ID >= 50300){
		error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED & ~E_WARNING);
	}
	else{
		error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
	}
	ini_set('display_errors', 1);

	// Show PHP Info
	if(isset($_REQUEST['info'])){
		phpinfo();
		exit();
	}

	// Set the current timezone, should that not be available
	// default to GMT.
	if(!date_default_timezone_set(date_default_timezone_get())) {
		date_default_timezone_set('GMT');
	}

	// Defines some constants
	$clean_url = rtrim($_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']), '/\\');
	$clean_url = preg_replace(array('/\/{2,}/i', '/\/install$/i'), array('/', NULL), $clean_url);

	define('DOMAIN', $clean_url);
	define('URL', 'http://' . $clean_url);
	define('INSTALL_URL', URL . '/install');

	$clean_path = rtrim(dirname($_SERVER['SCRIPT_FILENAME']), '/\\');
	$clean_path = preg_replace(array('/\/{2,}/i', '/\/install$/i'), array('/', NULL), $clean_path);

	define('DOCROOT', $clean_path);
	define('INSTALL', DOCROOT . '/install');
	define('INSTALL_LOGS', INSTALL . '/logs');

	define('VERSION', '2.3');

	// Required boot components
	require_once(DOCROOT . '/symphony/lib/boot/func.utilities.php');
	require_once(DOCROOT . '/symphony/lib/boot/defines.php');

	// If prompt to remove, delete the entire `/install` directory
	// and then redirect to Symphony
	if(isset($_GET['action']) && $_GET['action'] == 'remove') {
		require_once(DOCROOT . '/symphony/lib/toolkit/class.general.php');
		General::deleteDirectory(INSTALL);
		redirect(SYMPHONY_URL);
	}

	// If Symphony is already installed, run the updater
	if(file_exists(CONFIG)) {
		// System updater
		require_once(INSTALL . '/lib/class.updater.php');

		$script = Updater::instance();
	}
	// If there's no config file, run the installer
	else{
		// System installer
		require_once(INSTALL . '/lib/class.installer.php');

		$script = Installer::instance();
	}

	$script->run();

	exit;

