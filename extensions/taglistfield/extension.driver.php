<?php
	
	class Extension_TaglistField extends Extension {
		public function about() {
			return array(
				'name'			=> ' Taglist',
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
			Symphony::Database()->query("DROP TABLE `tbl_fields_taglist`");
		}
		
		public function install() {
			return Symphony::Database()->query("
				CREATE TABLE IF NOT EXISTS `tbl_fields_taglist` (
					`id` int(11) unsigned NOT NULL AUTO_INCREMENT,
					`field_id` int(11) unsigned NOT NULL,
					`validator` varchar(100) DEFAULT NULL,
					`pre_populate_source` varchar(15) DEFAULT NULL,
					PRIMARY KEY (`id`),
					KEY `field_id` (`field_id`),
					KEY `pre_populate_source` (`pre_populate_source`)
				)
			");
		}
	}
	
?>