<?php

	define_safe('__IN_SYMPHONY__', true);

	define_safe('MANIFEST', 	DOCROOT . '/manifest');
	define_safe('SYMPHONY', 	DOCROOT . '/symphony');
	define_safe('EXTENSIONS', 	DOCROOT . '/extensions');	
	define_safe('WORKSPACE', 	DOCROOT . '/workspace');
		
	define_safe('LIBRARY',	SYMPHONY . '/lib');
	define_safe('ASSETS', 	SYMPHONY . '/assets');
	
	define_safe('UTILITIES', 		WORKSPACE . '/utilities');
	define_safe('DATASOURCES',		WORKSPACE . '/data-sources');
	define_safe('EVENTS',			WORKSPACE . '/events');
	define_safe('TEXTFORMATTERS',	WORKSPACE . '/text-formatters');
	define_safe('PAGES',			WORKSPACE . '/pages');	
	
	define_safe('CACHE',	MANIFEST . '/cache');
	define_safe('TMP',		MANIFEST . '/tmp');
	define_safe('LOGS',		MANIFEST . '/logs');
	define_safe('CONFIG', 	MANIFEST . '/config.php');

	define_safe('TOOLKIT',	LIBRARY . '/toolkit');	
	define_safe('LANG',		LIBRARY . '/lang');	
	define_safe('CORE',		LIBRARY . '/core');
	define_safe('BOOT',		LIBRARY . '/boot');	
	
	define_safe('CONTENT', 	SYMPHONY . '/content');
	
	define_safe('TEMPLATE', SYMPHONY . '/template');

	define_safe('STARTTIME', precision_timer());

	define_safe('TWO_WEEKS',	(60*60*24*14));
	define_safe('CACHE_LIFETIME', TWO_WEEKS);

	define_safe('HTTPS', getenv('HTTPS'));
	define_safe('HTTP_HOST', getenv('HTTP_HOST'));
	define_safe('REMOTE_ADDR', getenv('REMOTE_ADDR')); 
	define_safe('HTTP_USER_AGENT', getenv('HTTP_USER_AGENT'));

	define_safe('__SECURE__', (HTTPS == 'on'));
	define_safe('URL', 'http' . (defined('__SECURE__') && __SECURE__ ? 's' : '') . '://' . DOMAIN);
	
	define_safe('ACTIVITY_LOG', LOGS . '/main');
