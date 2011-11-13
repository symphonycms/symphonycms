<?php

	Class migration_208 extends Migration{

		static function upgrade(){
			// 2.0.8RC1
			if(version_compare(self::$existing_version, '2.0.8RC1', '<=')) {
				Symphony::Database()->query('ALTER TABLE `tbl_fields_date` DROP `calendar`');
			}

			// 2.0.8RC3
			if(version_compare(self::$existing_version, '2.0.8RC3', '<=')) {
				// Add -Indexes to .htaccess
				$htaccess = file_get_contents(DOCROOT . '/.htaccess');

				if($htaccess !== false && !preg_match('/-Indexes/', $htaccess)){
					$htaccess = str_replace('Options +FollowSymlinks', 'Options +FollowSymlinks -Indexes', $htaccess);
					file_put_contents(DOCROOT . '/.htaccess', $htaccess);
				}
			}
		}

	}
