<?php

	

    Class Manager extends Object{

	    var $_Parent;
	    var $_pool;
	    
        function __construct(&$parent){
			$this->_Parent = $parent;
	        $this->_pool = array();
        }
        
        function __destruct(){
			
			if(is_array($this->_pool) && !empty($this->_pool)){	    
		        foreach($this->_pool as $o){
			     	unset($o);   
		        }
			}
			
        }
        
        function flush(){
	        $this->_pool = array();	        
        }  
        
        ##Returns the about details
        function about($name){

	        $classname = $this->__getClassName($name);   
	        $path = $this->__getDriverPath($name);

			if(!@file_exists($path)) return false;

			require_once($path);

			$handle = $this->__getHandleFromFilename(basename($path));

	        if($about = @call_user_func(array(&$classname, 'about')))			
				return array_merge($about, array('handle' => $handle));	
			
			return false;
									        
        } 
                     
        function __getClassName($name){
        }
        
        function __getClassPath($name){
        }
        
        function __getDriverPath($name){
        }        
        
		function __getHandleFromFilename($filename){
		}

        function listAll(){
        }
               
        function &create($name){
        }       
        
    }
    
