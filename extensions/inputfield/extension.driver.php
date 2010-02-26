<?php
	
	class Extension_InputField extends Extension {
		public function about() {
			return array(
				'name'			=> 'Field: Input',
				'version'		=> '2.0.0',
				'release-date'	=> '2010-02-16',
				'type'			=> array(
					'field'
				),
				'author'		=> array(
					'name'			=> 'Symphony Team',
					'website'		=> 'http://symphony-cms.com/',
					'email'			=> 'team@symphony-cms.com'
				)
			);
		}
		
		public function uninstall() {
			$this->_Parent->Database->query("DROP TABLE `tbl_fields_input`");
		}
		
		public function install() {
			return $this->_Parent->Database->query("
				CREATE TABLE IF NOT EXISTS `tbl_fields_input` (
					`id` int(11) unsigned NOT NULL AUTO_INCREMENT,
					`field_id` int(11) unsigned NOT NULL,
					`validator` varchar(100) DEFAULT NULL,
					PRIMARY KEY (`id`),
					KEY `field_id` (`field_id`)
				)
			");
		}
	}
	
?>
