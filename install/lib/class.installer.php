<?php

    class Installer extends Administration
    {
        /**
         * Override the default Symphony constructor to initialise the Log, Config
         * and Database objects for installation/update. This allows us to use the
         * normal accessors.
         */
        protected function __construct()
        {
            self::$Profiler = Profiler::instance();
            self::$Profiler->sample('Engine Initialisation');

            if (get_magic_quotes_gpc()) {
                General::cleanArray($_SERVER);
                General::cleanArray($_COOKIE);
                General::cleanArray($_GET);
                General::cleanArray($_POST);
            }

            // Include the default Config for installation.
            include(INSTALL . '/includes/config_default.php');
            static::initialiseConfiguration($settings);

            // Initialize date/time
            define_safe('__SYM_DATE_FORMAT__', self::Configuration()->get('date_format', 'region'));
            define_safe('__SYM_TIME_FORMAT__', self::Configuration()->get('time_format', 'region'));
            define_safe('__SYM_DATETIME_FORMAT__', __SYM_DATE_FORMAT__ . self::Configuration()->get('datetime_separator', 'region') . __SYM_TIME_FORMAT__);
            DateTimeObj::setSettings(self::Configuration()->get('region'));

            // Initialize Language, Logs and Database
            static::initialiseLang();
            static::initialiseLog(INSTALL_LOGS . '/install');
            static::initialiseDatabase();

            // Initialize error handlers
            GenericExceptionHandler::initialise(Symphony::Log());
            GenericErrorHandler::initialise(Symphony::Log());
        }

        /**
         * This function returns an instance of the Installer
         * class. It is the only way to create a new Installer, as
         * it implements the Singleton interface
         *
         * @return Installer
         */
        public static function instance()
        {
            if (!(self::$_instance instanceof Installer)) {
                self::$_instance = new Installer;
            }

            return self::$_instance;
        }

        /**
         * Initialises the language by looking at the `lang` key,
         * passed via GET or POST
         */
        public static function initialiseLang()
        {
            $lang = !empty($_REQUEST['lang']) ? preg_replace('/[^a-zA-Z\-]/', null, $_REQUEST['lang']) : 'en';
            Lang::initialize();
            Lang::set($lang, false);
        }

        /**
         * Overrides the default `initialiseLog()` method and writes
         * logs to manifest/logs/install
         *
         * @param null $filename
         * @return boolean|void
         * @throws Exception
         */
        public static function initialiseLog($filename = null)
        {
            if (is_dir(INSTALL_LOGS) || General::realiseDirectory(INSTALL_LOGS,
                self::Configuration()->get('write_mode', 'directory'))
            ) {
                return parent::initialiseLog($filename);
            }

            return;
        }

        /**
         * Overrides the default `initialiseDatabase()` method
         * This allows us to still use the normal accessor
         */
        public static function initialiseDatabase()
        {
            self::setDatabase();
        }

        public function run()
        {
            // Make sure a log file is available
            if (is_null(Symphony::Log())) {
                self::__render(new InstallerPage('missing-log'));
            }

            // Check essential server requirements
            $errors = self::__checkRequirements();
            if (!empty($errors)) {
                Symphony::Log()->error('Installer - Missing requirements.');

                foreach ($errors as $err) {
                    Symphony::Log()->error(
                        sprintf('Requirement - %s', $err['msg'])
                    );
                }

                self::__render(new InstallerPage('requirements', array(
                    'errors'=> $errors
                )));
            }

            // If language is not set and there is language packs available, show language selection pages
            if (!isset($_POST['lang']) && count(Lang::getAvailableLanguages(false)) > 1) {
                self::__render(new InstallerPage('languages'));
            }

            // Check for configuration errors and, if there are no errors, install Symphony!
            if (isset($_POST['fields'])) {
                $errors = self::__checkConfiguration();
                if (!empty($errors)) {
                    Symphony::Log()->error('Installer - Wrong configuration.');

                    foreach ($errors as $err) {
                        Symphony::Log()->error(
                            sprintf('Configuration - %s', $err['msg'])
                        );
                    }
                } else {
                    $disabled_extensions = self::__install();

                    self::__render(new InstallerPage('success', array(
                        'disabled-extensions' => $disabled_extensions
                    )));
                }
            }

            // Display the Installation page
            self::__render(new InstallerPage('configuration', array(
                'errors' => $errors,
                'default-config' => Symphony::Configuration()->get()
            )));
        }

        /**
         * This function checks the server can support a Symphony installation.
         * It checks that PHP is 5.2+, MySQL, Zlib, LibXML, XSLT modules are enabled
         * and a `install.sql` file exists.
         * If any of these requirements fail the installation will not proceed.
         *
         * @return array
         *  An associative array of errors, with `msg` and `details` keys
         */
        private static function __checkRequirements()
        {
            $errors = array();

            // Check for PHP 5.2+
            if (version_compare(phpversion(), '5.3', '<=')) {
                $errors[] = array(
                    'msg' => __('PHP Version is not correct'),
                    'details' => __('Symphony requires %1$s or greater to work, however version %2$s was detected.', array('<code><abbr title="PHP: Hypertext Pre-processor">PHP</abbr> 5.3</code>', '<code>' . phpversion() . '</code>'))
                );
            }

            // Make sure the install.sql file exists
            if (!file_exists(INSTALL . '/includes/install.sql') || !is_readable(INSTALL . '/includes/install.sql')) {
                $errors[] = array(
                    'msg' => __('Missing install.sql file'),
                    'details'  => __('It appears that %s is either missing or not readable. This is required to populate the database and must be uploaded before installation can commence. Ensure that PHP has read permissions.', array('<code>install.sql</code>'))
                );
            }

            // Is MySQL available?
            if (!function_exists('mysqli_connect')) {
                $errors[] = array(
                    'msg' => __('MySQLi extension not present'),
                    'details'  => __('Symphony requires PHP to be configured with MySQLi to work.')
                );
            }

            // Is ZLib available?
            if (!extension_loaded('zlib')) {
                $errors[] = array(
                    'msg' => __('ZLib extension not present'),
                    'details' => __('Symphony uses the ZLib compression library for log rotation.')
                );
            }

            // Is libxml available?
            if (!extension_loaded('xml') && !extension_loaded('libxml')) {
                $errors[] = array(
                    'msg' => __('XML extension not present'),
                    'details'  => __('Symphony needs the XML extension to pass data to the site frontend.')
                );
            }

            // Is libxslt available?
            if (!extension_loaded('xsl') && !extension_loaded('xslt') && !function_exists('domxml_xslt_stylesheet')) {
                $errors[] = array(
                    'msg' => __('XSLT extension not present'),
                    'details'  => __('Symphony needs an XSLT processor such as %s or Sablotron to build pages.', array('Lib<abbr title="eXtensible Stylesheet Language Transformation">XSLT</abbr>'))
                );
            }

            // Is json_encode available?
            if (!function_exists('json_decode')) {
                $errors[] = array(
                    'msg' => __('JSON functionality is not present'),
                    'details'  => __('Symphony uses JSON functionality throughout the backend for translations and the interface.')
                );
            }

            // Cannot write to root folder.
            if (!is_writable(DOCROOT)) {
                $errors['no-write-permission-root'] = array(
                    'msg' => 'Root folder not writable: ' . DOCROOT,
                    'details' => __('Symphony does not have write permission to the root directory. Please modify permission settings on %s. This can be reverted once installation is complete.', array('<code>' . DOCROOT . '</code>'))
                );
            }

            // Cannot write to workspace
            if (is_dir(DOCROOT . '/workspace') && !is_writable(DOCROOT . '/workspace')) {
                $errors['no-write-permission-workspace'] = array(
                    'msg' => 'Workspace folder not writable: ' . DOCROOT . '/workspace',
                    'details' => __('Symphony does not have write permission to the existing %1$s directory. Please modify permission settings on this directory and its contents to allow this, such as with a recursive %2$s command.', array('<code>/workspace</code>', '<code>chmod -R</code>'))
                );
            }

            return $errors;
        }

        /**
         * This function checks the current Configuration (which is the values entered
         * by the user on the installation form) to ensure that `/symphony` and `/workspace`
         * folders exist and are writable and that the Database credentials are correct.
         * Once those initial checks pass, the rest of the form values are validated.
         *
         * @return array An associative array of errors if something went wrong, otherwise an empty array.
         */
        private static function __checkConfiguration()
        {
            $errors = array();
            $fields = $_POST['fields'];

            // Testing the database connection
            try {
                Symphony::Database()->connect(
                    $fields['database']['host'],
                    $fields['database']['user'],
                    $fields['database']['password'],
                    $fields['database']['port'],
                    $fields['database']['db']
                );
            } catch (DatabaseException $e) {
                // Invalid credentials
                // @link http://dev.mysql.com/doc/refman/5.5/en/error-messages-server.html
                if ($e->getDatabaseErrorCode() === 1044 || $e->getDatabaseErrorCode() === 1045) {
                    $errors['database-invalid-credentials'] = array(
                        'msg' => 'Database credentials were denied',
                        'details' => __('Symphony was unable to access the database with these credentials.')
                    );
                }
                // Connection related
                else {
                    $errors['no-database-connection'] = array(
                        'msg' => 'Could not establish database connection.',
                        'details' => __('Symphony was unable to establish a valid database connection. You may need to modify host or port settings.')
                    );
                }
            }

            try {
                // Check the database table prefix is legal. #1815
                if (!preg_match('/^[0-9a-zA-Z\$_]*$/', $fields['database']['tbl_prefix'])) {
                    $errors['database-table-prefix']  = array(
                        'msg' => 'Invalid database table prefix: ‘' . $fields['database']['tbl_prefix'] . '’',
                        'details' =>  __('The table prefix %s is invalid. The table prefix must only contain numbers, letters or underscore characters.', array('<code>' . $fields['database']['tbl_prefix'] . '</code>'))
                    );
                }
                // Check the database credentials
                elseif (Symphony::Database()->isConnected()) {
                    // Incorrect MySQL version
                    $version = Symphony::Database()->fetchVar('version', 0, "SELECT VERSION() AS `version`;");
                    if (version_compare($version, '5.5', '<')) {
                        $errors['database-incorrect-version']  = array(
                            'msg' => 'MySQL Version is not correct. '. $version . ' detected.',
                            'details' => __('Symphony requires %1$s or greater to work, however version %2$s was detected. This requirement must be met before installation can proceed.', array('<code>MySQL 5.5</code>', '<code>' . $version . '</code>'))
                        );
                    }
                    else {
                        // Existing table prefix
                        if (Symphony::Database()->tableExists($fields['database']['tbl_prefix'] . '%')) {
                            $errors['database-table-prefix'] = array(
                                'msg'     => 'Database table prefix clash with ‘' . $fields['database']['db'] . '’',
                                'details' => __('The table prefix %s is already in use. Please choose a different prefix to use with Symphony.', array(

                                    '<code>' . $fields['database']['tbl_prefix'] . '</code>'
                                ))
                            );
                        }
                    }
                }
            } catch (DatabaseException $e) {
                $errors['unknown-database']  = array(
                        'msg' => 'Database ‘' . $fields['database']['db'] . '’ not found.',
                        'details' =>  __('Symphony was unable to connect to the specified database.')
                    );
            }

            // Website name not entered
            if (trim($fields['general']['sitename']) === '') {
                $errors['general-no-sitename']  = array(
                    'msg' => 'No sitename entered.',
                    'details' => __('You must enter a Site name. This will be shown at the top of your backend.')
                );
            }

            // Username Not Entered
            if (trim($fields['user']['username']) === '') {
                $errors['user-no-username']  = array(
                    'msg' => 'No username entered.',
                    'details' => __('You must enter a Username. This will be your Symphony login information.')
                );
            }

            // Password Not Entered
            if (trim($fields['user']['password']) === '') {
                $errors['user-no-password']  = array(
                    'msg' => 'No password entered.',
                    'details' => __('You must enter a Password. This will be your Symphony login information.')
                );
            }

            // Password mismatch
            elseif ($fields['user']['password'] !== $fields['user']['confirm-password']) {
                $errors['user-password-mismatch']  = array(
                    'msg' => 'Passwords did not match.',
                    'details' => __('The password and confirmation did not match. Please retype your password.')
                );
            }

            // No Name entered
            if (trim($fields['user']['firstname']) === '' || trim($fields['user']['lastname']) === '') {
                $errors['user-no-name']  = array(
                    'msg' => 'Did not enter First and Last names.',
                    'details' =>  __('You must enter your name.')
                );
            }

            // Invalid Email
            if (!preg_match('/^\w(?:\.?[\w%+-]+)*@\w(?:[\w-]*\.)+?[a-z]{2,}$/i', $fields['user']['email'])) {
                $errors['user-invalid-email']  = array(
                    'msg' => 'Invalid email address supplied.',
                    'details' =>  __('This is not a valid email address. You must provide an email address since you will need it if you forget your password.')
                );
            }

            // Admin path not entered
            if (trim($fields['symphony']['admin-path']) === '') {
                $errors['no-symphony-path']  = array(
                    'msg' => 'No Symphony path entered.',
                    'details' => __('You must enter a path for accessing Symphony, or leave the default. This will be used to access Symphony\'s backend.')
                );
            }

            return $errors;
        }

        /**
         * If something went wrong, the `__abort` function will write an entry to the Log
         * file and display the failure page to the user.
         *
         * @todo: Resume installation after an error has been fixed.
         * @param string $message
         * @param integer $start
         */
        protected static function __abort($message, $start)
        {
            Symphony::Log()->error($message);
            Symphony::Log()->error(sprintf('INSTALLATION ABORTED: Execution Time - %d sec (%s)',
                max(1, time() - $start),
                date('d.m.y H:i:s')
            ));

            self::__render(new InstallerPage('failure'));
        }

        private static function __install()
        {
            $start = microtime(true);
            $fields = $_POST['fields'];
            Symphony::Log()->info('INSTALLATION PROCESS STARTED (' . DateTimeObj::get('c') . ')');

            // Configuration: Populating array
            $conf = Symphony::Configuration()->get();

            foreach ($conf as $group => $settings) {
                foreach ($settings as $key => $value) {
                    if (isset($fields[$group]) && isset($fields[$group][$key])) {
                        $conf[$group][$key] = $fields[$group][$key];
                    }
                }
            }

            // Don't like this. Find another way.
            $conf['directory']['write_mode'] = octdec($conf['directory']['write_mode']);
            $conf['file']['write_mode'] = octdec($conf['file']['write_mode']);

            Symphony::Configuration()->setArray($conf);

            $steps = [
                // Create database
                CreateDatabase::class,
                // Create manifest folder structure
                CreateManifest::class,
                // Write .htaccess
                CreateHtaccess::class,
                // Create or import the workspace
                Workspace::class,
                // Enable extensions
                EnableExtensions::class,
                // Enable language
                EnableLanguage::class
            ];

            try {
                foreach ($steps as $step) {
                    (new $step(Symphony::Log()->getLog()))->handle(Symphony::Configuration());
                }
            } catch (Exception $ex) {
                self::__abort($ex->getMessage(), $start);
            }

            // Writing configuration file
            Symphony::Log()->info('WRITING: Configuration File');
            if (!Symphony::Configuration()->write(CONFIG, Symphony::Configuration()->get('write_mode', 'file'))) {
                self::__abort(
                    'Could not create config file ‘' . CONFIG . '’. Check permission on /manifest.',
                    $start
                );
            }

            // Installation completed. Woo-hoo!
            Symphony::Log()->info(sprintf('INSTALLATION COMPLETED: Execution Time - %d sec (%s)',
                max(1, time() - $start),
                date('d.m.y H:i:s')
            ));

            return [];
        }

        protected static function __render(InstallerPage $page)
        {
            $output = $page->generate();

            header('Content-Type: text/html; charset=utf-8');
            echo $output;
            exit;
        }
    }
