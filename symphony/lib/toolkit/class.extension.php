<?php

	Abstract Class Extension{
		
		const NAV_CHILD = 0;
		const NAV_GROUP = 1;
		const CRLF = PHP_EOL;
		
		protected $_Parent;
		
		abstract public function about();
		
		public function __construct($args){ $this->_Parent =& $args['parent']; }
		
		public function update($previousVersion=false){return true;}
		
		public function enable(){return true;}
		
		public function disable(){return true;}
		
		public function uninstall(){return true;}
		
		public function install(){return true;}

		public function getSubscribedDelegates(){}
		
		public function fetchNavigation(){ return NULL; }
		
	}
