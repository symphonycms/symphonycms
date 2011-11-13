<?php

	Class migration_203 extends Migration{

		static function upgrade(){

			// Add Navigation Groups
			if(!Symphony::Database()->tableContainsField('tbl_sections', 'navigation_group')){
				Symphony::Database()->query("ALTER TABLE `tbl_sections` ADD `navigation_group` VARCHAR( 50 ) NOT NULL DEFAULT 'Content'");
				Symphony::Database()->query("ALTER TABLE `tbl_sections` ADD INDEX (`navigation_group`)");
			}

			// Added support for upload field to handle empty mimetypes.
			$upload_fields = Symphony::Database()->fetch("SELECT id FROM tbl_fields WHERE `type` = 'upload'");
			foreach ($upload_fields as $upload_field) {
				Symphony::Database()->query("ALTER TABLE `tbl_entries_data_{$upload_field['id']}` CHANGE `mimetype` `mimetype` VARCHAR( 50 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL");
			}

		}

	}
