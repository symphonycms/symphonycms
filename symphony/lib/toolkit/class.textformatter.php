<?php

	if(!defined('__IN_SYMPHONY__')) die('<h2>Symphony Error</h2><p>You cannot directly access this file</p>');
	
	##Interface for page event objects
	abstract Class TextFormatter{
		
		var $_Parent;
		
		function __construct(&$parent){
			$this->_Parent = $parent;
		}
		
		abstract public function about();
				
		abstract public function run($string);
		
	}
	
