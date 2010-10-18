<?php

	error_reporting(E_ALL ^ E_NOTICE);

	function __errorHandler($errno=NULL, $errstr, $errfile=NULL, $errline=NULL, $errcontext=NULL){
		return;
	}

	function tableContainsField($table, $field){
		$sql = "DESC `{$table}` `{$field}`";
		$results = Frontend::instance()->Database->fetch($sql);

		return (is_array($results) && !empty($results));
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

	function render($output){
		header('Expires: Mon, 12 Dec 1982 06:14:00 GMT');
		header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
		header('Cache-Control: no-cache, must-revalidate, max-age=0');
		header('Pragma: no-cache');
		header(sprintf('Content-Length: %d', strlen($output)));

		echo $output;
		exit;
	}

	define('DOCROOT', rtrim(dirname(__FILE__), '/'));
	define('DOMAIN', rtrim(rtrim($_SERVER['HTTP_HOST'], '/') . dirname($_SERVER['PHP_SELF']), '/'));

	require_once(DOCROOT . '/symphony/lib/boot/bundle.php');
	require_once(TOOLKIT . '/class.general.php');

	set_error_handler('__errorHandler');

	define('kVERSION', '2.1.2');
	define('kCHANGELOG', 'http://symphony-cms.com/download/releases/version/'.kVERSION.'/');
	define('kINSTALL_ASSET_LOCATION', './symphony/assets/installer');
	define('kINSTALL_FILENAME', basename(__FILE__));

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

	if(isset($_GET['action']) && $_GET['action'] == 'remove'){

		if(is_writable(__FILE__)){
			unlink(__FILE__);
			redirect(URL . '/symphony/');
		}

		render(sprintf($shell,
			'<h1>Update Symphony <em>Version '.kVERSION.'</em><em><a href="'.kCHANGELOG.'">change log</a></em></h1>
			<h2>Deletion Failed!</h2>
			<p>Symphony was unable to delete your <code>update.php</code> file. For security reasons, please be sure to delete the file before proceeding.</p>
			<br />
			<p>To continue to the Symphony admin, please <a href="'.URL.'/symphony/">click here</a>.</p>'
		));

	}

	$settings = loadOldStyleConfig();
	$existing_version = $settings['symphony']['version'];

	if(isset($_POST['action']['update'])){

		$settings['symphony']['version'] = kVERSION;
		$settings['general']['useragent'] = 'Symphony/' . kVERSION;

		## Build is no longer used
		unset($settings['symphony']['build']);

		## Remove the old Maintenance Mode setting
		unset($settings['public']['maintenance_mode']);

		## Set the default language
		if(!isset($settings['symphony']['lang'])){
			$settings['symphony']['lang'] = 'en';
		}

		if(writeConfig(DOCROOT . '/manifest', $settings, $settings['file']['write_mode']) === true){

			// build a Frontend page instance to initialise database
			require_once(CORE . '/class.frontend.php');
			$frontend = Frontend::instance();

			if (version_compare($existing_version, '2.0.3', '<=')) {

				// Add Navigation Groups
				if(!tableContainsField('tbl_sections', 'navigation_group')){
					$frontend->Database->query("ALTER TABLE `tbl_sections` ADD `navigation_group` VARCHAR( 50 ) NOT NULL DEFAULT 'Content'");
					$frontend->Database->query("ALTER TABLE `tbl_sections` ADD INDEX (`navigation_group`)");
				}

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
				if(!tableContainsField('tbl_fields_author', 'default_to_current_user')){
					$frontend->Database->query("ALTER TABLE `tbl_fields_author` ADD `default_to_current_user` ENUM('yes', 'no') NOT NULL");
				}

				## Change .htaccess from `page` to `symphony-page`
				$htaccess = @file_get_contents(DOCROOT . '/.htaccess');

				if($htaccess !== false){
					$htaccess = str_replace('index.php?page=$1&%{QUERY_STRING}', 'index.php?symphony-page=$1&%{QUERY_STRING}', $htaccess);
					@file_put_contents(DOCROOT . '/.htaccess', $htaccess);
				}

			}


			if (version_compare($existing_version, '2.0.5', '<=')) {
				## Rebuild the .htaccess here

				$rewrite_base = trim(dirname($_SERVER['PHP_SELF']), DIRECTORY_SEPARATOR);

				if(strlen($rewrite_base) > 0){
					$rewrite_base .= '/';
				}

		        $htaccess = '
### Symphony 2.0.x ###
Options +FollowSymlinks -Indexes

<IfModule mod_rewrite.c>

	RewriteEngine on
	RewriteBase /'.$rewrite_base.'

	### DO NOT APPLY RULES WHEN REQUESTING "favicon.ico"
	RewriteCond %{REQUEST_FILENAME} favicon.ico [NC]
	RewriteRule .* - [S=14]

	### IMAGE RULES
	RewriteRule ^image\/(.+\.(jpg|gif|jpeg|png|bmp))$ extensions/jit_image_manipulation/lib/image.php?param=$1 [L,NC]

	### CHECK FOR TRAILING SLASH - Will ignore files
	RewriteCond %{REQUEST_FILENAME} !-f
	RewriteCond %{REQUEST_URI} !/$
	RewriteCond %{REQUEST_URI} !(.*)/$
	RewriteRule ^(.*)$ $1/ [L,R=301]

	### ADMIN REWRITE
	RewriteRule ^symphony\/?$ index.php?mode=administration&%{QUERY_STRING} [NC,L]

	RewriteCond %{REQUEST_FILENAME} !-d
	RewriteCond %{REQUEST_FILENAME} !-f
	RewriteRule ^symphony(\/(.*\/?))?$ index.php?symphony-page=$1&mode=administration&%{QUERY_STRING}	[NC,L]

	### FRONTEND REWRITE - Will ignore files and folders
	RewriteCond %{REQUEST_FILENAME} !-d
	RewriteCond %{REQUEST_FILENAME} !-f
	RewriteRule ^(.*\/?)$ index.php?symphony-page=$1&%{QUERY_STRING}	[L]

</IfModule>
######
';

				@file_put_contents(DOCROOT . '/.htaccess', $htaccess);

				// No longer need symphony/.htaccess
				if(file_exists(DOCROOT . '/symphony/.htaccess') && is_writable(DOCROOT . '/symphony/.htaccess')){
					unlink(DOCROOT . '/symphony/.htaccess');
				}

			}

			if(version_compare($existing_version, '2.0.6', '<=')){
				$frontend->Database->query('ALTER TABLE `tbl_extensions` CHANGE `version` `version` VARCHAR(20) NOT NULL');
			}

			if(version_compare($existing_version, '2.0.7RC1', '<=')){
				$frontend->Database->query('ALTER TABLE `tbl_authors` ADD `language` VARCHAR(15) NULL DEFAULT NULL');

				$settings['symphony']['pages_table_nest_children'] = 'no';
				writeConfig(DOCROOT . '/manifest', $settings, $settings['file']['write_mode']);
			}

			if(version_compare($existing_version, '2.0.8RC1', '<')){
				$frontend->Database->query('ALTER TABLE `tbl_fields_date` DROP `calendar`');
			}

			if(version_compare($existing_version, '2.0.8RC3', '<=')){
				## Add -Indexes to .htaccess
				$htaccess = @file_get_contents(DOCROOT . '/.htaccess');

				if($htaccess !== false && !preg_match('/-Indexes/', $htaccess)){
					$htaccess = str_replace('Options +FollowSymlinks', 'Options +FollowSymlinks -Indexes', $htaccess);
					@file_put_contents(DOCROOT . '/.htaccess', $htaccess);
				}

				## 2.1 uses SHA1 instead of MD5
				// Change the author table to allow 40 character values
				$frontend->Database->query(
					"ALTER TABLE `tbl_authors` CHANGE `password` `password` VARCHAR(40) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL"
				);

				// Generate a new password for the primary author account
				$new_password = General::generatePassword();
				$username = $frontend->Database->fetchVar('username', 0,
					"SELECT `username` FROM `tbl_authors` WHERE `primary` = 'yes' LIMIT 1"
				);

				$frontend->Database->query(
					sprintf("UPDATE `tbl_authors` SET `password` = SHA1('%s') WHERE `primary` = 'yes' LIMIT 1", $new_password)
				);

				// Purge all sessions, forcing everyone to update their passwords
				$frontend->Database->query( "TRUNCATE TABLE `tbl_sessions`");

				## Update Upload field
				$upload_entry_tables = $frontend->Database->fetchCol("field_id", "SELECT `field_id` FROM `tbl_fields_upload`");

				if(is_array($upload_entry_tables) && !empty($upload_entry_tables)) foreach($upload_entry_tables as $field) {
					$frontend->Database->query(sprintf(
							"ALTER TABLE `tbl_entries_data_%d` CHANGE `size` `size` INT(11) UNSIGNED NULL DEFAULT NULL", 
							$field
					));
				}
			}
			
			if(version_compare($existing_version, '2.1.0', '<=')){
				$frontend->Database->query(
					'ALTER TABLE  `tbl_fields_input` CHANGE  `validator`  `validator` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL;'
				);

				$frontend->Database->query(
					'ALTER TABLE  `tbl_fields_upload` CHANGE  `validator`  `validator` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL;'
				);

				$frontend->Database->query(
					'ALTER TABLE  `tbl_fields_taglist` CHANGE  `validator`  `validator` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL;'
				);
			}
			
			$sbl_version = $frontend->Database->fetchVar('version', 0,
				"SELECT `version` FROM `tbl_extensions` WHERE `name` = 'selectbox_link_field' LIMIT 1"
			);

			$code = sprintf($shell,
'				<h1>Update Symphony <em>Version '.kVERSION.'</em><em><a href="'.kCHANGELOG.'">change log</a></em></h1>
				<h2>Update Complete</h2>
				<p><strong>Post-Installation Steps: </strong></p>
				<br />
				<ol>
				'.

				(version_compare($existing_version, '2.1.0', '<') ? '
				<li>The password for user "<code>'.$username.'</code>" is now reset. The new temporary password is "<code>'.$new_password.'</code>". Please login and change it now.</li>' : NULL)

				.

				(file_exists(DOCROOT . '/symphony/.htaccess') ? '<li><strong>WARNING:</strong> The updater tried, but failed, to remove the file <code>symphony/.htaccess</code>. It is vitally important that this file be removed, otherwise the administration area will not function. If you have customisations to this file, you should be able to just remove the Symphony related block, but there are no guarantees.</li>' : NULL)

				.

				(version_compare($existing_version, '2.0.5', '<') ? '<li>Version <code>2.0.5</code> introduced multiple includable elements, in the Data Source Editor, for a single field. After updating from <code>2.0.5</code> or lower, the DS editor will seem to "forget" about any <code>Textarea</code> fields selected when you are editing existing Data Sources. After updating, you must ensure you re-select them before saving. <strong>Note, this will only effect Data Sources that you edit and were created prior to <code>2.0.5</code></strong>. Until that point, the field will still be included in any front-end <code>XML</code></li>' : NULL)

				.

				(version_compare($existing_version, '2.0.5', '<=') ? '<li>As of 2.0.5, Symphony comes pre-packaged with the "Debug Dev Kit" and "Profile Dev Kit" extensions, which replace the built-in functionality. Prior to using them, you must ensure the folder <code>extensions/debugdevkit/lib/bitter/caches/</code> is writable by <code>PHP</code>.</li>' : NULL)

				.

				(version_compare($existing_version, '2.0.2', '<') ? '<li>Since <code>2.0.2</code>, the built-in image manipulation features have been replaced with the <a href="http://github.com/pointybeard/jit_image_manipulation/tree/master">JIT Image Manipulation</a> extension. Should you have uploaded (or cloned) this to your Extensions folder, be sure to <a href="'.URL.'/symphony/system/extensions/">enable it.</a></li>' : NULL)

				.

				(!is_null($sbl_version) && version_compare($sbl_version, '1.14', '<') ? '<li>The "Select Box Link" field extension has been updated to 1.14, however this installation of Symphony appears to be running an older version ('.$sbl_version.'). Versions prior to 1.14 will not work correctly under Symphony <code>'.kVERSION.'</code>. The latest version can be download via the <a href"http://symphony-cms.com/download/extensions/view/20054/">Select Box Link download page</a> on the Symphony site.</li>' : NULL)

				.'</ol>

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

		render($code);

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

			render($code);
		}

		$code = sprintf($shell,
'				<h1>Update Symphony <em>Version '.kVERSION.'</em><em><a href="'.kCHANGELOG.'">change log</a></em></h1>
				<h2>Update Existing Installation</h2>
				<p>This script will update your existing Symphony '.$settings['symphony']['version'].' installation to version '.kVERSION.'.</p>
				<br />
				'.(version_compare($existing_version, '2.1.0', '<') ? '<p><strong>Pre-Installation Notes: </strong></p>' : NULL).'
				<br />
				<ol>
				'.(version_compare($existing_version, '2.0.6', '<') ? '
				<li>As of <code>2.0.6</code>, the core <code>.htaccess</code> has changed substantially. As a result, there is no fool proof way to automatically update it. Instead, if you have any customisations to your <code>.htaccess</code>, please back up the existing copy before updating. You will then need to manually migrate the customisations to the new <code>.htaccess</code>.</li>' : NULL) .'

				'.(version_compare($existing_version, '2.1.0', '<') ? '
				<li>As of version <code>2.1</code>, the <a href="http://php.net/sha1"><code>SHA1</code></a> algorithm is used instead of MD5 for generating password data. After updating, the owner\'s login password will be reset. Please also note that all other users\' passwords will no longer be valid and will require a manual reset through Symphony\'s forgotten password feature. Alternatively, as an administrator, you can also change your users\' password on their behalf.</li>' : NULL) .'

				</ol>
				<div class="submit">
					<input type="submit" name="action[update]" value="Update Symphony"/>
				</div>');

		render($code);

	}

