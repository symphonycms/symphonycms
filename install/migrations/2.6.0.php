<?php

    Class migration_260 extends Migration
    {

        static function getVersion()
        {
            return '2.6.0-beta.2';
        }

        static function getReleaseNotes()
        {
            return 'http://getsymphony.com/download/releases/version/2.6.0/';
        }

        static function upgrade()
        {
            // Add date field options
            try {
                Symphony::Database()->query('
                    ALTER TABLE `tbl_fields_date`
                    ADD `calendar` enum("yes","no") COLLATE utf8_unicode_ci NOT NULL DEFAULT "no",
                    ADD `time` enum("yes","no") COLLATE utf8_unicode_ci NOT NULL DEFAULT "yes";
                ');
            }
            catch (Exception $ex) {}

            // Add namespace field to the cache table. RE: #2162
            try {
                Symphony::Database()->query('
                    ALTER TABLE `tbl_cache` ADD `namespace` VARCHAR(255) COLLATE utf8_unicode_ci;
                ');
            }
            catch (Exception $ex) {}

            // Add UNIQUE key constraint to the `hash` RE: #2163
            try {
                Symphony::Database()->import('
                    ALTER TABLE `tbl_cache` DROP INDEX `hash`;
                    ALTER TABLE `tbl_cache` ADD UNIQUE INDEX `hash` (`hash`)
                ');
            }
            catch (Exception $ex) {}

            if(version_compare(self::$existing_version, self::getVersion(), '<=')) {
                // [#] Add weekoffset to configuration
                Symphony::Configuration()->set('weekoffset', 0, 'region');
            }

            // Update the version information
            return parent::upgrade();
        }
    }
