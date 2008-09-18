<?php

	header('Expires: Mon, 12 Dec 1982 06:14:00 GMT');
	header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
	header('Cache-Control: no-cache, must-revalidate, max-age=0');
	header('Pragma: no-cache');

	## Check if Symphony needs installing
	if(!file_exists('manifest/config.php')){
		
		if(file_exists('install.php')){
			header('Location: install.php');
			exit();
		}
		
		die('<h2>Error</h2><p>Could not locate Symphony configuration file. Please check <code>manifest/config.php</code> exists.</p>');
	}

	define('kBUILD', '2000');

	$build = kBUILD;
	define('kSUPPORT_SERVER', 'http://status.symphony21.com');
	define('kINSTALL_ASSET_LOCATION', kSUPPORT_SERVER . '/install/assets/4.0');	
	
	define('kINSTALL_FILENAME', basename(__FILE__));
	
	## Show PHP Info
	if(isset($_REQUEST['info'])){
		phpinfo(); 
		exit();
	}
	
	if(isset($_POST['action']['update'])):
		
		include('manifest/config.php');
		
		$error = NULL;
		
		$driver_filename = TOOLKIT . '/class.' . $settings['database']['driver'] . '.php';
		$driver = $settings['database']['driver'];
		
		if(!is_file($driver_filename)){
			trigger_error("Could not find database driver '<code>$driver</code>'", E_USER_ERROR);
			return false;
		}
		
		require_once($driver_filename);
		
		$Database = new $driver;
		
		$details = $settings['database'];
		
		if(!$Database->connect($details['host'], $details['user'], $details['password'], $details['port'])) return false;				
		if(!$Database->select($details['db'])) return false;
		if(!$Database->isConnected()) return false;
		
		$Database->setPrefix($details['tbl_prefix']);

		if($settings['database']['runtime_character_set_alter'] == '1'){
			$Database->setCharacterEncoding($settings['database']['character_encoding']);
			$Database->setCharacterSet($settings['database']['character_set']);
		}

		if($settings['database']['force_query_caching'] == 'off') $Database->disableCaching();
		elseif($settings['database']['force_query_caching'] == 'on') $Database->enableCaching();
	
		
		### Do update things here
		
		//ALTER TABLE `tbl_fields_select` CHANGE `static_options` `static_options` TEXT NULL DEFAULT NULL;
		$Database->query("ALTER TABLE `tbl_fields_select` CHANGE `static_options` `static_options` TEXT NULL DEFAULT NULL;");
	
		/*
		
		** Tag list and select box
		ALTER TABLE `tbl_entries_data_100` 
		CHANGE `handle` `handle` VARCHAR(255) NULL DEFAULT NULL,
		CHANGE `value` `value` VARCHAR(255) NULL DEFAULT NULL

		*/

		$fields = $Database->fetchCol('id', "SELECT `id` FROM `tbl_fields` WHERE `type` = 'taglist' OR `type` = 'select' ");
	
		if(is_array($fields) && !empty($fields)){
			foreach($fields as $field_id){
			
				$table = 'tbl_entries_data_' . $field_id;

				$Database->query("
					
					ALTER TABLE `$table`
					CHANGE `handle` `handle` VARCHAR(255) NULL DEFAULT NULL,
					CHANGE `value` `value` VARCHAR(255) NULL DEFAULT NULL
					
				");
			}
		}

		/** Upload Fields **
		ALTER TABLE `tbl_entries_data_43` ADD `size` INT(11) UNSIGNED NOT NULL,
		ADD `mimetype` VARCHAR(50) NOT NULL,
		ADD `meta` VARCHAR(255) NULL;

		ALTER TABLE `tbl_entries_data_43` ADD INDEX (`mimetype`);
		***/
		
		$fields = $Database->fetchCol('id', "SELECT `id` FROM `tbl_fields` WHERE `type` = 'upload' ");
		
		if(is_array($fields) && !empty($fields)){
			foreach($fields as $field_id){
				
				$table = 'tbl_entries_data_' . $field_id;
				
				$Database->query("
					
					ALTER TABLE `$table` 
					ADD `size` INT(11) UNSIGNED NOT NULL,
					ADD `mimetype` VARCHAR(50) NOT NULL,
					ADD `meta` VARCHAR(255) NULL;
					
				");
				
				$Database->query("ALTER TABLE `$table` ADD INDEX (`mimetype`);");				
				
			}
		}
				
		#####
	
		$code = '<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
<head>
	<title>Update Symphony</title>
	<link rel="stylesheet" type="text/css" href="'.kINSTALL_ASSET_LOCATION.'/main.css"/>
	<script type="text/javascript" src="'.kINSTALL_ASSET_LOCATION.'/main.js"></script>
</head>
	<body>
		<h1>Update Symphony <em>Version 2.0 revision 4</em></h1>
		<h2>Update Successful</h2>

		<p>Symphony has been successfully updated to Revision 5. It is a good idea to delete <code>update.php</code>.</p>

	</body>
</html>';
	
	else:
	
		$code = '<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
<head>
	<title>Update Symphony</title>
	<link rel="stylesheet" type="text/css" href="'.kINSTALL_ASSET_LOCATION.'/main.css"/>
	<script type="text/javascript" src="'.kINSTALL_ASSET_LOCATION.'/main.js"></script>
</head>
	<body>
		<h1>Update Symphony <em>Version 2.0 revision 4</em></h1>
		<h2>Outstanding Requirements</h2>
		
		<p>Before you use Symphony 2 Beta revision 5, you must run this script to update from <strong>revision 4</strong> to <strong>revision 5</strong>.</p>
		
		<form action="" method="POST">
			<div class="submit">
				<input type="submit" name="action[update]" value="Update" />
			</div>
		</form>	
	</body>
</html>';

	endif;
	
	print $code;
	
	exit();