<?php

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
	
	require_once('symphony/lib/toolkit/class.general.php');

	error_reporting(E_ALL ^ E_NOTICE);
	set_error_handler('__errorHandler');

	define('kBUILD', '353');
	define('kVERSION', '2.0.2');
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
		
		$settings['symphony']['build'] = kBUILD;
		$settings['symphony']['version'] = kVERSION;

		if(writeConfig(DOCROOT . '/manifest', $settings, $settings['file']['write_mode']) === true){

			$code = sprintf($shell, 
'				<h1>Update Symphony <em>Version '.kVERSION.'</em><em><a href="http://overture21.com/forum/comments.php?DiscussionID=644">change log</a></em></h1>
				<h2>Update Complete</h2>
				<p>This script, <code>update.php</code>, should be removed as a safety precaution. <a href="'.URL.'/symphony/">Click here</a> to proceed to your administration area.</p>');

		}
		
		else{
			
			$code = sprintf($shell, 
'				<h1>Update Symphony <em>Version '.kVERSION.'</em><em><a href="http://overture21.com/forum/comments.php?DiscussionID=644">change log</a></em></h1>
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
'			<h1>Update Symphony <em>Version '.kVERSION.'</em><em><a href="http://overture21.com/forum/comments.php?DiscussionID=644">change log</a></em></h1>
			<h2>Existing Installation</h2>
			<p>It appears that Symphony has already been installed at this location and is up to date.</p>');

			die($code);
		}

		$code = sprintf($shell,
'				<h1>Update Symphony <em>Version '.kVERSION.'</em><em><a href="http://overture21.com/forum/comments.php?DiscussionID=644">change log</a></em></h1>
				<h2>Update Existing Installation</h2>
				<p>This script will update your existing Symphony '.$settings['symphony']['version'].' installation to version 2.0.2</p>
			
				<div class="submit">
					<input type="submit" name="action[update]" value="Update Symphony"/>
				</div>');

		die($code);

	}	
	
