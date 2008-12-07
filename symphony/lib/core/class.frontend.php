<?php

	
	
	require_once(CORE . '/class.symphony.php');
	require_once(TOOLKIT . '/class.xmldoc.php');	
	require_once(TOOLKIT . '/class.lang.php');
	require_once(TOOLKIT . '/class.manager.php');	
	require_once(TOOLKIT . '/class.frontendpage.php');
		
	Class Frontend extends Symphony{
		
		public $displayProfilerReport;
		
		function __construct(){
			parent::__construct();
			
			$this->Profiler->sample('Engine Initialisation');

			$this->_env = array();
			
			## To prevent users that are logged in from getting maintenance pages, ensure the URL matches
			## the one speficied in the config file.
			/*$url_bits = parse_url(URL);

			if($_SERVER['HTTP_HOST'] != $url_bits['host'] && $_SERVER['HTTP_HOST'] != ($url_bits['host'] . ':' . $url_bits['port'])){
				
				##Clean up the query string
				$query = str_replace('page=' . $_REQUEST['page'], NULL, $_SERVER['QUERY_STRING']);
				$query = ltrim($query, '&');

				##Reconstruct the correct URL and redirect them there
				$destination = URL . '/' . $_REQUEST['page'] . '/' . ($query != '' ? "?$query" : '');
				$destination = rtrim($destination, '/') . '/';

				##Let the browser know its a 301 page
				header('HTTP/1.1 301 Moved Permanently');
				redirect($destination);
				exit();
				
			}*/	
			##
		}

		public function isLoggedIn(){
			if($_REQUEST['auth-token'] && strlen($_REQUEST['auth-token']) == 8) return $this->loginFromToken($_REQUEST['auth-token']);
			
			return parent::isLoggedIn();
		}
		
		public function display($page){
			
			$mode = FrontendPage::FRONTEND_OUTPUT_NORMAL;
			
			if($this->isLoggedIn()){
				if(isset($_GET['debug'])) $mode = FrontendPage::FRONTEND_OUTPUT_DEBUG;
				elseif(isset($_GET['profile'])) $mode = FrontendPage::FRONTEND_OUTPUT_PROFILE;
			}
			
			$oPage =& new FrontendPage($this);

			$output = $oPage->generate($page, $mode);

			return $output;

		}
		
	}
