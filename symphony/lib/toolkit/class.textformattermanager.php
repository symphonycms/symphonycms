<?php

	include_once(TOOLKIT . '/class.textformatter.php');

    Class TextformatterManager extends Manager{
	    
	    function __find($name){
		 
		    if(is_file(TEXTFORMATTERS . "/formatter.$name.php")) return TEXTFORMATTERS;
			else{	  
				    
				$extensionManager = new ExtensionManager($this->_Parent);
				$extensions = $extensionManager->listInstalledHandles();
				
				if(is_array($extensions) && !empty($extensions)){
					foreach($extensions as $e){
						if(is_file(EXTENSIONS . "/$e/text-formatters/formatter.$name.php")) return EXTENSIONS . "/$e/text-formatters";	
					}	
				}		    
	    	}
	    		    
		    return false;
	    }
	            
        function __getClassName($name){
	        return 'formatter' . $name;
        }
        
        function __getClassPath($name){
	        return $this->__find($name);
        }
        
        function __getDriverPath($name){	        
	        return $this->__getClassPath($name) . "/formatter.$name.php";
        }          

		function __getHandleFromFilename($filename){
			return preg_replace(array('/^formatter./i', '/.php$/i'), '', $filename);
		}
        
        function listAll(){
	        
			$result = array();
			$people = array();
			
	        $structure = General::listStructure(TEXTFORMATTERS, '/formatter.[\\w-]+.php/', false, 'ASC', TEXTFORMATTERS);
	        
	        if(is_array($structure['filelist']) && !empty($structure['filelist'])){		        
	        	foreach($structure['filelist'] as $f){
		        	$f = str_replace(array('formatter.', '.php'), '', $f);					        	
					$result[$f] = $this->about($f);
				}
			}
			
			$extensionManager = new ExtensionManager($this->_Parent);
			$extensions = $extensionManager->listInstalledHandles();
			
			if(is_array($extensions) && !empty($extensions)){
				foreach($extensions as $e){										
					
					if(!is_dir(EXTENSIONS . "/$e/text-formatters")) continue;
					
					$tmp = General::listStructure(EXTENSIONS . "/$e/text-formatters", '/formatter.[\\w-]+.php/', false, 'ASC', EXTENSIONS . "/$e/text-formatters");
						
			        if(is_array($tmp['filelist']) && !empty($tmp['filelist'])){
			        	foreach($tmp['filelist'] as $f){
							$f = preg_replace(array('/^formatter./i', '/.php$/i'), '', $f);
							$result[$f] = $this->about($f);
						}
					}
				}	
			}
			
			ksort($result);
			return $result;	        
        }

        function &create($name){
	        
	        $classname = $this->__getClassName($name);	        
	        $path = $this->__getDriverPath($name);

	        if(!is_file($path)){
		        trigger_error(__('Could not find Text Formatter <code>%s</code>. If the Text Formatter was provided by an Extensions, ensure that it is installed, and enabled.', array($name)), E_USER_ERROR);
		        return false;
	        }
	        
			if(!@class_exists($classname))									
				require_once($path);

			return new $classname($this->_Parent);	
	        
        }       
        
    }
    
