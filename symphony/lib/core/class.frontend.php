<?php

	require_once(CORE . '/class.symphony.php');
	require_once(TOOLKIT . '/class.lang.php');
	require_once(TOOLKIT . '/class.manager.php');
	require_once(TOOLKIT . '/class.frontendpage.php');
	Class Parameter{

		public $value;
		public $key;

		public function __construct($key, $value){
			$this->value = $value;
			$this->key = $key;
		}

		public function __toString(){
			if(is_array($this->value)) return implode(',', $this->value);
			return (!is_null($this->value) ? $this->value : '');
		}
	}

	Final Class Register implements Iterator{

		private $parameters;

		private $_result;
		private $_position;
		private $_current;
		private $_keys;

		public function register(array $params){
			foreach($params as $key => $value) $this->$key = $value;
		}

		public function __construct(){
			$this->parameters = array();
			$this->_position = 0;
		}

		public function __set($name, $value){
			$this->parameters[$name] = new Parameter($name, $value);
			$this->_keys = array_keys($this->parameters);
		}

		public function __get($name){
			 if(isset($this->parameters[$name])) 
				return $this->parameters[$name];

			throw new Exception('No such parameter "' . $name . '"');
		}

		public function __isset($name){
			return (isset($this->parameters[$name]) && ($this->parameters[$name] instanceof Parameter));
		}

		public function current(){
			return current($this->parameters);
		}

		public function next(){
			$this->_position++;
			next($this->parameters);
		}

		public function position(){
			return $this->_position;
		}

		public function rewind(){
			reset($this->parameters);
			$this->_position = 0;
		}

		public function key(){
			return $this->_keys[$this->_position];
		}

		public function length(){
			return count($this->parameters);
		}

		public function valid(){
			return $this->_position < $this->length();
		}
		
		public function toArray(){
			$result = array();
			foreach($this as $key => $parameter){
				$result[$key] = (string)$parameter;
			}
			return $result;
		}
	}
	
	Class FrontendPageNotFoundException extends Exception{
	}

	Class FrontendPageNotFoundExceptionHandler extends SymphonyErrorPageHandler{
		public static function render($e){
			// TODO: Fix me to use Views
			$page_id = Symphony::Database()->fetchVar('page_id', 0, "SELECT `page_id` FROM `tbl_pages_types` WHERE `type` = '404' LIMIT 1");

			if(is_null($page_id)){
				parent::render(new SymphonyErrorPage(
					__('The page you requested does not exist.'),
					__('Page Not Found'),
					'error',
					array('header' => 'HTTP/1.0 404 Not Found')
				));
			}
			else{
				$url = '/' . Frontend::instance()->resolvePagePath($page_id) . '/';

				$output = Frontend::instance()->display($url);
				header(sprintf('Content-Length: %d', strlen($output)));
				echo $output;
				exit;
			}
		}
	}

	Class Frontend extends Symphony {
		private static $view;

		public static function instance() {
			if (!(self::$_instance instanceof Frontend)) {
				self::$_instance = new self;
			}

			return self::$_instance;
		}

		public static function loadedView(){
			return self::$view;
		}

		public function display($page=NULL){
			
			// VIEW RESOLVING --------------------------
			try{
				if(is_null($page)){
					$views = View::findFromType('index');
					self::$view = array_shift($views);
				}
				else{
					self::$view = View::loadFromURL($page);
				}
			
				if(!(self::$view instanceof View)) throw Exception('Page not found');
			
				if(!Frontend::instance()->isLoggedIn() && in_array('admin', self::$view->types)){
				
					$views = View::findFromType('403');
					self::$view = array_shift($views);
				
					if(!(self::$view instanceof View)){
						throw new SymphonyErrorPage( 
							__('Please <a href="%s">login</a> to view this page.', array(ADMIN_URL . '/login/')), 
							__('Forbidden'), NULL,
							array('HTTP/1.0 403 Forbidden')
						);
					}
				}
			}
			
			catch(Exception $e){
				$views = View::findFromType('404');
				self::$view = array_shift($views);

				if(!(self::$view instanceof View)){
					throw new SymphonyErrorPage(
						__('The page you requested does not exist.'),
						__('Page Not Found'), NULL,
						array('HTTP/1.0 404 Not Found')
					);
				}
			}

			####
			# Delegate: FrontendInitialised
			ExtensionManager::instance()->notifyMembers('FrontendInitialised', '/frontend/');
			
			
			// SETTING UP PARAMETERS --------------------------
			
			$Parameters = new Register;

			$root_page = array_shift(explode('/', self::$view->parent()->path));
			$current_path = explode(dirname($_SERVER['SCRIPT_NAME']), $_SERVER['REQUEST_URI'], 2);
			$current_path = '/' . ltrim(end($current_path), '/');

			$Parameters->register(array(
				'today' => DateTimeObj::get('Y-m-d'),
				'current-time' => DateTimeObj::get('H:i'),
				'this-year' => DateTimeObj::get('Y'),
				'this-month' => DateTimeObj::get('m'),
				'this-day' => DateTimeObj::get('d'),
				'timezone' => DateTimeObj::get('P'),
				'website-name' => Symphony::Configuration()->get('sitename', 'symphony'),
				'symphony-version' => Symphony::Configuration()->get('version', 'symphony'),
				'upload-limit' => min(
					ini_size_to_bytes(ini_get('upload_max_filesize')), 
					Symphony::Configuration()->get('max_upload_size','admin')
				),
				'root' => URL,
				'workspace' => URL . '/workspace',
				'page-title' => self::$view->title,
				'root-page' => (!is_null($root_page) ? $root_page : self::$view->handle),
				'current-page' => self::$view->handle,
				'current-path' => $current_path,
				'parent-path' => '/' . self::$view->path,
				'current-url' => URL . $current_path,
			));
		
			if(isset($this->{'url-parameters'}) && is_array($this->{'url-parameters'})){
				foreach($this->{'url-parameters'} as $p){
					$Parameters->$p = NULL;
				}

				foreach($this->parameters() as $p => $v){
					$Parameters->$p = str_replace(' ', '+', $v);
				}
			
			}
			
			if(is_array($_GET) && !empty($_GET)){
				foreach($_GET as $key => $val){
					if(in_array($key, array('symphony-page', 'debug', 'profile'))) continue;
					$Parameters->{"url-{$key}"} = $val;
				}
			}

			if(is_array($_COOKIE[__SYM_COOKIE_PREFIX__]) && !empty($_COOKIE[__SYM_COOKIE_PREFIX__])){
				foreach($_COOKIE[__SYM_COOKIE_PREFIX__] as $key => $val){
					$Parameters->{"cookie-{$key}"} = $val;
				}
			}
			
			####
			# Delegate: FrontendParamsResolve
			# Description: Just after having resolved the page params, but prior to any commencement of output creation
			# Global: Yes
			ExtensionManager::instance()->notifyMembers('FrontendParamsResolve', '/frontend/', array('parameters' => &$Parameters));
			
			
			// RENDER THE VIEW --------------------------
			
			// Can ask the view to operate on an existing 
			// Document. Useful if we pass it around beyond 
			// the scope of View::render()
			$Document = new XMLDocument; 
			$Document->appendChild($Document->createElement('data'));
			
			return self::$view->render($Parameters, $Document);
		}
	}

	return 'Frontend';