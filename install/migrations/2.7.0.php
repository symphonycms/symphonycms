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

                'html'  => 'text/html; charset=utf-8',
                'json'  => 'application/json; charset=utf-8',
                'text'  => 'text/plain; charset=utf-8',
                'xhtml' => 'application/xhtml+xml; charset=utf-8',
                'xml'   => 'text/xml; charset=utf-8'
            )
        ));

        // Update the version information
        return parent::upgrade();
    }
}
