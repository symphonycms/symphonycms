<?php

    class migration_266 extends Migration
    {
        public static function getVersion()
        {
            return '2.6.6';
        }

        public static function getReleaseNotes()
        {
            return 'http://getsymphony.com/download/releases/version/2.6.6/';
        }

        public static function upgrade()
        {
            // Add the upload blacklist (see c763e6a)
            $blacklist = Symphony::Configuration()->get('upload_blacklist', 'admin');
            if (empty($blacklist)) {
                Symphony::Configuration()->set('upload_blacklist', '/\.(?:php[34567s]?|phtml)$/i', 'admin');
            }

            // Update the version information
            return parent::upgrade();
        }
    }
