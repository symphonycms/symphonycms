<?php

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
	require_once(TOOLKIT . '/class.extensionmanager.php');
		
	Abstract Class Symphony implements Singleton{
		
		public $Log;
		public $Configuration;
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
			$this->Configuration = new Configuration(true);
			$this->Configuration->setArray($settings);

			$cookie_path = parse_url(URL, PHP_URL_PATH);
			$cookie_path = '/' . trim($cookie_path, '/');
			define_safe('__SYM_COOKIE_PATH__', $cookie_path);
			define_safe('__SYM_COOKIE_PREFIX_', $this->Configuration->get('cookie_prefix', 'symphony'));

			define_safe('__LANG__', ($this->Configuration->get('lang', 'symphony') ? $this->Configuration->get('lang', 'symphony') : 'en'));				
			
			define_safe('__SYM_DATE_FORMAT__', $this->Configuration->get('date_format', 'region'));
			define_safe('__SYM_TIME_FORMAT__', $this->Configuration->get('time_format', 'region'));
			define_safe('__SYM_DATETIME_FORMAT__', __SYM_DATE_FORMAT__ . ' ' . __SYM_TIME_FORMAT__);
						
			$this->initialiseLog();
			
			error_reporting(E_ALL);
			set_error_handler(array(&$this, '__errorHandler'));
			
			$this->Cookie =& new Cookie(__SYM_COOKIE_PREFIX_, TWO_WEEKS, __SYM_COOKIE_PATH__);

			try{
				Lang::init(LANG . '/lang.%s.php', __LANG__);
			}
			catch(Exception $e){
				trigger_error($e->getMessage(), E_USER_ERROR);
			}

			if(!$this->initialiseDatabase()){
				$error = $this->Database->getLastError();
				$this->customError(E_USER_ERROR, 'Symphony Database Error', $error['num'] . ': ' . $error['msg'], true, true, 'database-error', array('error' => $error, 'message' => __('There was a problem whilst attempting to establish a database connection. Please check all connection information is correct. The following error was returned.')));
			}
			
			if(!$this->initialiseExtensionManager()) trigger_error('Error creating Symphony extension manager.', E_USER_ERROR);

			DateTimeObj::setDefaultTimezone($this->Configuration->get('timezone', 'region'));
			
		}
		
		public function initialiseExtensionManager(){
			$this->ExtensionManager = new ExtensionManager($this);
			return ($this->ExtensionManager instanceof ExtensionManager);
		}
		
		public static function Database(){
			if(class_exists('Frontend')){
				return Frontend::instance()->Database;
			}
			
			return Administration::instance()->Database;
		}

		public function initialiseDatabase(){
			$error = NULL;
			
			$driver_filename = TOOLKIT . '/class.' . $this->Configuration->get('driver', 'database') . '.php';
			$driver = $this->Configuration->get('driver', 'database');
			
			if(!is_file($driver_filename)){
				trigger_error("Could not find database driver '<code>$driver</code>'", E_USER_ERROR);
				return false;
			}
			
			require_once($driver_filename);
			
			$this->Database = new $driver;
			
			$details = $this->Configuration->get('database');
			
			if(!$this->Database->connect($details['host'], $details['user'], $details['password'], $details['port'])) return false;				
			if(!$this->Database->select($details['db'])) return false;
			if(!$this->Database->isConnected()) return false;
			
			$this->Database->setPrefix($details['tbl_prefix']);

			if($this->Configuration->get('runtime_character_set_alter', 'database') == '1'){
				$this->Database->setCharacterEncoding($this->Configuration->get('character_encoding', 'database'));
				$this->Database->setCharacterSet($this->Configuration->get('character_set', 'database'));
			}

			if($this->Configuration->get('force_query_caching', 'database') == 'off') $this->Database->disableCaching();
			elseif($this->Configuration->get('force_query_caching', 'database') == 'on') $this->Database->enableCaching();
			
			return true;
		}
		
		public function initialiseLog(){
			
			$this->Log =& new Log(ACTIVITY_LOG);
			$this->Log->setArchive(($this->Configuration->get('archive', 'log') == '1' ? true : false));
			$this->Log->setMaxSize(intval($this->Configuration->get('maxsize', 'log')));
				
			if($this->Log->open() == 1){
				$this->Log->writeToLog('Symphony Log', true);
				$this->Log->writeToLog('Version: '. $this->Configuration->get('version', 'symphony'), true);
				$this->Log->writeToLog('--------------------------------------------', true);
			}
						
		}

		public function isLoggedIn(){

			$un = $this->Database->cleanValue($this->Cookie->get('username'));
			$pw = $this->Database->cleanValue($this->Cookie->get('pass'));

			$id = $this->Database->fetchVar('id', 0, "SELECT `id` FROM `tbl_authors` WHERE `username` = '$un' AND `password` = '$pw' LIMIT 1");

			if($id){
				$this->_user_id = $id;
				$this->Database->update(array('last_seen' => DateTimeObj::get('Y-m-d H:i:s')), 'tbl_authors', " `id` = '$id'");
				$this->Author =& new Author($this, $id);
				return true;
			}
			
			$this->Cookie->expire();
			return false;
		}

		public function logout(){
			$this->Cookie->expire();
		}
		
		public function login($username, $password, $isHash=false){
			
			$username = $this->Database->cleanValue($username);
			$password = $this->Database->cleanValue($password);
			
			if(!$isHash) $password = md5($password);

			$id = $this->Database->fetchVar('id', 0, "SELECT `id` FROM `tbl_authors` WHERE `username` = '$username' AND `password` = '$password' LIMIT 1");

			if($id){
				$this->_user_id = $id;
				$this->Author =& new Author($this, $id);
				$this->Cookie->set('username', $username);
				$this->Cookie->set('pass', $password);
				$this->Database->update(array('last_seen' => DateTimeObj::get('Y-m-d H:i:s')), 'tbl_authors', " `id` = '$id'");
				return true;
			}
			
			return false;
			
		}
		
		public function loginFromToken($token){
			
			$token = $this->Database->cleanValue($token);
			
			if(strlen($token) == 6){
				$row = $this->Database->fetchRow(0, "SELECT `a`.`id`, `a`.`username`, `a`.`password` 
													 FROM `tbl_authors` AS `a`, `tbl_forgotpass` AS `f`
													 WHERE `a`.`id` = `f`.`author_id` AND `f`.`expiry` > '".DateTimeObj::getGMT('c')."' AND `f`.`token` = '$token'
													 LIMIT 1");
				
				$this->Database->delete('tbl_forgotpass', " `token` = '{$token}' ");
			}
			
			else{
				$row = $this->Database->fetchRow(0, "SELECT `id`, `username`, `password` 
													 FROM `tbl_authors` 
													 WHERE SUBSTR(MD5(CONCAT(`username`, `password`)), 1, 8) = '$token' AND `auth_token_active` = 'yes' 
													 LIMIT 1");				
			}

			if($row){
				$this->_user_id = $row['id'];
				$this->Author =& new Author($this, $row['id']);
				$this->Cookie->set('username', $row['username']);
				$this->Cookie->set('pass', $row['password']);
				$this->Database->update(array('last_seen' => DateTimeObj::getGMT('Y-m-d H:i:s')), 'tbl_authors', " `id` = '$id'");
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
			$page = $this->Database->fetchRow(0, "
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
					$parent = $this->Database->fetchRow(0, "
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
		
		public function customError($errno, $heading, $message, $log=true, $forcekill=false, $template='error', $additional=array()){
			$this->__errorHandler($errno, $message, NULL, NULL, NULL, $heading, $log, $template, $additional);
			if($forcekill) exit();
		}
		
		public function __errorHandler($errno=NULL, $errstr, $errfile=NULL, $errline=NULL, $errcontext=NULL, $heading=NULL, $log=true, $template='error', $additional=array()){
			
			if(error_reporting() == 0) return;
			
			switch ($errno) {
		        case E_NOTICE:
		        case E_USER_NOTICE:
					//$this->Log->pushToLog("$errno - $errstr".($errfile ? " in file $errfile" : '') . ($errline ? " on line $errline" : ''), Log::kNOTICE, true);
		            break;
			
				case E_WARNING:
				case E_USER_WARNING:
					if($log) $this->Log->pushToLog("{$errno} - ".strip_tags((is_object($errstr) ? $errstr->generate() : $errstr)).($errfile ? " in file {$errfile}" : '') . ($errline ? " on line {$errline}" : ''), Log::kWARNING, true);
	            	break;				
				
				case E_ERROR:
				case E_USER_ERROR:
			
					if($log) $this->Log->pushToLog("{$errno} - ".strip_tags((is_object($errstr) ? $errstr->generate() : $errstr)).($errfile ? " in file {$errfile}" : '') . ($errline ? " on line {$errline}" : ''), Log::kERROR, true);
			
					if(!is_object($errstr) && $errline) $errstr = "Line {$errline} &ndash; {$errstr}";
			
					if(!is_file(TEMPLATE . "/tpl.{$template}.php")) die("<h1>Symphony Fatal Error</h1><p>{$errstr}</p>");
			
					$heading = ($heading ? $heading : 'Symphony System Error');
					
					include(TEMPLATE . "/tpl.{$template}.php");
					
					break;
			}
			
		}
		
	}

