<?php

	##Interface for datasouce objects
	Abstract Class DataSource{
		
		const FILTER_AND = 1;
		const FILTER_OR = 2;
		
		protected $_env;
		protected $_Parent;
		protected $_param_output_only;
		protected $_dependencies;
		protected $_force_empty_result;
		
		const CRLF = "\r\n";
		
		public static function loadFromName($name, $environment=NULL, $process_params=true){
			$classname = self::__getClassName($name);	        
	        $path = self::__getDriverPath($name);
	        
	        if(!@is_file($path)){
		        throw new Exception(
					__('Could not find Data Source <code>%s</code>. If the Data Source was provided by an Extensions, ensure that it is installed, and enabled.', array($name))
				);
	        }
	        
			if(!class_exists($classname)){
				require_once($path);
			}
								
			return new $classname($environment, $process_params);
		}
		
		protected static function __find($name){
		 
		    if(@is_file(DATASOURCES . "/data.{$name}.php")) return DATASOURCES;
		    else{	

				$extensions = ExtensionManager::instance()->listInstalledHandles();
				
				if(is_array($extensions) && !empty($extensions)){
					foreach($extensions as $e){
						if(@is_file(EXTENSIONS . "/{$e}/data-sources/data.{$name}.php")) return EXTENSIONS . "/{$e}/data-sources";
					}	
				}		    
	    	}
	    		    
		    return false;
	    }
        
		protected static function __getHandleFromFilename($filename){
			return preg_replace(array('/^data./i', '/.php$/i'), '', $filename);
		}

        protected static function __getClassName($name){
	        return 'datasource' . $name;
        }
        
        protected static function __getClassPath($name){
	        return self::__find($name);
        }
        
        protected static function __getDriverPath($name){	        
	        return self::__getClassPath($name) . "/data.{$name}.php";
        }
		
		public function __construct($env=NULL, $process_params=true){
			$this->_Parent = Symphony::Parent();
			$this->_force_empty_result = false;
			$this->_dependencies = array();
			
			if(isset($this->dsParamPARAMOUTPUT) && !is_array($this->dsParamPARAMOUTPUT)){
				$this->dsParamPARAMOUTPUT = array($this->dsParamPARAMOUTPUT);
			}
			
			if($process_params){ 
				$this->processParameters($env);
			}
		}
		
		public function processParameters($env=NULL){
									
			if($env) $this->_env = $env;
			
			if((isset($this->_env) && is_array($this->_env)) && is_array($this->dsParamFILTERS) && !empty($this->dsParamFILTERS)){
				foreach($this->dsParamFILTERS as $key => $value){
					$value = stripslashes($value);
					$new_value = $this->__processParametersInString($value, $this->_env);

					if(strlen(trim($new_value)) == 0) unset($this->dsParamFILTERS[$key]);
					else $this->dsParamFILTERS[$key] = $new_value;
					
				}
			}

			if(isset($this->dsParamORDER)) $this->dsParamORDER = $this->__processParametersInString($this->dsParamORDER, $this->_env);
			
			if(isset($this->dsParamSORT)) $this->dsParamSORT = $this->__processParametersInString($this->dsParamSORT, $this->_env);

			if(isset($this->dsParamSTARTPAGE)) {
				$this->dsParamSTARTPAGE = $this->__processParametersInString($this->dsParamSTARTPAGE, $this->_env);
				if ($this->dsParamSTARTPAGE == '') $this->dsParamSTARTPAGE = '1';
			}
		
			if(isset($this->dsParamLIMIT)) $this->dsParamLIMIT = $this->__processParametersInString($this->dsParamLIMIT, $this->_env);
		
			if(isset($this->dsParamREQUIREDPARAM) && $this->__processParametersInString($this->dsParamREQUIREDPARAM, $this->_env, false) == '') {
				$this->_force_empty_result = true; // don't output any XML
				$this->dsParamPARAMOUTPUT = NULL; // don't output any parameters
				$this->dsParamINCLUDEDELEMENTS = NULL; // don't query any fields in this section
			}
			
			$this->_param_output_only = ((!is_array($this->dsParamINCLUDEDELEMENTS) || empty($this->dsParamINCLUDEDELEMENTS)) && !isset($this->dsParamGROUP));
			
			if($this->dsParamREDIRECTONEMPTY == 'yes' && $this->_force_empty_result){
				throw new FrontendPageNotFoundException;
			}
					
		}
		
		// THIS FUNCTION WILL BE REMOVED IN THE NEXT 
		// VERSION, PLEASE THROW AN EXCEPTION INSTEAD
		protected function __redirectToErrorPage(){
			throw new FrontendPageNotFoundException;
		}
		
		public function emptyXMLSet(XMLElement $xml=NULL){
			if(is_null($xml)) $xml = new XMLElement($this->dsParamROOTELEMENT);
			$xml->appendChild($this->__noRecordsFound());
			
			return $xml;
		}
		
		protected function __appendIncludedElements(&$wrapper, $fields){
			if(!isset($this->dsParamINCLUDEDELEMENTS) || !is_array($this->dsParamINCLUDEDELEMENTS) || empty($this->dsParamINCLUDEDELEMENTS)) return;
			
			foreach($this->dsParamINCLUDEDELEMENTS as $index) {
				
				if(!is_object($fields[$index])){
					trigger_error(__('%s is not a valid object. Failed to append to XML.', array($index)), E_USER_WARNING);
					continue;
				}
				$wrapper->appendChild($fields[$index]);
			}	
		}
		
		protected function __determineFilterType($value){
			return (false === strpos($value, '+') ? Datasource::FILTER_OR : Datasource::FILTER_AND);
		}
		
		protected function __noRecordsFound(){
			return new XMLElement('error', __('No records found.'));
		}

		protected function __processParametersInString($value, $env, $includeParenthesis=true, $escape=false){
			if(trim($value) == '') return NULL;

			if(!$includeParenthesis) $value = '{'.$value.'}';

			if(preg_match_all('@{([^}]+)}@i', $value, $matches, PREG_SET_ORDER)){

				foreach($matches as $match){
					
					list($source, $cleaned) = $match;
					
					$replacement = NULL;
					
					$bits = preg_split('/:/', $cleaned, -1, PREG_SPLIT_NO_EMPTY);
					
					foreach($bits as $param){
						
						if($param{0} != '$'){
							$replacement = $param;
							break;
						}
						
						$param = trim($param, '$');
						
						$replacement = $this->__findParameterInEnv($param, $env);
						
						if(is_array($replacement)){
							$replacement = array_map(array('Datasource', 'escapeCommas'), $replacement);							
							if(count($replacement) > 1) $replacement = implode(',', $replacement);
							else $replacement = end($replacement);
						}
						
						if(!empty($replacement)) break;
						
					}
					
					if($escape == true) $replacement = urlencode($replacement);
					$value = str_replace($source, $replacement, $value);
					
				}
			}

			return $value;
		}

		public static function escapeCommas($string){
			return preg_replace('/(?<!\\\\),/', "\\,", $string);
		}
		
		public static function removeEscapedCommas($string){
			return preg_replace('/(?<!\\\\)\\\\,/', ',', $string);
		}
		
		protected function __findParameterInEnv($needle, $env){

			if(isset($env['env']['url'][$needle])) return $env['env']['url'][$needle];

			if(isset($env['env']['pool'][$needle])) return $env['env']['pool'][$needle];

			if(isset($env['param'][$needle])) return $env['param'][$needle];

			return NULL;
						
		}

		## This function is required in order to edit it in the data source editor page. 
		## Do not overload this function if you are creating a custom data source. It is only
		## used by the data source editor
		public function allowEditorToParse(){
			return false;
		}
				
		## This function is required in order to identify what type of data source this is for
		## use in the data source editor. It must remain intact. Do not overload this function into
		## custom data sources.
		public function getSource(){
			return NULL;
		}
				
		public function getDependencies(){
			return $this->_dependencies;
		}
				
		##Static function
		public function about(){		
		}

		public function grab(){
		}
	
	}
	
