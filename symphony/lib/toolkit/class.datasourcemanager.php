<?php

	

	require_once(TOOLKIT . '/class.datasource.php');
	require_once(TOOLKIT . '/class.manager.php');	

    Class DatasourceManager extends Manager{

	    function __find($name){
		 
		    if(is_file(DATASOURCES . "/data.$name.php")) return DATASOURCES;
		    else{	
			      
				$extensionManager = new ExtensionManager($this->_Parent);
				$extensions = $extensionManager->listInstalledHandles();
				
				if(is_array($extensions) && !empty($extensions)){
					foreach($extensions as $e){
						if(is_file(EXTENSIONS . "/$e/data-sources/data.$name.php")) return EXTENSIONS . "/$e/data-sources";
					}	
				}		    
	    	}
	    		    
		    return false;
	    }
        
		function __getHandleFromFilename($filename){
			$filename = preg_replace(array('/^data./i', '/.php$/i'), '', $filename);
			return $filename;
		}

        function __getClassName($name){
	        return 'datasource' . $name;
        }
        
        function __getClassPath($name){
	        return $this->__find($name);
        }
        
        function __getDriverPath($name){	        
	        return $this->__getClassPath($name) . "/data.$name.php";
        }
               
        function listAll(){
	        
			$result = array();
			$people = array();
			
	        $structure = General::listStructure(DATASOURCES, '/data.[\\w-]+.php/', false, 'ASC', DATASOURCES);
	        
	        if(is_array($structure['filelist']) && !empty($structure['filelist'])){		        
	        	foreach($structure['filelist'] as $f){
					$f = self::__getHandleFromFilename($f);
									        	
					if($about = $this->about($f)){

					  	$classname = $this->__getClassName($f);   
				    	$path = $this->__getDriverPath($f);

						$can_parse = false;
						$type = NULL;
						
				    	if(is_callable(array($classname,'allowEditorToParse')))
							$can_parse = @call_user_func(array(&$classname, 'allowEditorToParse'));

						if(is_callable(array($classname,'getSource')))	
							$type = @call_user_func(array(&$classname, 'getSource'));
						
						$about['can_parse'] = $can_parse;
						$about['type'] = $type;
						$result[$f] = $about;		

					}
				}
			}
			
			//$structure = General::listStructure(EXTENSIONS, array(), false, 'ASC', EXTENSIONS);
			//$extensions = $structure['dirlist'];
			
			$extensionManager = new ExtensionManager($this->_Parent);
			$extensions = $extensionManager->listInstalledHandles();
			
			if(is_array($extensions) && !empty($extensions)){
				foreach($extensions as $e){											
					
					if(!is_dir(EXTENSIONS . "/$e/data-sources")) continue;
					
					$tmp = General::listStructure(EXTENSIONS . "/$e/data-sources", '/data.[\\w-]+.php/', false, 'ASC', EXTENSIONS . "/$e/data-sources");

			    	if(is_array($tmp['filelist']) && !empty($tmp['filelist'])){		        
			        	foreach($tmp['filelist'] as $f){					        	
				        	$f = self::__getHandleFromFilename($f);	   

							if($about = $this->about($f)){
								
								$about['can_parse'] = false;
								$about['type'] = NULL;
								$result[$f] = $about;	
								
							}
						}
					}				
				}	
			}

			ksort($result);
			return $result;	        
        }
               
        ##Creates a new extension object and returns a pointer to it
        function &create($name, $environment=NULL, $process_params=true){
	        
	        $classname = $this->__getClassName($name);	        
	        $path = $this->__getDriverPath($name);
	        
	        if(!is_file($path)){
		        trigger_error(__('Could not find Data Source <code>%s</code>. If the Data Source was provided by an Extensions, ensure that it is installed, and enabled.', array($name)), E_USER_ERROR);
		        return false;
	        }
	        
			if(!class_exists($classname)) require_once($path);
								
			return new $classname($this->_Parent, $environment, $process_params);
	        
        }        
         
    }
    
