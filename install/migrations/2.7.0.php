<?php

    class migration_270 extends Migration
    {
        public static function getVersion()
        {
            return '2.7.0.beta1';
        }

        public static function getReleaseNotes()
        {
            return 'http://getsymphony.com/download/releases/version/2.7.0/';
        }

        public static function upgrade()
        {
            
            // Update the version information
            return parent::upgrade();
        }
    }
