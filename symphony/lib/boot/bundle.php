<?php

	/**
	 * @package boot
	 */

	error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED & ~E_STRICT);
	ini_set('magic_quotes_runtime', 0);

	require_once(DOCROOT . '/symphony/lib/boot/func.utilities.php');
	require_once(DOCROOT . '/symphony/lib/core/class.configuration.php');
	require_once(DOCROOT . '/symphony/lib/toolkit/class.general.php');
	$Configuration = new Configuration(true);

	// Create the $settings var from the config file
	if(file_exists(DOCROOT . '/manifest/config.php')) {
		include(DOCROOT . '/manifest/config.php');
		$Configuration->setArray($settings);
	}
	else {
		include(DOCROOT . '/install/includes/config_default.php');
	}

	require_once(DOCROOT . '/symphony/lib/boot/defines.php');

	if (!file_exists(CONFIG)) {
		$bInsideInstaller = (bool)preg_match('%(/|\\\\)install(/|\\\\)index.php$%', $_SERVER['SCRIPT_FILENAME']);

		if (!$bInsideInstaller && file_exists(DOCROOT . '/install/index.php')) {
			header(sprintf('Location: %s/install/', URL));
			exit;
		}

		else if(!$bInsideInstaller) {
			die('<h2>Error</h2><p>Could not locate Symphony configuration file. Please check <code>manifest/config.php</code> exists.</p>');
		}
	}
