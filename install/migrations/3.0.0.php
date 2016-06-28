<?php
namespace SymphonyCms\Installer\Migrations;

use SymphonyCms\Installer\Lib\Migration;
use Symphony;
use Exception;

class migration_300 extends Migration
{
    public static function getVersion()
    {
        return '3.0.0-alpha.1';
    }

    public static function getReleaseNotes()
    {
        return 'http://getsymphony.com/download/releases/version/3.0.0/';
    }

    public static function upgrade()
    {
        // Add the delegate order field, RE: #2354
        try {
            Symphony::Database()->query("
                ALTER TABLE `tbl_extensions_delegates`
                ADD `order` INT(11) SIGNED NOT NULL DEFAULT '0',
            ");
        } catch (Exception $ex) {
        }

        // Add in new Session configuration options, RE: #2135
        Symphony::Configuration()->setArray(array(
            'session' => array(
                'admin_session_name' => 'symphony_admin',
                'public_session_name' => 'symphony_public',
                'admin_session_expires' => '2 weeks',
                'public_session_expires' => '2 weeks',
                'session_gc_probability' => '1',
                'session_gc_divisor' => Symphony::Configuration()->get('session_gc_divisor', 'symphony')
            )
        ));

        // Update the version information
        return parent::upgrade();
    }
}
