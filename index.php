<?php

	## Check if Symphony needs installing
	if(!file_exists('manifest/config.php')){
		
		if(file_exists('install.php')){
			header('Location: install.php');
			exit();
		}
		
		die('<h2>Error</h2><p>Could not locate Symphony configuration file. Please check <code>manifest/config.php</code> exists.</p>');
	}
		
	require_once('manifest/config.php');
	require_once(CORE . '/class.frontend.php');
	
	$Frontend = new Frontend();
	
	print $Frontend->display(getCurrentPage());

	exit();