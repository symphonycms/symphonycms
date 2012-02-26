<?php

	Class migration_230 extends Migration{

		static function run($function, $existing_version = null) {
			self::$existing_version = $existing_version;

			try{
				$canProceed = self::$function();

				return ($canProceed === false) ? false : true;
			}
			catch(DatabaseException $e) {
				Symphony::Log()->writeToLog('Could not complete upgrading. MySQL returned: ' . $e->getDatabaseErrorCode() . ': ' . $e->getDatabaseErrorMessage(), E_ERROR, true);

				return false;
			}
			catch(Exception $e){
				Symphony::Log()->writeToLog('Could not complete upgrading because of the following error: ' . $e->getMessage(), E_ERROR, true);

				return false;
			}
		}

		static function getVersion(){
			return '2.3beta3';
		}

		static function getReleaseNotes(){
			return 'https://gist.github.com/1162216';
		}

		static function upgrade(){
			// 2.3dev
			if(version_compare(self::$existing_version, '2.3dev', '<=')) {
				Symphony::Configuration()->set('version', '2.3dev', 'symphony');
				Symphony::Configuration()->set('useragent', 'Symphony/2.3dev', 'general');

				// Add Publish Label to `tbl_fields`
				if(!Symphony::Database()->tableContainsField('tbl_fields', 'publish_label')) {
					Symphony::Database()->query('ALTER TABLE `tbl_fields` ADD `publish_label` VARCHAR(255) DEFAULT NULL');
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

			// 2.3 Beta 1
			if(version_compare(self::$existing_version, '2.3beta1', '<=')) {
				Symphony::Configuration()->set('version', '2.3beta1', 'symphony');
				Symphony::Configuration()->set('useragent', 'Symphony/2.3 Beta 1', 'general');

				Symphony::Configuration()->write();
			}

			// 2.3 Beta 2
			if(version_compare(self::$existing_version, '2.3beta2', '<=')) {

				// Migrate Publish Labels (if created) to the Label field
				// Then drop Publish Label, we're going to use element_name and label
				// to take care of the same functionality!
				try {
					if(Symphony::Database()->tableContainsField('tbl_fields', 'publish_label')) {
						$fields = Symphony::Database()->fetch('SELECT `publish_label`, `label`, `id` FROM `tbl_fields`');

						foreach($fields as $field){
							if(!$field['publish_label']) continue;

							Symphony::Database()->query(sprintf("
								UPDATE `tbl_fields`
								SET `label` = '%s'
								WHERE `id` = %d
								LIMIT 1;
								",
								$field['publish_label'],
								$field['id']
							));
						}

						Symphony::Database()->query("ALTER TABLE `tbl_fields` DROP `publish_label`");
					}
				}
				catch(Exception $ex) {
					Symphony::Log()->pushToLog($ex->getMessage(), E_NOTICE, true);
				}

				// Add uniqueness constraint for the Authors table. #937
				if(!Symphony::Database()->query("ALTER TABLE `tbl_authors` ADD UNIQUE KEY `email` (`email`)")) {
					Symphony::Log()->pushToLog(
						__("You have multiple Authors with the same email address, which can cause issues with password retrieval. Please ensure all Authors have unique email addresses before updating. " . $ex->getMessage()),
						E_USER_ERROR,
						true
					);

					return false;
				}

				// Update the version information
				Symphony::Configuration()->set('version', '2.3beta2', 'symphony');
				Symphony::Configuration()->set('useragent', 'Symphony/2.3 Beta 2', 'general');

				Symphony::Configuration()->write();
			}

			// 2.3 Beta 3
			if(version_compare(self::$existing_version, '2.3beta3', '<=')) {

				// Refresh indexes on existing Author field tables
				$author_fields = Symphony::Database()->fetchCol("field_id", "SELECT `field_id` FROM `tbl_fields_author`");

				foreach($author_fields as $id) {
					$table = '`tbl_entries_data_' . $id . '`';

					// MySQL doesn't support DROP IF EXISTS, so we'll try and catch.
					try {
						Symphony::Database()->query("ALTER TABLE " . $table . " DROP INDEX `entry_id`");
					}
					catch (Exception $ex) {}

					try {
						Symphony::Database()->query("CREATE UNIQUE INDEX `author` ON " . $table . " (`entry_id`, `author_id`)");
						Symphony::Database()->query("OPTIMIZE TABLE " . $table);
					}
					catch (Exception $ex) {}
				}

				// Move section sorting data from the database to the filesystem. #977
				$sections = Symphony::Database()->fetch("SELECT `handle`, `entry_order`, `entry_order_direction` FROM `tbl_sections`");

				foreach($sections as $s) {
					Symphony::Configuration()->set('section_' . $s['handle'] . '_sortby', $s['entry_order'], 'sorting');
					Symphony::Configuration()->set('section_' . $s['handle'] . '_order', $s['entry_order_direction'], 'sorting');
				}

				// Update the version information
				Symphony::Configuration()->set('version', '2.3beta3', 'symphony');
				Symphony::Configuration()->set('useragent', 'Symphony/2.3 Beta 3', 'general');

				return Symphony::Configuration()->write();
			}
		}

		static function preUpdateNotes(){
			return array(
				__("Please ensure all Authors have unique email addresses before updating.")
			);
		}

	}
