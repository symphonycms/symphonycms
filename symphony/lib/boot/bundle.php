<?php

    /**
     * @package boot
     */

    // Redirect to installer if it exists
    if (!file_exists(CONFIG)) {
        $bInsideInstaller = (bool)preg_match('%(/|\\\\)install(/|\\\\)index.php$%', server_safe('SCRIPT_FILENAME'));

        if (!$bInsideInstaller && Symphony::isInstallerAvailable()) {
            redirect(URL . '/install/');
            exit;
        } elseif (!$bInsideInstaller) {
            die('<h2>Error</h2><p>Could not locate Symphony configuration file. Please check <code>manifest/config.php</code> exists.</p>');
        }
    } else {
        // Start with the Exception handler disable before authentication.
        // This limits the possibility of leaking infos.
        ExceptionHandler::$enabled = false;

        // Load configuration file:
        include CONFIG;
        Symphony::initialiseConfiguration($settings);

        // Report all errors
        if (Symphony::Configuration()->get('error_reporting_all', 'symphony') === 'yes') {
            error_reporting(E_ALL);
        }

        // Handle custom admin paths, #702
        $adminPath = Symphony::Configuration()->get('admin-path', 'symphony');
        $adminPath = (is_null($adminPath)) ? 'symphony' :  $adminPath;
        // getCurrentPage() always starts with / #2522
        $adminRegExp = '%^\/' . preg_quote($adminPath) . '\/%';

        if (preg_match($adminRegExp, getCurrentPage()) === 1) {
            $_GET['symphony-page'] = preg_replace($adminRegExp, '', getCurrentPage(), 1);

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
         * Returns the app mode
         * @since Symphony 3.0.0
         */
        define_safe('APP_MODE', (
            isset($_GET['mode'])
                ? $_GET['mode']
                : 'frontend'
        ));

        // Set up error handler
        Symphony::initialiseErrorHandler();

        // Set up database and extensions
        if (!defined('SYMPHONY_LAUNCHER_NO_DB')) {
            Symphony::initialiseDatabase();
            Symphony::initialiseExtensionManager();

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
        }

        // Use default launcher:
        if (defined('SYMPHONY_LAUNCHER') === false) {
            define('SYMPHONY_LAUNCHER', 'symphony_launcher');
        }
    }
