<?php

    /**
     * @package boot
     */

    require_once DOCROOT . '/symphony/lib/boot/func.utilities.php';
    require_once DOCROOT . '/symphony/lib/boot/defines.php';
    require_once CORE . '/class.symphony.php';

    if(!defined('PHP_VERSION_ID'))
    {
        $version = PHP_VERSION;

        /**
         * For versions of PHP below 5.2.7, the PHP_VERSION_ID constant, doesn't
         * exist, so this will just mimic the functionality as described on the
         * PHP documentation
         *
         * @link http://php.net/manual/en/function.phpversion.php
         * @var integer
         */
        define('PHP_VERSION_ID', ($version{0} * 10000 + $version{2} * 100 + $version{4}));
    }

    // Set appropriate error reporting:
    error_reporting(
        PHP_VERSION_ID >= 50300
            ? E_ALL & ~E_NOTICE & ~E_DEPRECATED & ~E_STRICT
            : E_ALL & ~E_NOTICE
    );

    // Turn of old-style magic:
    ini_set('magic_quotes_runtime', false);

    // Redirect to installer if it exists
    if (!file_exists(CONFIG))
    {
        $bInsideInstaller = (bool)preg_match('%(/|\\\\)install(/|\\\\)index.php$%', $_SERVER['SCRIPT_FILENAME']);

        if (!$bInsideInstaller && Symphony::isInstallerAvailable()) {
            header(sprintf('Location: %s/install/', URL));
            exit;
        }

        else if(!$bInsideInstaller) {
            die('<h2>Error</h2><p>Could not locate Symphony configuration file. Please check <code>manifest/config.php</code> exists.</p>');
        }
    }
    else {
        // Load configuration file:
        include CONFIG;
        Symphony::initialiseConfiguration($settings);
        Symphony::initialiseDatabase();
        Symphony::initialiseExtensionManager();

        // Handle custom admin paths, #702
        $adminPath = Symphony::Configuration()->get('admin-path', 'symphony');
        $adminPath = (is_null($adminPath)) ? 'symphony' :  $adminPath;
        if (strpos($_GET['symphony-page'], $adminPath, 0) === 0) {
            $_GET['symphony-page'] = str_replace($adminPath . '/', '', $_GET['symphony-page']);

            if ($_GET['symphony-page'] == '') {
                unset($_GET['symphony-page']);
            }

            $_GET['mode'] = $_REQUEST['mode'] = 'administration';
        }

        /**
         * Returns the URL + /symphony. This should be used whenever the a developer
         * wants to link to the Symphony root
         * @since Symphony 2.2
         * @var string
         */
        define_safe('SYMPHONY_URL', URL . '/' . $adminPath);

        /**
         * Overload the default Symphony launcher logic.
         * @delegate ModifySymphonyLauncher
         * @since Symphony 2.5.0
         * @param string $context
         * '/all/'
         */
        Symphony::ExtensionManager()->notifyMembers(
            'ModifySymphonyLauncher', '/all/'
        );

        // Use default launcher:
        if (defined('SYMPHONY_LAUNCHER') === false)
        {
            define('SYMPHONY_LAUNCHER', 'symphony_launcher');
        }
    }