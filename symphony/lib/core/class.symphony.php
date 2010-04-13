<?php

	require_once(CORE . '/class.errorhandler.php');

	require_once(TOOLKIT . '/class.dbc.php');
	require_once(CORE . '/class.configuration.php');
	require_once(CORE . '/class.datetimeobj.php');
	require_once(CORE . '/class.log.php');
	require_once(CORE . '/class.cookie.php');
	require_once(CORE . '/interface.singleton.php');
	require_once(CORE . '/class.cache.php');

	require_once(TOOLKIT . '/class.page.php');
	require_once(TOOLKIT . '/class.view.php');
	require_once(TOOLKIT . '/class.xmlelement.php');
	require_once(TOOLKIT . '/class.widget.php');
	require_once(TOOLKIT . '/class.general.php');
	require_once(TOOLKIT . '/class.profiler.php');
	require_once(TOOLKIT . '/class.user.php');
	require_once(TOOLKIT . '/class.xslproc.php');

	require_once(TOOLKIT . '/class.usermanager.php');
	require_once(TOOLKIT . '/class.extensionmanager.php');

	Class SymphonyErrorPageHandler extends GenericExceptionHandler{
		public static function render($e){

			if(is_null($e->getTemplatePath())){
				header('HTTP/1.0 500 Server Error');
				echo '<h1>Symphony Fatal Error</h1><p>'.$e->getMessage().'</p>';
				exit;
			}

			$xml = new DOMDocument('1.0', 'utf-8');
			$xml->formatOutput = true;

			$root = $xml->createElement('data');
			$xml->appendChild($root);

			$root->appendChild($xml->createElement('heading', General::sanitize($e->getHeading())));
			$root->appendChild($xml->createElement('message', General::sanitize(
				$e->getMessageObject() instanceof XMLElement ? $e->getMessageObject()->generate(true) : trim($e->getMessage())
			)));
			if(!is_null($e->getDescription())){
				$root->appendChild($xml->createElement('description', General::sanitize($e->getDescription())));
			}

			header('HTTP/1.0 500 Server Error');
			header('Content-Type: text/html; charset=UTF-8');
			header('Symphony-Error-Type: ' . $e->getErrorType());

			foreach($e->getHeaders() as $header){
				header($header);
			}

			$output = parent::__transform($xml, basename($e->getTemplatePath()));

			header(sprintf('Content-Length: %d', strlen($output)));
			echo $output;

			exit;
		}
	}

	Class SymphonyErrorPage extends Exception{

		private $_heading;
		private $_message;
		private $_type;
		private $_headers;
		private $_messageObject;
		private $_help_line;

		public function __construct($message, $heading='Fatal Error', $description=NULL, array $headers=array()){

			$this->_messageObject = NULL;
			if($message instanceof XMLElement){
				$this->_messageObject = $message;
				$message = $this->_messageObject->generate();
			}

			parent::__construct($message);

			$this->_heading = $heading;
			$this->_headers = $headers;
			$this->_description = $description;
		}

		public function getMessageObject(){
			return $this->_messageObject;
		}

		public function getHeading(){
			return $this->_heading;
		}

		public function getErrorType(){
			return $this->_template;
		}

		public function getDescription(){
			return $this->_description;
		}

		public function getTemplatePath(){

			$template = NULL;

			if(file_exists(MANIFEST . '/templates/exception.symphony.xsl')){
				$template = MANIFEST . '/templates/exception.symphony.xsl';
			}

			elseif(file_exists(TEMPLATES . '/exception.symphony.xsl')){
				$template = TEMPLATES . '/exception.symphony.xsl';
			}

			return $template;
		}

		public function getHeaders(){
			return $this->_headers;
		}
	}

	Abstract Class Symphony implements Singleton{

		public static $Log;

		protected static $Configuration;
		protected static $Database;

		protected static $_lang;

		public $Profiler;
		public $Cookie;
		public $User;

		protected static $_instance;

		const CRLF = "\r\n";

		protected function __construct(){

			$this->Profiler = new Profiler;

			if(get_magic_quotes_gpc()) {
				General::cleanArray($_SERVER);
				General::cleanArray($_COOKIE);
				General::cleanArray($_GET);
				General::cleanArray($_POST);
			}

			self::$Configuration = new Configuration;

			DateTimeObj::setDefaultTimezone(self::Configuration()->get('timezone', 'region'));

			self::$_lang = (self::Configuration()->get('lang', 'symphony') ? self::Configuration()->get('lang', 'symphony') : 'en');

			// Legacy support for __LANG__ constant
			define_safe('__LANG__', self::lang());

			define_safe('__SYM_DATE_FORMAT__', self::Configuration()->get('date_format', 'region'));
			define_safe('__SYM_TIME_FORMAT__', self::Configuration()->get('time_format', 'region'));
			define_safe('__SYM_DATETIME_FORMAT__', __SYM_DATE_FORMAT__ . ' ' . __SYM_TIME_FORMAT__);

			define_safe('ADMIN', trim(self::Configuration()->core()->symphony->{'administration-path'}, '/'));
			define_safe('ADMIN_URL', URL . '/' . ADMIN);

			$this->initialiseLog();

			GenericExceptionHandler::initialise();
			GenericErrorHandler::initialise(self::$Log);

			$this->initialiseCookie();

			$this->initialiseDatabase();

			Cache::setDriver(self::Configuration()->core()->{'cache-driver'});

			Lang::loadAll(true);

		}

		public function lang(){
			return self::$_lang;
		}
		public function initialiseCookie(){

			$cookie_path = @parse_url(URL, PHP_URL_PATH);
			$cookie_path = '/' . trim($cookie_path, '/');

			define_safe('__SYM_COOKIE_PATH__', $cookie_path);
			define_safe('__SYM_COOKIE_PREFIX__', self::Configuration()->get('cookie_prefix', 'symphony'));

			$this->Cookie = new Cookie(__SYM_COOKIE_PREFIX__, TWO_WEEKS, __SYM_COOKIE_PATH__);
		}

		public static function Configuration(){
			return self::$Configuration;
		}

		public static function Database(){
			return self::$Database;
		}

		public static function Parent() {
			if (class_exists('Administration')) {
				return Administration::instance();
			}

			else {
				return Frontend::instance();
			}
		}

		public function initialiseDatabase(){
			$details = (object)Symphony::Configuration()->db();

			$db = new DBCMySQLProfiler;

			if($details->runtime_character_set_alter == 'yes'){
				$db->character_encoding = $details->character_encoding;
				$db->character_set = $details->character_set;
			}

			$connection_string = sprintf('mysql://%s:%s@%s:%s/%s/',
											$details->user,
											$details->password,
											$details->host,
											$details->port,
											$details->db);

			$db->connect($connection_string);
			$db->prefix = $details->{'tbl-prefix'};

			$db->force_query_caching = NULL;
			if(!is_null($details->disable_query_caching)) $db->force_query_caching = ($details->disable_query_caching == 'yes' ? true : false);

			self::$Database = $db;

			return true;
		}

		public function initialiseLog(){

			self::$Log = new Log(ACTIVITY_LOG);
			self::$Log->setArchive((self::Configuration()->get('archive', 'log') == '1' ? true : false));
			self::$Log->setMaxSize(intval(self::Configuration()->get('maxsize', 'log')));

			if(self::$Log->open() == 1){
				self::$Log->writeToLog('Symphony Log', true);
				self::$Log->writeToLog('Version: '. self::Configuration()->get('version', 'symphony'), true);
				self::$Log->writeToLog('--------------------------------------------', true);
			}

		}

		public function isLoggedIn(){

			if ($this->User) return true;

			$username = $this->Cookie->get('username');
			$password = $this->Cookie->get('pass');

			if(strlen(trim($username)) > 0 && strlen(trim($password)) > 0){
				$result = Symphony::Database()->query(
					"
						SELECT
							u.id
						FROM
							tbl_users AS u
						WHERE
							u.username = '%s'
							AND u.password = '%s'
						LIMIT 1
					",
					array($username, $password)
				);

				if ($result->valid()) {
					$this->_user_id = $result->current()->id;

					Symphony::Database()->update(
						'tbl_users',
						array('last_seen' => DateTimeObj::get('Y-m-d H:i:s')),
						array($this->_user_id),
						"`id` = '%s'"
					);

					$this->User = new User($this->_user_id);
					$this->reloadLangFromAuthorPreference();

					return true;
				}
			}

			$this->Cookie->expire();
			return false;
		}

		public function logout(){
			$this->Cookie->expire();
		}

		// TODO: Most of this logic is duplicated with the isLoggedIn function.
		public function login($username, $password, $isHash = false) {
			if (strlen(trim($username)) > 0 && strlen(trim($password)) > 0) {
				if (!$isHash) $password = md5($password);

				$result = Symphony::Database()->query(
					"
						SELECT
							u.id
						FROM
							tbl_users AS u
						WHERE
							u.username = '%s'
							AND u.password = '%s'
						LIMIT 1
					",
					array($username, $password)
				);

				if ($result->valid()) {
					$this->_user_id = $result->current()->id;

					$this->User = new User($this->_user_id);
					$this->Cookie->set('username', $username);
					$this->Cookie->set('pass', $password);

					Symphony::Database()->update(
						'tbl_users',
						array('last_seen' => DateTimeObj::get('Y-m-d H:i:s')),
						array($this->_user_id),
						"`id` = '%d'"
					);

					$this->reloadLangFromAuthorPreference();

					return true;
				}
			}

			return false;
		}

		public function loginFromToken($token){
			$token = Symphony::Database()->escape($token);

			if (strlen(trim($token)) == 0) return false;

			if (strlen($token) == 6){
				$result = Symphony::Database()->query("
						SELECT
							`u`.id, `u`.username, `u`.password
						FROM
							`tbl_users` AS u, `tbl_forgotpass` AS f
						WHERE
							`u`.id = `f`.user_id
						AND
							`f`.expiry > '%s'
						AND
							`f`.token = '%s'
						LIMIT 1
					",
					DateTimeObj::getGMT('c'),
					$token
				);

				Symphony::Database()->delete('tbl_forgotpass', array($token), "`token` = '%s'");
			}

			else{
				$result = Symphony::Database()->query("
						SELECT
							id, username, password
						FROM
							`tbl_users`
						WHERE
							SUBSTR(MD5(CONCAT(`username`, `password`)), 1, 8) = '%s'
						AND
							auth_token_active = 'yes'
						LIMIT 1
					",
					$token
				);
			}

			if($result->valid()) {
				$row = $result->current();
				$this->_user_id = $row->id;

				$this->User = new User($this->_user_id);
				$this->Cookie->set('username', $row->username);
				$this->Cookie->set('pass', $row->password);

				Symphony::Database()->update(
					'tbl_authors',
					array('last_seen' => DateTimeObj::getGMT('Y-m-d H:i:s')),
					array($this->_user_id),
					"`id` = '%d'"
				);

				$this->reloadLangFromAuthorPreference();

				return true;
			}

			return false;

		}

		public function reloadLangFromAuthorPreference(){

			$lang = $this->User->get('language');
			if($lang && $lang != self::lang()){
				self::$_lang = $lang;
				if($lang != 'en') {
					Lang::loadAll();
				}
				else {
					// As there is no English dictionary the default dictionary needs to be cleared
					Lang::clear();
				}
			}

		}

		public function resolvePageTitle($page_id) {
			$path = $this->resolvePage($page_id, 'title');

			if(!is_array($path)) $path = array($path);

			return implode(': ', $path);
		}

		public function resolvePagePath($page_id) {
			$path = $this->resolvePage($page_id, 'handle');

			if(!is_array($path)) $path = array($path);

			return implode('/', $path);
		}


		// TODO: Fix this, now that Pages are Views and not in the database
		public function resolvePage($page_id, $column) {
			$page = self::$Database->query("
				SELECT
					p.{$column},
					p.parent
				FROM
					`tbl_pages` AS p
				WHERE
					p.id = '{$page_id}'
					OR p.handle = '{$page_id}'
				LIMIT 1
			");

			$path = array(
				$page[$column]
			);

			if ($page['parent'] != null) {
				$next_parent = $page['parent'];

				while (
					$parent = self::$Database->fetchRow(0, "
						SELECT
							p.*
						FROM
							`tbl_pages` AS p
						WHERE
							p.id = '{$next_parent}'
					")
				) {
					array_unshift($path, $parent[$column]);

					$next_parent = $parent['parent'];
				}
			}

			return $path;
		}

		public function customError($code, $heading, $message, $log=true, $forcekill=false, $template='general', array $additional=array()){
			throw new SymphonyErrorPage($message, $heading, $template, $additional);
		}

	}

	return 'Symphony';