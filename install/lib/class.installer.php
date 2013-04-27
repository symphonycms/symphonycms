<?php

	require_once(CORE . '/class.administration.php');
	require_once(TOOLKIT . '/class.cryptography.php');
	require_once(TOOLKIT . '/class.lang.php');

	require_once(INSTALL . '/lib/class.installerpage.php');

	Class Installer extends Administration {

		/**
		 * Override the default Symphony constructor to initialise the Log, Config
		 * and Database objects for installation/update. This allows us to use the
		 * normal accessors.
		 */
		protected function __construct() {
			if(get_magic_quotes_gpc()) {
				General::cleanArray($_SERVER);
				General::cleanArray($_COOKIE);
				General::cleanArray($_GET);
				General::cleanArray($_POST);
			}

			// Include the default Config for installation.
			include(INSTALL . '/includes/config_default.php');
			$this->initialiseConfiguration($settings);

			// Initialize date/time
			define_safe('__SYM_DATE_FORMAT__', self::Configuration()->get('date_format', 'region'));
			define_safe('__SYM_TIME_FORMAT__', self::Configuration()->get('time_format', 'region'));
			define_safe('__SYM_DATETIME_FORMAT__', __SYM_DATE_FORMAT__ . self::Configuration()->get('datetime_separator', 'region') . __SYM_TIME_FORMAT__);
			DateTimeObj::setSettings(self::Configuration()->get('region'));

			// Initialize language
			$this->initialiseLang();

			// Initialize logs
			$this->initialiseLog(INSTALL_LOGS . '/install');

			// Initialize database
			$this->initialiseDatabase();

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
		public static function instance(){
			if(!(self::$_instance instanceof Installer)) {
				self::$_instance = new Installer;
			}

			return self::$_instance;
		}

		/**
		 * Initialises the language by looking at the `lang` key,
		 * passed via GET or POST
		 */
		public function initialiseLang(){
			$lang = !empty($_REQUEST['lang']) ? preg_replace('/[^a-zA-Z\-]/', NULL, $_REQUEST['lang']) : 'en';
			Lang::initialize();
			Lang::set($lang, false);
		}

		/**
		 * Overrides the default `initialiseLog()` method and writes
		 * logs to manifest/logs/install
		 */
		public function initialiseLog($filename = null){
			if(is_dir(INSTALL_LOGS) || General::realiseDirectory(INSTALL_LOGS, self::Configuration()->get('write_mode', 'directory'))) {
				parent::initialiseLog($filename);
			}
		}

		/**
		 * Overrides the default `initialiseDatabase()` method
		 * This allows us to still use the normal accessor
		 */
		public function initialiseDatabase(){
			$this->setDatabase();
		}

		public function run() {
			// Make sure a log file is available
			if(is_null(Symphony::Log()) || !file_exists(Symphony::Log()->getLogPath())) {
				self::__render(new InstallerPage('missing-log'));
			}

			// Check essential server requirements
			$errors = self::__checkRequirements();
			if(!empty($errors)){
				Symphony::Log()->pushToLog(
					sprintf('Installer - Missing requirements.'),
					E_ERROR, true
				);

				foreach($errors as $err){
					Symphony::Log()->pushToLog(
						sprintf('Requirement - %s', $err['msg']),
						E_ERROR, true
					);
				}

				self::__render(new InstallerPage('requirements', array(
					'errors'=> $errors
				)));
			}

			// If language is not set and there is language packs available, show language selection pages
			if(!isset($_POST['lang']) && count(Lang::getAvailableLanguages(false)) > 1){
				self::__render(new InstallerPage('languages'));
			}

			// Check for configuration errors and, if there are no errors, install Symphony!
			if(isset($_POST['fields'])) {
				$errors = self::__checkConfiguration();
				if(!empty($errors)){
					Symphony::Log()->pushToLog(
						sprintf('Installer - Wrong configuration.'),
						E_ERROR, true
					);

					foreach($errors as $err){
						Symphony::Log()->pushToLog(
							sprintf('Configuration - %s', $err['msg']),
							E_ERROR, true
						);
					}
				}
				else{
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
		private static function __checkRequirements(){
			$errors = array();

			// Check for PHP 5.2+
			if(version_compare(phpversion(), '5.2', '<=')){
				$errors[] = array(
					'msg' => __('PHP Version is not correct'),
					'details' => __('Symphony requires %1$s or greater to work, however version %2$s was detected.', array('<code><abbr title="PHP: Hypertext Pre-processor">PHP</abbr> 5.2</code>', '<code>' . phpversion() . '</code>'))
				);
			}

			// Make sure the install.sql file exists
			if(!file_exists(INSTALL . '/includes/install.sql') || !is_readable(INSTALL . '/includes/install.sql')){
				$errors[] = array(
					'msg' => __('Missing install.sql file'),
					'details'  => __('It appears that %s is either missing or not readable. This is required to populate the database and must be uploaded before installation can commence. Ensure that PHP has read permissions.', array('<code>install.sql</code>'))
				);
			}

			// Is MySQL available?
			if(!function_exists('mysql_connect')){
				$errors[] = array(
					'msg' => __('MySQL extension not present'),
					'details'  => __('Symphony requires MySQL to work.')
				);
			}

			// Is ZLib available?
			if(!extension_loaded('zlib')){
				$errors[] = array(
					'msg' => __('ZLib extension not present'),
					'details' => __('Symphony uses the ZLib compression library for log rotation.')
				);
			}

			// Is libxml available?
			if(!extension_loaded('xml') && !extension_loaded('libxml')){
				$errors[] = array(
					'msg' => __('XML extension not present'),
					'details'  => __('Symphony needs the XML extension to pass data to the site frontend.')
				);
			}

			// Is libxslt available?
			if(!extension_loaded('xsl') && !extension_loaded('xslt') && !function_exists('domxml_xslt_stylesheet')){
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
			if(!is_writable(DOCROOT)){
				$errors['no-write-permission-root'] = array(
					'msg' => 'Root folder not writable: ' . DOCROOT,
					'details' => __('Symphony does not have write permission to the root directory. Please modify permission settings on %s. This can be reverted once installation is complete.', array('<code>' . DOCROOT . '</code>'))
				);
			}

			// Cannot write to workspace
			if(is_dir(DOCROOT . '/workspace') && !is_writable(DOCROOT . '/workspace')){
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
		 * @return
		 *  An associative array of errors if something went wrong, otherwise an empty array.
		 */
		private static function __checkConfiguration(){
			$errors = array();
			$fields = $_POST['fields'];

			// Testing the database connection
			try{
				Symphony::Database()->connect(
					$fields['database']['host'],
					$fields['database']['user'],
					$fields['database']['password'],
					$fields['database']['port']
				);
			}
			catch(DatabaseException $e){
				// Invalid credentials
				// @link http://dev.mysql.com/doc/refman/5.5/en/error-messages-server.html
				if($e->getDatabaseErrorCode() === 1044 or $e->getDatabaseErrorCode() === 1045) {
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

			try{
				if(Symphony::Database()->isConnected()) {
					// Looking for the given database name
					Symphony::Database()->select($fields['database']['db']);

					// Incorrect MySQL version
					$version = Symphony::Database()->fetchVar('version', 0, "SELECT VERSION() AS `version`;");
					if(version_compare($version, '5.0', '<')){
						$errors['database-incorrect-version']  = array(
							'msg' => 'MySQL Version is not correct. '. $version . ' detected.',
							'details' => __('Symphony requires %1$s or greater to work, however version %2$s was detected. This requirement must be met before installation can proceed.', array('<code>MySQL 5.0</code>', '<code>' . $version . '</code>'))
						);
					}

					else {
						// Existing table prefix
						$tables = Symphony::Database()->fetch(sprintf(
							"SHOW TABLES FROM `%s` LIKE '%s'",
							mysql_real_escape_string($fields['database']['db'], Symphony::Database()->getConnectionResource()),
							mysql_real_escape_string($fields['database']['tbl_prefix'], Symphony::Database()->getConnectionResource()) . '%'
						));

						if(is_array($tables) && !empty($tables)) {
							$errors['database-table-clash']  = array(
								'msg' => 'Database table prefix clash with ‘' . $fields['database']['db'] . '’',
								'details' =>  __('The table prefix %s is already in use. Please choose a different prefix to use with Symphony.', array('<code>' . $fields['database']['tbl_prefix'] . '</code>'))
							);
						}
					}
				}
			}
			catch(DatabaseException $e){
					$errors['unknown-database']  = array(
						'msg' => 'Database ‘' . $fields['database']['db'] . '’ not found.',
						'details' =>  __('Symphony was unable to connect to the specified database.')
					);
			}

			// Website name not entered
			if(trim($fields['general']['sitename']) == ''){
				$errors['general-no-sitename']  = array(
					'msg' => 'No sitename entered.',
					'details' => __('You must enter a Site name. This will be shown at the top of your backend.')
				);
			}

			// Username Not Entered
			if(trim($fields['user']['username']) == ''){
				$errors['user-no-username']  = array(
					'msg' => 'No username entered.',
					'details' => __('You must enter a Username. This will be your Symphony login information.')
				);
			}

			// Password Not Entered
			if(trim($fields['user']['password']) == ''){
				$errors['user-no-password']  = array(
					'msg' => 'No password entered.',
					'details' => __('You must enter a Password. This will be your Symphony login information.')
				);
			}

			// Password mismatch
			elseif($fields['user']['password'] != $fields['user']['confirm-password']){
				$errors['user-password-mismatch']  = array(
					'msg' => 'Passwords did not match.',
					'details' => __('The password and confirmation did not match. Please retype your password.')
				);
			}

			// No Name entered
			if(trim($fields['user']['firstname']) == '' || trim($fields['user']['lastname']) == ''){
				$errors['user-no-name']  = array(
					'msg' => 'Did not enter First and Last names.',
					'details' =>  __('You must enter your name.')
				);
			}

			// Invalid Email
			if(!preg_match('/^\w(?:\.?[\w%+-]+)*@\w(?:[\w-]*\.)+?[a-z]{2,}$/i', $fields['user']['email'])){
				$errors['user-invalid-email']  = array(
					'msg' => 'Invalid email address supplied.',
					'details' =>  __('This is not a valid email address. You must provide an email address since you will need it if you forget your password.')
				);
			}

			return $errors;
		}

		/**
		 * If something went wrong, the `__abort` function will write an entry to the Log
		 * file and display the failure page to the user.
		 * @todo: Resume installation after an error has been fixed.
		 */
		protected static function __abort($message, $start){
			Symphony::Log()->pushToLog($message, E_ERROR, true);

			Symphony::Log()->writeToLog(        '============================================', true);
			Symphony::Log()->writeToLog(sprintf('INSTALLATION ABORTED: Execution Time - %d sec (%s)',
				max(1, time() - $start),
				date('d.m.y H:i:s')
			), true);
			Symphony::Log()->writeToLog(        '============================================' . PHP_EOL . PHP_EOL . PHP_EOL, true);

			self::__render(new InstallerPage('failure'));
		}

		private static function __install(){
			$fields = $_POST['fields'];
			$start = time();

			Symphony::Log()->writeToLog(PHP_EOL . '============================================', true);
			Symphony::Log()->writeToLog(          'INSTALLATION PROCESS STARTED (' . DateTimeObj::get('c') . ')', true);
			Symphony::Log()->writeToLog(          '============================================', true);

			// MySQL: Establishing connection
			Symphony::Log()->pushToLog('MYSQL: Establishing Connection', E_NOTICE, true, true);

			try{
				Symphony::Database()->connect(
					$fields['database']['host'],
					$fields['database']['user'],
					$fields['database']['password'],
					$fields['database']['port'],
					$fields['database']['db']
				);
			}
			catch(DatabaseException $e){
				self::__abort(
					'There was a problem while trying to establish a connection to the MySQL server. Please check your settings.',
				$start);
			}

			// MySQL: Setting prefix & character encoding
			Symphony::Database()->setPrefix($fields['database']['tbl_prefix']);
			Symphony::Database()->setCharacterEncoding();
			Symphony::Database()->setCharacterSet();

			// MySQL: Importing schema
			Symphony::Log()->pushToLog('MYSQL: Importing Table Schema', E_NOTICE, true, true);

			try{
				Symphony::Database()->import(file_get_contents(INSTALL . '/includes/install.sql'), true);
			}
			catch(DatabaseException $e){
				self::__abort(
					'There was an error while trying to import data to the database. MySQL returned: ' . $e->getDatabaseErrorCode() . ': ' . $e->getDatabaseErrorMessage(),
				$start);
			}

			// MySQL: Creating default author
			Symphony::Log()->pushToLog('MYSQL: Creating Default Author', E_NOTICE, true, true);

			try{
				Symphony::Database()->insert(array(
					'id' 					=> 1,
					'username' 				=> Symphony::Database()->cleanValue($fields['user']['username']),
					'password' 				=> Cryptography::hash(Symphony::Database()->cleanValue($fields['user']['password'])),
					'first_name' 			=> Symphony::Database()->cleanValue($fields['user']['firstname']),
					'last_name' 			=> Symphony::Database()->cleanValue($fields['user']['lastname']),
					'email' 				=> Symphony::Database()->cleanValue($fields['user']['email']),
					'last_seen' 			=> NULL,
					'user_type' 			=> 'developer',
					'primary' 				=> 'yes',
					'default_area' 			=> NULL,
					'auth_token_active' 	=> 'no'
				), 'tbl_authors');
			}
			catch(DatabaseException $e){
				self::__abort(
					'There was an error while trying create the default author. MySQL returned: ' . $e->getDatabaseErrorCode() . ': ' . $e->getDatabaseErrorMessage(),
				$start);
			}

			// Configuration: Populating array
			$conf = Symphony::Configuration()->get();

			foreach($conf as $group => $settings){
				foreach($settings as $key => $value){
					if(isset($fields[$group]) && isset($fields[$group][$key])){
						$conf[$group][$key] = $fields[$group][$key];
					}
				}
			}

			// Create manifest folder structure
			Symphony::Log()->pushToLog('WRITING: Creating ‘manifest’ folder (/manifest)', E_NOTICE, true, true);
			if(!General::realiseDirectory(DOCROOT . '/manifest', $conf['directory']['write_mode'])){
				self::__abort(
					'Could not create ‘manifest’ directory. Check permission on the root folder.',
				$start);
			}

			Symphony::Log()->pushToLog('WRITING: Creating ‘logs’ folder (/manifest/logs)', E_NOTICE, true, true);
			if(!General::realiseDirectory(DOCROOT . '/manifest/logs', $conf['directory']['write_mode'])){
				self::__abort(
					'Could not create ‘logs’ directory. Check permission on /manifest.',
				$start);
			}

			Symphony::Log()->pushToLog('WRITING: Creating ‘cache’ folder (/manifest/cache)', E_NOTICE, true, true);
			if(!General::realiseDirectory(DOCROOT . '/manifest/cache', $conf['directory']['write_mode'])){
				self::__abort(
					'Could not create ‘cache’ directory. Check permission on /manifest.',
				$start);
			}

			Symphony::Log()->pushToLog('WRITING: Creating ‘tmp’ folder (/manifest/tmp)', E_NOTICE, true, true);
			if(!General::realiseDirectory(DOCROOT . '/manifest/tmp', $conf['directory']['write_mode'])){
				self::__abort(
					'Could not create ‘tmp’ directory. Check permission on /manifest.',
				$start);
			}

			// Writing configuration file
			Symphony::Log()->pushToLog('WRITING: Configuration File', E_NOTICE, true, true);

			Symphony::Configuration()->setArray($conf);

			if(!Symphony::Configuration()->write(CONFIG, $conf['file']['write_mode'])){
				self::__abort(
					'Could not create config file ‘' . CONFIG . '’. Check permission on /manifest.',
				$start);
			}

			// Writing htaccess file
			Symphony::Log()->pushToLog('CONFIGURING: Frontend', E_NOTICE, true, true);

			$rewrite_base = ltrim(preg_replace('/\/install$/i', NULL, dirname($_SERVER['PHP_SELF'])), '/');
			$htaccess = str_replace(
				'<!-- REWRITE_BASE -->', $rewrite_base,
				file_get_contents(INSTALL . '/includes/htaccess.txt')
			);

			if(!General::writeFile(DOCROOT . "/.htaccess", $htaccess, $conf['file']['write_mode'], 'a')){
				self::__abort(
					'Could not write ‘.htaccess’ file. Check permission on ' . DOCROOT,
				$start);
			}

			// Writing /workspace folder
			if(!is_dir(DOCROOT . '/workspace')){
				// Create workspace folder structure
				Symphony::Log()->pushToLog('WRITING: Creating ‘workspace’ folder (/workspace)', E_NOTICE, true, true);
				if(!General::realiseDirectory(DOCROOT . '/workspace', $conf['directory']['write_mode'])){
					self::__abort(
						'Could not create ‘workspace’ directory. Check permission on the root folder.',
					$start);
				}

				Symphony::Log()->pushToLog('WRITING: Creating ‘data-sources’ folder (/workspace/data-sources)', E_NOTICE, true, true);
				if(!General::realiseDirectory(DOCROOT . '/workspace/data-sources', $conf['directory']['write_mode'])){
					self::__abort(
						'Could not create ‘workspace/data-sources’ directory. Check permission on the root folder.',
					$start);
				}

				Symphony::Log()->pushToLog('WRITING: Creating ‘events’ folder (/workspace/events)', E_NOTICE, true, true);
				if(!General::realiseDirectory(DOCROOT . '/workspace/events', $conf['directory']['write_mode'])){
					self::__abort(
						'Could not create ‘workspace/events’ directory. Check permission on the root folder.',
					$start);
				}

				Symphony::Log()->pushToLog('WRITING: Creating ‘pages’ folder (/workspace/pages)', E_NOTICE, true, true);
				if(!General::realiseDirectory(DOCROOT . '/workspace/pages', $conf['directory']['write_mode'])){
					self::__abort(
						'Could not create ‘workspace/pages’ directory. Check permission on the root folder.',
					$start);
				}

				Symphony::Log()->pushToLog('WRITING: Creating ‘utilities’ folder (/workspace/utilities)', E_NOTICE, true, true);
				if(!General::realiseDirectory(DOCROOT . '/workspace/utilities', $conf['directory']['write_mode'])){
					self::__abort(
						'Could not create ‘workspace/utilities’ directory. Check permission on the root folder.',
					$start);
				}
			}

			else {
				Symphony::Log()->pushToLog('An existing ‘workspace’ directory was found at this location. Symphony will use this workspace.', E_NOTICE, true, true);

				// MySQL: Importing workspace data
				Symphony::Log()->pushToLog('MYSQL: Importing Workspace Data...', E_NOTICE, true, true);

				if(is_file(DOCROOT . '/workspace/install.sql')) {
					try{
						Symphony::Database()->import(
							file_get_contents(DOCROOT . '/workspace/install.sql'),
							($fields['database']['use-server-encoding'] != 'yes' ? true : false),
							true
						);
					}
					catch(DatabaseException $e){
						self::__abort(
							'There was an error while trying to import data to the database. MySQL returned: ' . $e->getDatabaseErrorCode() . ': ' . $e->getDatabaseErrorMessage(),
						$start);
					}
				}
			}

			// Write extensions folder
			if(!is_dir(DOCROOT . '/extensions')) {
				// Create extensions folder
				Symphony::Log()->pushToLog('WRITING: Creating ‘extensions’ folder (/extensions)', E_NOTICE, true, true);
				if(!General::realiseDirectory(DOCROOT . '/extensions', $conf['directory']['write_mode'])){
					self::__abort(
						'Could not create ‘extension’ directory. Check permission on the root folder.',
					$start);
				}
			}

			// Install existing extensions
			Symphony::Log()->pushToLog('CONFIGURING: Installing existing extensions', E_NOTICE, true, true);
			$disabled_extensions = array();
			foreach(new DirectoryIterator(EXTENSIONS) as $e) {
				if($e->isDot() || $e->isFile() || !is_file($e->getRealPath() . '/extension.driver.php')) continue;

				$handle = $e->getBasename();
				try {
					if(!ExtensionManager::enable($handle)){
						$disabled_extensions[] = $handle;
						Symphony::Log()->pushToLog('Could not enable the extension ‘' . $handle . '’.', E_NOTICE, true, true);
					}
				} catch(Exception $ex) {
					$disabled_extensions[] = $handle;
					Symphony::Log()->pushToLog('Could not enable the extension ‘' . $handle . '’. '. $ex->getMessage(), E_NOTICE, true, true);
				}
			}

			// Loading default language
			if(isset($_REQUEST['lang']) && $_REQUEST['lang'] != 'en'){
				Symphony::Log()->pushToLog('CONFIGURING: Default language', E_NOTICE, true, true);

				$language = Lang::Languages();
				$language = $language[$_REQUEST['lang']];

				// Is the language extension enabled?
				if(in_array('lang_' . $language['handle'], ExtensionManager::listInstalledHandles())){
					Symphony::Configuration()->set('lang', $_REQUEST['lang'], 'symphony');
					if(!Symphony::Configuration()->write(CONFIG, $conf['file']['write_mode'])){
						Symphony::Log()->pushToLog('Could not write default language ‘' . $language['name'] . '’ to config file.', E_NOTICE, true, true);
					}
				}
				else{
					Symphony::Log()->pushToLog('Could not enable the desired language ‘' . $language['name'] . '’.', E_NOTICE, true, true);
				}
			}

			// Installation completed. Woo-hoo!
			Symphony::Log()->writeToLog(        '============================================', true);
			Symphony::Log()->writeToLog(sprintf('INSTALLATION COMPLETED: Execution Time - %d sec (%s)',
				max(1, time() - $start),
				date('d.m.y H:i:s')
			), true);
			Symphony::Log()->writeToLog(        '============================================' . PHP_EOL . PHP_EOL . PHP_EOL, true);

			return $disabled_extensions;
		}

		protected static function __render(InstallerPage $page) {
			$output = $page->generate();

			header('Content-Type: text/html; charset=utf-8');
			echo $output;
			exit;
		}

	}
