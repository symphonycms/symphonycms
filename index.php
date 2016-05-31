<?php

    // Find out where we are:
    define('DOCROOT', __DIR__);

    // Propagate this change to all executables:
    chdir(DOCROOT);

    // Include autoloader:
    require_once DOCROOT . '/vendor/autoload.php';

    // Include the boot script:
    require_once DOCROOT . '/symphony/lib/boot/bundle.php';

    // Begin Symphony proper:
    symphony(
        isset($_GET['mode'])
            ? $_GET['mode']
            : null
    );
