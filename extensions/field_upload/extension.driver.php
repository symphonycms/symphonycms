<?php

	class Extension_Field_Upload extends Extension {
	/*-------------------------------------------------------------------------
		Definition:
	-------------------------------------------------------------------------*/

		public function about() {
			return array(
				'name'			=> 'Upload',
				'version'		=> '2.0.0',
				'release-date'	=> '2010-02-16',
				'author'		=> array(
					'name'			=> 'Rowan Lewis',
					'website'		=> 'http://rowanlewis.com/',
					'email'			=> 'me@rowanlewis.com'
				),
				'description'	=> 'An upload field that allows features to be plugged in.',
				'type'			=> array(
					'Field', 'Core'
				),
			);
		}

		public function uninstall() {
			Symphony::Database()->query("DROP TABLE `tbl_fields_upload`");
		}

		public function install() {
			Symphony::Database()->query("
				CREATE TABLE IF NOT EXISTS `tbl_fields_upload` (
					`id` int(11) unsigned NOT NULL auto_increment,
					`field_id` int(11) unsigned NOT NULL,
					`destination` varchar(255) NOT NULL,
					`validator` varchar(50) default NULL,
					`serialise` enum('yes','no') default NULL,
					PRIMARY KEY  (`id`),
					KEY `field_id` (`field_id`)
				)
			");

			// TODO: Upgrade existing table

			return true;
		}

	/*-------------------------------------------------------------------------
		Utilites:
	-------------------------------------------------------------------------*/

		protected $addedHeaders = false;

		public function addHeaders($page) {
			if (!$this->addedHeaders) {

				Symphony::Parent()->Page->insertNodeIntoHead(Symphony::Parent()->Page->createScriptElement(URL . '/extensions/field_upload/assets/publish.css'));
				Symphony::Parent()->Page->insertNodeIntoHead(Symphony::Parent()->Page->createStylesheetElement(URL . '/extensions/field_upload/assets/publish.js'));

				$this->addedHeaders = true;
			}
		}
	}
