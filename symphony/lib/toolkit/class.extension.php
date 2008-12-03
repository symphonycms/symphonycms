<?php

	

	Class Extension{
		
		const NAV_CHILD = 0;
		const NAV_GROUP = 1;
		const CRLF = "\r\n";
		
		protected $_Parent;
		
		function __construct($args){ $this->_Parent =& $args['parent']; }
		
		public function update(){}
		
		public function enable(){}
		
		public function disable(){}
		
		public function uninstall(){}
		
		public function install(){}
		
		public function about(){}

		public function getSubscribedDelegates(){}
		
		public function fetchNavigation(){ return NULL; }
		
		/*
		return array(
			
			array(
				'location' => 200,
				'name' => 'File Manager',
				'link' => '/'
			),		
			
			array(
				'location' => 100,
				'name' => 'File Manager',
				'children' => array(
					
					array(
						'name' => 'Browse',
						'link' => '/index/'							
					)
				)
			)
		);
		*/
		
	}