<?php

	Class migration_204 extends Migration{

		static function upgrade(){

			$date_fields = Symphony::Database()->fetch("SELECT id FROM tbl_fields WHERE `type` = 'date'");

			foreach ($date_fields as $field) {
				Symphony::Database()->query("ALTER TABLE `tbl_entries_data_{$field['id']}` CHANGE `local` `local` INT(11) DEFAULT NULL;");
				Symphony::Database()->query("ALTER TABLE `tbl_entries_data_{$field['id']}` CHANGE `gmt` `gmt` INT(11) DEFAULT NULL;");
			}

			// Update author field table to support the default value checkbox
			if(!Symphony::Database()->tableContainsField('tbl_fields_author', 'default_to_current_user')){
				Symphony::Database()->query("ALTER TABLE `tbl_fields_author` ADD `default_to_current_user` ENUM('yes', 'no') NOT NULL");
			}

			// Change .htaccess from `page` to `symphony-page`
			$htaccess = file_get_contents(DOCROOT . '/.htaccess');

			if($htaccess !== false){
				$htaccess = str_replace('index.php?page=$1&%{QUERY_STRING}', 'index.php?symphony-page=$1&%{QUERY_STRING}', $htaccess);
				file_put_contents(DOCROOT . '/.htaccess', $htaccess);
			}

		}

	}
