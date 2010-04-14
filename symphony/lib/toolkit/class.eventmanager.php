<?php

	require_once(TOOLKIT . '/class.event.php');

    Class EventManager extends Manager{

		public static function instance() {
			if (!(self::$_instance instanceof self)) {
				self::$_instance = new self(Symphony::Parent());
			}

			return self::$_instance;
		}

	    function __find($name){

		    if(is_file(EVENTS . "/event.$name.php")) return EVENTS;
			else{

				$extensions = ExtensionManager::instance()->listInstalledHandles();

				if(is_array($extensions) && !empty($extensions)){
					foreach($extensions as $e){
						if(is_file(EXTENSIONS . "/$e/events/event.$name.php")) return EXTENSIONS . "/$e/events";
					}
				}
	    	}

		    return false;
	    }

        function __getClassName($name){
	        return 'event' . $name;
        }

        function __getClassPath($name){
	        return $this->__find($name);
        }

        function __getDriverPath($name){
	        return $this->__getClassPath($name) . "/event.$name.php";
        }

		function __getHandleFromFilename($filename){
			return preg_replace(array('/^event./i', '/.php$/i'), '', $filename);
		}

        function listAll(){

			$result = array();
			$people = array();

	        $structure = General::listStructure(EVENTS, '/event.[\\w-]+.php/', false, 'ASC', EVENTS);

	        if(is_array($structure['filelist']) && !empty($structure['filelist'])){
	        	foreach($structure['filelist'] as $f){
		        	$f = self::__getHandleFromFilename($f);

					if($about = $this->about($f)){

						$classname = $this->__getClassName($f);
						$path = $this->__getDriverPath($f);
						$can_parse = false;
						$type = NULL;

						if(is_callable(array($classname,'allowEditorToParse')))
							$can_parse = call_user_func(array(&$classname, 'allowEditorToParse'));

						if(is_callable(array($classname,'getSource')))
							$type = call_user_func(array(&$classname, 'getSource'));

						$about['can_parse'] = $can_parse;
						$about['source'] = $source;
						$result[$f] = $about;

					}
				}
			}

			$extensions = ExtensionManager::instance()->listInstalledHandles();

			/* TODO: Check that if the following checks are needed considering ->valid() is run inside the listInstalledHandles() function */
			if(is_array($extensions) && !empty($extensions)){
				foreach($extensions as $e){

					if(!is_dir(EXTENSIONS . "/$e/events")) continue;

					$tmp = General::listStructure(EXTENSIONS . "/$e/events", '/event.[\\w-]+.php/', false, 'ASC', EXTENSIONS . "/$e/events");

		        	if(is_array($tmp['filelist']) && !empty($tmp['filelist'])){
		        		foreach($tmp['filelist'] as $f){
							$f = $f = self::__getHandleFromFilename($f);

							if($about = $this->about($f)){

								$classname = $this->__getClassName($f);

								$can_parse = false;
								$type = NULL;

								$about['can_parse'] = $can_parse;
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
        function &create($name, $environment=NULL){

	        $classname = $this->__getClassName($name);
	        $path = $this->__getDriverPath($name);

	        if(!is_file($path)){
		        trigger_error(__('Could not find Event <code>%s</code>. If the Event was provided by an Extensions, ensure that it is installed, and enabled.', array($name)), E_USER_ERROR);
		        return false;
	        }

			if(!@class_exists($classname))
				require_once($path);

			return new $classname($this->_Parent, $environment);

        }

    }

