<?php

    Class migration_240 extends Migration
    {

        public static $publish_filtering_disabled = false;

        public static function getVersion()
        {
            return '2.4';
        }

        public static function getReleaseNotes()
        {
            return 'http://getsymphony.com/download/releases/version/2.4/';
        }

        public static function upgrade()
        {
            // [#702] Update to include Admin Path configuration
            if(version_compare(self::$existing_version, '2.4beta2', '<=')) {
                // Add missing config value for index view string length
                Symphony::Configuration()->set('cell_truncation_length', '75', 'symphony');
                // Add admin-path to configuration
                Symphony::Configuration()->set('admin-path', 'symphony', 'symphony');
            }

            // [#1626] Update all tables to be UTF-8 encoding/collation
            // @link https://gist.github.com/michael-e/5789168
            $tables = Symphony::Database()->fetch("SHOW TABLES");
            if(is_array($tables) && !empty($tables)){
                foreach($tables as $table){
                    $table = current($table);

                    // If it's not a Symphony table, ignore it
                    if(!preg_match('/^' . Symphony::Database()->getPrefix() . '/', $table)) continue;

                    Symphony::Database()->query(sprintf(
                        "ALTER TABLE `%s` CHARACTER SET utf8 COLLATE utf8_unicode_ci",
                        $table
                    ));
                    Symphony::Database()->query(sprintf(
                        "ALTER TABLE `%s` CONVERT TO CHARACTER SET utf8 COLLATE utf8_unicode_ci",
                        $table
                    ));
                }
            }

            // [#1420] Change date field to be a varchar instead of an ENUM to support prepopulation
            try {
                Symphony::Database()->query('
                    ALTER TABLE `tbl_fields_date`
                    CHANGE `pre_populate` `pre_populate` varchar(80) COLLATE utf8_unicode_ci DEFAULT NULL;
                ');
            }
            catch (Exception $ex) {}

            // [#1997] Add filtering column to the Sections table
            if(!Symphony::Database()->tableContainsField('tbl_sections', 'filter')) {
                Symphony::Database()->query("
                    ALTER TABLE `tbl_sections`
                    ADD `filter` enum('yes','no') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'yes';
                ");
            }

            $installed_extensions = Symphony::ExtensionManager()->listInstalledHandles();
            if(in_array('publishfiltering', $installed_extensions)) {
                Symphony::ExtensionManager()->uninstall('publishfiltering');
                self::$publish_filtering_disabled = true;
            }

            // [#1874] XSRF/CRSF options
            if(version_compare(self::$existing_version, '2.4beta3', '<=')) {
                // How long should a XSRF token be valid
                Symphony::Configuration()->set('token_lifetime', '15 minutes', 'symphony');
                // Should the token be removed as soon as it has been used?
                Symphony::Configuration()->set('invalidate_tokens_on_request', false, 'symphony');
            }

            // [#1874] XSRF/CRSF options
            if(version_compare(self::$existing_version, '2.4RC1', '<=')) {
                // On update, disable XSRF for compatibility purposes
                Symphony::Configuration()->set('enable_xsrf', 'no', 'symphony');
            }

            // Update the version information
            return parent::upgrade();
        }

        public static function preUpdateNotes()
        {
            return array(
                __("Symphony 2.4 is a major release that contains breaking changes from previous versions. It is highly recommended to review the releases notes and make a complete backup of your installation before updating as these changes may affect the functionality of your site."),
                __("This release will automatically convert all existing Symphony database tables to %s.", array("<code>utf8_unicode_ci</code>")),
                __("CRSF has been implemented in this release and is turned off by default. To enable for the backend, change %s from %s to %s in your configuration. To enable for the frontend, update the XSS Filter extension and follow the README.", array('<code>enable_xsrf</code>', '<code>no</code>', '<code>yes</code>'))
            );
        }

        public static function postUpdateNotes()
        {
            $notes = array();

            if(self::$publish_filtering_disabled) {
                $notes[] = __("As Symphony 2.4 adds the Publish Filtering extension into the core, the standalone extension has been uninstalled. You can remove it from your installation at any time.");
            }

            $notes[] = __("The Dynamic XML Datasource has been deprecated from the core in favour of the %s extension. You will no longer be able to create new Dynamic XML Data Sources from the Symphony Data Source editor. Existing Dynamic XML Data Sources can be edited and will continue to function until Symphony 2.7.0.", array(
                "<a href='http://symphonyextensions.com/extensions/remote_datasource/'>Remote Datasource</a>"
            ));

            return $notes;
        }

    }
