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
            // Update `pre_populate` replace "yes" with "now"
            // Update `pre_populate` replace "no" with ""
            try {
                Symphony::Database()->query('
                        UPDATE `tbl_fields_date` SET `pre_populate` = "now" WHERE `pre_populate`= "yes";
                ');
                Symphony::Database()->query('
                        UPDATE `tbl_fields_date` SET `pre_populate` = "" WHERE `pre_populate`= "no";
                ');
            } catch (Exception $ex) {
            }

            // Update the version information
            return parent::upgrade();
        }
    }
