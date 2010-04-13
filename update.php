<?php
	/*
	**	NO DBC INTEGRATION HAS BEEN DONE ON THIS PAGE
	*/

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

	define('DOCROOT', rtrim(dirname(__FILE__), '/'));
	define('DOMAIN', rtrim(rtrim($_SERVER['HTTP_HOST'], '/') . dirname($_SERVER['PHP_SELF']), '/'));

	require_once(DOCROOT . '/symphony/lib/boot/bundle.php');
	require_once(TOOLKIT . '/class.general.php');

	if(isset($_GET['action']) && $_GET['action'] == 'remove'){
		unlink(DOCROOT . '/update.php');
		redirect(ADMIN_URL . '/');
	}

	set_error_handler('__errorHandler');

	define('kVERSION', '2.0.8RC1');
	define('kCHANGELOG', 'http://symphony-cms.com/download/releases/version/'.kVERSION.'/');
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
	$existing_version = $settings['symphony']['version'];

	if(isset($_POST['action']['update'])){

		$settings['symphony']['version'] = kVERSION;

		## Build and Useragent are no longer used
		unset($settings['symphony']['build']);

		if(isset($settings['general']['useragent'])){
			$settings['symphony']['sitename'] = $settings['general']['useragent'];
			unset($settings['general']['useragent']);
		}

		## Move "file:write_mode" and "directory:write_mode" under the "symphony" heading
		if(isset($settings['file']['write_mode'])){
			$settings['symphony']['file_write_mode'] = $settings['file']['write_mode'];
			unset($settings['file']['write_mode']);
		}

		if(isset($settings['directory']['write_mode'])){
			$settings['symphony']['file_write_mode'] = $settings['directory']['write_mode'];
			unset($settings['directory']['write_mode']);
		}

		## Set the default language
		if(!isset($settings['symphony']['lang'])){
			$settings['symphony']['lang'] = 'en';
		}

		if(writeConfig(DOCROOT . '/manifest', $settings, $settings['symphony']['file_write_mode']) === true){

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

		        $rewrite_base = trim(dirname($_SERVER['PHP_SELF']), '/');

		        if(strlen($rewrite_base) > 0){
					$rewrite_base .= '/';
				}

		        $htaccess = '
### Symphony 2.0.x ###
Options +FollowSymlinks

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

			if (version_compare($existing_version, '2.0.6', '<=')) {
				$frontend->Database->query('ALTER TABLE  `tbl_extensions` CHANGE  `version`  `version` VARCHAR(20) NOT NULL');

				// Author table rename and structure change
				$frontend->Database->query('ALTER TABLE `tbl_authors` DROP `user_type`, DROP `primary`;');
				$frontend->Database->query('RENAME TABLE `tbl_authors` TO `tbl_users`;');
				$frontend->Database->query("ALTER TABLE `tbl_forgotpass` CHANGE `author_id` `user_id` INT(11) NOT NULL DEFAULT '0';");

				$author_fields = $frontend->Database->fetchCol('id', "SELECT `id` FROM tbl_fields WHERE `type` = 'author'");
				$frontend->Database->query("UPDATE `tbl_fields` SET `type` = 'user' WHERE `type` = 'author'");
				$frontend->Database->query("RENAME TABLE `tbl_fields_author` TO `tbl_fields_user`");

				foreach ($author_fields as $field_id) {
					$frontend->Database->query("ALTER TABLE `tbl_entries_data_{$field_id}` CHANGE `author_id` `user_id` INT(11) UNSIGNED NOT NULL;");
				}

				$frontend->Database->query('ALTER TABLE `tbl_entries` CHANGE `author_id` `user_id` INT(11) UNSIGNED NOT NULL');
			}

			if(version_compare($existing_version, '2.0.7RC1', '<=')){
				$frontend->Database->query('ALTER TABLE `tbl_authors` ADD `language` VARCHAR(15) NULL DEFAULT NULL');

				$settings['symphony']['pages_table_nest_children'] = 'no';
				writeConfig(DOCROOT . '/manifest', $settings, $settings['file']['write_mode']);
			}

			if(version_compare($existing_version, '2.0.8', '<')){
				$frontend->Database->query('ALTER TABLE `tbl_fields_date` DROP `calendar`');
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

				(file_exists(DOCROOT . '/symphony/.htaccess') ? '<li><strong>WARNING:</strong> The updater tried, but failed, to remove the file <code>symphony/.htaccess</code>. It is vitally important that this file be removed, otherwise the administration area will not function. If you have customisations to this file, you should be able to just remove the Symphony related block, but there are no guarantees.</li>' : NULL)

				.

				(version_compare($existing_version, '2.0.5', '<') ? '<li>Version <code>2.0.5</code> introduced multiple includable elements, in the Data Source Editor, for a single field. After updating from <code>2.0.5</code> or lower, the DS editor will seem to "forget" about any <code>Textarea</code> fields selected when you are editing existing Data Sources. After updating, you must ensure you re-select them before saving. <strong>Note, this will only effect Data Sources that you edit and were created prior to <code>2.0.5</code></strong>. Until that point, the field will still be included in any front-end <code>XML</code></li>' : NULL)

				.

				(version_compare($existing_version, '2.0.5', '<=') ? '<li>As of 2.0.5, Symphony comes pre-packaged with the "Debug Dev Kit" and "Profile Dev Kit" extensions, which replace the built-in functionality. Prior to using them, you must ensure the folder <code>extensions/debugdevkit/lib/bitter/caches/</code> is writable by <code>PHP</code>.</li>' : NULL)

				.

				(version_compare($existing_version, '2.0.2', '<') ? '<li>Since <code>2.0.2</code>, the built-in image manipulation features have been replaced with the <a href="http://github.com/pointybeard/jit_image_manipulation/tree/master">JIT Image Manipulation</a> extension. Should you have uploaded (or cloned) this to your Extensions folder, be sure to <a href="'.ADMIN_URL . '/system/extensions/">enable it.</a></li>' : NULL)

				.

				(!is_null($sbl_version) && version_compare($sbl_version, '1.14', '<') ? '<li>The "Select Box Link" field extension has been updated to 1.14, however this installation of Symphony appears to be running an older version ('.$sbl_version.'). Versions prior to 1.14 will not work correctly under Symphony 2.0.7. The latest version can be download via the <a href"http://symphony-cms.com/download/extensions/view/20054/">Select Box Link download page</a> on the Symphony site.</li>' : NULL)

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
				'.
				(version_compare($existing_version, '2.0.6', '<') ? '
				<br />
				<p><strong>Pre-Installation Step: </strong> As of <code>2.0.6</code>, the core <code>.htaccess</code> has changed substantially. As a result, there is no fool proof way to automatically update it. Instead, if you have any customisations to your <code>.htaccess</code>, please back up the existing copy before updating. You will then need to manually migrate the customisations to the new <code>.htaccess</code>.</p>' : NULL) .'
				<br />
				<div class="submit">
					<input type="submit" name="action[update]" value="Update Symphony"/>
				</div>');

		die($code);

	}

