<?php

	Class migration_206 extends Migration{

		static function upgrade(){
			Symphony::Database()->query('ALTER TABLE `tbl_extensions` CHANGE `version` `version` VARCHAR(20) NOT NULL');
		}


		static function pre_notes(){
			return array(
				__('As of %1$s, the core %2$s has changed substantially. As a result, there is no fool proof way to automatically update it. Instead, if you have any customisations to your %2$s, please back up the existing copy before updating. You will then need to manually migrate the customisations to the new %2$s.', array('<code>2.0.6</code>', '<code>.htaccess</code>'))
			);
		}

	}

