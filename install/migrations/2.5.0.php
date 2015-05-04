<?php

    Class migration_250 extends Migration
    {

        public static function getVersion()
        {
            return '2.5.0';
        }

        public static function getReleaseNotes()
        {
            return 'http://getsymphony.com/download/releases/version/2.5/';
        }

        public static function upgrade()
        {
            // Add association interfaces
            try {
                Symphony::Database()->query('
                    ALTER TABLE `tbl_sections_association`
                    ADD `interface` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
                    ADD `editor` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL;
                ');
            }
            catch (Exception $ex) {}

            // Remove show_association #2082
            try {
                Symphony::Database()->query('
                    ALTER TABLE `tbl_fields_select` DROP COLUMN show_association;
                ');
            }
            catch (Exception $ex) {}

            // Remove XSRF configuration options #2118
            Symphony::Configuration()->remove('token_lifetime', 'symphony');
            Symphony::Configuration()->remove('invalidate_tokens_on_request', 'symphony');

            // Update the version information
            return parent::upgrade();
        }

    }
