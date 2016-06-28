<?php

/**
 * @package install
 */
namespace SymphonyCms\Installer\Lib;

use Administration;
use DatabaseException;
use DateTimeObj;
use Exception;
use General;
use GenericErrorHandler;
use GenericExceptionHandler;
use Lang;
use Profiler;
use Symphony;
use SymphonyCms\Installer\Steps;

class Installer extends Administration
{
    protected static $requirements;

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
        static::initialiseConfiguration(require_once(INSTALL . '/includes/config_default.php'));

        // Initialize date/time
        define_safe('__SYM_DATE_FORMAT__', self::Configuration()->get('date_format', 'region'));
        define_safe('__SYM_TIME_FORMAT__', self::Configuration()->get('time_format', 'region'));
        define_safe(
            '__SYM_DATETIME_FORMAT__',
            __SYM_DATE_FORMAT__ . self::Configuration()->get('datetime_separator', 'region') . __SYM_TIME_FORMAT__
        );
        DateTimeObj::setSettings(self::Configuration()->get('region'));

        // Initialize Language, Logs and Database
        static::initialiseLang();
        static::initialiseLog(INSTALL_LOGS . '/install');
        static::initialiseDatabase();

        // Initialize error handlers
        GenericExceptionHandler::initialise(Symphony::Log());
        GenericErrorHandler::initialise(Symphony::Log());

        self::$requirements = new Requirements();
    }

    /**
     * Initialises the language by looking at the `lang` key, passed via GET or POST
     */
    public static function initialiseLang()
    {
        $lang = !empty($_REQUEST['lang']) ? preg_replace('/[^a-zA-Z\-]/', null, $_REQUEST['lang']) : 'en';
        Lang::initialize();
        Lang::set($lang, false);
    }

    /**
     * Overrides the default `initialiseLog()` method and writes logs to  `manifest/logs/install`
     *
     * @param null $filename
     * @return boolean|void
     * @throws Exception
     */
    public static function initialiseLog($filename = null)
    {
        if (is_dir(INSTALL_LOGS) || General::realiseDirectory(
            INSTALL_LOGS,
            self::Configuration()->get('write_mode', 'directory')
        )
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

    /**
     * This function returns an instance of the Installer class. It is the only way
     * to create a new Installer, as it implements the Singleton interface
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
                'errors' => $errors
            )));
        }

        // If language is not set and there is language packs available, show language selection pages
        if (!isset($_POST['lang']) && count(Lang::getAvailableLanguages(false)) > 1) {
            self::__render(new InstallerPage('languages'));
        }

        // Check for configuration errors and, if there are no errors, install Symphony!
        if (isset($_POST['fields'])) {
            $fields = $_POST['fields'];
            $errors = self::checkConfiguration($fields);
            if (!empty($errors)) {
                Symphony::Log()->error('Installer - Wrong configuration.');

                foreach ($errors as $err) {
                    Symphony::Log()->error(sprintf('Configuration - %s', $err['msg']));
                }
            } else {
                $disabled_extensions = self::install($fields);

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
     * @param InstallerPage $page
     */
    protected static function __render(InstallerPage $page)
    {
        header('Content-Type: text/html; charset=utf-8');
        echo $page->generate();
        exit;
    }

    /**
     * This function checks the server can support a Symphony installation.
     * If any of these requirements fail the installation will not proceed.
     *
     * @return array
     *  An associative array of errors, with `msg` and `details` keys
     */
    private static function __checkRequirements()
    {
        $errors = array();

        // Make sure the install.sql file exists
        if (!file_exists(INSTALL . '/includes/install.sql') || !is_readable(INSTALL . '/includes/install.sql')) {
            $errors[] = array(
                'msg' => __('Missing install.sql file'),
                'details' => __(
                    'It appears that %s is either missing or not readable. This is required to populate the database and must be uploaded before installation can commence. Ensure that PHP has read permissions.',
                    array('<code>install.sql</code>')
                )
            );
        }

        $errors = array_merge($errors, self::$requirements->check());

        return $errors;
    }

    /**
     * This function checks the current Configuration (which is the values entered
     * by the user on the installation form) to ensure that `/symphony` and `/workspace`
     * folders exist and are writable and that the Database credentials are correct.
     * Once those initial checks pass, the rest of the form values are validated.
     *
     * @param array $fields
     * @return array An associative array of errors if something went wrong, otherwise an empty array.
     */
    public static function checkConfiguration(array $fields)
    {
        $errors = array();

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
            } // Connection related
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
                $errors['database-table-prefix'] = array(
                    'msg' => 'Invalid database table prefix: ‘' . $fields['database']['tbl_prefix'] . '’',
                    'details' => __(
                        'The table prefix %s is invalid. The table prefix must only contain numbers, letters or underscore characters.',
                        array('<code>' . $fields['database']['tbl_prefix'] . '</code>')
                    )
                );
            } // Check the database credentials
            elseif (Symphony::Database()->isConnected()) {
                // Incorrect MySQL version
                $version = Symphony::Database()->fetchVar('version', 0, "SELECT VERSION() AS `version`;");
                if (version_compare($version, '5.5', '<')) {
                    $errors['database-incorrect-version'] = array(
                        'msg' => 'MySQL Version is not correct. ' . $version . ' detected.',
                        'details' => __(
                            'Symphony requires %1$s or greater to work, however version %2$s was detected. This requirement must be met before installation can proceed.',
                            array('<code>MySQL 5.5</code>', '<code>' . $version . '</code>')
                        )
                    );
                } else {
                    // Existing table prefix
                    if (Symphony::Database()->tableExists($fields['database']['tbl_prefix'] . '%')) {
                        $errors['database-table-prefix'] = array(
                            'msg' => 'Database table prefix clash with ‘' . $fields['database']['db'] . '’',
                            'details' => __(
                                'The table prefix %s is already in use. Please choose a different prefix to use with Symphony.',
                                array(

                                    '<code>' . $fields['database']['tbl_prefix'] . '</code>'
                                )
                            )
                        );
                    }
                }
            }
        } catch (DatabaseException $e) {
            $errors['unknown-database'] = array(
                'msg' => 'Database ‘' . $fields['database']['db'] . '’ not found.',
                'details' => __('Symphony was unable to connect to the specified database.')
            );
        }

        // Website name not entered
        if (trim($fields['general']['sitename']) === '') {
            $errors['general-no-sitename'] = array(
                'msg' => 'No sitename entered.',
                'details' => __('You must enter a Site name. This will be shown at the top of your backend.')
            );
        }

        // Username Not Entered
        if (trim($fields['user']['username']) === '') {
            $errors['user-no-username'] = array(
                'msg' => 'No username entered.',
                'details' => __('You must enter a Username. This will be your Symphony login information.')
            );
        }

        // Password Not Entered
        if (trim($fields['user']['password']) === '') {
            $errors['user-no-password'] = array(
                'msg' => 'No password entered.',
                'details' => __('You must enter a Password. This will be your Symphony login information.')
            );
        } // Password mismatch
        elseif ($fields['user']['password'] !== $fields['user']['confirm-password']) {
            $errors['user-password-mismatch'] = array(
                'msg' => 'Passwords did not match.',
                'details' => __('The password and confirmation did not match. Please retype your password.')
            );
        }

        // No Name entered
        if (trim($fields['user']['firstname']) === '' || trim($fields['user']['lastname']) === '') {
            $errors['user-no-name'] = array(
                'msg' => 'Did not enter First and Last names.',
                'details' => __('You must enter your name.')
            );
        }

        // Invalid Email
        if (!preg_match('/^\w(?:\.?[\w%+-]+)*@\w(?:[\w-]*\.)+?[a-z]{2,}$/i', $fields['user']['email'])) {
            $errors['user-invalid-email'] = array(
                'msg' => 'Invalid email address supplied.',
                'details' => __('This is not a valid email address. You must provide an email address since you will need it if you forget your password.')
            );
        }

        // Admin path not entered
        if (trim($fields['symphony']['admin-path']) === '') {
            $errors['no-symphony-path'] = array(
                'msg' => 'No Symphony path entered.',
                'details' => __('You must enter a path for accessing Symphony, or leave the default. This will be used to access Symphony\'s backend.')
            );
        }

        return $errors;
    }

    /**
     * @param array $data
     * @return array
     */
    public static function install(array $data)
    {
        $start = microtime(true);
        Symphony::Log()->info('INSTALLATION PROCESS STARTED (' . DateTimeObj::get('c') . ')');

        // Configuration: Populating array
        $conf = Symphony::Configuration()->get();

        foreach ($conf as $group => $settings) {
            foreach ($settings as $key => $value) {
                // This ensures on data the configuration cares about is populated,
                // anything else will be ignored and accessible in `$data`.
                if (isset($data[$group]) && isset($data[$group][$key])) {
                    $conf[$group][$key] = $data[$group][$key];
                }
            }
        }

        Symphony::Configuration()->setArray($conf);

        $steps = [
            // Create database
            Steps\CreateDatabase::class,
            // Create manifest folder structure
            Steps\CreateManifest::class,
            // Write .htaccess
            Steps\CreateHtaccess::class,
            // Create or import the workspace
            Steps\Workspace::class,
            // Enable extensions
            Steps\EnableExtensions::class,
            // Enable language
            Steps\EnableLanguage::class
        ];

        try {
            foreach ($steps as $step) {
                $installStep = new $step(Symphony::Log()->getLog());
                $installStep->setOverride(false);

                if (false === $installStep->handle(Symphony::Configuration(), $data)) {
                    throw new Exception(sprintf('Aborting installation, %s failed', $step));
                }
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
        Symphony::Log()->info(sprintf(
            'INSTALLATION COMPLETED: Execution Time - %d sec (%s)',
            max(1, time() - $start),
            date('d.m.y H:i:s')
        ));

        return [];
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
        Symphony::Log()->error(sprintf(
            'INSTALLATION ABORTED: Execution Time - %f sec (%s)',
            microtime(true) - $start,
            date('d.m.y H:i:s')
        ));

        self::__render(new InstallerPage('failure'));
    }
}
