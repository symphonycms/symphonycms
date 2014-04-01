<?php

	Class migration_224 extends Migration{

		static function getVersion(){
			return '2.2.4';
		}

		static function getReleaseNotes(){
			return 'http://getsymphony.com/download/releases/version/2.2.4/';
		}

		static function upgrade(){
			Symphony::Configuration()->set('version', '2.2.4', 'symphony');
			if(Symphony::Configuration()->write() === false) {
				throw new Exception('Failed to write configuration file, please check the file permissions.');
			}
			else {
				return true;
			}
		}

	}
