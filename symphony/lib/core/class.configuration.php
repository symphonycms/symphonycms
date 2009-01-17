<?php
	
	Class Configuration{
		
		private $_properties = array();
		private $_forceLowerCase = false;

		public function __construct($forceLowerCase=false){
			$this->_forceLowerCase = $forceLowerCase;
		}
		
		public function flush(){
			$this->_properties = array();	
		}
		
		public function get($name=NULL, $index=NULL){
			
			## Return the whole array if no name or index is requested
			if(!$name && !$index) return $this->_properties;
			
			if($this->_forceLowerCase){
				$name = strtolower($name); $index = strtolower($index);
			}
					
			if($index){
				return (isset($this->_properties[$index][$name]) ? stripslashes($this->_properties[$index][$name]) : NULL);
			}
				
			return (isset($this->_properties[$name]) ? $this->_properties[$name] : NULL);
		}
		
		public function set($name, $val, $index=NULL){
			
			if($this->_forceLowerCase){ 
				$name = strtolower($name); $index = strtolower($index);
			}
			
			if($index) $this->_properties[$index][$name] = $val;	
			else $this->_properties[$name] = $val;

		}
		
		public function remove($name, $index=NULL){
			
			if($this->_forceLowerCase){ 
				$name = strtolower($name); $index = strtolower($index); 
			}
			
			if($index && isset($this->_properties[$index][$name]))
				unset($this->_properties[$index][$name]);
				
			elseif($this->_properties[$name])
				unset($this->_properties[$name]);
					
		}
				
		public function setArray(array $array){
			$this->_properties = array_merge($this->_properties, $array);
		}		
		
		public function __toString(){

			$string = 'array(';
			foreach($this->_properties as $group => $data){
				$string .= "\r\n\r\n\r\n\t\t###### ".strtoupper($group)." ######";
				$string .= "\r\n\t\t'$group' => array(";
				foreach($data as $key => $value){
					$string .= "\r\n\t\t\t'$key' => ".(strlen($value) > 0 ? "'".addslashes($value)."'" : 'NULL').",";
				}
				$string .= "\r\n\t\t),";
				$string .= "\r\n\t\t########";
			}
			$string .= "\r\n\t)";
			
			return $string;
		}
		
	}

