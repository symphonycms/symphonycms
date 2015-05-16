<?php

    Class migration_222 extends Migration
    {

        public static function getVersion()
        {
            return '2.2.2';
        }

        public static function getReleaseNotes()
        {
            return 'http://getsymphony.com/download/releases/version/2.2.2/';
        }

        public static function upgrade()
        {
            // 2.2.2 Beta 1
            if(version_compare(self::$existing_version, '2.2.2 Beta 1', '<=')) {
                Symphony::Configuration()->set('version', '2.2.2 Beta 1', 'symphony');

                // Rename old variations of the query_caching configuration setting
                if(Symphony::Configuration()->get('disable_query_caching', 'database')){
                    $value = (Symphony::Configuration()->get('disable_query_caching', 'database') == "no") ? "on" : "off";

                    Symphony::Configuration()->set('query_caching', $value, 'database');
                    Symphony::Configuration()->remove('disable_query_caching', 'database');
                }

                // Add Session GC collection as a configuration parameter
                Symphony::Configuration()->set('session_gc_divisor', '10', 'symphony');

                // Save the manifest changes
                Symphony::Configuration()->write();
            }

            // 2.2.2 Beta 2
            if(version_compare(self::$existing_version, '2.2.2 Beta 2', '<=')) {
                Symphony::Configuration()->set('version', '2.2.2 Beta 2', 'symphony');
                try {
                    // Change Textareas to be MEDIUMTEXT columns
                    $textarea_tables = Symphony::Database()->fetchCol("field_id", "SELECT `field_id` FROM `tbl_fields_textarea`");

                    foreach($textarea_tables as $field) {
                        Symphony::Database()->query(sprintf(
                            "ALTER TABLE `tbl_entries_data_%d` CHANGE `value` `value` MEDIUMTEXT, CHANGE `value_formatted` `value_formatted` MEDIUMTEXT",
                            $field
                        ));
                        Symphony::Database()->query(sprintf('OPTIMIZE TABLE `tbl_entries_data_%d`', $field));
                    }
                }
                catch(Exception $ex) {}

                // Save the manifest changes
                Symphony::Configuration()->write();
            }

            // Update the version information
            return parent::upgrade();
        }

    }
