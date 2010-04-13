<?php

	Abstract Class Extension{
		
		const NAV_CHILD = 0;
		const NAV_GROUP = 1;
		const CRLF = PHP_EOL;
		
		const ENABLED = 3;
		const DISABLED = 4;
		const NOT_INSTALLED = 5;	
		const REQUIRES_UPDATE = 6;
		
		abstract public function about();
		
		public function update($previousVersion=false){return true;}
		
		public function enable(){return true;}
		
		public function disable(){return true;}
		
		public function uninstall(){return true;}
		
		public function install(){return true;}

		public function getSubscribedDelegates(){}
		
		public function fetchNavigation(){ return NULL; }
		
		public function getExtensionPath() {
			$em = ExtensionManager::instance();
			return $em->getClassPath(strtolower(substr(get_class($this), 10)));
		}
	}
