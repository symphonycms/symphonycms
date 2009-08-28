<?php

	require_once(CORE . '/class.errorhandler.php');
	
	require_once(CORE . '/class.configuration.php');
	require_once(CORE . '/class.datetimeobj.php');
	require_once(CORE . '/class.log.php');
	require_once(CORE . '/class.cookie.php');
	require_once(CORE . '/interface.singleton.php');
	
	require_once(TOOLKIT . '/class.page.php');
	require_once(TOOLKIT . '/class.xmlelement.php');
	require_once(TOOLKIT . '/class.widget.php');
	require_once(TOOLKIT . '/class.general.php');
	require_once(TOOLKIT . '/class.profiler.php');
	require_once(TOOLKIT . '/class.author.php');

	require_once(TOOLKIT . '/class.authormanager.php');	
	require_once(TOOLKIT . '/class.extensionmanager.php');
		
	Abstract Class Symphony implements Singleton{
		
		
		public static $Configuration;
		public static $Database;
		
		public $Log;
		public $Profiler;
		public $Cookie;
		public $Author;
		public $ExtensionManager;
		
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
			
			include(CONFIG);
			self::$Configuration = new Configuration(true);
			self::$Configuration->setArray($settings);

			define_safe('__LANG__', (self::$Configuration->get('lang', 'symphony') ? self::$Configuration->get('lang', 'symphony') : 'en'));				
			
			define_safe('__SYM_DATE_FORMAT__', self::$Configuration->get('date_format', 'region'));
			define_safe('__SYM_TIME_FORMAT__', self::$Configuration->get('time_format', 'region'));
			define_safe('__SYM_DATETIME_FORMAT__', __SYM_DATE_FORMAT__ . ' ' . __SYM_TIME_FORMAT__);
						
			$this->initialiseLog();

			GenericExceptionHandler::initialise();
			GenericErrorHandler::initialise($this->Log);
			
			$this->initialiseCookie();
			
			try{
				Lang::init(LANG . '/lang.%s.php', __LANG__);
			}
			catch(Exception $e){
				trigger_error($e->getMessage(), E_USER_ERROR);
			}

			$this->initialiseDatabase();
	
			if(!$this->initialiseExtensionManager()){
				throw new SymphonyErrorPage('Error creating Symphony extension manager.');
			}

			DateTimeObj::setDefaultTimezone(self::$Configuration->get('timezone', 'region'));
			
		}
		
		public function initialiseCookie(){
			
			$cookie_path = @parse_url(URL, PHP_URL_PATH);
			$cookie_path = '/' . trim($cookie_path, '/');
			
			define_safe('__SYM_COOKIE_PATH__', $cookie_path);
			define_safe('__SYM_COOKIE_PREFIX_', self::$Configuration->get('cookie_prefix', 'symphony'));
						
			$this->Cookie = new Cookie(__SYM_COOKIE_PREFIX_, TWO_WEEKS, __SYM_COOKIE_PATH__);			
		}
		
		public function initialiseExtensionManager(){
			$this->ExtensionManager = new ExtensionManager($this);
			return ($this->ExtensionManager instanceof ExtensionManager);
		}
		
		
		public static function Configuration(){
			return self::$Configuration;
		}
				
		public static function Database(){
			return self::$Database;
		}

		public function initialiseDatabase(){
			$error = NULL;
			
			$driver_filename = TOOLKIT . '/class.' . self::$Configuration->get('driver', 'database') . '.php';
			$driver = self::$Configuration->get('driver', 'database');

			if(!is_file($driver_filename)){
				throw new SymphonyErrorPage("Could not find database driver '<code>{$driver}</code>'", 'Symphony Database Error');
			}
			
			require_once($driver_filename);
			
			self::$Database = new $driver;
			
			$details = self::$Configuration->get('database');
			
			try{
				if(!self::$Database->connect($details['host'], $details['user'], $details['password'], $details['port'])) return false;				
				if(!self::$Database->select($details['db'])) return false;
				if(!self::$Database->isConnected()) return false;
			
				self::$Database->setPrefix($details['tbl_prefix']);

				if(self::$Configuration->get('runtime_character_set_alter', 'database') == '1'){
					self::$Database->setCharacterEncoding(self::$Configuration->get('character_encoding', 'database'));
					self::$Database->setCharacterSet(self::$Configuration->get('character_set', 'database'));
				}

				if(self::$Configuration->get('force_query_caching', 'database') == 'off') self::$Database->disableCaching();
				elseif(self::$Configuration->get('force_query_caching', 'database') == 'on') self::$Database->enableCaching();
			}
			catch(DatabaseException $e){
				$error = self::$Database->getlastError();
				throw new SymphonyErrorPage(
					$error['num'] . ': ' . $error['msg'], 
					'Symphony Database Error',
					'database-error', 
					array(
						'error' => $error, 
						'message' => __('There was a problem whilst attempting to establish a database connection. Please check all connection information is correct. The following error was returned.')
					)
				);				
			}
			
			return true;
		}
		
		public function initialiseLog(){
			
			$this->Log = new Log(ACTIVITY_LOG);
			$this->Log->setArchive((self::$Configuration->get('archive', 'log') == '1' ? true : false));
			$this->Log->setMaxSize(intval(self::$Configuration->get('maxsize', 'log')));
				
			if($this->Log->open() == 1){
				$this->Log->writeToLog('Symphony Log', true);
				$this->Log->writeToLog('Version: '. self::$Configuration->get('version', 'symphony'), true);
				$this->Log->writeToLog('--------------------------------------------', true);
			}
						
		}

		public function isLoggedIn(){

			$username = self::$Database->cleanValue($this->Cookie->get('username'));
			$password = self::$Database->cleanValue($this->Cookie->get('pass'));
			
			if(strlen(trim($username)) > 0 && strlen(trim($password)) > 0){
			
				$id = self::$Database->fetchVar('id', 0, "SELECT `id` FROM `tbl_authors` WHERE `username` = '$username' AND `password` = '$password' LIMIT 1");

				if($id){
					$this->_user_id = $id;
					self::$Database->update(array('last_seen' => DateTimeObj::get('Y-m-d H:i:s')), 'tbl_authors', " `id` = '$id'");
					$this->Author = new Author($id);
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

				$id = self::$Database->fetchVar('id', 0, "SELECT `id` FROM `tbl_authors` WHERE `username` = '$username' AND `password` = '$password' LIMIT 1");

				if($id){
					$this->_user_id = $id;
					$this->Author = new Author($id);
					$this->Cookie->set('username', $username);
					$this->Cookie->set('pass', $password);
					self::$Database->update(array('last_seen' => DateTimeObj::get('Y-m-d H:i:s')), 'tbl_authors', " `id` = '$id'");
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
													 FROM `tbl_authors` AS `a`, `tbl_forgotpass` AS `f`
													 WHERE `a`.`id` = `f`.`author_id` AND `f`.`expiry` > '".DateTimeObj::getGMT('c')."' AND `f`.`token` = '$token'
													 LIMIT 1");
				
				self::$Database->delete('tbl_forgotpass', " `token` = '{$token}' ");
			}
			
			else{
				$row = self::$Database->fetchRow(0, "SELECT `id`, `username`, `password` 
													 FROM `tbl_authors` 
													 WHERE SUBSTR(MD5(CONCAT(`username`, `password`)), 1, 8) = '$token' AND `auth_token_active` = 'yes' 
													 LIMIT 1");				
			}

			if($row){
				$this->_user_id = $row['id'];
				$this->Author = new Author($row['id']);
				$this->Cookie->set('username', $row['username']);
				$this->Cookie->set('pass', $row['password']);
				self::$Database->update(array('last_seen' => DateTimeObj::getGMT('Y-m-d H:i:s')), 'tbl_authors', " `id` = '$id'");
				return true;
			}
			
			return false;
						
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
		
		public function customError($code, $heading, $message, $log=true, $forcekill=false, $template='error', array $additional=array()){
			throw new SymphonyErrorPage($message, $heading, $template, $additional);
		}
	
	}
	
	Class SymphonyErrorPageHandler extends GenericExceptionHandler{
		public static function render($e){
			
			if($e->getTemplate() === false){
				echo '<h1>Symphony Fatal Error</h1><p>'.$e->getMessage().'</p>';
				exit;
			}
			
			include($e->getTemplate());

		}
	}
	
	Class SymphonyErrorPage extends Exception{
		
		private $_heading;
		private $_message;
		private $_template;
		private $_additional;
		private $_messageObject;
		
		public function __construct($message, $heading='Symphony Fatal Error', $template='error', array $additional=NULL){
			
			$this->_messageObject = NULL;
			if($message instanceof XMLElement){
				$this->_messageObject = $message;
				$message = $this->_messageObject->generate();
			}
			
			parent::__construct($message);
			
			$this->_heading = $heading;

			$this->_template = $template;
			$this->_additional = (object)$additional;
		}
		
		public function getMessageObject(){
			return $this->_messageObject;
		}
		
		public function getHeading(){
			return $this->_heading;
		}
		
		public function getTemplate(){
			$template = sprintf('%s/tpl.%s.php', TEMPLATE, $this->_template);
			return (file_exists($template) ? $template : false);
		}
		
		public function getAdditional(){
			return $this->_additional;
		}	
	}

