<?php

	define('DOCROOT', rtrim(dirname(dirname(__FILE__)), '\\/'));
	define('DOMAIN', rtrim(rtrim($_SERVER['HTTP_HOST'], '\\/') . dirname(dirname($_SERVER['PHP_SELF'])), '\\/'));

	require(DOCROOT . '/symphony/lib/boot/bundle.php');
	require_once(CORE . '/class.administration.php');

	$Admin = Administration::instance();

	$output = $Admin->display(getCurrentPage());

	## Temporary: Display debuging information
	if($Admin->displayProfilerReport == true){
		ob_start();
		printf("\n<!-- \n Total Render Time: %s \n\n", $Admin->Profiler->retrieveTotalRunningTime());
		print_r($Admin->Database->getStatistics());
		print_r($Admin->Profiler);
		print "\n -->";
		$output .= ob_get_contents();
		ob_end_clean();
	}

	header(sprintf('Content-Length: %d', strlen($output)));
	echo $output;

	exit();
	