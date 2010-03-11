<?php

	require_once(TOOLKIT . '/class.datasource.php');
	require_once(TOOLKIT . '/class.manager.php');	

    Class DatasourceManager extends Manager{
		
		public static function instance() {
			if (!(self::$_instance instanceof self)) {
				self::$_instance = new self(Symphony::Parent());
			}
				
			return self::$_instance;
		}

	    function __find($name){
		 
		    if(@is_file(DATASOURCES . "/data.$name.php")) return DATASOURCES;
		    else{	

				$extensions = ExtensionManager::instance()->listInstalledHandles();
				
				if(is_array($extensions) && !empty($extensions)){
					foreach($extensions as $e){
						if(@is_file(EXTENSIONS . "/$e/data-sources/data.$name.php")) return EXTENSIONS . "/$e/data-sources";
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
			
			$extensions = ExtensionManager::instance()->listInstalledHandles();
			
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
        public function create($name, $environment=NULL, $process_params=true){
			return Datasource::loadFromName($name, $environment, $process_params);
        }

    }
    
