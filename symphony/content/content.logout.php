<?php
	
	Class contentLogout extends HTMLPage{
		
		var $_Parent;
		
		function __construct(&$parent){
			parent::__construct();
			
			$this->_Parent = $parent;
		}

		function build(){
			$this->view();
		}
	
		function view(){
			$this->_Parent->logout();
			redirect(URL);
		}
	
	}
	
?>