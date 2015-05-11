<?php

    // Find out where we are:
    define('DOCROOT', __DIR__);
    define('APP_MODE', (
        isset($_GET['mode'])
        ? $_GET['mode']
        : 'frontend'
    ));

    // Include autoloader:
    require_once DOCROOT . '/vendor/autoload.php';

    // Include the boot script:
    require_once DOCROOT . '/symphony/lib/boot/bundle.php';

    // Begin Symphony proper:
    symphony(APP_MODE);
