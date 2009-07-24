<?php
	
	error_reporting(E_ALL ^ E_NOTICE);
	
	function __errorHandler($errno=NULL, $errstr, $errfile=NULL, $errline=NULL, $errcontext=NULL){
		return;
	}

    function writeConfig($dest, $conf, $mode){

        $string  = "<?php\n";

		$string .= "\n\t\$settings = array(";
		foreach($conf as $group => $data){
			$string .= "\r\n\r\n\r\n\t\t###### ".strtoupper($group)." ######";
			$string .= "\r\n\t\t'$group' => array(";
			foreach($data as $key => $value){
				$string .= "\r\n\t\t\t'$key' => ".(strlen($value) > 0 ? "'".addslashes($value)."'" : 'NULL').",";
			}
			$string .= "\r\n\t\t),";
			$string .= "\r\n\t\t########";
		}
		$string .= "\r\n\t);\n\n";

        return General::writeFile($dest . '/config.php', $string, $mode);

    }

	function loadOldStyleConfig(){
		$config = preg_replace(array('/^<\?php/i', '/\?>$/i', '/if\(\!defined\([^\r\n]+/i', '/require_once[^\r\n]+/i'), NULL, file_get_contents('manifest/config.php'));

		if(@eval($config) === false){
			throw new Exception('Failed to load existing config');
		}
		
		return $settings;
	}

	define('DOCROOT', rtrim(dirname(__FILE__), '/'));
	define('DOMAIN', rtrim(rtrim($_SERVER['HTTP_HOST'], '/') . dirname($_SERVER['PHP_SELF']), '/'));
	
	require_once('symphony/lib/boot/func.utilities.php');
	require_once('symphony/lib/boot/defines.php');
	require_once(TOOLKIT . '/class.general.php');
	
	if (isset($_GET['action']) && $_GET['action'] == 'remove') {
		unlink(DOCROOT . '/update.php');
		redirect(URL . '/symphony/');
	}
	
	set_error_handler('__errorHandler');

	define('kVERSION', '2.0.4');
	define('kCHANGELOG', 'http://symphony-cms.com/blog/entry/204-release/');
	define('kINSTALL_ASSET_LOCATION', './symphony/assets/installer');	
	define('kINSTALL_FILENAME', basename(__FILE__));

	header('Expires: Mon, 12 Dec 1982 06:14:00 GMT');
	header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
	header('Cache-Control: no-cache, must-revalidate, max-age=0');
	header('Pragma: no-cache');
	
	$shell = '<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
<head>
<title>Update Existing Installation</title>
<link rel="stylesheet" type="text/css" href="'.kINSTALL_ASSET_LOCATION.'/main.css"/>
</head>
<body>
	<form action="" method="post">
%s
	</form>
</body>
</html>';
	
	$settings = loadOldStyleConfig();
	
	if(isset($_POST['action']['update'])){
		
		$existing_version = $settings['symphony']['version'];
		
		$settings['symphony']['version'] = kVERSION;
		$settings['general']['useragent'] = 'Symphony/' . kVERSION;
		
		## Build is no longer used
		unset($settings['symphony']['build']);
		
		if(writeConfig(DOCROOT . '/manifest', $settings, $settings['file']['write_mode']) === true){
			
			// build a Frontend page instance to initialise database
			require(DOCROOT . '/symphony/lib/boot/bundle.php');
			require_once(DOCROOT . '/manifest/config.php');
			require_once(CORE . '/class.frontend.php');
			$frontend = Frontend::instance();
			
			if (version_compare($existing_version, '2.0.3', '<=')) {
				// Add Navigation Groups
				$frontend->Database->query("ALTER TABLE `tbl_sections` ADD `navigation_group` VARCHAR( 50 ) NOT NULL DEFAULT 'Content'");
				$frontend->Database->query("ALTER TABLE `tbl_sections` ADD INDEX (`navigation_group`)");

				// Added support for upload field to handle empty mimetypes.
				$upload_fields = $frontend->Database->fetch("SELECT id FROM tbl_fields WHERE `type` = 'upload'");
				foreach ($upload_fields as $upload_field) {
					$frontend->Database->query("ALTER TABLE `tbl_entries_data_{$upload_field['id']}` CHANGE `mimetype` `mimetype` VARCHAR( 50 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL");
				}
			}
			
			if (version_compare($existing_version, '2.0.4', '<=')) {
				$date_fields = $frontend->Database->fetch("SELECT id FROM tbl_fields WHERE `type` = 'date'");
				
				foreach ($date_fields as $field) {
					$frontend->Database->query("ALTER TABLE `tbl_entries_data_{$field['id']}` CHANGE `local` `local` INT(11) DEFAULT NULL;");
					$frontend->Database->query("ALTER TABLE `tbl_entries_data_{$field['id']}` CHANGE `gmt` `gmt` INT(11) DEFAULT NULL;");
				}
				
				// Update author field table to support the default value checkbox
				$frontend->Database->query("ALTER TABLE `tbl_fields_author` ADD `default_to_current_user` ENUM('yes', 'no') NOT NULL");
				
				## Change .htaccess from `page` to `symphony-page`
				$htaccess = @file_get_contents(DOCROOT . '/.htaccess');

				if($htaccess !== false){
					$htaccess = str_replace('index.php?page=$1&%{QUERY_STRING}', 'index.php?symphony-page=$1&%{QUERY_STRING}', $htaccess);
					@file_put_contents(DOCROOT . '/.htaccess', $htaccess);
				}				
				
			}
			

			
			$code = sprintf($shell, 
'				<h1>Update Symphony <em>Version '.kVERSION.'</em><em><a href="'.kCHANGELOG.'">change log</a></em></h1>
				<h2>Update Complete</h2>
				
				<p><strong>Post Installation Step: </strong>Since 2.0.2, the built-in image manipulation features have been replaced with the <a href="http://github.com/pointybeard/jit_image_manipulation/tree/master">JIT Image Manipulation</a> extension. Should you have uploaded (or cloned) this to your Extensions folder, be sure to <a href="'.URL.'/symphony/system/extensions/">enable it.</a></p>
				<br />
				<p>This script, <code>update.php</code>, should be removed as a safety precaution. <a href="'.URL.'/update.php?action=remove">Click here</a> to remove this file and proceed to your administration area.</p>');

		}
		
		else{
			
			$code = sprintf($shell, 
'				<h1>Update Symphony <em>Version '.kVERSION.'</em><em><a href="'.kCHANGELOG.'">change log</a></em></h1>
				<h2>Update Failed!</h2>
				<p>An error occurred while attempting to write to the Symphony configuration, <code>manifest/config.php</code>. Please check it is writable and try again.</p>

				<div class="submit">
					<input type="submit" name="action[update]" value="Update Symphony"/>
				</div>
				
				');
			
		}

		die($code);

	}

	// Check if Symphony is already installed
	if(file_exists('manifest/config.php')){

		if(isset($settings['symphony']['version']) && version_compare(kVERSION, $settings['symphony']['version'], '<=')){
			$code = sprintf($shell,
'			<h1>Update Symphony <em>Version '.kVERSION.'</em><em><a href="'.kCHANGELOG.'">change log</a></em></h1>
			<h2>Existing Installation</h2>
			<p>It appears that Symphony has already been installed at this location and is up to date.</p>
			<br />
			<p>This script, <code>update.php</code>, should be removed as a safety precaution. <a href="'.URL.'/update.php?action=remove">Click here</a> to remove this file and proceed to your administration area.</p>');

			die($code);
		}

		$code = sprintf($shell,
'				<h1>Update Symphony <em>Version '.kVERSION.'</em><em><a href="'.kCHANGELOG.'">change log</a></em></h1>
				<h2>Update Existing Installation</h2>
				<p>This script will update your existing Symphony '.$settings['symphony']['version'].' installation to version '.kVERSION.'.</p>
			
				<div class="submit">
					<input type="submit" name="action[update]" value="Update Symphony"/>
				</div>');

		die($code);

	}	
	
