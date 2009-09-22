<?php

	abstract Class Event{
		
		protected $_Parent;
		protected $_env;
		
		const CRLF = "\r\n";
		const kHIGH = 3;
		const kNORMAL = 2;
		const kLOW = 1;
				
		public function __construct(&$parent, $env=NULL){
			$this->_Parent = $parent;
			$this->_env = $env;
			
			$this->__processParameters();
		}
		
		private function __processParameters(){
			
			if(isset($this->_env) && is_array($this->_env)){
				if(isset($this->eParamOVERRIDES) && is_array($this->eParamOVERRIDES) && !empty($this->eParamOVERRIDES)){
					foreach($this->eParamOVERRIDES as $field => $replacement){
						$replacement = $this->__processParametersInString(stripslashes($replacement), $this->_env);
						
						if($replacement === NULL){
							unset($this->eParamOVERRIDES[$field]);
							continue;
						}
						
						$this->eParamOVERRIDES[$field] = $replacement;
					}
				}
				
				if(isset($this->eParamDEFAULTS) && is_array($this->eParamDEFAULTS) && !empty($this->eParamDEFAULTS)){
					foreach($this->eParamDEFAULTS as $field => $replacement){
						$replacement = self::__processParametersInString(stripslashes($replacement), $this->_env);

						if($replacement === NULL){
							unset($this->eParamDEFAULTS[$key]);
							continue;
						}

						$this->eParamDEFAULTS[$field] = $replacement;
					}
				}
			}	
		}
		
		private static function __processParametersInString($value, array $env=NULL){

			if(preg_match_all('@{\$([^}]+)}@i', $value, $matches, PREG_SET_ORDER)){

				foreach($matches as $index => $match){
					list($pattern, $param) = $match;
					$replacement = self::__findParameterInEnv($param, $env);
					
					if($value == $pattern && $replacement === NULL) return NULL;
					
					$value = str_replace($pattern, $replacement, $value);
				}

			}

			return $value;
		}
		
		private static function __findParameterInEnv($needle, $env){

			if(isset($env['env']['url'][$needle])){
				return $env['env']['url'][$needle];
			}
			
			elseif(isset($env['param'][$needle])){
				return $env['param'][$needle];
			}

			return NULL;			
		}		
		
		## This function is required in order to edit it in the event editor page. 
		## Do not overload this function if you are creating a custom event. It is only
		## used by the event editor
		public static function allowEditorToParse(){
			return false;
		}
				
		## This function is required in order to identify what type of event this is for
		## use in the event editor. It must remain intact. Do not overload this function in
		## custom events.
		public static function getSource(){
			return NULL;
		}
						
		abstract public static function about();
				
		abstract public function load();
		
		abstract protected function __trigger();
	}
	
