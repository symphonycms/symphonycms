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
            // Add the log filter value
            // This was added in 2.7.1, but the update process never took care of it
            // Re: #2762
            $filter = Symphony::Configuration()->get('filter', 'log');
            if ($filter === null) {
                Symphony::Configuration()->set('filter', E_ALL ^ E_DEPRECATED, 'log');
            }

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

        public static function postUpdateNotes()
        {
            return array(
                'If none were defined, Symphony did set the default log filter value while updating. ' .
                    'By default, it will log everything except deprecation notices, ' .
                    'i.e. <code>E_ALL ^ E_DEPRECATED.</code>',
                'Make sure to manually adjust your config.php file for your use case.' .
                    'Symphony uses the same values as PHP\'s <code>error_reporting()</code> function.',
            );
        }
    }
