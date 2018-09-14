<?php

    class migration_278 extends Migration
    {
        public static function getVersion()
        {
            return '2.7.8';
        }

        public static function getReleaseNotes()
        {
            return 'https://www.getsymphony.com/download/releases/version/2.7.8/';
        }

        public static function upgrade()
        {
            // Update the default value for last_seen
            // This was done in 2.7.0, but the update process never took care of it
            // Re: #2594
            Symphony::Database()->query("
                ALTER TABLE `tbl_authors`
                    MODIFY `last_seen` datetime DEFAULT '1000-01-01 00:00:00'
            ");

            // Update the version information
            return parent::upgrade();
        }
    }
