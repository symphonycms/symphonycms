<?php
	$settings = array(


		###### ADMIN ######
		'admin' => array(
			'max_upload_size' => '5242880',
		),
		########


		###### SYMPHONY ######
		'symphony' => array(
			'admin-path' => 'symphony',
			'pagination_maximum_rows' => '20',
			'association_maximum_rows' => '5',
			'lang' => 'en',
			'pages_table_nest_children' => 'no',
			'version' => VERSION,
			'cell_truncation_length' => '75',
			'enable_xsrf' => 'yes',
		),
		########


		###### LOG ######
		'log' => array(
			'archive' => '1',
			'maxsize' => '102400',
		),
		########


		###### DATABASE ######
		'database' => array(
			'host' => 'localhost',
			'port' => '3306',
			'user' => null,
			'password' => null,
			'db' => null,
			'tbl_prefix' => 'sym_',
		),
		########


		###### SESSION (NEW) ######
		'session' => array(
			'admin_session_name' => 'symphony_admin',
			'public_session_name' => 'symphony_public',
			'admin_session_expires' => '2 weeks',
			'public_session_expires' => '2 weeks',
			'session_gc_probability' => '1',
			'session_gc_divisor' => '10'
		),
		########


		###### PUBLIC ######
		'public' => array(
			'display_event_xml_in_source' => 'no',
		),
		########


		###### GENERAL ######
		'general' => array(
			'sitename' => 'Symphony CMS',
			'useragent' => 'Symphony/' . VERSION,
		),
		########


		###### FILE ######
		'file' => array(
			'write_mode' => '0644',
		),
		########


		###### DIRECTORY ######
		'directory' => array(
			'write_mode' => '0755',
		),
		########


		###### REGION ######
		'region' => array(
			'time_format' => 'g:i a',
			'date_format' => 'm/d/Y',
			'datetime_separator' => ' ',
			'timezone' => null
		),
		########


		###### CACHE ######
		'cache_driver' => array(
			'default' => 'database',
		),
		########
	);
