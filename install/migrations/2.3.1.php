<?php

	Class migration_231 extends Migration{

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
			return '2.3.1dev';
		}

		static function getReleaseNotes(){
			return 'https://gist.github.com/2828337';
		}

		static function upgrade(){
			// 2.3.1dev
			if(version_compare(self::$existing_version, '2.3.1dev', '<=')) {

				// Remove unused setting
				$author_table = 'tbl_fields_author';
				if(Symphony::Database()->tableContainsField($author_table, 'allow_author_change')) {
					Symphony::Database()->query("ALTER TABLE `' . $author_table . '` DROP `allow_author_change`;");
				}

				// Author Types [#1219]
				if(!Symphony::Database()->tableContainsField($author_table, 'author_types')) {
					Symphony::Database()->query("ALTER TABLE `' . $author_table . '` ADD `author_types` VARCHAR(255) DEFAULT NULL;");
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
