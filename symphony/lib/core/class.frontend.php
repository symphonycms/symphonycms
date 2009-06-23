<?php

	require_once(CORE . '/class.symphony.php');	
	require_once(TOOLKIT . '/class.lang.php');
	require_once(TOOLKIT . '/class.manager.php');	
	require_once(TOOLKIT . '/class.frontendpage.php');
		
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
		}
		
		public function isLoggedIn() {
			if (isset($_REQUEST['auth-token']) && $_REQUEST['auth-token'] && strlen($_REQUEST['auth-token']) == 8) {
				return $this->loginFromToken($_REQUEST['auth-token']);
			}
			
			return parent::isLoggedIn();
		}
		
		public static function Page() {
			return self::$_page;
		}
		
		public function display($page) {
			self::$_page = new FrontendPage($this);
			
			####
			# Delegate: FrontendInitialised
			$this->ExtensionManager->notifyMembers('FrontendInitialised', '/frontend/');
			
			$output = self::$_page->generate($page);
			
			return $output;
		}
	}
