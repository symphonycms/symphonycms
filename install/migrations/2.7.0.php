<?php

    class migration_270 extends Migration
    {
        public static function getVersion()
        {
            return '2.7.0';
        }

        public static function getReleaseNotes()
        {
            return 'http://getsymphony.com/download/releases/version/2.7.0/';
        }

        public static function upgrade()
        {
            // Update `pre_populate` replace "yes" with "now"
            // Update `pre_populate` replace "no" or NULL with ""
            try {
                Symphony::Database()->query('
                        UPDATE `tbl_fields_date` SET `pre_populate` = "now" WHERE `pre_populate`= "yes";
                ');
                Symphony::Database()->query('
                        UPDATE `tbl_fields_date` SET `pre_populate` = NULL WHERE `pre_populate` = "no" OR `pre_populate` = ""
                ');
            } catch (Exception $ex) {
                // ignore
            }

            // Add dates and author columns
            $now = DateTimeObj::get('Y-m-d H:i:s');
            $nowGMT = DateTimeObj::getGMT('Y-m-d H:i:s');
            Symphony::Database()->query("
                ALTER TABLE `tbl_sections`
                    ADD COLUMN `author_id` int(11) unsigned NOT NULL DEFAULT 1,
                    ADD COLUMN `modification_author_id` int(11) unsigned NOT NULL DEFAULT 1,
                    ADD COLUMN `creation_date` datetime NOT NULL DEFAULT '$now',
                    ADD COLUMN `creation_date_gmt` datetime NOT NULL DEFAULT '$nowGMT',
                    ADD COLUMN `modification_date` datetime NOT NULL DEFAULT '$now',
                    ADD COLUMN `modification_date_gmt` datetime NOT NULL DEFAULT '$nowGMT',
                    ADD KEY `creation_date` (`creation_date`),
                    ADD KEY `creation_date_gmt` (`creation_date_gmt`),
                    ADD KEY `modification_date` (`modification_date`),
                    ADD KEY `modification_date_gmt` (`modification_date_gmt`);
            ");
            Symphony::Database()->query("
                ALTER TABLE `tbl_entries`
                    ADD COLUMN `modification_author_id` int(11) unsigned NOT NULL DEFAULT 1
                        AFTER `author_id`
            ");

            // Update the version information
            return parent::upgrade();
        }
    }
