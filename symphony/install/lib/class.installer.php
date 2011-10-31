<?php

	require_once(CORE . '/class.errorhandler.php');
	require_once(CORE . '/class.log.php');
	require_once(CORE . '/class.configuration.php');
	require_once(CORE . '/class.datetimeobj.php');

	require_once(TOOLKIT . '/class.lang.php');
	require_once(TOOLKIT . '/class.general.php');
	require_once(TOOLKIT . '/class.mysql.php');
	require_once(TOOLKIT . '/class.xmlelement.php');
	require_once(TOOLKIT . '/class.widget.php');

	Class InstallerException extends Exception {

		const TYPE_REQUIREMENT = 0;
		const TYPE_ERROR       = 1;

		/**
		 * An associative array with three keys, 'query', 'msg' and 'num'
		 * @var array
		 */
		private $_error = array(); // $type, $logmsg, $usermsg

		/**
		 * Constructor takes a message and an associative array to set to
		 * `$_error`. The message is passed to the default Exception constructor
		 */
		public function __construct(array $error){
			parent::__construct($error['logmsg']);
			$this->_error = $error;
		}

		/**
		 * Accessor function for the original query that caused this Exception
		 *
		 * @return string
		 */
		public function getType(){
			return $this->_error['type'];
		}

		/**
		 * Accessor function for the Database message from this Exception
		 *
		 * @return string
		 */
		public function getUserMessage(){
			return $this->_error['pagemsg'];
		}

	}

	Class InstallerExceptionHandler extends GenericExceptionHandler {

		/**
		 * Initialise will set the error handler to be the `__CLASS__::handler` function.
		 *
		 * @param Log|null $log
		 *  An instance of a Symphony Log object to write errors to
		 */
		public static function initialise(Log $Log = null){
			parent::initialise($Log);

			self::$enabled = true;
		}

		/**
		 * The render function will take an Exception and output a HTML page
		 *
		 * @param Exception $e
		 *  The Exception object
		 * @return string
		 *  An HTML string
		 */
		public static function render(InstallerException $e){

			$lines = NULL;
			$odd = true;

			$markdown = "\t" . $e->getMessage() . "\n";
			$markdown .= "\t" . $e->getFile() . " line " . $e->getLine() . "\n\n";
			foreach(self::__nearByLines($e->getLine(), $e->getFile()) as $line => $string) {
				$markdown .= "\t" . ($line+1) . $string;
			}

			foreach(self::__nearByLines($e->getLine(), $e->getFile()) as $line => $string){
				$lines .= sprintf(
					'<li%s%s><strong>%d:</strong> <code>%s</code></li>',
					($odd == true ? ' class="odd"' : NULL),
					(($line+1) == $e->getLine() ? ' id="error"' : NULL),
					++$line,
					str_replace("\t", '&nbsp;&nbsp;&nbsp;&nbsp;', htmlspecialchars($string))
				);

				$odd = !$odd;
			}

			$trace = NULL;
			$odd = true;

			foreach($e->getTrace() as $t){
				$trace .= sprintf(
					'<li%s><code>[%s:%d] <strong>%s%s%s();</strong></code></li>',
					($odd == true ? ' class="odd"' : NULL),
					(isset($t['file']) ? $t['file'] : NULL),
					(isset($t['line']) ? $t['line'] : NULL),
					(isset($t['class']) ? $t['class'] : NULL),
					(isset($t['type']) ? $t['type'] : NULL),
					$t['function']
				);
				$odd = !$odd;
			}

			$queries = NULL;
			$odd = true;
			if(is_object(Symphony::Database())){

				$debug = Symphony::Database()->debug();

				if(count($debug['query']) > 0){
					foreach($debug['query'] as $query){

						$queries .= sprintf(
							'<li%s><code>%s;</code> <small>[%01.4f]</small></li>',
							($odd == true ? ' class="odd"' : NULL),
							htmlspecialchars($query['query']),
							(isset($query['time']) ? $query['time'] : NULL)
						);
						$odd = !$odd;
					}
				}

			}

			return sprintf(file_get_contents(TEMPLATE . '/errorhandler.tpl'),
				($e instanceof ErrorException ? GenericErrorHandler::$errorTypeStrings[$e->getSeverity()] : 'Fatal Error'),
				$e->getMessage(),
				$e->getFile(),
				$e->getLine(),
				$markdown,
				$lines,
				$trace,
				$queries
			);

		}

	}

	Class Installer {

		private static $_page;

		private static $_log;

		private static $_conf;

		public static function initialize(){

			self::__checkEssentialRequirements();

			// Initialize language
			$lang = 'en';

			if(!empty($_REQUEST['lang'])){
				$lang = preg_replace('/[^a-zA-Z\-]/', NULL, $_REQUEST['lang']);
			}

			Lang::initialize();
			Lang::set($lang, false);

			// Initialize log
			self::$_log = new Log('install-log.txt');

			if(@file_exists(self::$_log->getLogPath())){
				self::$_log->open();
			}
			else{
				self::$_log->open(Log::OVERWRITE);

				self::$_log->writeToLog('Symphony Installer Log', true);
				self::$_log->writeToLog('Opened: '. DateTimeObj::get('c'), true);
				self::$_log->writeToLog('Version: '. kVERSION, true);
				self::$_log->writeToLog('Domain: '._INSTALL_URL_, true);
				self::$_log->writeToLog('--------------------------------------------', true);
			}

			// Initialize configuration
			self::$_conf = new Configuration();

			include_once(INSTALL . '/includes/defaultconfig.php'); // $conf

			self::$_conf->setArray($conf);
			unset($conf);

			// Define some other constants
			$clean_path = $_SERVER["HTTP_HOST"] . dirname($_SERVER["PHP_SELF"]);
			$clean_path = rtrim($clean_path, '/\\');
			$clean_path = preg_replace('/\/{2,}/i', '/', $clean_path);

			define('_INSTALL_DOMAIN_', $clean_path);
			define('_INSTALL_URL_', 'http://' . $clean_path);

			// If its not an update, we need to set a couple of important constants.
			define('__IN_SYMPHONY__', true);
			define('DOCROOT', './');

			$rewrite_base = trim(dirname($_SERVER['PHP_SELF']), '/\\');

			if(strlen($rewrite_base) > 0){
				$rewrite_base .= '/';
			}

			define('REWRITE_BASE', $rewrite_base);
		}

		private static function __checkEssentialRequirements(){
			$errors = array();

			// Check if Symphony is already installed
			if(file_exists(SYMPHONY . '/manifest/config.php')){
				$errors[] = array(
					'type' => InstallerException::TYPE_ERROR,
#					'log' => 'Error - Symphony is already installed.',
					'msg' => array(
						'short' => __('Existing Installation'),
						'long'  => __('It appears that Symphony has already been installed at this location.')
					)
				);
			}
			else {

				// Check for PHP 5.2+
				if(version_compare(phpversion(), '5.2', '<=')){
					$errors[] = array(
						'type' => InstallerException::TYPE_REQUIREMENT,
#						'log' => 'Requirement - PHP Version is not correct. '.phpversion().' detected.',
						'msg' => array(
							'short' => __('PHP Version is not correct. %s detected', array(phpversion())),
							'long'  => __('Symphony needs %s or above', array('<abbr title="PHP: Hypertext Pre-processor">PHP</abbr> 5.2'))
						)
					);
				}

				// Make sure the install.sql file exists
				if(!file_exists(INSTALL . '/includes/install.sql') || !is_readable(INSTALL . '/includes/install.sql')){
					$errors[] = array(
						'type' => InstallerException::TYPE_REQUIREMENT,
#						'log' => 'Requirement - Missing install.sql file.',
						'msg' => array(
							'short' => __('Missing install.sql file'),
							'long'  => __('It appears that %s is either missing or not readable. This is required to populate the database and must be uploaded before installation can commence. Ensure that PHP has read permissions.', array('<code>install.sql</code>'))
						)
					);
				}

			}

			// Throw an exception if errors are present
			if(!empty($errors)){
				throw new InstallerException($errors);
			}

		}

		private function __checkRequirement($item, $type, $expected){

			switch($type){

				case 'func':

					$test = function_exists($item);
					if($test != $expected) return false;
					break;

				case 'setting':
					$test = ini_get($item);
					if(strtolower($test) != strtolower($expected)) return false;
					break;

				case 'ext':
					foreach(explode(':', $item) as $ext){
						$test = extension_loaded($ext);
						if($test == $expected) return true;
					}

					return false;
					break;

				 case 'version':
					if(version_compare($item, $expected, '>=') != 1) return false;
					break;

				 case 'permission':
					if(!is_writable($item)) return false;
					break;

				 case 'remote':
					$result = curler($item);
					if(strpos(strtolower($result), 'error') !== false) return false;
					break;

			}

			return true;

		}


#		public static function getDefaultConfiguration(){

#		}


#		function getTableSchema(){
#			return file_get_contents('install.sql');
#		}

#		function getWorkspaceData(){
#			return file_get_contents('workspace/install.sql');
#		}

	}
