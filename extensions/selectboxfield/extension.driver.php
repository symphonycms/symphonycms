<?php
	
	class Extension_SelectboxField extends Extension {
		public function about() {
			return array(
				'name'			=> ' Selectbox',
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
			Symphony::Database()->query("DROP TABLE `tbl_fields_select`");
		}
		
		public function install() {
			return Symphony::Database()->query("
				CREATE TABLE IF NOT EXISTS `tbl_fields_select` (
					`id` int(11) unsigned NOT NULL AUTO_INCREMENT,
					`field_id` int(11) unsigned NOT NULL,
					`allow_multiple_selection` enum('yes','no') NOT NULL DEFAULT 'no',
					`static_options` text,
					`dynamic_options` int(11) unsigned DEFAULT NULL,
					PRIMARY KEY (`id`),
					KEY `field_id` (`field_id`)
				)
			");
		}
	}
	
?>