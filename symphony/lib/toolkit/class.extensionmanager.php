<?php

	include_once(TOOLKIT . '/class.manager.php');
	include_once(TOOLKIT . '/class.extension.php');
	
	define_safe('EXTENSION_ENABLED', 10);
	define_safe('EXTENSION_DISABLED', 11);
	define_safe('EXTENSION_NOT_INSTALLED', 12);	
	define_safe('EXTENSION_REQUIRES_UPDATE', 13);	
	
    Class ExtensionManager extends Manager{
		
		static private $_enabled_extensions = NULL;
		static private $_subscriptions = NULL;
		
        function __getClassName($name){
	        return 'extension_' . $name;
        }
        
        function __getClassPath($name){
	        return EXTENSIONS . strtolower("/$name");
        }
        
        function __getDriverPath($name){
	        return $this->__getClassPath($name) . '/extension.driver.php';
        }       
        
        public function getClassPath($name){
	        return EXTENSIONS . strtolower("/$name");
        }

		public function sortByStatus($s1, $s2){
			
			if($s1['status'] == EXTENSION_ENABLED) $status_s1 = 2;
			elseif(in_array($s1['status'], array(EXTENSION_DISABLED, EXTENSION_NOT_INSTALLED, EXTENSION_REQUIRES_UPDATE))) $status_s1 = 1;
			else $status_s1 = 0;
			
			if($s2['status'] == EXTENSION_ENABLED) $status_s2 = 2;
			elseif(in_array($s2['status'], array(EXTENSION_DISABLED, EXTENSION_NOT_INSTALLED, EXTENSION_REQUIRES_UPDATE))) $status_s2 = 1;			
			else $status_s2 = 0;	
			
			return $status_s2 - $status_s1;		
		}
		
		public function sortByName($a, $b) {
			return strnatcasecmp($a['name'], $b['name']);	
		}
      
		public function enable($name){
			if(false == ($obj =& $this->create($name))){
				trigger_error(__('Could not %1$s %2$s, there was a problem loading the object. Check the driver class exists.', array(__FUNCTION__, $name)), E_USER_WARNING);
				return false;
			}
				
			## If not installed, install it
			if($this->__requiresInstallation($name) && $obj->install() === false){
				return false;
			}
			
			## If requires and upate before enabling, than update it first
			elseif(($about = $this->about($name)) && ($previousVersion = $this->__requiresUpdate($about)) !== false) $obj->update($previousVersion);

			$id = $this->registerService($name);
			
			## Now enable the extension
			$obj->enable();
			
			unset($obj);
						
			return true;
						
		}

		public function disable($name){
		
			if(false == ($obj =& $this->create($name))){
				trigger_error(__('Could not %1$s %2$s, there was a problem loading the object. Check the driver class exists.', array(__FUNCTION__, $name)), E_USER_ERROR);
				return false;
			}

			$id = $this->registerService($name, false);
			
			$obj->disable();
			unset($obj);
				
			$this->pruneService($name, true);
						
			return true;			
		}

		public function uninstall($name){
			
			if(false == ($obj =& $this->create($name))){
				trigger_error(__('Could not %1$s %2$s, there was a problem loading the object. Check the driver class exists.', array(__FUNCTION__, $name)), E_USER_WARNING);
				return false;
			}
			
			$obj->uninstall();
			unset($obj);
						
			$this->pruneService($name);		
			
			return true;	
		}
		
		public function fetchStatus($name){
			if(!$status = Symphony::Database()->fetchVar('status', 0, "SELECT `status` FROM `tbl_extensions` WHERE `name` = '$name' LIMIT 1")) return EXTENSION_NOT_INSTALLED;
			
			if($status == 'enabled') return EXTENSION_ENABLED;
			
			return EXTENSION_DISABLED;

		}
		
		public function pruneService($name, $delegates_only=false){

	        $classname = $this->__getClassName($name);   
	        $path = $this->__getDriverPath($name);

			if(!@file_exists($path)) return false;
			
			$delegates = Symphony::Database()->fetchCol('id', "SELECT tbl_extensions_delegates.`id` FROM `tbl_extensions_delegates` 
											 LEFT JOIN `tbl_extensions` ON (`tbl_extensions`.id = `tbl_extensions_delegates`.extension_id) 
											 WHERE `tbl_extensions`.name = '$name'");
			Symphony::Database()->delete('tbl_extensions_delegates', " `id` IN ('".@implode("', '", $delegates)."') ");
											
			if(!$delegates_only) Symphony::Database()->query("DELETE FROM `tbl_extensions` WHERE `name` = '$name'");

			## Remove the unused DB records
			$this->__cleanupDatabase();
			
			return true;					
		}
		
		public function registerService($name, $enable=true){

	        $classname = $this->__getClassName($name);   
	        $path = $this->__getDriverPath($name);

			if(!@file_exists($path)) return false;

			require_once($path);
			
			$subscribed = call_user_func(array(&$classname, "getSubscribedDelegates"));
			
			if($existing_id = $this->fetchExtensionID($name))
				Symphony::Database()->query("DELETE FROM `tbl_extensions_delegates` WHERE `tbl_extensions_delegates`.extension_id = $existing_id");

			Symphony::Database()->query("DELETE FROM `tbl_extensions` WHERE `name` = '$name'");				

			$info = $this->about($name);

			Symphony::Database()->insert(
				array(
					'name' => $name, 
					'status' => ($enable ? 'enabled' : 'disabled'), 
					'version' => $info['version']
				), 
				'tbl_extensions'
			);	

			$id = Symphony::Database()->getInsertID();
						
			if(is_array($subscribed) && !empty($subscribed)){
				foreach($subscribed as $s){

					Symphony::Database()->insert(
						array(
							'extension_id' => $id, 
							'page' => $s['page'], 
							'delegate' => $s['delegate'],
							'callback' => $s['callback']
						), 
						'tbl_extensions_delegates'
					);
					
				}
			}

			## Remove the unused DB records
			$this->__cleanupDatabase();
			
			return $id;
		}

		public function listInstalledHandles(){
			if(is_null(self::$_enabled_extensions)) {
				self::$_enabled_extensions = Symphony::Database()->fetchCol('name', 
					"SELECT `name` FROM `tbl_extensions` WHERE `status` = 'enabled'"
				);
			}
			return self::$_enabled_extensions;
		}

        ## Will return a list of all extensions and their about information
        public function listAll(){
	        
			$extensions = array();
			$result = array();
	        $structure = General::listStructure(EXTENSIONS, array(), false, 'asc', EXTENSIONS);
	
	        $extensions = $structure['dirlist'];

	        if(is_array($extensions) && !empty($extensions)){
		        foreach($extensions as $e){	        
					if($about = $this->about($e)) $result[$e] = $about;							        
		        }
	        }

			return $result;
        }
        
        public function notifyMembers($delegate, $page, $context=array()){

	        if((int)Symphony::Configuration()->get('allow_page_subscription', 'symphony') != 1) return;
	
			if (is_null(self::$_subscriptions)) {
				self::$_subscriptions = Symphony::Database()->fetch("
					SELECT t1.name, t2.page, t2.delegate, t2.callback
					FROM `tbl_extensions` as t1 INNER JOIN `tbl_extensions_delegates` as t2 ON t1.id = t2.extension_id
					WHERE t1.status = 'enabled'");										
			}
				
			// Make sure $page is an array
			if(!is_array($page)){
				
				// Support for pseudo-global delegates (including legacy support for /administration/)
				if(preg_match('/\/?(administration|backend)\/?/', $page)){
					$page = array(
						'backend', '/backend/',
						'administration', '/administration/'
					);
				}
				
				else{
					$page = array($page);
				}
			}
			
			// Support for global delegate subscription
			if(!in_array('*', $page)){
				$page[] = '*';
			}
			
			$services = null;
			foreach(self::$_subscriptions as $subscription) {
				foreach($page as $p) {
					if ($p == $subscription['page'] && $delegate == $subscription['delegate']) {
						if (!is_array($services)) $services = array();
						$services[] = $subscription;
					}
				}				
			}
			
			if(!is_array($services) || empty($services)) return NULL;
	
	        $context += array('parent' => &$this->_Parent, 'page' => $page, 'delegate' => $delegate);
			
			foreach($services as $s){

				$obj = $this->create($s['name']);

				if(is_object($obj) && in_array($s['callback'], get_class_methods($obj))){
					$obj->{$s['callback']}($context);
					unset($obj);
				}	
								
			}
		  	
        }

        ## Creates a new object and returns a pointer to it
        public function create($name, $param=array(), $slient=false){
	        
			if(!is_array(self::$_pool)) $this->flush();
	
			if(!isset(self::$_pool[$name])){
		        $classname = $this->__getClassName($name);	        
		        $path = $this->__getDriverPath($name);
	        
		        if(!@is_file($path)){
			        if(!$slient) trigger_error(__('Could not find extension at location %s', array($path)), E_USER_ERROR);	        	
			        return false;
		        }
	        
				if(!class_exists($classname)) require_once($path);
			
				if(!is_array($param)) $param = array();	
				
				if(!isset($param['parent'])) $param['parent'] =& $this->_Parent;	
			
				##Create the object
				self::$_pool[$name] = new $classname($param);	
			}
			
			return self::$_pool[$name];
	        
        }

		## Return object instance of a named extension
		public function getInstance($name){
			
			$extensions = $this->_pool;
			
			foreach($extensions as $e){
				if(get_class($e) == $name) return $e;
			}
			
		}

		private function __requiresUpdate($info){

			if($info['status'] == EXTENSION_NOT_INSTALLED) return false;
			
			$current_version = $this->fetchInstalledVersion($info['handle']);

			return (version_compare($current_version, $info['version'], '<') ? $current_version : false);
		}
		
		private function __requiresInstallation($name){	
			$id = Symphony::Database()->fetchVar('id', 0, "SELECT `id` FROM `tbl_extensions` WHERE `name` = '$name' LIMIT 1");
			return (is_numeric($id) ? false : true);
		}
		
		
		public function fetchInstalledVersion($name){
			return Symphony::Database()->fetchVar('version', 0, "SELECT `version` FROM `tbl_extensions` WHERE `name` = '$name' LIMIT 1");
		}
		
		public function fetchExtensionID($name){
			return Symphony::Database()->fetchVar('id', 0, "SELECT `id` FROM `tbl_extensions` WHERE `name` = '$name' LIMIT 1");
		}
		
		public function fetchCustomMenu($name){
			
		    $classname = $this->__getClassName($name); 	
				
			if(!class_exists($classname)){
  
		        $path = $this->__getDriverPath($name);

				if(!@file_exists($path)) return false;

				require_once($path);				
			}
			
			return @call_user_func(array(&$classname, 'fetchCustomMenu'));		
		}
		
        ## Returns the about details of a service
        public function about($name){
	
	        $classname = $this->__getClassName($name);   
	        $path = $this->__getDriverPath($name);

			if(!@file_exists($path)) return false;

			require_once($path);	
			        
	        if($about = @call_user_func(array(&$classname, "about"))){
				
				$about['handle'] = $name;
				$about['status'] = $this->fetchStatus($name);

				$nav = @call_user_func(array(&$classname, 'fetchNavigation'));
				
				if($nav != NULL) $about['navigation'] = $nav;
				
				if($this->__requiresUpdate($about)) $about['status'] = EXTENSION_REQUIRES_UPDATE;

				return $about;
			}

			return false;
									        
        }
				
		private function __cleanupDatabase(){
			
			## Grab any extensions sitting in the database
			$rows = Symphony::Database()->fetch("SELECT * FROM `tbl_extensions`");
			
			## Iterate over each row
			if(is_array($rows) && !empty($rows)){
				foreach($rows as $r){
					$name = $r['name'];
					
					## Grab the install location
					$path = $this->__getClassPath($name);
					
					## If it doesnt exist, remove the DB rows
					if(!@is_dir($path)){

						if($existing_id = $this->fetchExtensionID($name))
							Symphony::Database()->query("DELETE FROM `tbl_extensions_delegates` WHERE `tbl_extensions_delegates`.extension_id = $existing_id");

						Symphony::Database()->query("DELETE FROM `tbl_extensions` WHERE `name` = '$name' LIMIT 1");
		
					}
				}
			}
			
			## Remove old delegate information
			$disabled = Symphony::Database()->fetchCol('id', "SELECT `id` FROM `tbl_extensions` WHERE `status` = 'disabled'");
			$sql = "DELETE FROM `tbl_extensions_delegates` WHERE `extension_id` IN ('".@implode("', '", $disabled)."')";
			Symphony::Database()->query($sql);			
			
		}
    
    }
