<?php

	Class migration_207 extends Migration{

		static function upgrade(){
			// 2.0.7RC1
			if(version_compare(self::$existing_version, '2.0.7RC1', '<=')) {
				Symphony::Database()->query('ALTER TABLE `tbl_authors` ADD `language` VARCHAR(15) NULL DEFAULT NULL');

				Symphony::Configuration()->set('pages_table_nest_children', 'no', 'symphony');
				Symphony::Configuration()->write();
			}
		}

	}
