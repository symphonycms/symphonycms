<?php

    Abstract Class Manager{

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

	        if(is_callable(array($classname, 'about'))){
				$about = call_user_func(array($classname, 'about'));
				return array_merge($about, array('handle' => $handle));
			}

        } 
                     
        public function __getClassName($name){
        }
        
        public function __getClassPath($name){
        }
        
        public function __getDriverPath($name){
        }        
        
		public function __getHandleFromFilename($filename){
		}

        public function listAll(){
        }
               
        public function &create($name){
        }       
        
    }
    
