<?php
    $settings = [
        // DO INSTALL !
        'action' => [
            'install' => true,
        ],
        // Remove if you want to selected the language
        'lang' => 'en',
        // Posts fields
        'fields' => [
            'general' => [
                'sitename' => 'Symphony CI Test',
            ],
            'database' => [
                'db' => 'symphony_test',
                'user' => 'root',
                'password' => '',
                'host' => 'localhost',
                'port' => '3306',
                'tbl_prefix' => 'sym_',
            ],
            'user' => [
                'username' => 'root',
                'password' => 'symphony',
                'firstname' => 'test',
                'lastname' => 'test',
                'email' => 'test@example.org',
            ],
        ],
    ];
