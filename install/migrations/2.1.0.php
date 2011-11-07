<?php

	Class migration_210 extends Migration{

		static function upgrade(){

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
