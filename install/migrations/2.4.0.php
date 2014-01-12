<?php

	Class migration_240 extends Migration {

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
			return '2.4a1';
		}

		static function getReleaseNotes(){
			return 'http://getsymphony.com/download/releases/version/2.4/';
		}

		static function upgrade() {
			// Update to include Admin Path configuration #702
			if(version_compare(self::$existing_version, '2.4a1', '<=')) {
				// Add missing config value for index view string length
				Symphony::Configuration()->set('cell_truncation_length', '75', 'symphony');
				// Add admin-path to configuration
				Symphony::Configuration()->set('admin-path', 'symphony', 'symphony');
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
