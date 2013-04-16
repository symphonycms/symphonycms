<?php

	Class migration_233 extends Migration{

		static function run($function, $existing_version = null) {
			self::$existing_version = $existing_version;

			try{
				$canProceed = self::$function();

				return ($canProceed === false) ? false : true;
			}
			catch(DatabaseException $e) {
				Symphony::Log()->writeToLog('Could not complete upgrading. MySQL returned: ' . $e->getDatabaseErrorCode() . ': ' . $e->getMessage(), E_ERROR, true);

				return false;
			}
			catch(Exception $e){
				Symphony::Log()->writeToLog('Could not complete upgrading because of the following error: ' . $e->getMessage(), E_ERROR, true);

				return false;
			}
		}

		static function getVersion(){
			return '2.3.3beta2';
		}

		static function getReleaseNotes(){
			return 'https://gist.github.com/brendo/5300783';
		}

		static function upgrade(){
			if(version_compare(self::$existing_version, '2.3.3beta1', '<=')) {
				// Update DB for the new author role #1692
				Symphony::Database()->query(sprintf(
					"ALTER TABLE `tbl_authors` CHANGE `user_type` `user_type` enum('author', 'manager', 'developer') DEFAULT 'author'",
					$field
				));

				// Remove directory from the upload fields, #1719
				$upload_tables = Symphony::Database()->fetchCol("field_id", "SELECT `field_id` FROM `tbl_fields_upload`");

				if(is_array($upload_tables) && !empty($upload_tables)) foreach($upload_tables as $field) {
					Symphony::Database()->query(sprintf(
						"UPDATE default_entries_data_%d SET file = substring_index(file, '/', -1)",
						$field
					));
				}
			}

			if(version_compare(self::$existing_version, '2.3.3beta2', '<=')) {
				// Update rows for associations
				if(!Symphony::Configuration()->get('association_maximum_rows', 'symphony')) {
					Symphony::Configuration()->set('association_maximum_rows', '5', 'symphony');
				}
			}

			// Update the version information
			Symphony::Configuration()->set('version', self::getVersion(), 'symphony');
			Symphony::Configuration()->set('useragent', 'Symphony/' . self::getVersion(), 'general');

			if(Symphony::Configuration()->write() === false) {
				throw new Exception('Failed to write configuration file, please check the file permissions.');
			}
			else {
				return true;
			}
		}

		static function preUpdateNotes(){
			return array(
				__("On update, all files paths will be removed from the core Upload field entry tables. If you are using an Upload field extension, ensure that the extension is compatible with this release before continuing.")
			);
		}

	}
