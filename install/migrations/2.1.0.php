<?php

	Class migration_210 extends Migration{

		static function upgrade(){
			// 2.1 uses SHA1 instead of MD5
			if(version_compare(self::$existing_version, '2.1.0', '<=')) {
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

				Symphony::Database()->query(
					'ALTER TABLE  `tbl_fields_input` CHANGE  `validator`  `validator` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL;'
				);

				Symphony::Database()->query(
					'ALTER TABLE  `tbl_fields_upload` CHANGE  `validator`  `validator` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL;'
				);

				Symphony::Database()->query(
					'ALTER TABLE  `tbl_fields_taglist` CHANGE  `validator`  `validator` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL;'
				);
			}
		}

		static function pre_notes(){
			return array(
				__('As of version %1$s, the %2$s algorithm is used instead of MD5 for generating password data. After updating, the owner’s login password will be reset. Please also note that all other users’ passwords will no longer be valid and will require a manual reset through Symphony’s forgotten password feature. Alternatively, as an administrator, you can also change your users’ password on their behalf.', array('<code>2.1</code>', '<a href="http://php.net/sha1"><code>SHA1</code></a>'))
			);
		}

		static function post_notes(){
			return array(
				__('The password for user %1$s is now reset. The new temporary password is %2$s. Please login and change it now.', array('<code>' . $username . '</code>', '<code>' . $new_password . '</code>'))
			);
		}

	}
