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
			// Update the version information
			Symphony::Configuration()->set('version', self::getVersion(), 'symphony');
			Symphony::Configuration()->set('useragent', 'Symphony/' . self::getVersion(), 'general');

			if(Symphony::Configuration()->write() === false) {
				throw new Exception('Failed to write configuration file, please check the file permissions.');
			}
			else {
				return true;
			}

			try {
				$htaccess = file_get_contents(DOCROOT . '/.htaccess');

				if($htaccess !== false && !preg_match('/SetEnv SYMPHONY_PATH/', $htaccess)){
					$addition = '
		Options +FollowSymlinks -Indexes
		
		SetEnv SYMPHONY_PATH symphony

		';

					$htaccess = str_replace('Options +FollowSymlinks -Indexes', $addition, $htaccess);
					file_put_contents(DOCROOT . '/.htaccess', $htaccess);
				}
			}
			catch (Exception $ex) {}
		}

	}
