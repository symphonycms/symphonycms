<?php

    Class migration_232 extends Migration
    {

        public static function getVersion()
        {
            return '2.3.2';
        }

        public static function getReleaseNotes()
        {
            return 'http://getsymphony.com/download/releases/version/2.3.2/';
        }

        public static function upgrade()
        {
            //  Update DB for the new Mime-type length. #1534
            if(version_compare(self::$existing_version, '2.3.2beta1', '<=')) {
                $upload_entry_tables = Symphony::Database()->fetchCol("field_id", "SELECT `field_id` FROM `tbl_fields_upload`");

                if(is_array($upload_entry_tables) && !empty($upload_entry_tables)){
                    foreach($upload_entry_tables as $field){
                        Symphony::Database()->query(sprintf(
                            "ALTER TABLE `tbl_entries_data_%d` CHANGE `mimetype` `mimetype` varchar(100) DEFAULT NULL",
                            $field
                        ));
                    }
                }
            }

            // Reapply increase length of password field to accomodate longer hashes
            // fix as it looks like we created an error in the 2.3.1 migration. RE #1648
            Symphony::Database()->query("ALTER TABLE `tbl_authors` CHANGE `password` `password` VARCHAR( 150 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL");

            // Update the version information
            return parent::upgrade();
        }

        public static function preUpdateNotes()
        {
            return array(
                __("This release fixes a bug with the 'Redirect to 404 page when no results are found' setting on the Sections Datasource. Unfortunately you will need to resave your datasources to activate this fix.")
            );
        }

    }
