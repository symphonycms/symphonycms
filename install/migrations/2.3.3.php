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
			return '2.3.3';
		}

		static function getReleaseNotes(){
			return 'http://getsymphony.com/download/releases/version/2.3.3/';
		}

		static function upgrade(){
			//  Update DB for the new author role #1692
			if(version_compare(self::$existing_version, '2.3.2', '<=')) {
				Symphony::Database()->query(sprintf(
					"ALTER TABLE `tbl_authors` CHANGE `user_type` `user_type` enum('author', 'manager', 'developer') DEFAULT 'author'",
					$field
				));

                if(!Symphony::Configuration()->get('association_maximum_rows', 'symphony')) {
                    Symphony::Configuration()->set('association_maximum_rows', '5', 'symphony');
                }
			}
            return true;
		}

		static function preUpdateNotes(){
			return false;
		}

	}
