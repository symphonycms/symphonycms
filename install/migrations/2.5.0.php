<?php

	Class migration_250 extends Migration {

		static $publish_filtering_disabled = false;

		static function getVersion(){
			return '2.5.0beta1';
		}

		static function getReleaseNotes(){
			return 'http://getsymphony.com/download/releases/version/2.5.0/';
		}

		static function upgrade() {
			// Add association interfaces
			try {
				Symphony::Database()->query('
					ALTER TABLE `tbl_sections_association`
					ADD `interface` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
					ADD `editor` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL;
				');
			}
			catch (Exception $ex) {}

			// Remove show_association #2082
			try {
				Symphony::Database()->query('
					ALTER TABLE `tbl_fields_select` DROP COLUMN show_association;
				');
			}
			catch (Exception $ex) {}

			// Update the version information
			return parent::upgrade();
		}

	}
