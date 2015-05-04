<?php

    Class migration_231 extends Migration
    {

        public static function getVersion()
        {
            return '2.3.1';
        }

        public static function getReleaseNotes()
        {
            return 'http://getsymphony.com/download/releases/version/2.3.1/';
        }

        public static function upgrade()
        {
            // 2.3.1dev
            if(version_compare(self::$existing_version, '2.3.1dev', '<=')) {

                // Remove unused setting from the Author field
                $author_table = 'tbl_fields_author';
                if(Symphony::Database()->tableContainsField($author_table, 'allow_author_change')) {
                    Symphony::Database()->query("ALTER TABLE `$author_table` DROP `allow_author_change`;");
                }

                // Author Types [#1219]
                if(!Symphony::Database()->tableContainsField($author_table, 'author_types')) {
                    Symphony::Database()->query("ALTER TABLE `$author_table` ADD `author_types` VARCHAR(255) DEFAULT NULL;");
                }

                // Entries Modification Date [#983]
                if(!Symphony::Database()->tableContainsField('tbl_entries', 'modification_date')) {
                    Symphony::Database()->query("ALTER TABLE `tbl_entries` ADD `modification_date` DATETIME NOT NULL;");
                    Symphony::Database()->query("ALTER TABLE `tbl_entries` ADD KEY `modification_date` (`modification_date`)");
                    Symphony::Database()->query("UPDATE `tbl_entries` SET modification_date = creation_date;");
                }

                if(!Symphony::Database()->tableContainsField('tbl_entries', 'modification_date_gmt')) {
                    Symphony::Database()->query("ALTER TABLE `tbl_entries` ADD `modification_date_gmt` DATETIME NOT NULL;");
                    Symphony::Database()->query("ALTER TABLE `tbl_entries` ADD KEY `modification_date_gmt` (`modification_date_gmt`)");
                    Symphony::Database()->query("UPDATE `tbl_entries` SET modification_date_gmt = creation_date_gmt;");
                }

                // Cleanup #977, remove `entry_order` & `entry_order_direction` from `tbl_sections`
                if(Symphony::Database()->tableContainsField('tbl_sections', 'entry_order')) {
                    Symphony::Database()->query("ALTER TABLE `tbl_sections` DROP `entry_order`;");
                }

                if(Symphony::Database()->tableContainsField('tbl_sections', 'entry_order_direction')) {
                    Symphony::Database()->query("ALTER TABLE `tbl_sections` DROP `entry_order_direction`;");
                }
            }

            if(version_compare(self::$existing_version, '2.3.1RC1', '<=')) {
                // Add Security Rules from 2.2 to .htaccess
                try {
                    $htaccess = file_get_contents(DOCROOT . '/.htaccess');

                    if($htaccess !== false && preg_match('/### SECURITY - Protect crucial files/', $htaccess)){
                        $security = '
            ### SECURITY - Protect crucial files
            RewriteRule ^manifest/(.*)$ - [F]
            RewriteRule ^workspace/(pages|utilities)/(.*)\.xsl$ - [F]
            RewriteRule ^(.*)\.sql$ - [F]
            RewriteRule (^|/)\. - [F]

            ### DO NOT APPLY RULES WHEN REQUESTING "favicon.ico"';

                        $htaccess = str_replace('### SECURITY - Protect crucial files.*### DO NOT APPLY RULES WHEN REQUESTING "favicon.ico"', $security, $htaccess);
                        file_put_contents(DOCROOT . '/.htaccess', $htaccess);
                    }
                }
                catch (Exception $ex) {}

                // Increase length of password field to accomodate longer hashes
                Symphony::Database()->query("ALTER TABLE `tbl_authors` CHANGE `password` `password` VARCHAR( 150 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL");
            }

            // Update the version information
            return parent::upgrade();
        }

    }
