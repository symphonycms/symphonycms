<?php

	Class migration_24 extends Migration{

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
			return '2.4';
		}

		static function getReleaseNotes(){
			return 'http://getsymphony.com/download/releases/version/2.4/';
		}

		static function upgrade(){
			// 2.3.1
			if(version_compare(self::$existing_version, '2.3.1', '<=')) {
				// Add missing config value for index view string length
				Symphony::Configuration()->set('cell_truncation_length', '75', 'symphony');
			}
		}

	}
