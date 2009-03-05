<?php

	##Interface for page event objects
	abstract Class TextFormatter{
		
		var $_Parent;
		
		function __construct(&$parent){
			$this->_Parent = $parent;
		}
		
		abstract public function about();
				
		abstract public function run($string);
		
	}
	
