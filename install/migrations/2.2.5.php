<?php

	Class migration_225 extends Migration{

		static function getVersion(){
			return '2.2.5';
		}

		static function getReleaseNotes(){
			return 'http://getsymphony.com/download/releases/version/2.2.5/';
		}

		static function upgrade(){
			Symphony::Configuration()->set('version', '2.2.5', 'symphony');
			if(Symphony::Configuration()->write() === false) {
				throw new Exception('Failed to write configuration file, please check the file permissions.');
			}
			else {
				return true;
			}
		}

	}
