<?php

	if(!is_file('../manifest/config.php')) die('<h2>Error</h2><p>Could not locate Symphony configuration file. Please check <code>manifest/config.php</code> exists.</p>');
			
	require_once('../manifest/config.php');	
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

	header(sprintf("Content-Length: %d", strlen($output)));
	echo $output;

	exit();