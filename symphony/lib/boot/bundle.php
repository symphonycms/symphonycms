<?php

    /**
     * @package boot
     */

    // Set appropriate error reporting:
    error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED & ~E_STRICT);

    // Turn off old-style magic:
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
        Symphony::initialiseErrorHandler();
        Symphony::initialiseDatabase();
        Symphony::initialiseExtensionManager();

        // Handle custom admin paths, #702
        $adminPath = Symphony::Configuration()->get('admin-path', 'symphony');
        $adminPath = (is_null($adminPath)) ? 'symphony' :  $adminPath;
        if (isset($_GET['symphony-page']) && strpos($_GET['symphony-page'], $adminPath, 0) === 0) {
            $_GET['symphony-page'] = preg_replace('%^' . preg_quote($adminPath) . '\/%', '', $_GET['symphony-page'], 1);

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
