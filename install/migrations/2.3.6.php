<?php

	Class migration_236 extends Migration {

		static function getVersion(){
			return '2.3.6';
		}

		static function getReleaseNotes(){
			return 'http://getsymphony.com/download/releases/version/2.3.6/';
		}

		static function upgrade() {
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
