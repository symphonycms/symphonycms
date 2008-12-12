<?php

	if(!is_file('../manifest/config.php')) die('<h2>Error</h2><p>Could not locate Symphony configuration file. Please check <code>manifest/config.php</code> exists.</p>');
			
	require_once('../manifest/config.php');	
	require_once(CORE . '/class.administration.php');
	
	$Admin = Administration::instance();

	print $Admin->display(getCurrentPage());
	
	## Temporary: Display debuging information
	if($Admin->displayProfilerReport == true){
		print '<!-- ' . Administration::CRLF;
		print 'Total Render Time: ' . $Admin->Profiler->retrieveTotalRunningTime() . Administration::CRLF . Administration::CRLF;
		print_r($Admin->Database->getStatistics());
		print_r($Admin->Profiler);
		print CRLF . ' -->';
	}
	
	exit();