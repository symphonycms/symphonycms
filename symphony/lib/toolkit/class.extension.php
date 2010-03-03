<?php

	Abstract Class Extension{
		
		const NAV_CHILD = 0;
		const NAV_GROUP = 1;
		const CRLF = "\r\n";
		
		abstract public function about();
		
		public function update($previousVersion=false){return true;}
		
		public function enable(){ return true; }
		
		public function disable(){ return true; }
		
		public function uninstall(){ return true; }
		
		public function install(){ return true; }

		public function getSubscribedDelegates(){}
		
		public function fetchNavigation(){ return NULL; }
		
	}
