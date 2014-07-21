<?php

	// Find out where we are:
	define('DOCROOT', rtrim(dirname(__FILE__), '\\/'));

	// Include the boot script:
	include DOCROOT . '/symphony/lib/boot/bundle.php';

	// Begin Symphony proper:
	symphony(
		isset($_GET['mode'])
			? $_GET['mode']
			: null
	);
