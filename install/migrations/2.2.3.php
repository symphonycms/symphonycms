<?php

	Class migration_223 extends Migration{

		static function getVersion(){
			return '2.2.3';
		}

		static function getReleaseNotes(){
			return 'http://getsymphony.com/download/releases/version/2.2.3/';
		}

		static function upgrade(){
			Symphony::Configuration()->set('version', '2.2.3', 'symphony');
			if(Symphony::Configuration()->write() === false) {
				throw new Exception('Failed to write configuration file, please check the file permissions.');
			}
			else {
				return true;
			}
		}

	}
