<?php

	Class migration_232 extends Migration{

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
			return '2.3.2beta1';
		}

		static function getReleaseNotes(){
			return 'https://gist.github.com/4075748';
		}

		static function upgrade(){
			//	Update DB for the new Mime-type length
			if(version_compare($symphony_version, '2.3.1', '<')){
				$upload_entry_tables = Symphony::Database()->fetchCol("field_id", "SELECT `field_id` FROM `tbl_fields_upload`");

				if(is_array($upload_entry_tables) && !empty($upload_entry_tables)){
					foreach($upload_entry_tables as $field){
						Symphony::Database()->query(sprintf(
							"ALTER TABLE `tbl_entries_data_%d` CHANGE `mimetype` `mimetype` varchar(100) DEFAULT NULL",
							$field
						));
					}
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

	}
