<?php
    $settings = array(
        // Remove if you want only to prefill
        'action' => array(
            'install' => true,
        ),
        // Remove if you want to selected the language
        'lang' => 'en',
        // Posts fields
        // Fields with default values can be removed
        'fields' => array(
            'general' => array(
                'sitename' => '',
            ),
            'symphony' => array(
                'admin-path' => '',
            ),
            'region' => array(
                'timezone' => '',
                'date_format' => '',
                'time_format' => '',
            ),
            'database' => array(
                'db' => '',
                'user' => '',
                'password' => '',
                'host' => '',
                'port' => '',
                'tbl_prefix' => '',
            ),
            'file' => array(
                'write_mode' => '',
            ),
            'directory' => array(
                'write_mode' => '',
            ),
            'user' => array(
                'username' => '',
                'password' => '',
                'firstname' => '',
                'lastname' => '',
                'email' => '',
            ),
        ),
    );
