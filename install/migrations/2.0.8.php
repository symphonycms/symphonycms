<?php

	Class migration_208 extends Migration{

		static function upgrade(){

			// 2.0.8RC1

			Symphony::Database()->query('ALTER TABLE `tbl_fields_date` DROP `calendar`');

			// 2.0.8RC3

			// Add -Indexes to .htaccess
			$htaccess = file_get_contents(DOCROOT . '/.htaccess');

			if($htaccess !== false && !preg_match('/-Indexes/', $htaccess)){
				$htaccess = str_replace('Options +FollowSymlinks', 'Options +FollowSymlinks -Indexes', $htaccess);
				file_put_contents(DOCROOT . '/.htaccess', $htaccess);
			}

			// 2.1 uses SHA1 instead of MD5
			// Change the author table to allow 40 character values
			Symphony::Database()->query(
				"ALTER TABLE `tbl_authors` CHANGE `password` `password` VARCHAR(40) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL"
			);

			// Generate a new password for the primary author account
			$new_password = General::generatePassword();
			$username = Symphony::Database()->fetchVar('username', 0,
				"SELECT `username` FROM `tbl_authors` WHERE `primary` = 'yes' LIMIT 1"
			);

			Symphony::Database()->query(
				sprintf("UPDATE `tbl_authors` SET `password` = SHA1('%s') WHERE `primary` = 'yes' LIMIT 1", $new_password)
			);

			// Purge all sessions, forcing everyone to update their passwords
			Symphony::Database()->query( "TRUNCATE TABLE `tbl_sessions`");

			// Update Upload field
			$upload_entry_tables = Symphony::Database()->fetchCol("field_id", "SELECT `field_id` FROM `tbl_fields_upload`");

			if(is_array($upload_entry_tables) && !empty($upload_entry_tables)) foreach($upload_entry_tables as $field) {
				Symphony::Database()->query(sprintf(
						"ALTER TABLE `tbl_entries_data_%d` CHANGE `size` `size` INT(11) UNSIGNED NULL DEFAULT NULL",
						$field
				));
			}

		}

	}
