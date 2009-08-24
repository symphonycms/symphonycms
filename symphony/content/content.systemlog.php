<?php

	Class contentSystemLog{
		
		var $_Parent;
		
		public function __construct(&$parent){
			$this->_Parent = $parent;
		}
		
		public function build(){
			
			if(!is_file(ACTIVITY_LOG) || !$log = @file_get_contents(ACTIVITY_LOG)) $this->_Parent->errorPageNotFound();
			
			header('Content-Type: text/plain');
			
			print $log;
			exit();
		}
		
	}
	
