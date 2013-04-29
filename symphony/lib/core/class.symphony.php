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
	require_once(CORE . '/class.errorhandler.php');
	require_once(CORE . '/class.configuration.php');
	require_once(CORE . '/class.log.php');
	require_once(CORE . '/class.cookie.php');
	require_once(CORE . '/interface.singleton.php');

	require_once(TOOLKIT . '/class.page.php');
	require_once(TOOLKIT . '/class.ajaxpage.php');
	require_once(TOOLKIT . '/class.xmlelement.php');
	require_once(TOOLKIT . '/class.widget.php');
	require_once(TOOLKIT . '/class.general.php');
	require_once(TOOLKIT . '/class.cryptography.php');
	require_once(TOOLKIT . '/class.profiler.php');
	require_once(TOOLKIT . '/class.author.php');
	require_once(TOOLKIT . '/class.email.php');
	require_once(TOOLKIT . '/class.mysql.php');

	require_once(TOOLKIT . '/class.extensionmanager.php');
	require_once(TOOLKIT . '/class.pagemanager.php');
	require_once(TOOLKIT . '/class.authormanager.php');
	require_once(TOOLKIT . '/class.emailgatewaymanager.php');
	require_once(TOOLKIT . '/class.entrymanager.php');
	require_once(TOOLKIT . '/class.fieldmanager.php');
	require_once(TOOLKIT . '/class.sectionmanager.php');
	require_once(TOOLKIT . '/class.textformattermanager.php');
	require_once(TOOLKIT . '/class.datasourcemanager.php');
	require_once(TOOLKIT . '/class.eventmanager.php');

	Abstract Class Symphony implements Singleton{

		/**
		 * An instance of the Symphony class, either `Administration` or `Frontend`.
		 * @var Symphony
		 */
		protected static $_instance = null;

		/**
		 * An instance of the `Configuration` class
		 * @var Configuration
		 */
		private static $Configuration = null;

		/**
		 * An instance of the `Database` class
		 * @var MySQL
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
		 * An instance of the Profiler class
		 * @var Profiler
		 */
		private static $Profiler = null;

		/**
		 * The current page namespace, used for translations
		 * @since Symphony 2.3
		 * @var string
		 */
		private static $namespace = false;

		/**
		 * A previous exception that has been fired. Defaults to null.
		 * @since Symphony 2.3.2
		 * @var Exception
		 */
		private $exception = null;

		/**
		 * An instance of the Cookie class
		 * @var Cookie
		 */
		public $Cookie = null;

		/**
		 * An instance of the currently logged in Author
		 * @var Author
		 */
		public $Author = null;

		/**
		 * The Symphony constructor initialises the class variables of Symphony.
		 * It will set the DateTime settings, define new date constants and initialise
		 * the correct Language for the currently logged in Author. If magic quotes
		 * are enabled, Symphony will sanitize the `$_SERVER`, `$_COOKIE`,
		 * `$_GET` and `$_POST` arrays. The constructor loads in
		 * the initial Configuration values from the `CONFIG` file
		 */
		protected function __construct(){
			self::$Profiler = Profiler::instance();
			self::$Profiler->sample('Engine Initialisation');

			if(get_magic_quotes_gpc()) {
				General::cleanArray($_SERVER);
				General::cleanArray($_COOKIE);
				General::cleanArray($_GET);
				General::cleanArray($_POST);
			}

			$this->initialiseConfiguration();

			define_safe('__SYM_DATE_FORMAT__', self::Configuration()->get('date_format', 'region'));
			define_safe('__SYM_TIME_FORMAT__', self::Configuration()->get('time_format', 'region'));
			define_safe('__SYM_DATETIME_FORMAT__', __SYM_DATE_FORMAT__ . self::Configuration()->get('datetime_separator', 'region') . __SYM_TIME_FORMAT__);
			DateTimeObj::setSettings(self::Configuration()->get('region'));

			// Initialize language management
			Lang::initialize();

			$this->initialiseLog();

			GenericExceptionHandler::initialise(self::Log());
			GenericErrorHandler::initialise(self::Log());

			$this->initialiseDatabase();
			$this->initialiseExtensionManager();
			$this->initialiseCookie();

			// If the user is not a logged in Author, turn off the verbose error messages.
			if(!self::isLoggedIn() && is_null($this->Author)){
				GenericExceptionHandler::$enabled = false;
			}

			// Set system language
			Lang::set(self::$Configuration->get('lang', 'symphony'));
		}

		/**
		* Accessor for the Symphony instance, whether it be Frontend
		* or Administration
		*
		* @since Symphony 2.2
		* @return Symphony
		*/
		public static function Engine() {
			if(class_exists('Administration')) {
				return Administration::instance();
			}
			else if(class_exists('Frontend')) {
				return Frontend::instance();
			}
			else throw new Exception(__('No suitable engine object found'));
		}

		/**
		 * Setter for `$Configuration`. This function initialise the configuration
		 * object and populate its properties based on the given $array.
		 *
		 * @since Symphony 2.3
		 * @param array $data
		 *  An array of settings to be stored into the Configuration object
		 */
		public function initialiseConfiguration(array $data = array()){
			if(empty($data)){
				// Includes the existing CONFIG file and initialises the Configuration
				// by setting the values with the setArray function.
				include(CONFIG);
				$data = $settings;
			}

			self::$Configuration = new Configuration(true);
			self::$Configuration->setArray($data);
		}

		/**
		 * Accessor for the current `Configuration` instance. This contains
		 * representation of the the Symphony config file.
		 *
		 * @return Configuration
		 */
		public static function Configuration(){
			return self::$Configuration;
		}

		/**
		 * Accessor for the current `Profiler` instance.
		 *
		 * @since Symphony 2.3
		 * @return Profiler
		 */
		public static function Profiler(){
			return self::$Profiler;
		}

		/**
		 * Setter for `$Log`. This function uses the configuration
		 * settings in the 'log' group in the Configuration to create an instance. Date
		 * formatting options are also retrieved from the configuration.
		 *
		 * @param string $filename (optional)
		 *  The file to write the log to, if omitted this will default to `ACTIVITY_LOG`
		 */
		public function initialiseLog($filename = null) {
			if(self::$Log instanceof Log && self::$Log->getLogPath() == $filename) return true;

			if(is_null($filename)) $filename = ACTIVITY_LOG;

			self::$Log = new Log($filename);
			self::$Log->setArchive((self::Configuration()->get('archive', 'log') == '1' ? true : false));
			self::$Log->setMaxSize(intval(self::Configuration()->get('maxsize', 'log')));
			self::$Log->setDateTimeFormat(self::Configuration()->get('date_format', 'region') . ' ' . self::Configuration()->get('time_format', 'region'));

			if(self::$Log->open(Log::APPEND, self::Configuration()->get('write_mode', 'file')) == 1){
				self::$Log->initialise('Symphony Log');
			}
		}

		/**
		 * Accessor for the current `Log` instance
		 *
		 * @since Symphony 2.3
		 * @return Log
		 */
		public static function Log() {
			return self::$Log;
		}

		/**
		 * Setter for `$Cookie`. This will use PHP's parse_url
		 * function on the current URL to set a cookie using the cookie_prefix
		 * defined in the Symphony configuration. The cookie will last two
		 * weeks.
		 *
		 * This function also defines two constants, `__SYM_COOKIE_PATH__`
		 * and `__SYM_COOKIE_PREFIX__`.
		 *
		 * @deprecated Prior to Symphony 2.3.2, the constant `__SYM_COOKIE_PREFIX_`
		 *  had a typo where it was missing the second underscore. Symphony will
		 *  support both constants, `__SYM_COOKIE_PREFIX_` and `__SYM_COOKIE_PREFIX__`
		 *  until Symphony 2.5
		 */
		public function initialiseCookie(){
			$cookie_path = @parse_url(URL, PHP_URL_PATH);
			$cookie_path = '/' . trim($cookie_path, '/');

			define_safe('__SYM_COOKIE_PATH__', $cookie_path);
			define_safe('__SYM_COOKIE_PREFIX_', self::Configuration()->get('cookie_prefix', 'symphony'));
			define_safe('__SYM_COOKIE_PREFIX__', self::Configuration()->get('cookie_prefix', 'symphony'));

			$this->Cookie = new Cookie(__SYM_COOKIE_PREFIX__, TWO_WEEKS, __SYM_COOKIE_PATH__);
		}

		/**
		 * Setter for `$ExtensionManager` using the current
		 * Symphony instance as the parent. If for some reason this fails,
		 * a Symphony Error page will be thrown
		 */
		public function initialiseExtensionManager(){
			if(self::$ExtensionManager instanceof ExtensionManager) return true;

			self::$ExtensionManager = new ExtensionManager;

			if(!(self::$ExtensionManager instanceof ExtensionManager)){
				$this->throwCustomError(__('Error creating Symphony extension manager.'));
			}
		}

		/**
		 * Accessor for the current `$ExtensionManager` instance.
		 *
		 * @since Symphony 2.2
		 * @return ExtensionManager
		 */
		public static function ExtensionManager() {
			return self::$ExtensionManager;
		}

		/**
		 * Setter for `$Database`, accepts a Database object. If `$database`
		 * is omitted, this function will set `$Database` to be of the `MySQL`
		 * class.
		 *
		 * @since Symphony 2.3
		 * @param StdClass $database (optional)
		 *  The class to handle all Database operations, if omitted this function
		 *  will set `self::$Database` to be an instance of the `MySQL` class.
		 * @return boolean
		 *  This function will always return true
		 */
		public function setDatabase(StdClass $database = null) {
			if (self::Database()) return true;

			self::$Database = !is_null($database) ? $database : new MySQL;

			return true;
		}

		/**
		 * Accessor for the current `$Database` instance.
		 *
		 * @return MySQL
		 */
		public static function Database(){
			return self::$Database;
		}

		/**
		 * This will initialise the Database class and attempt to create a connection
		 * using the connection details provided in the Symphony configuration. If any
		 * errors occur whilst doing so, a Symphony Error Page is displayed.
		 *
		 * @return boolean
		 *  This function will return true if the `$Database` was
		 *  initialised successfully.
		 */
		public function initialiseDatabase(){
			$this->setDatabase();

			$details = self::Configuration()->get('database');

			try{
				if(!self::Database()->connect($details['host'], $details['user'], $details['password'], $details['port'], $details['db'])) return false;
				if(!self::Database()->isConnected()) return false;

				self::Database()->setPrefix($details['tbl_prefix']);
				self::Database()->setCharacterEncoding();
				self::Database()->setCharacterSet();

				// Set Timezone, need to convert human readable, ie. Australia/Brisbane to be +10:00
				// @see https://github.com/symphonycms/symphony-2/issues/1726
				$timezone = self::Configuration()->get('timezone', 'region');
				$symphony_date = new DateTime('now', new DateTimeZone($timezone));

				// MySQL wants the offset to be in the format +/-H:I, getOffset returns offset in seconds
				$utc = new DateTime('now ' . $symphony_date->getOffset() . ' seconds', new DateTimeZone("UTC"));

				// Support PHP5.2
				// @see https://github.com/symphonycms/symphony-2/issues/1735
				if(function_exists('date_diff') === false) {
					$offset = mysql_date_diff($utc, $symphony_date);
				}
				// On PHP5.3+ we can use DateInterval to format the difference
				// in way that MySQL will be happy
				else {
					$offset = $symphony_date->diff($utc)->format('%R%H:%I');
				}

				self::Database()->setTimeZone($offset);

				if(self::Configuration()->get('query_caching', 'database') == 'off') self::Database()->disableCaching();
				elseif(self::Configuration()->get('query_caching', 'database') == 'on') self::Database()->enableCaching();
			}
			catch(DatabaseException $e){
				$this->throwCustomError(
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
		 * Attempts to log an Author in given a username and password.
		 * If the password is not hashed, it will be hashed using the sha1
		 * algorithm. The username and password will be sanitized before
		 * being used to query the Database. If an Author is found, they
		 * will be logged in and the sanitized username and password (also hashed)
		 * will be saved as values in the `$Cookie`.
		 *
		 * @see toolkit.General#hash()
		 * @param string $username
		 *  The Author's username. This will be sanitized before use.
		 * @param string $password
		 *  The Author's password. This will be sanitized and then hashed before use
		 * @param boolean $isHash
		 *  If the password provided is already hashed, setting this parameter to
		 *  true will stop it becoming rehashed. By default it is false.
		 * @return boolean
		 *  True if the Author was logged in, false otherwise
		 */
		public function login($username, $password, $isHash=false){
			$username = self::Database()->cleanValue($username);
			$password = self::Database()->cleanValue($password);

			if(strlen(trim($username)) > 0 && strlen(trim($password)) > 0){

				$author = AuthorManager::fetch('id', 'ASC', 1, null, sprintf("
						`username` = '%s'
					", $username
				));

				if(!empty($author) && Cryptography::compare($password, current($author)->get('password'), $isHash)) {
					$this->Author = current($author);

					// Only migrate hashes if there is no update available as the update might change the tbl_authors table.
					if($this->isUpgradeAvailable() === false && Cryptography::requiresMigration($this->Author->get('password'))){
						$this->Author->set('password', Cryptography::hash($password));
						self::Database()->update(array('password' => $this->Author->get('password')), 'tbl_authors', " `id` = '" . $this->Author->get('id') . "'");
					}

					$this->Cookie->set('username', $username);
					$this->Cookie->set('pass', $this->Author->get('password'));
					self::Database()->update(array(
						'last_seen' => DateTimeObj::get('Y-m-d H:i:s')),
						'tbl_authors',
						sprintf(" `id` = %d", $this->Author->get('id'))
					);

					return true;
				}
			}

			return false;
		}

		/**
		 * Symphony allows Authors to login via the use of tokens instead of
		 * a username and password. A token is derived from concatenating the
		 * Author's username and password and applying the sha1 hash to
		 * it, from this, a portion of the hash is used as the token. This is a useful
		 * feature often used when setting up other Authors accounts or if an
		 * Author forgets their password.
		 *
		 * @param string $token
		 *  The Author token, which is a portion of the hashed string concatenation
		 *  of the Author's username and password
		 * @return boolean
		 *  True if the Author is logged in, false otherwise
		 */
		public function loginFromToken($token){
			$token = self::Database()->cleanValue($token);

			if(strlen(trim($token)) == 0) return false;

			if(strlen($token) == 6){
				$row = self::Database()->fetchRow(0, sprintf("
						SELECT `a`.`id`, `a`.`username`, `a`.`password`
						FROM `tbl_authors` AS `a`, `tbl_forgotpass` AS `f`
						WHERE `a`.`id` = `f`.`author_id`
						AND `f`.`expiry` > '%s'
						AND `f`.`token` = '%s'
						LIMIT 1
					",
					DateTimeObj::getGMT('c'), $token
				));

				self::Database()->delete('tbl_forgotpass', " `token` = '{$token}' ");
			}
			else{
				$row = self::Database()->fetchRow(0, sprintf(
					"SELECT `id`, `username`, `password`
					FROM `tbl_authors`
					WHERE SUBSTR(%s(CONCAT(`username`, `password`)), 1, 8) = '%s'
					AND `auth_token_active` = 'yes'
					LIMIT 1",
					'SHA1', $token
				));
			}

			if($row){
				$this->Author = AuthorManager::fetchByID($row['id']);
				$this->Cookie->set('username', $row['username']);
				$this->Cookie->set('pass', $row['password']);
				self::Database()->update(array('last_seen' => DateTimeObj::getGMT('Y-m-d H:i:s')), 'tbl_authors', " `id` = '$id'");

				return true;
			}

			return false;
		}

		/**
		 * This function will destroy the currently logged in `$Author`
		 * session, essentially logging them out.
		 *
		 * @see core.Cookie#expire()
		 */
		public function logout(){
			$this->Cookie->expire();
		}

		/**
		 * This function determines whether an there is a currently logged in
		 * Author for Symphony by using the `$Cookie`'s username
		 * and password. If an Author is found, they will be logged in, otherwise
		 * the `$Cookie` will be destroyed.
		 *
		 * @see core.Cookie#expire()
		 */
		public function isLoggedIn(){

			// Ensures that we're in the real world.. Also reduces three queries from database
			// We must return true otherwise exceptions are not shown
			if (is_null(self::$_instance)) return true;

			if ($this->Author){
				return true;
			}
			else{

				$username = self::Database()->cleanValue($this->Cookie->get('username'));
				$password = self::Database()->cleanValue($this->Cookie->get('pass'));

				if(strlen(trim($username)) > 0 && strlen(trim($password)) > 0){

					$author = AuthorManager::fetch('id', 'ASC', 1, null, sprintf("
							`username` = '%s'
						", $username
					));

					if(!empty($author) && Cryptography::compare($password, current($author)->get('password'), true)) {
						$this->Author = current($author);
						self::Database()->update(array(
							'last_seen' => DateTimeObj::get('Y-m-d H:i:s')),
							'tbl_authors',
							sprintf(" `id` = %d", $this->Author->get('id'))
						);

						// Only set custom author language in the backend
						if(class_exists('Administration')) {
							Lang::set($this->Author->get('language'));
						}

						return true;
					}
				}

				$this->Cookie->expire();
				return false;
			}
		}

		/**
		 * Returns the most recent version found in the `/install/migrations` folder.
		 * Returns a version string to be used in `version_compare()` if an updater
		 * has been found. Returns `FALSE` otherwise.
		 *
		 * @since Symphony 2.3.1
		 * @return mixed
		 */
		public function getMigrationVersion(){
			if($this->isInstallerAvailable()){
				$migrations = scandir(DOCROOT . '/install/migrations');
				$migration_file = end($migrations);
				include_once(DOCROOT . '/install/lib/class.migration.php');
				include_once(DOCROOT . '/install/migrations/' . $migration_file);

				$migration_class = 'migration_' . str_replace('.', '', substr($migration_file, 0, -4));
				return call_user_func(array($migration_class, 'getVersion'));
			}
			else{
				return FALSE;
			}
		}

		/**
		 * Checks if an update is available and applicable for the current installation.
		 *
		 * @since Symphony 2.3.1
		 * @return boolean
		 */
		public function isUpgradeAvailable(){
			if($this->isInstallerAvailable()){
				$migration_version = $this->getMigrationVersion();
				$current_version = Symphony::Configuration()->get('version', 'symphony');
				return version_compare($current_version, $migration_version, '<');
			}
			else{
				return FALSE;
			}
		}

		/**
		 * Checks if the installer/upgrader is available.
		 *
		 * @since Symphony 2.3.1
		 * @return boolean
		 */
		public function isInstallerAvailable(){
			return file_exists(DOCROOT . '/install/index.php');
		}

		/**
		 * A wrapper for throwing a new Symphony Error page.
		 *
		 * @deprecated @since Symphony 2.3.2
		 *
		 * @see `throwCustomError`
		 * @param string $heading
		 *  A heading for the error page
		 * @param string|XMLElement $message
		 *  A description for this error, which can be provided as a string
		 *  or as an XMLElement.
		 * @param string $template
		 *  A string for the error page template to use, defaults to 'generic'. This
		 *  can be the name of any template file in the `TEMPLATES` directory.
		 *  A template using the naming convention of `tpl.*.php`.
		 * @param array $additional
		 *  Allows custom information to be passed to the Symphony Error Page
		 *  that the template may want to expose, such as custom Headers etc.
		 */
		public function customError($heading, $message, $template='generic', array $additional=array()){
			$this->throwCustomError($message, $heading, Page::HTTP_STATUS_ERROR, $template, $additional);
		}

		/**
		 * A wrapper for throwing a new Symphony Error page.
		 *
		 * This methods sets the `GenericExceptionHandler::$enabled` value to `true`.
		 *
		 * @see core.SymphonyErrorPage
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
		 */
		public function throwCustomError($message, $heading='Symphony Fatal Error', $status=Page::HTTP_STATUS_ERROR, $template='generic', array $additional=array()){
			GenericExceptionHandler::$enabled = true;
			throw new SymphonyErrorPage($message, $heading, $template, $additional, $status);
		}

		/**
		 * Setter accepts a previous Exception. Useful for determining the context
		 * of a current exception (ie. detecting recursion).
		 *
		 * @since Symphony 2.3.2
		 * @param Exception $ex
		 */
		public function setException(Exception $ex) {
			$this->exception = $ex;
		}

		/**
		 * Accessor for `$this->exception`.
		 *
		 * @since Symphony 2.3.2
		 * @return Exception|null
		 */
		public function getException() {
			return $this->exception;
		}

		/**
		 * Given the `$page_id` and a `$column`, this function will return an
		 * array of the given `$column` for the Page, including all parents.
		 *
		 * @deprecated This function will be removed in Symphony 2.4. Use
		 * `PageManager::resolvePage` instead.
		 * @param mixed $page_id
		 * The ID of the Page that currently being viewed, or the handle of the
		 * current Page
		 * @return array
		 * An array of the current Page, containing the `$column`
		 * requested. The current page will be the last item the array, as all
		 * parent pages are prepended to the start of the array
		 */
		public function resolvePage($page_id, $column) {
			return PageManager::resolvePage($page_id, $column);
		}

		/**
		 * Given the `$page_id`, return the complete title of the
		 * current page.
		 *
		 * @deprecated This function will be removed in Symphony 2.4. Use
		 * `PageManager::resolvePageTitle` instead.
		 * @param mixed $page_id
		 * The ID of the Page that currently being viewed, or the handle of the
		 * current Page
		 * @return string
		 * The title of the current Page. If the page is a child of another
		 * it will be prepended by the parent and a colon, ie. Articles: Read
		 */
		public function resolvePageTitle($page_id) {
			return PageManager::resolvePage($page_id, 'title');
		}

		/**
		 * Given the `$page_id`, return the complete path to the
		 * current page.
		 *
		 * @deprecated This function will be removed in Symphony 2.4. Use
		 * `PageManager::resolvePagePath` instead.
		 * @param mixed $page_id
		 * The ID of the Page that currently being viewed, or the handle of the
		 * current Page
		 * @return string
		 *  The complete path to the current Page including any parent
		 *  Pages, ie. /articles/read
		 */
		public function resolvePagePath($page_id) {
			return PageManager::resolvePage($page_id, 'handle');
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
		public static function getPageNamespace() {
			if(self::$namespace !== false) return self::$namespace;

			$page = getCurrentPage();

			if(!is_null($page)) $page = trim($page, '/');

			if(substr($page, 0, 7) == 'publish') {
				self::$namespace = '/publish';
			}
			else if(empty($page) && isset($_REQUEST['mode'])) {
				self::$namespace = '/login';
			}
			else if(empty($page)) {
				self::$namespace = null;
			}
			else {
				$bits = explode('/', $page);

				if($bits[0] == 'extension') {
					self::$namespace = sprintf('/%s/%s/%s', $bits[0], $bits[1], $bits[2]);
				}
				else {
					self::$namespace =  sprintf('/%s/%s', $bits[0], $bits[1]);
				}
			}

			return self::$namespace;
		}
	}

	/**
	 * The `SymphonyErrorPageHandler` extends the `GenericExceptionHandler`
	 * to allow the template for the exception to be provided from the `TEMPLATES`
	 * directory
	 */
	Class SymphonyErrorPageHandler extends GenericExceptionHandler {

		/**
		 * The render function will take a `SymphonyErrorPage` exception and
		 * output a HTML page. This function first checks to see if their is a custom
		 * template for this exception otherwise it reverts to using the default
		 * `usererror.generic.php`
		 *
		 * @param Exception $e
		 *  The Exception object
		 * @return string
		 *  An HTML string
		 */
		public static function render(Exception $e){
			if($e->getTemplate() === false){
				Page::renderStatusCode($e->getHttpStatusCode());
				if(isset($e->getAdditional()->header)) header($e->getAdditional()->header);

				echo '<h1>Symphony Fatal Error</h1><p>'.$e->getMessage().'</p>';
				exit;
			}

			include($e->getTemplate());
		}
	}

	/**
	 * `SymphonyErrorPage` extends the default `Exception` class. All
	 * of these exceptions will halt execution immediately and return the
	 * exception as a HTML page. By default the HTML template is `usererror.generic.php`
	 * from the `TEMPLATES` directory.
	 */

	Class SymphonyErrorPage extends Exception{

		/**
		 * A heading for the error page, this will be prepended to
		 * "Symphony Fatal Error".
		 * @return string
		 */
		private $_heading;

		/**
		 * A string for the error page template to use, defaults to 'generic'. This
		 * can be the name of any template file in the `TEMPLATES` directory.
		 * A template using the naming convention of `usererror.*.php`.
		 * @var string
		 */
		private $_template = 'generic';

		/**
		 * If the message as provided as an `XMLElement`, it will be saved to
		 * this parameter
		 * @var XMLElement
		 */
		private $_messageObject = null;

		/**
		 * An object of an additional information for this error page. Note that
		 * this is provided as an array and then typecast to an object
		 * @var StdClass
		 */
		private $_additional = null;

		/**
		 * A simple container for the response status code.
		 * Full value is setted usign `$Page->setHttpStatus()`
		 * in the template.
		 */
		private $_status = Page::HTTP_STATUS_ERROR;

		/**
		 * Constructor for SymphonyErrorPage sets it's class variables
		 *
		 * @param string|XMLElement $message
		 *  A description for this error, which can be provided as a string
		 *  or as an XMLElement.
		 * @param string $heading
		 *  A heading for the error page, by default this is "Symphony Fatal Error"
		 * @param string $template
		 *  A string for the error page template to use, defaults to 'generic'. This
		 *  can be the name of any template file in the `TEMPLATES` directory.
		 *  A template using the naming convention of `tpl.*.php`.
		 * @param array $additional
		 *  Allows custom information to be passed to the Symphony Error Page
		 *  that the template may want to expose, such as custom Headers etc.
		 * @param integer $status
		 *  Properly sets the HTTP status code for the response. Defaults to
		 *  `Page::HTTP_STATUS_ERROR`
		 */
		public function __construct($message, $heading='Symphony Fatal Error', $template='generic', array $additional=array(), $status=Page::HTTP_STATUS_ERROR){

			if($message instanceof XMLElement){
				$this->_messageObject = $message;
				$message = $this->_messageObject->generate();
			}

			parent::__construct($message);

			$this->_heading = $heading;
			$this->_template = $template;
			$this->_additional = (object)$additional;
			$this->_status = $status;
		}

		/**
		 * Accessor for the `$_heading` of the error page
		 *
		 * @return string
		 */
		public function getHeading(){
			return $this->_heading;
		}

		/**
		 * Accessor for `$_messageObject`
		 *
		 * @return XMLElement
		 */
		public function getMessageObject(){
			return $this->_messageObject;
		}

		/**
		 * Accessor for `$_additional`
		 *
		 * @return StdClass
		 */
		public function getAdditional(){
			return $this->_additional;
		}

		/**
		 * Accessor for `$_status`
		 *
		 * @since Symphony 2.3.2
		 * @return integer
		 */
		public function getHttpStatusCode() {
			return $this->_status;
		}

		/**
		 * Returns the path to the current template by looking at the
		 * `WORKSPACE/template/` directory, then at the `TEMPLATES`
		 * directory for the convention `usererror.*.php`. If the template
		 * is not found, `false` is returned
		 *
		 * @since Symphony 2.3
		 * @return mixed
		 *  String, which is the path to the template if the template is found,
		 *  false otherwise
		 */
		public function getTemplate(){
			$format = '%s/usererror.%s.php';
			if(file_exists($template = sprintf($format, WORKSPACE . '/template', $this->_template)))
				return $template;
			elseif(file_exists($template = sprintf($format, TEMPLATE, $this->_template)))
				return $template;
			else
				return false;
		}

		/**
		 * A simple getter to the template name in order to be able
		 * to identify which type of exception this is.
		 *
		 * @since Symphony 2.3.2
		 * @return string
		 */
		public function getTemplateName() {
			return $this->_template;
		}
	}

	/**
	 * The `DatabaseExceptionHandler` provides a render function to provide
	 * customised output for database exceptions. It displays the exception
	 * message as provided by the Database.
	 */
	Class DatabaseExceptionHandler extends GenericExceptionHandler {

		/**
		 * The render function will take a `DatabaseException` and output a
		 * HTML page.
		 *
		 * @param Exception $e
		 *  The Exception object
		 * @return string
		 *  An HTML string
		 */
		public static function render(Exception $e) {
			$trace = $queries = null;

			foreach($e->getTrace() as $t){
				$trace .= sprintf(
					'<li><code><em>[%s:%d]</em></code></li><li><code>&#160;&#160;&#160;&#160;%s%s%s();</code></li>',
					$t['file'],
					$t['line'],
					(isset($t['class']) ? $t['class'] : null),
					(isset($t['type']) ? $t['type'] : null),
					$t['function']
				);
			}

			if(is_object(Symphony::Database())){
				$debug = Symphony::Database()->debug();

				if(!empty($debug)) foreach($debug as $query){
					$queries .= sprintf(
						'<li><em>[%01.4f]</em><code> %s;</code> </li>',
						(isset($query['execution_time']) ? $query['execution_time'] : null),
						htmlspecialchars($query['query'])
					);
				}
			}

			$html = sprintf(file_get_contents(self::getTemplate('fatalerror.database')),
				$e->getDatabaseErrorMessage(),
				$e->getQuery(),
				$trace,
				$queries
			);

			return str_replace('{SYMPHONY_URL}', SYMPHONY_URL, $html);
		}
	}
