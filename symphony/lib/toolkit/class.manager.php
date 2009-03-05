<?php

    Abstract Class Manager extends Object{

	    var $_Parent;
	    protected static $_pool;
	    
        public function __construct(&$parent){
			$this->_Parent = $parent;
        }
        
        public function flush(){
	        self::$_pool = array();	        
        }  
        
        ##Returns the about details
        public function about($name){

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
    
