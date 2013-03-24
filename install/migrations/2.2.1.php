<?php

	Class migration_221 extends Migration{

		static function run($function, $existing_version = null) {
			self::$existing_version = $existing_version;

			try{
				$canProceed = self::$function();

				return ($canProceed === false) ? false : true;
			}
			catch(DatabaseException $e) {
				Symphony::Log()->writeToLog('Could not complete upgrading. MySQL returned: ' . $e->getDatabaseErrorCode() . ': ' . $e->getDatabaseErrorMessage(), E_ERROR, true);

				return false;
			}
			catch(Exception $e){
				Symphony::Log()->writeToLog('Could not complete upgrading because of the following error: ' . $e->getMessage(), E_ERROR, true);

				return false;
			}
		}

		static function getVersion(){
			return '2.2.1';
		}

		static function getReleaseNotes(){
			return 'http://symphony-cms.com/download/releases/version/2.2.1/';
		}

		static function upgrade(){

			// 2.2.1 Beta 1
			if(version_compare(self::$existing_version, '2.2.1 Beta 1', '<=')) {
				Symphony::Configuration()->set('version', '2.2.1 Beta 1', 'symphony');
				try {
					Symphony::Database()->query('CREATE INDEX `session_expires` ON `tbl_sessions` (`session_expires`)');
					Symphony::Database()->query('OPTIMIZE TABLE `tbl_sessions`');
				}
				catch (Exception $ex) {}
				Symphony::Configuration()->write();
			}

			// 2.2.1 Beta 2
			if(version_compare(self::$existing_version, '2.2.1 Beta 2', '<=')) {
				Symphony::Configuration()->set('version', '2.2.1 Beta 2', 'symphony');

				// Add Security Rules from 2.2 to .htaccess
				try {
					$htaccess = file_get_contents(DOCROOT . '/.htaccess');

					if($htaccess !== false && !preg_match('/### SECURITY - Protect crucial files/', $htaccess)){
						$security = '
			### SECURITY - Protect crucial files
			RewriteRule ^manifest/(.*)$ - [F]
			RewriteRule ^workspace/(pages|utilities)/(.*)\.xsl$ - [F]
			RewriteRule ^(.*)\.sql$ - [F]
			RewriteRule (^|/)\. - [F]

			### DO NOT APPLY RULES WHEN REQUESTING "favicon.ico"';

						$htaccess = str_replace('### DO NOT APPLY RULES WHEN REQUESTING "favicon.ico"', $security, $htaccess);
						file_put_contents(DOCROOT . '/.htaccess', $htaccess);
					}
				}
				catch (Exception $ex) {}

				// Add correct index to the `tbl_cache`
				try {
					Symphony::Database()->query('ALTER TABLE `tbl_cache` DROP INDEX `creation`');
					Symphony::Database()->query('CREATE INDEX `expiry` ON `tbl_cache` (`expiry`)');
					Symphony::Database()->query('OPTIMIZE TABLE `tbl_cache`');
				}
				catch (Exception $ex) {}

				// Remove Hide Association field from Select Data tables
				$select_tables = Symphony::Database()->fetchCol("field_id", "SELECT `field_id` FROM `tbl_fields_select`");

				if(is_array($select_tables) && !empty($select_tables)) foreach($select_tables as $field) {
					if(Symphony::Database()->tableContainsField('tbl_entries_data_' . $field, 'show_association')) {
						Symphony::Database()->query(sprintf(
							"ALTER TABLE `tbl_entries_data_%d` DROP `show_association`",
							$field
						));
					}
				}

				// Update Select table to include the sorting option
				if(!Symphony::Database()->tableContainsField('tbl_fields_select', 'sort_options')) {
					Symphony::Database()->query('ALTER TABLE `tbl_fields_select` ADD `sort_options` ENUM( "yes", "no" ) COLLATE utf8_unicode_ci NOT NULL DEFAULT "no"');
				}

				// Remove the 'driver' from the Config
				Symphony::Configuration()->remove('driver', 'database');
				Symphony::Configuration()->write();

				// Remove the NOT NULL from the Author tables
				try {
					$author = Symphony::Database()->fetchCol("field_id", "SELECT `field_id` FROM `tbl_fields_author`");

					foreach($author as $id) {
						$table = '`tbl_entries_data_' . $id . '`';

						Symphony::Database()->query(
							'ALTER TABLE ' . $table . ' CHANGE `author_id` `author_id` int(11) unsigned NULL'
						);
					}
				}
				catch(Exception $ex) {}

				Symphony::Configuration()->write();
			}

			// 2.2.1
			if(version_compare(self::$existing_version, '2.2.1', '<=')) {
				Symphony::Configuration()->set('version', '2.2.1', 'symphony');
			}

			if(Symphony::Configuration()->write() === false) {
				throw new Exception('Failed to write configuration file, please check the file permissions.');
			}
			else {
				return true;
			}
		}

		static function postUpdateNotes(){
			return array(
				__('Version %s introduces some improvements and fixes to Static XML Datasources. If you have any Static XML Datasources in your installation, please be sure to re-save them through the Data Source Editor to prevent unexpected results.', array('<code>2.2.1</code>'))
			);
		}

	}
