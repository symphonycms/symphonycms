<?php

	/**
	 * @package boot
	 */

	if(!defined('PHP_VERSION_ID')){
		$version = PHP_VERSION;

		/**
		 * For versions of PHP below 5.2.7, the PHP_VERSION_ID constant, doesn't
		 * exist, so this will just mimic the functionality as described on the
		 * PHP documentation
		 *
		 * @link http://php.net/manual/en/function.phpversion.php
		 * @var integer
		 */
		define('PHP_VERSION_ID', ($version{0} * 10000 + $version{2} * 100 + $version{4}));
	}

	if (PHP_VERSION_ID >= 50300){
		error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED & ~E_STRICT);
	}
	else{
		error_reporting(E_ALL & ~E_NOTICE);
	}

	ini_set('magic_quotes_runtime', 0);

	require_once(DOCROOT . '/symphony/lib/boot/func.utilities.php');
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
