<?php

	

	Class Extension{
		
		const NAV_CHILD = 0;
		const NAV_GROUP = 1;
		const CRLF = "\r\n";
		
		protected $_Parent;
		
		function __construct($args){ $this->_Parent =& $args['parent']; }
		
		public function update($previousVersion=false){return true;}
		
		public function enable(){return true;}
		
		public function disable(){return true;}
		
		public function uninstall(){return true;}
		
		public function install(){return true;}
		
		public function about(){}

		public function getSubscribedDelegates(){}
		
		public function fetchNavigation(){ return NULL; }
		
		/*
		return array(
			
			array(
				'location' => 200,
				'name' => __('File Manager'),
				'link' => '/'
			),		
			
			array(
				'location' => 100,
				'name' => __('File Manager'),
				'children' => array(
					
					array(
						'name' => __('Browse'),
						'link' => '/index/'							
					)
				)
			)
		);
		*/
		
	}
