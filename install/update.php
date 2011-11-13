<?php

	// Include all the needed resources
	include_once('boot.php');

	// Define some constants
	define('SCRIPT_FILENAME', INSTALL_URL . '/update.php');
	define('VERSION', '2.3dev');

	// System updater
	require_once(INSTALL . '/lib/class.updater.php');

	$updater = Updater::instance();
	$updater->run();

	exit;
