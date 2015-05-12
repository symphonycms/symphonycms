<?php

    // Find out where we are:
    define('DOCROOT', __DIR__);
    define('APP_MODE', (
        isset($_GET['mode'])
        ? $_GET['mode']
        : 'frontend'
    ));

    // Include autoloader:
    $autoloader = require_once DOCROOT . '/vendor/autoload.php';

    // Include the boot script:
    require_once DOCROOT . '/symphony/lib/boot/bundle.php';

    /**
     * When the composer autoloader has been loaded.
     *
     * @delegate ComposerReady
     * @param string $context
     *  '/all/'
     * @param Composer\Autoload\ClassLoader $autoloader
     *  The Composer autoloader.
     * @since 3.0.0
     */
    Symphony::ExtensionManager()->notifyMembers('ComposerReady', '/all/', [
        'autoloader' => $autoloader
    ]);

    // Begin Symphony proper:
    symphony(APP_MODE);
