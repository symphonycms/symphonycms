<?php

	require_once(CORE . '/class.errorhandler.php');
	
	require_once(CORE . '/class.configuration.php');
	require_once(CORE . '/class.datetimeobj.php');
	require_once(CORE . '/class.log.php');
	require_once(CORE . '/class.cookie.php');
	require_once(CORE . '/interface.singleton.php');
	
	require_once(TOOLKIT . '/class.page.php');
	require_once(TOOLKIT . '/class.view.php');
	require_once(TOOLKIT . '/class.xmlelement.php');
	require_once(TOOLKIT . '/class.widget.php');
	require_once(TOOLKIT . '/class.general.php');
	require_once(TOOLKIT . '/class.profiler.php');
	require_once(TOOLKIT . '/class.user.php');

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
			
			
			
			/*$args = array(
				$e->getHeading(),
				URL,
				($e->getMessageObject() instanceof XMLElement ? $e->getMessageObject()->generate(true) : trim($e->getMessage())),
			);*/

			header('HTTP/1.0 500 Server Error');
			header('Content-Type: text/html; charset=UTF-8');
			header('Symphony-Error-Type: ' . $e->getErrorType());
			
			foreach($e->getHeaders() as $header){
				header($header);
			}

			$output = parent::__transform($xml, 'error.symphony.xsl');

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

		public function __construct($message, $heading='Symphony Fatal Error', $description=NULL, array $headers=array()){

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

			if(file_exists(MANIFEST . '/templates/error.symphony.xsl')){
				$template = MANIFEST . '/templates/error.symphony.xsl';
			}
			
			elseif(file_exists(TEMPLATES . '/error.symphony.xsl')){
				$template = TEMPLATES . '/error.symphony.xsl';
			}

			return $template;
		}

		public function getHeaders(){
			return $this->_headers;
		}	
	}
		
	Abstract Class Symphony implements Singleton{
		
		protected static $Configuration;
		protected static $Database;
		
		protected static $_lang;
		
		public $Log;
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
			GenericErrorHandler::initialise($this->Log);
			
			$this->initialiseCookie();

			$this->initialiseDatabase();
			
			Lang::loadAll(true);

			DateTimeObj::setDefaultTimezone(self::Configuration()->get('timezone', 'region'));
			
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
			$error = NULL;
			
			$driver = self::Configuration()->db()->driver;
			$driver_filename = TOOLKIT . "/class.{$driver}.php";

			if(!is_file($driver_filename)){
				throw new SymphonyErrorPage(
					__('Missing database driver "%s"', array($driver)), 
					__('Symphony Database Error'), 
					__('The database driver specified in "manifest/conf/db.xml" could not be found. Please ensure it exists and is readable.')
				);
			}
			
			require_once($driver_filename);
			
			self::$Database = new $driver;
			
			$details = self::Configuration()->db()->properties();

			try{
				if(!self::$Database->connect($details->host, $details->user, $details->password, $details->port)) return false;
				if(!self::$Database->select($details->db)) return false;
				if(!self::$Database->isConnected()) return false;
			
				self::$Database->setPrefix($details->{'tbl-prefix'});

				if($details->{'runtime_character_set_alter'} == '1'){
					self::$Database->setCharacterEncoding($details->{'character_encoding'});
					self::$Database->setCharacterSet($details->{'character_set'});
				}

				if($details->{'query-caching'} == 'off') self::$Database->disableCaching();
				elseif($details->{'query-caching'} == 'on') self::$Database->enableCaching();
			}
			catch(DatabaseException $e){
				$error = self::$Database->getlastError();
				throw new SymphonyErrorPage(
					$error['num'] . ': ' . $error['msg'], 
					'Symphony Database Error',
					__('There was a problem whilst attempting to establish a database connection. Please check all connection information is correct. The following error was returned.')
				);				
			}
			
			return true;
		}
		
		public function initialiseLog(){
			
			$this->Log = new Log(ACTIVITY_LOG);
			$this->Log->setArchive((self::Configuration()->get('archive', 'log') == '1' ? true : false));
			$this->Log->setMaxSize(intval(self::Configuration()->get('maxsize', 'log')));
				
			if($this->Log->open() == 1){
				$this->Log->writeToLog('Symphony Log', true);
				$this->Log->writeToLog('Version: '. self::Configuration()->get('version', 'symphony'), true);
				$this->Log->writeToLog('--------------------------------------------', true);
			}
						
		}
		
		public function isLoggedIn(){
			
			if ($this->User) return true;
			
			$username = self::$Database->cleanValue($this->Cookie->get('username'));
			$password = self::$Database->cleanValue($this->Cookie->get('pass'));
			
			if(strlen(trim($username)) > 0 && strlen(trim($password)) > 0){
			
				$id = self::$Database->fetchVar('id', 0, "SELECT `id` FROM `tbl_users` WHERE `username` = '$username' AND `password` = '$password' LIMIT 1");

				if($id){
					$this->_user_id = $id;
					self::$Database->update(array('last_seen' => DateTimeObj::get('Y-m-d H:i:s')), 'tbl_users', " `id` = '$id'");
					$this->User = new User($id);
					
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

		public function login($username, $password, $isHash=false){
			
			$username = self::$Database->cleanValue($username);
			$password = self::$Database->cleanValue($password);
			
			if(strlen(trim($username)) > 0 && strlen(trim($password)) > 0){			
				
				if(!$isHash) $password = md5($password);

				$id = self::$Database->fetchVar('id', 0, "SELECT `id` FROM `tbl_users` WHERE `username` = '$username' AND `password` = '$password' LIMIT 1");

				if($id){
					$this->_user_id = $id;
					$this->User = new User($id);
					$this->Cookie->set('username', $username);
					$this->Cookie->set('pass', $password);
					self::$Database->update(array('last_seen' => DateTimeObj::get('Y-m-d H:i:s')), 'tbl_users', " `id` = '{$id}'");
					
					$this->reloadLangFromAuthorPreference();

					return true;
				}
			}
			
			return false;
			
		}
		
		public function loginFromToken($token){
			
			$token = self::$Database->cleanValue($token);
			
			if(strlen(trim($token)) == 0) return false;
			
			if(strlen($token) == 6){
				$row = self::$Database->fetchRow(0, "SELECT `a`.`id`, `a`.`username`, `a`.`password` 
													 FROM `tbl_users` AS `a`, `tbl_forgotpass` AS `f`
													 WHERE `a`.`id` = `f`.`user_id` AND `f`.`expiry` > '".DateTimeObj::getGMT('c')."' AND `f`.`token` = '$token'
													 LIMIT 1");
				
				self::$Database->delete('tbl_forgotpass', " `token` = '{$token}' ");
			}
			
			else{
				$row = self::$Database->fetchRow(0, "SELECT `id`, `username`, `password` 
													 FROM `tbl_users` 
													 WHERE SUBSTR(MD5(CONCAT(`username`, `password`)), 1, 8) = '$token' AND `auth_token_active` = 'yes' 
													 LIMIT 1");				
			}

			if($row){
				$this->_user_id = $row['id'];
				$this->User = new User($row['id']);
				$this->Cookie->set('username', $row['username']);
				$this->Cookie->set('pass', $row['password']);
				self::$Database->update(array('last_seen' => DateTimeObj::getGMT('Y-m-d H:i:s')), 'tbl_authors', " `id` = '$id'");
				
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
			
			return @implode(': ', $path);
		}
		
		public function resolvePagePath($page_id) {
			$path = $this->resolvePage($page_id, 'handle');
			
			return @implode('/', $path);
		}

		public function resolvePage($page_id, $column) {
			$page = self::$Database->fetchRow(0, "
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