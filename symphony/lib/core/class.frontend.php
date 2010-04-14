<?php

	require_once(CORE . '/class.symphony.php');
	require_once(TOOLKIT . '/class.lang.php');
	require_once(TOOLKIT . '/class.manager.php');
	require_once(TOOLKIT . '/class.frontendpage.php');

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
		public $displayProfilerReport;

		private static $_page;

		public static function instance() {
			if (!(self::$_instance instanceof Frontend)) {
				self::$_instance = new self;
			}

			return self::$_instance;
		}

		protected function __construct() {
			parent::__construct();

			$this->Profiler->sample('Engine Initialisation');
			$this->_env = array();

			// Need this part for backwards compatiblity
			$this->Database = Symphony::Database();
			$this->Configuration = Symphony::Configuration();
		}

		public function isLoggedIn() {
			if (isset($_REQUEST['auth-token']) && $_REQUEST['auth-token'] && strlen($_REQUEST['auth-token']) == 8) {
				return $this->loginFromToken($_REQUEST['auth-token']);
			}

			return parent::isLoggedIn();
		}

		public static function Page(){
			return self::$_page;
		}

		public function display($page=NULL){

			self::$_page = new FrontendPage;

			####
			# Delegate: FrontendInitialised
			ExtensionManager::instance()->notifyMembers('FrontendInitialised', '/frontend/');

			$output = self::$_page->generate($page);

			return $output;
		}
	}

	return "Frontend";