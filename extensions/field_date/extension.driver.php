<?php
	
	class Extension_Field_Date extends Extension {
		public function about() {
			return array(
				'name'			=> 'Date',
				'version'		=> '2.0.0',
				'release-date'	=> '2010-02-16',
				'author'		=> array(
					'name'			=> 'Symphony Team',
					'website'		=> 'http://symphony-cms.com/',
					'email'			=> 'team@symphony-cms.com'
				),
				'type'			=> array(
					'Field', 'Core'
				),
			);
		}
		
		public function uninstall() {
			Symphony::Database()->query("DROP TABLE `tbl_fields_date`");
		}
		
		public function install() {
			Symphony::Database()->query("
				CREATE TABLE IF NOT EXISTS `tbl_fields_date` (
					`id` int(11) unsigned NOT NULL AUTO_INCREMENT,
					`field_id` int(11) unsigned NOT NULL,
					`pre_populate` enum('yes','no') NOT NULL DEFAULT 'no',
					PRIMARY KEY (`id`),
					KEY `field_id` (`field_id`)
				)
			");
			
			return true;
		}
	}