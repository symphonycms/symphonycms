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
			// [#702] Update to include Admin Path configuration
			if(version_compare(self::$existing_version, '2.4a1', '<=')) {
				// Add missing config value for index view string length
				Symphony::Configuration()->set('cell_truncation_length', '75', 'symphony');
				// Add admin-path to configuration
				Symphony::Configuration()->set('admin-path', 'symphony', 'symphony');
			}

			// [#1626] Update all tables to be UTF-8 encoding/collation
			// @link https://gist.github.com/michael-e/5789168
			$tables = Symphony::Database()->fetch("SHOW TABLES");
			if(is_array($tables) && !empty($tables)){
				foreach($tables as $table){
					$table = current($table);

					// If it's not a Symphony table, ignore it
					if(!preg_match('/^' . Symphony::Database()->getPrefix() . '/', $table)) continue;

					Symphony::Database()->query(sprintf(
						"ALTER TABLE `%s` CHARACTER SET utf8 COLLATE utf8_unicode_ci",
						$table
					));
					Symphony::Database()->query(sprintf(
						"ALTER TABLE `%s` CONVERT TO CHARACTER SET utf8 COLLATE utf8_unicode_ci",
						$table
					));
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
