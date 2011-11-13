<?php

	Abstract Class Migration {

		static $existing_version = null;

		final static function run($function, $existing_version = null) {
			self::$existing_version = $existing_version;

			try{
				self::$function();

				return true;
			}
			catch(DatabaseException $e){
				$error = Symphony::Database()->getLastError();
				Symphony::Log()->writeToLog('Could not complete upgrading. MySQL returned: ' . $error['num'] . ': ' . $error['msg'], E_ERROR, true);

				return false;
			}
			catch(Exception $e){
				Symphony::Log()->writeToLog('Could not complete upgrading because of the following error: ' . $e->getMessage(), E_ERROR, true);

				return false;
			}
		}

		static function upgrade(){
			return;
		}

		static function downgrade(){
			return;
		}

		static function pre_notes(){
			return array();
		}

		static function post_notes(){
			return array();
		}

	}
