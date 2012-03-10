<?php

	/**
	 * Router for the testing HTTP server built into PHP 5.4.
	 * Use with the following command from the root directory
	 * of your Symphony installation:
	 *
	 *	php -S localhost:8080 router.php
	 *
	 * If you need to add your own rules, copy this file and
	 * use it as your own custom router.
	 */

	namespace Rewrite;

	require_once 'symphony/lib/toolkit/class.rewrite.php';

	// Prepare environment:
	initialize();

	// SECURITY - Protect crucial files
	test('%^/manifest/(.*)%')
		| test('%.+\.(sql|xsl)$%')
		| test('%^/\.%')
		&& die('<h1>Access Denied</h1>');

	// Do not apply rules when requesting actual files:
	if (is_file()) return false;

	// Make sure the URL ends in a trailing slash:
	test('%(?=/$)%')
		|| redirect('%^.+%', '$0/');

	// Remove references to index.php:
	test('%/(symphony/)?index.php(/.*/?)%')
		&& redirect('$1$2');

	// Admin area rewrite:
	test('%^/symphony/?$%')
		&& rewrite('/index.php?mode=administration&%{QUERY_STRING}')
		&& finalize();

	test('%/symphony(\/(.*\/?))?$%')
		&& rewrite('/index.php?symphony-page=$1&mode=administration&%{QUERY_STRING}')
		&& finalize();

	// Frontend page rewrite:
	rewrite('%^/(.*\/?)$%', '/index.php?symphony-page=$1&%{QUERY_STRING}')
		&& finalize();

	if (isset($_SERVER['SCRIPT_FILENAME'])) {
		include_once $_SERVER['SCRIPT_FILENAME'];
	}

	else {
		include_once 'index.php';
	}