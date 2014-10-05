<?php

    // Find out where we are:
    define('DOCROOT', rtrim(dirname(__FILE__), '\\/'));

    // Is there vendor autoloader?
    if (@file_exists(DOCROOT . '/vendor/autoload.php')) {
        require_once DOCROOT . '/vendor/autoload.php';
    } else {
        require_once DOCROOT . '/symphony/lib/boot/autoload.php';
    }
    
    // Include the boot script:
    require_once DOCROOT . '/symphony/lib/boot/bundle.php';

    // Begin Symphony proper:
    symphony(
        isset($_GET['mode'])
            ? $_GET['mode']
            : null
    );
