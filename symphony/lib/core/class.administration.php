<?php

	require_once(CORE . '/class.symphony.php');
	require_once(TOOLKIT . '/class.lang.php');
	require_once(TOOLKIT . '/class.manager.php');	
	require_once(TOOLKIT . '/class.htmlpage.php');
	require_once(TOOLKIT . '/class.ajaxpage.php');
		
	Class Administration extends Symphony{
		
		private $_currentPage;
		private $_callback;

		public $Page;
		
		public static function instance(){
			if(!(self::$_instance instanceof Administration)) 
				self::$_instance = new self;
				
			return self::$_instance;
		}
		
		protected function __construct(){
			parent::__construct();
			$this->Profiler->sample('Engine Initialisation');
			
			// Need this part for backwards compatiblity			
			$this->Database = Symphony::Database();
			$this->Configuration = Symphony::Configuration();
						
			$this->_callback = NULL;
		}
		
		public function isLoggedIn(){
			if (isset($_REQUEST['auth-token']) && $_REQUEST['auth-token'] && in_array(strlen($_REQUEST['auth-token']), array(6, 8))) return $this->loginFromToken($_REQUEST['auth-token']);
			
			return parent::isLoggedIn();
		}
		
		private function __buildPage($page){
	
			$this->isLoggedIn();
			
			if(empty($page)){
				
				if(!$this->isLoggedIn()): $page = '/login';
				
				else:
				
					$section_handle = $this->Database->fetchVar('handle', 0, "SELECT `handle` FROM `tbl_sections` WHERE `id` = '".$this->Author->get('default_section')."' LIMIT 1");
				
					if(!$section_handle){
						$section_handle = $this->Database->fetchVar('handle', 0, "SELECT `handle` FROM `tbl_sections` ORDER BY `sortorder` LIMIT 1");
					}
				
					if(!$section_handle){
						
						if($this->Author->isDeveloper()) redirect(URL . '/symphony/blueprints/sections/');
						else redirect(URL . "/symphony/system/authors/edit/".$this->Author->get('id')."/");
						
					}
				
					else{
						redirect(URL . "/symphony/publish/{$section_handle}/");
					}
				
				endif;
			}
			
			if(!$this->_callback = $this->getPageCallback($page)){
				$this->errorPageNotFound();
			}
				
			include_once((isset($this->_callback['driverlocation']) ? $this->_callback['driverlocation'] : CONTENT) . '/content.' . $this->_callback['driver'] . '.php'); 			
			$this->Page = new $this->_callback['classname']($this);

			if(!$this->isLoggedIn() && $this->_callback['driver'] != 'login'){
				if(is_callable(array($this->Page, 'handleFailedAuthorisation'))) $this->Page->handleFailedAuthorisation();
				else{
				
					include_once(CONTENT . '/content.login.php'); 			
					$this->Page = new contentLogin($this);
					$this->Page->build();
				
				}
			}
			
			else $this->Page->build($this->_callback['context']);
			
			return $this->Page;
		}
		
		public function getPageCallback($page=NULL, $update=false){
			
			if((!$page || !$update) && $this->_callback) return $this->_callback;
			elseif(!$page && !$this->_callback) trigger_error(__('Cannot request a page callback without first specifying the page.'));
			
			$this->_currentPage = URL . preg_replace('/\/{2,}/', '/', '/symphony' . $page);
			$bits = preg_split('/\//', trim($page, '/'), 3, PREG_SPLIT_NO_EMPTY);
			
			if($bits[0] == 'login'){
			
				$callback = array(
						'driver' => 'login',
						'context' => preg_split('/\//', $bits[1] . '/' . $bits[2], -1, PREG_SPLIT_NO_EMPTY),
						'classname' => 'contentLogin',
						'pageroot' => '/login/'
					);
			}
			
			elseif($bits[0] == 'extension' && isset($bits[1])){
				
				$extention_name = $bits[1];
				$bits = preg_split('/\//', trim($bits[2], '/'), 2, PREG_SPLIT_NO_EMPTY);
				
				$callback = array(
								'driver' => NULL,
								'context' => NULL,
								'pageroot' => NULL,
								'classname' => NULL,
								'driverlocation' => EXTENSIONS . '/' . $extention_name . '/content/'
							);			
								
				$callback['driver'] = 'index'; //ucfirst($extention_name);
				$callback['classname'] = 'contentExtension' . ucfirst($extention_name) . 'Index';
				$callback['pageroot'] = '/extension/' . $extention_name. '/';	
				
				if(isset($bits[0])){
					$callback['driver'] = $bits[0];
					$callback['classname'] = 'contentExtension' . ucfirst($extention_name) . ucfirst($bits[0]);
					$callback['pageroot'] .= $bits[0] . '/';
				}
				
				if(isset($bits[1])) $callback['context'] = preg_split('/\//', $bits[1], -1, PREG_SPLIT_NO_EMPTY);
				
				if(!is_file($callback['driverlocation'] . '/content.' . $callback['driver'] . '.php')) return false;
								
			}
			
			elseif($bits[0] == 'publish'){
				
				if(!isset($bits[1])) return false;

				$callback = array(
								'driver' => 'publish',
								'context' => array('section_handle' => $bits[1], 'page' => NULL, 'entry_id' => NULL, 'flag' => NULL),
								'pageroot' => '/' . $bits[0] . '/' . $bits[1] . '/',
								'classname' => 'contentPublish'
							);
				
				if(isset($bits[2])){
					$extras = preg_split('/\//', $bits[2], -1, PREG_SPLIT_NO_EMPTY);
					
					$callback['context']['page'] = $extras[0];
					if(isset($extras[1])) $callback['context']['entry_id'] = intval($extras[1]);
				
					if(isset($extras[2])) $callback['context']['flag'] = $extras[2];
					
				}
				
				else $callback['context']['page'] = 'index';
				
			}
			
			else{
				
				$callback = array(
								'driver' => NULL,
								'context' => NULL,
								'pageroot' => NULL,
								'classname' => NULL
							);
			
				$callback['driver'] = ucfirst($bits[0]);
				$callback['pageroot'] = '/' . $bits[0] . '/';
				
				if(isset($bits[1])){
					$callback['driver'] = $callback['driver'] . ucfirst($bits[1]);
					$callback['pageroot'] .= $bits[1] . '/';
				}
			
				if(isset($bits[2])) $callback['context'] = preg_split('/\//', $bits[2], -1, PREG_SPLIT_NO_EMPTY);
			
				$callback['classname'] = 'content' . $callback['driver'];
				$callback['driver'] = strtolower($callback['driver']);
				
				if(!is_file(CONTENT . '/content.' . $callback['driver'] . '.php')) return false;
				
			}
			
			## TODO: Add delegate for custom callback creation
			
			return $callback;
			
		}
		
		public function getCurrentPageURL(){
			return $this->_currentPage;
		}
		
		public function display($page){
			
			$this->Profiler->sample('Page build process started');
			$this->__buildPage($page);
			
			####
			# Delegate: AdminPagePreGenerate
			# Description: Immediately before generating the admin page. Provided with the page object
			# Global: Yes
			$this->ExtensionManager->notifyMembers('AdminPagePreGenerate', '/backend/', array('oPage' => &$this->Page));
			
			$output = $this->Page->generate();

			####
			# Delegate: AdminPagePostGenerate
			# Description: Immediately after generating the admin page. Provided with string containing page source
			# Global: Yes
			$this->ExtensionManager->notifyMembers('AdminPagePostGenerate', '/backend/', array('output' => &$output));

			$this->Profiler->sample('Page built');
			
			return $output;	
		}

		public function saveConfig(){
			$string  = "<?php\n\t\$settings = ".(string)self::Configuration().";\n";
			return General::writeFile(CONFIG, $string, self::Configuration()->get('write_mode', 'file'));
		}
		
		public function errorPageNotFound(){
			$this->customError(E_USER_ERROR, 'Page Not Found', 'The page you requested does not exist.', false, true, 'error', array('header' => 'HTTP/1.0 404 Not Found'));			
		}

	}

