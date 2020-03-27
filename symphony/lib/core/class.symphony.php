<?php

/**
 * @package core
 */
/**
 * The Symphony class is an abstract class that implements the
 * Singleton interface. It provides the glue that forms the Symphony
 * CMS and initialises the toolkit classes. Symphony is extended by
 * the Frontend and Administration classes
 */

abstract class Symphony implements Singleton
{
    /**
     * An instance of the Symphony class, either `Administration` or `Frontend`.
     * @var Symphony
     */
    protected static $_instance = null;

    /**
     * An instance of the Profiler class
     * @var Profiler
     */
    protected static $Profiler = null;

    /**
     * An instance of the `Configuration` class
     * @var Configuration
     */
    private static $Configuration = null;

    /**
     * An instance of the `Database` class
     * @var Database
     */
    private static $Database = null;

    /**
     * An instance of the `ExtensionManager` class
     * @var ExtensionManager
     */
    private static $ExtensionManager = null;

    /**
     * An instance of the `Log` class
     * @var Log
     */
    private static $Log = null;

    /**
     * The current page namespace, used for translations
     * @since Symphony 2.3
     * @var string
     */
    private static $namespace = false;

    /**
     * An instance of the Cookie class
     * @var Cookie
     */
    private static $Cookie = null;

    /**
     * An instance of the currently logged in Author
     * @var Author
     */
    private static $Author = null;

    /**
     * A previous exception that has been fired. Defaults to null.
     * @since Symphony 2.3.2
     * @var Exception
     */
    private static $exception = null;

    /**
     * The Symphony constructor initialises the class variables of Symphony. At present
     * constructor has a couple of responsibilities:
     * - Start a profiler instance
     * - If magic quotes are enabled, clean `$_SERVER`, `$_COOKIE`, `$_GET`, `$_POST` and the `$_REQUEST` arrays.
     * - Initialise the correct Language for the currently logged in Author.
     * - Start the session and adjust the error handling if the user is logged in
     *
     * The `$_REQUEST` array has been added in 2.7.0
     */
    protected function __construct()
    {
        self::$Profiler = Profiler::instance();

        if (get_magic_quotes_gpc()) {
            General::cleanArray($_SERVER);
            General::cleanArray($_COOKIE);
            General::cleanArray($_GET);
            General::cleanArray($_POST);
            General::cleanArray($_REQUEST);
        }

        // Initialize language management
        Lang::initialize();
        Lang::set(self::$Configuration->get('lang', 'symphony'));

        // Initialize session support
        static::initialiseSessionHandler();
        static::initialiseCookie();

        // If the user is not a logged in Author, turn off the verbose error messages.
        ExceptionHandler::$enabled = static::isLoggedIn() && static::Author();

        // Engine is ready.
        static::Profiler()->sample('Engine Initialisation');
    }

    /**
     * Setter for the Symphony Log and Error Handling system
     *
     * @since Symphony 2.6.0
     */
    public static function initialiseErrorHandler()
    {
        // Initialise logging
        self::initialiseLog();
        ExceptionHandler::initialise(self::Log());
        ErrorHandler::initialise(self::Log());
    }

    /**
     * Setter for the Symphony Session Handling system.
     *
     * This function also defines a constant, `__SYM_COOKIE_PATH__`.
     *
     * @since Symphony 3.0.0
     * @throws Exception
     */
    public static function initialiseSessionHandler()
    {
        define_safe('__SYM_COOKIE_PATH__', DIRROOT === '' ? '/' : DIRROOT);

        $session = Session::start(TWO_WEEKS, __SYM_COOKIE_PATH__);
        if (!$session) {
            throw new Exception('Session failed to start, no session id found');
        }
    }

    /**
     * Accessor for the Symphony instance, whether it be Frontend
     * or Administration
     *
     * @since Symphony 2.2
     * @throws Exception
     * @return Symphony
     */
    public static function Engine()
    {
        if (defined('APP_MODE') && APP_MODE === 'administration') {
            return Administration::instance();
        } elseif (defined('APP_MODE') && APP_MODE === 'frontend') {
            return Frontend::instance();
        // @deprecated @since Symphony 3.0.0
        // This acts as a compat layer. Will be removed.
        } elseif (class_exists('Administration', false)) {
            return Administration::instance();
        // @deprecated @since Symphony 3.0.0
        // This acts as a compat layer. Will be removed.
        } elseif (class_exists('Frontend', false)) {
            return Frontend::instance();
        } else {
            throw new Exception(__('No suitable engine object found'));
        }
    }

    /**
     * Returns the current engine namespace.
     * This is used when firing delegates, to scope them properly.
     * Currently, it returns '/backend/' when the APP_MODE is 'administration'.
     * Otherwise, it returns '/APP_MODE/'.
     *
     * @since Symphony 3.0.0
     * @see getPageNamespace()
     * @return string
     *  The name of the engine's namespace
     */
    public static function getEngineNamespace()
    {
        if (APP_MODE === 'administration') {
            return '/backend/';
        }
        return '/' . APP_MODE . '/';
    }

    /**
     * Setter for `$Configuration`. This function initialise the configuration
     * object and populate its properties based on the given `$array`. Since
     * Symphony 2.6.5, it will also set Symphony's date constants.
     *
     * @since Symphony 2.3
     * @param array $data
     *  An array of settings to be stored into the Configuration object
     */
    public static function initialiseConfiguration(array $data = array())
    {
        if (empty($data)) {
            // Includes the existing CONFIG file and initialises the Configuration
            // by setting the values with the setArray function.
            include CONFIG;

            $data = $settings;
        }

        self::$Configuration = new Configuration(true);
        self::$Configuration->setArray($data);

        // Set date format throughout the system
        $region = self::Configuration()->get('region');
        define_safe('__SYM_DATE_FORMAT__', $region['date_format']);
        define_safe('__SYM_TIME_FORMAT__', $region['time_format']);
        define_safe('__SYM_DATETIME_FORMAT__', __SYM_DATE_FORMAT__ . $region['datetime_separator'] . __SYM_TIME_FORMAT__);
        DateTimeObj::setSettings($region);
    }

    /**
     * Accessor for the current `Configuration` instance. This contains
     * representation of the the Symphony config file.
     *
     * @return Configuration
     */
    public static function Configuration()
    {
        return self::$Configuration;
    }

    /**
     * Is XSRF enabled for this Symphony install?
     *
     * @since Symphony 2.4
     * @return boolean
     */
    public static function isXSRFEnabled()
    {
        return self::Configuration()->get('enable_xsrf', 'symphony') === 'yes';
    }

    /**
     * Accessor for the current `Profiler` instance.
     *
     * @since Symphony 2.3
     * @return Profiler
     */
    public static function Profiler()
    {
        return self::$Profiler;
    }

    /**
     * Setter for `$Log`. This function uses the configuration
     * settings in the 'log' group in the Configuration to create an instance. Date
     * formatting options are also retrieved from the configuration.
     *
     * @param string $filename (optional)
     *  The file to write the log to, if omitted this will default to `ACTIVITY_LOG`
     * @throws Exception
     * @return bool|void
     */
    public static function initialiseLog($filename = null)
    {
        if (self::$Log instanceof Log && self::$Log->getLogPath() == $filename) {
            return true;
        }

        if (is_null($filename)) {
            $filename = ACTIVITY_LOG;
        }

        self::$Log = new Log($filename);
        self::$Log->setArchive((self::Configuration()->get('archive', 'log') == '1' ? true : false));
        self::$Log->setMaxSize(self::Configuration()->get('maxsize', 'log'));
        self::$Log->setFilter(self::Configuration()->get('filter', 'log'));

        if (self::$Log->open(Log::APPEND, self::Configuration()->get('write_mode', 'file')) == '1') {
            self::$Log->initialise('Symphony Log');
        }
    }

    /**
     * Accessor for the current `Log` instance
     *
     * @since Symphony 2.3
     * @return Log
     */
    public static function Log()
    {
        return self::$Log;
    }

    /**
     * Setter for `$Cookie`. This will use PHP's parse_url
     * function on the current URL to set a cookie using the cookie_prefix
     * defined in the Symphony configuration. The cookie will last two
     * weeks.
     *
     * This function also defines a constant, `__SYM_COOKIE_PREFIX__`.
     */
    public static function initialiseCookie()
    {
        define_safe('__SYM_COOKIE_PREFIX__', self::Configuration()->get('cookie_prefix', 'symphony'));

        self::$Cookie = new Cookie(__SYM_COOKIE_PREFIX__);
    }

    /**
     * Accessor for the current `$Cookie` instance.
     *
     * @since Symphony 2.5.0
     * @return Cookie
     */
    public static function Cookie()
    {
        return self::$Cookie;
    }

    /**
     * Setter for `$ExtensionManager` using the current
     * Symphony instance as the parent. If for some reason this fails,
     * a Symphony Error page will be thrown
     * @param Boolean $force (optional)
     *  When set to true, this function will always create a new
     *  instance of ExtensionManager, replacing self::$ExtensionManager.
     */
    public static function initialiseExtensionManager($force = false)
    {
        if (!$force && self::$ExtensionManager instanceof ExtensionManager) {
            return true;
        }

        self::$ExtensionManager = new ExtensionManager;

        if (!(self::$ExtensionManager instanceof ExtensionManager)) {
            static::throwCustomError(__('Error creating Symphony extension manager.'));
        }
    }

    /**
     * Accessor for the current `$ExtensionManager` instance.
     *
     * @since Symphony 2.2
     * @return ExtensionManager
     */
    public static function ExtensionManager()
    {
        return self::$ExtensionManager;
    }

    /**
     * Setter for `$Database`, accepts a Database object. If `$database`
     * is omitted, this function will set `$Database` to be of the `MySQL`
     * class.
     *
     * @deprecated @since Symphony 3.0.0 - This function now does nothing
     * @since Symphony 2.3
     * @param StdClass $database (optional)
     *  The class to handle all Database operations, if omitted this function
     *  will set `self::$Database` to be an instance of the `MySQL` class.
     * @return boolean
     *  This function will always return true
     */
    public static function setDatabase(StdClass $database = null)
    {
        return true;
    }

    /**
     * Accessor for the current `$Database` instance.
     *
     * @return Database
     */
    public static function Database()
    {
        return self::$Database;
    }

    /**
     * This will initialise the Database class and attempt to create a connection
     * using the connection details provided in the Symphony configuration. If any
     * errors occur whilst doing so, a Symphony Error Page is displayed.
     *
     * @throws SymphonyException
     * @return boolean
     *  This function will return true if the `$Database` was
     *  initialised successfully.
     */
    public static function initialiseDatabase()
    {
        $details = self::Configuration()->get('database');
        self::$Database = new Database($details);

        try {
            static::Database()->connect();

            if (!static::Database()->isConnected()) {
                return false;
            }

            static::Database()->setTimeZone(self::Configuration()->get('timezone', 'region'));
        } catch (DatabaseException $e) {
            static::throwCustomError(
                $e->getDatabaseErrorCode() . ': ' . $e->getDatabaseErrorMessage(),
                __('Symphony Database Error'),
                Page::HTTP_STATUS_ERROR,
                'database',
                array(
                    'error' => $e,
                    'message' => __('There was a problem whilst attempting to establish a database connection. Please check all connection information is correct.') . ' ' . __('The following error was returned:')
                )
            );
        }

        return true;
    }

    /**
     * Accessor for the current `$Author` instance.
     *
     * @since Symphony 2.5.0
     * @return Author
     */
    public static function Author()
    {
        return self::$Author;
    }

    /**
     * Attempts to log an Author in given a username and password.
     * If the password is not hashed, it will be hashed using the sha1
     * algorithm. The username and password will be sanitized before
     * being used to query the Database. If an Author is found, they
     * will be logged in and the sanitized username and password (also hashed)
     * will be saved as values in the `$Cookie`.
     *
     * @see toolkit.Cryptography#hash()
     * @throws DatabaseException
     * @param string $username
     *  The Author's username. This will be sanitized before use.
     * @param string $password
     *  The Author's password. This will be sanitized and then hashed before use
     * @param boolean $isHash
     *  If the password provided is already hashed, setting this parameter to
     *  true will stop it becoming rehashed. By default it is false.
     * @return boolean
     *  true if the Author was logged in, false otherwise
     */
    public static function login($username, $password, $isHash = false)
    {
        $username = trim($username);
        $password = trim($password);

        if (strlen($username) > 0 && strlen($password) > 0) {
            $author = (new AuthorManager)
                ->select()
                ->username($username)
                ->limit(1)
                ->execute()
                ->next();

            if ($author && Cryptography::compare($password, $author->get('password'), $isHash)) {
                self::$Author = $author;

                // Only migrate hashes if there is no update available as the update might change the tbl_authors table.
                // Also, only upgrade if the password is clear text.
                if (!self::isUpgradeAvailable() && !$isHash && Cryptography::requiresMigration(self::$Author->get('password'))) {
                    self::$Author->set('password', Cryptography::hash($password));

                    static::Database()
                        ->update('tbl_authors')
                        ->set(['password' => self::$Author->get('password')])
                        ->where(['id' => self::$Author->get('id')])
                        ->execute();
                }

                static::Cookie()->set('username', $username);
                static::Cookie()->set('pass', self::$Author->get('password'));
                // Is this a real login ?
                if (!$isHash) {
                    static::Cookie()->regenerate();
                }

                return static::Database()
                    ->update('tbl_authors')
                    ->set(['last_seen' => DateTimeObj::get('Y-m-d H:i:s')])
                    ->where(['id' => self::$Author->get('id')])
                    ->execute()
                    ->success();
            }
        }

        return false;
    }

    /**
     * Symphony allows Authors to login via the use of tokens instead of
     * a username and password.
     * A token is a random string of characters.
     * This is a useful feature often used when setting up other Authors accounts or
     * if an Author forgets their password.
     *
     * @param string $token
     *  The Author token
     * @throws DatabaseException
     * @return boolean
     *  true if the Author is logged in, false otherwise
     */
    public static function loginFromToken($token)
    {
        $token = trim($token);

        if (strlen($token) === 0) {
            return false;
        }

        $am = new AuthorManager;
        // Try with the password reset
        $rowByResetPass = $am->fetchByPasswordResetToken($token);
        if ($rowByResetPass) {
            $row = $rowByResetPass;
            // consume the token
            static::Database()
                ->delete('tbl_forgotpass')
                ->where(['token' => $token])
                ->execute();
        } else {
            // Fallback to auth token
            $row = $am->fetchByAuthToken($token);
        }

        if ($row) {
            self::$Author = $row;
            static::Cookie()->set('username', $row['username']);
            static::Cookie()->set('pass', $row['password']);
            static::Cookie()->regenerate();

            return static::Database()
                ->update('tbl_authors')
                ->set(['last_seen' => DateTimeObj::get('Y-m-d H:i:s')])
                ->where(['id' => $row['id']])
                ->execute()
                ->success();
        }

        return false;
    }

    /**
     * This function will destroy the currently logged in `$Author`
     * session, essentially logging them out.
     *
     * @see core.Cookie#expire()
     */
    public static function logout()
    {
        static::Cookie()->expire();
    }

    /**
     * This function determines whether an there is a currently logged in
     * Author for Symphony by using the `$Cookie`'s username
     * and password. If the instance is not found, they will be logged
     * in using the cookied credentials.
     *
     * @see login()
     * @return boolean
     */
    public static function isLoggedIn()
    {
        // Check to see if we already have an Author instance.
        if (static::Author()) {
            return true;
        }

        // No author instance found, attempt to log in with the cookied credentials
        return static::login(static::Cookie()->get('username'), static::Cookie()->get('pass'), true);
    }

    /**
     * Returns the most recent version found in the `/install/migrations` folder.
     * Returns a semver version string if an updater
     * has been found.
     * Returns `false` otherwise.
     *
     * @since Symphony 2.3.1
     * @return string|boolean
     */
    public static function getMigrationVersion()
    {
        if (self::isInstallerAvailable() && class_exists('Updater')) {
            $migrations = Updater::getAvailableMigrations();
            $m = end($migrations);
            if (!$m) {
                return false;
            }
            return $m->getVersion();
        }

        return false;
    }

    /**
     * Checks if an update is available and applicable for the current installation.
     *
     * @since Symphony 2.3.1
     * @return boolean
     */
    public static function isUpgradeAvailable()
    {
        if (self::isInstallerAvailable()) {
            $migration_version = self::getMigrationVersion();
            $vc = new VersionComparator(Symphony::Configuration()->get('version', 'symphony'));

            return $vc->lessThan($migration_version);
        }

        return false;
    }

    /**
     * Checks if the installer/updater is available.
     *
     * @since Symphony 2.3.1
     * @return boolean
     */
    public static function isInstallerAvailable()
    {
        return file_exists(DOCROOT . '/install/index.php');
    }

    /**
     * A wrapper for throwing a new Symphony Error page.
     *
     * @see core.SymphonyException
     * @param string|XMLElement $message
     *  A description for this error, which can be provided as a string
     *  or as an XMLElement.
     * @param string $heading
     *  A heading for the error page
     * @param integer $status
     *  Properly sets the HTTP status code for the response. Defaults to
     *  `Page::HTTP_STATUS_ERROR`. Use `Page::HTTP_STATUS_XXX` to set this value.
     * @param string $template
     *  A string for the error page template to use, defaults to 'generic'. This
     *  can be the name of any template file in the `TEMPLATES` directory.
     *  A template using the naming convention of `tpl.*.php`.
     * @param array $additional
     *  Allows custom information to be passed to the Symphony Error Page
     *  that the template may want to expose, such as custom Headers etc.
     * @throws SymphonyException
     */
    public static function throwCustomError($message, $heading = 'Symphony Fatal Error', $status = Page::HTTP_STATUS_ERROR, $template = 'generic', array $additional = array())
    {
        throw new SymphonyException($message, $heading, $template, $additional, $status);
    }

    /**
     * Setter accepts a previous Throwable. Useful for determining the context
     * of a current Throwable (ie. detecting recursion).
     *
     * @since Symphony 2.3.2
     *
     * @since Symphony 2.7.0
     *  This function works with both Exception and Throwable
     *  Supporting both PHP 5.6 and 7 forces use to not qualify the $e parameter
     *
     * @param Throwable $ex
     */
    public static function setException($ex)
    {
        self::$exception = $ex;
    }

    /**
     * Accessor for `self::$exception`.
     *
     * @since Symphony 2.3.2
     * @return Throwable|null
     */
    public static function getException()
    {
        return self::$exception;
    }

    /**
     * Returns the page namespace based on the current URL.
     * A few examples:
     *
     * /login
     * /publish
     * /blueprints/datasources
     * [...]
     * /extension/$extension_name/$page_name
     *
     * This method is especially useful in couple with the translation function.
     *
     * @see toolkit#__()
     * @return string
     *  The page namespace, without any action string (e.g. "new", "saved") or
     *  any value that depends upon the single setup (e.g. the section handle in
     *  /publish/$handle)
     */
    public static function getPageNamespace()
    {
        if (self::$namespace !== false) {
            return self::$namespace;
        }

        $page = getCurrentPage();

        if (!is_null($page)) {
            $page = trim($page, '/');
        }

        if (substr($page, 0, 7) == 'publish') {
            self::$namespace = '/publish';
        } elseif (empty($page) && isset($_REQUEST['mode'])) {
            self::$namespace = '/login';
        } elseif (empty($page)) {
            self::$namespace = null;
        } else {
            $bits = explode('/', $page);

            if ($bits[0] == 'extension') {
                self::$namespace = sprintf('/%s/%s/%s', $bits[0], $bits[1], $bits[2]);
            } else {
                self::$namespace =  sprintf('/%s/%s', $bits[0], isset($bits[1]) ? $bits[1] : '');
            }
        }

        return self::$namespace;
    }

    /**
     * Called by `symphony_launcher()`, this function is responsible for rendering the current
     * page. Engines are required to implement it.
     *
     * @param string $page
     * @return string
     *  The http response body
     */
    abstract public function display($page);
}

