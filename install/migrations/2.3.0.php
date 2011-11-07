<?php

	Class migration_230 extends Migration{

		static function upgrade(){

			// 2.3dev

			// Add Publish Label to `tbl_fields`
			if(!Symphony::Database()->tableContainsField('tbl_fields', 'publish_label')) {
				Symphony::Database()->query('ALTER TABLE `tbl_fields` ADD `publish_label` VARCHAR(255) COLLATE utf8_unicode_ci NULL DEFAULT NULL');
			}

			// Migrate any Checkbox's Long Description to Publish Label
			try {
				$checkboxes = Symphony::Database()->fetch("SELECT `field_id`, `description` FROM `tbl_fields_checkbox`");

				foreach($checkboxes as $field) {
					if(!isset($field['description'])) continue;

					Symphony::Database()->query(sprintf("
						UPDATE `tbl_fields`
						SET `publish_label` = '%s'
						WHERE `id` = %d
						LIMIT 1;
						",
						$field['description'],
						$field['field_id']
					));
				}

				Symphony::Database()->query("ALTER TABLE `tbl_fields_checkbox` DROP `description`");
			} catch(Exception $ex) {}

			// Removing unused settings
			Symphony::Configuration()->remove('allow_page_subscription', 'symphony');
			Symphony::Configuration()->remove('strict_error_handling', 'symphony');
			Symphony::Configuration()->remove('character_set', 'database');
			Symphony::Configuration()->remove('character_encoding', 'database');
			Symphony::Configuration()->remove('runtime_character_set_alter', 'database');

			if(Symphony::Configuration()->get('pagination_maximum_rows', 'symphony') == '17'){
				Symphony::Configuration()->set('pagination_maximum_rows', '20', 'symphony');
			}

			Symphony::Configuration()->write();

		}

	}
