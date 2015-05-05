<?php

Class migration_270 extends Migration
{

    static function getVersion()
    {
        return '2.7.0';
    }

    static function getReleaseNotes()
    {
        return 'http://getsymphony.com/download/releases/version/2.7.0/';
    }

    static function upgrade()
    {
        // Add content types to configuration
        Symphony::Configuration()->setArray(array(

            'content_types' => array(

                'html'  => 'text/html',
                'json'  => 'application/json',
                'txt'   => 'text/plain',
                'xhtml' => 'application/xhtml+xml',
                'xml'   => 'text/xml'
            )
        ));

        // Update the version information
        return parent::upgrade();
    }
}
