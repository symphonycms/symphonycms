<?php

    Class migration_300 extends Migration
    {

        static function getVersion()
        {
            return '3.0.0-alpha.1';
        }

        static function getReleaseNotes()
        {
            return 'http://getsymphony.com/download/releases/version/3.0.0/';
        }

        static function upgrade()
        {
            // Add the delegate order field, RE: #2354
            try {
                Symphony::Database()->query("
                    ALTER TABLE `tbl_extensions_delegates`
                    ADD `order` int(11) SIGNED NOT NULL DEFAULT '0',
                ");
            }
            catch (Exception $ex) {}

            // Update the version information
            return parent::upgrade();
        }
    }
