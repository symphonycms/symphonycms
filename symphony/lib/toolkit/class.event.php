<?php

	

	abstract Class Event{
		
		protected $_Parent;
		protected $_env;
		
		const CRLF = "\r\n";
		const kHIGH = 3;
		const kNORMAL = 2;
		const kLOW = 1;
				
		function __construct(&$parent, $env=NULL){
			$this->_Parent = $parent;
			$this->_env = $env;
		}
		
		## This function is required in order to edit it in the event editor page. 
		## Do not overload this function if you are creating a custom event. It is only
		## used by the event editor
		public static function allowEditorToParse(){
			return false;
		}
				
		## This function is required in order to identify what type of event this is for
		## use in the event editor. It must remain intact. Do not overload this function in
		## custom events.
		public static function getSource(){
			return NULL;
		}
						
		abstract public static function about();
				
		abstract public function load();
		
		abstract protected function __trigger();
	}
	
