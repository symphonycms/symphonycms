<?php

	// Include all the needed resources
	include_once('boot.php');

	// Define some constants
	define('SCRIPT_FILENAME', INSTALL_URL . '/install.php');
	define('VERSION', '2.3dev');

	// System installer
	require_once(INSTALL . '/lib/class.installer.php');

	Installer::run();

	exit;

