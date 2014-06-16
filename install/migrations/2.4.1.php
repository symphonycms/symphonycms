<?php

	Class migration_241 extends Migration {

		static $publish_filtering_disabled = false;

		static function getVersion(){
			return '2.4.1beta1';
		}

		static function getReleaseNotes(){
			return 'http://getsymphony.com/download/releases/version/2.4.1/';
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

			// Update the version information
			return parent::upgrade();
		}

		static function preUpdateNotes(){
			return array();
		}

		static function postUpdateNotes(){
			return array();
		}

	}
