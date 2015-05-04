<?php

    Class migration_220 extends Migration
    {

        public static function getVersion()
        {
            return '2.2';
        }

        public static function getReleaseNotes()
        {
            return 'http://getsymphony.com/download/releases/version/2.2/';
        }

        public static function upgrade()
        {
            // 2.2.0dev
            if(version_compare(self::$existing_version, '2.2.0dev', '<=')) {
                Symphony::Configuration()->set('version', '2.2dev', 'symphony');
                if(Symphony::Database()->tableContainsField('tbl_sections_association', 'cascading_deletion')) {
                    Symphony::Database()->query(
                        'ALTER TABLE `tbl_sections_association` CHANGE  `cascading_deletion` `hide_association` enum("yes","no") COLLATE utf8_unicode_ci NOT NULL DEFAULT "no";'
                    );

                    // Update Select table to include the new association field
                    Symphony::Database()->query('ALTER TABLE `tbl_fields_select` ADD `show_association` ENUM( "yes", "no" ) COLLATE utf8_unicode_ci NOT NULL DEFAULT "yes"');
                }

                if(Symphony::Database()->tableContainsField('tbl_authors', 'default_section')) {
                    // Allow Authors to be set to any area in the backend.
                    Symphony::Database()->query(
                        'ALTER TABLE `tbl_authors` CHANGE `default_section` `default_area` VARCHAR(255) COLLATE utf8_unicode_ci DEFAULT NULL;'
                    );
                }
                Symphony::Configuration()->write();
            }

            // 2.2.0
            if(version_compare(self::$existing_version, '2.2', '<=')) {
                Symphony::Configuration()->set('version', '2.2', 'symphony');
                Symphony::Configuration()->set('datetime_separator', ' ', 'region');
                Symphony::Configuration()->set('strict_error_handling', 'yes', 'symphony');

                // We've added UNIQUE KEY indexes to the Author, Checkbox, Date, Input, Textarea and Upload Fields
                // Time to go through the entry tables and make this change as well.
                $author = Symphony::Database()->fetchCol("field_id", "SELECT `field_id` FROM `tbl_fields_author`");
                $checkbox = Symphony::Database()->fetchCol("field_id", "SELECT `field_id` FROM `tbl_fields_checkbox`");
                $date = Symphony::Database()->fetchCol("field_id", "SELECT `field_id` FROM `tbl_fields_date`");
                $input = Symphony::Database()->fetchCol("field_id", "SELECT `field_id` FROM `tbl_fields_input`");
                $textarea = Symphony::Database()->fetchCol("field_id", "SELECT `field_id` FROM `tbl_fields_textarea`");
                $upload = Symphony::Database()->fetchCol("field_id", "SELECT `field_id` FROM `tbl_fields_upload`");

                $field_ids = array_merge($author, $checkbox, $date, $input, $textarea, $upload);

                foreach($field_ids as $id) {
                    $table = '`tbl_entries_data_' . $id . '`';

                    try {
                        Symphony::Database()->query("ALTER TABLE " . $table . " DROP INDEX `entry_id`");
                    }
                    catch (Exception $ex) {}

                    try {
                        Symphony::Database()->query("CREATE UNIQUE INDEX `entry_id` ON " . $table . " (`entry_id`)");
                        Symphony::Database()->query("OPTIMIZE TABLE " . $table);
                    }
                    catch (Exception $ex) {}
                }
            }

            // Update the version information
            return parent::upgrade();
        }

    }
