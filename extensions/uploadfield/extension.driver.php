<?php
	
	class Extension_UploadField extends Extension {
		public function about() {
			return array(
				'name'			=> 'Field: Upload',
				'version'		=> '2.0.0',
				'release-date'	=> '2010-02-16',
				'author'		=> array(
					'name'			=> 'Symphony Team',
					'website'		=> 'http://symphony-cms.com/',
					'email'			=> 'team@symphony-cms.com'
				)
			);
		}
		
		public function uninstall() {
			Symphony::Database()->query("DROP TABLE `tbl_fields_upload`");
		}
		
		public function install() {
			return Symphony::Database()->query("
				CREATE TABLE IF NOT EXISTS `tbl_fields_upload` (
				  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
				  `field_id` int(11) unsigned NOT NULL,
				  `destination` varchar(255) NOT NULL,
				  `validator` varchar(50) DEFAULT NULL,
				  PRIMARY KEY (`id`),
				  KEY `field_id` (`field_id`)
				)
			");
		}
	}
	
