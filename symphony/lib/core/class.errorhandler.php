<?php

	Class GenericExceptionHandler{
		
		public static $enabled;
		
		public static function initialise(){
			self::$enabled = true;
			set_exception_handler(array(__CLASS__, 'handler'));
		}
		
		protected static function __nearbyLines($line, $file, $window=5){
			return array_slice(file($file), max(0, ($line - 1) - $window), $window*2, true);
		}

		public static function handler($e){
			try{

				if(self::$enabled !== true) return;
				
				$class = __CLASS__;
				$exception_type = get_class($e);
				if(class_exists("{$exception_type}Handler") && method_exists("{$exception_type}Handler", 'render')){
					$class = "{$exception_type}Handler";
				}

				$output = call_user_func(array($class, 'render'), $e);
				
				header('HTTP/1.0 500 Internal Server Error');
				header('Content-Type: text/html; charset=utf-8');
				header(sprintf('Content-Length: %d', strlen($output)));
				echo $output;
				exit;
				
			}
			catch(Exception $e){
				print "Looks like the Exception handler crapped out <pre>";
				print_r($e);
				die();
			}
		}
		
		public static function render($e){

			$lines = NULL;
			$odd = true;
			
			$markdown .= "\t" . $e->getMessage() . "\n";
			$markdown .= "\t" . $e->getFile() . " line " . $e->getLine() . "\n\n";

			foreach(self::__nearByLines($e->getLine(), $e->getFile()) as $line => $string){
				
				$markdown .= "\t" . ($line+1) . htmlspecialchars($string);
				
				$lines .= sprintf(
					'<li%s%s><strong>%d:</strong> <code>%s</code></li>', 
					($odd == true ? ' class="odd"' : NULL),
					(($line+1) == $e->getLine() ? ' id="error"' : NULL),
					++$line, 
					str_replace("\t", '&nbsp;&nbsp;&nbsp;&nbsp;', htmlspecialchars($string))
				);
				
				$odd = !$odd;
			}
			
			$trace = NULL;
			$odd = true;
			
			foreach($e->getTrace() as $t){
				$trace .= sprintf(
					'<li%s><code>[%s:%d] <strong>%s%s%s();</strong></code></li>', 
					($odd == true ? ' class="odd"' : NULL),
					(isset($t['file']) ? $t['file'] : NULL), 
					(isset($t['line']) ? $t['line'] : NULL), 
					(isset($t['class']) ? $t['class'] : NULL), 
					(isset($t['type']) ? $t['type'] : NULL),  
					$t['function']
				);
				$odd = !$odd;
			}
			
			$queries = NULL;
			$odd = true;
			if(is_object(Symphony::Database())){
				
				$debug = Symphony::Database()->debug();

				if(count($debug['query']) > 0){
					foreach($debug['query'] as $query){

						$queries .= sprintf(
							'<li%s><code>%s;</code> <small>[%01.4f]</small></li>',
							($odd == true ? ' class="odd"' : NULL),
							htmlspecialchars($query['query']),
							(isset($query['time']) ? $query['time'] : NULL)
						);
						$odd = !$odd;
					}
				}
				
			}
			
			return sprintf(file_get_contents(TEMPLATE . '/exception.generic.txt'),
				($e instanceof ErrorException ? GenericErrorHandler::$errorTypeStrings[$e->getSeverity()] : 'Fatal Error'),
				URL,
				$e->getMessage(), 
				$e->getFile(), 
				$e->getLine(), 
				$markdown,
				$lines,
				$trace,
				$queries
			);
			
		}
	}
	
	Class GenericErrorHandler{
		
		public static $enabled;
		protected static $_Log;
		protected static $_enabledErrorTypes;
		
		public static $errorTypeStrings = array (
			
			E_NOTICE         		=> 'Notice',
			E_WARNING        		=> 'Warning',
			E_ERROR          		=> 'Error',
			E_PARSE          		=> 'Parsing Error',
                                       
			E_CORE_ERROR     		=> 'Core Error',
			E_CORE_WARNING   		=> 'Core Warning',
			E_COMPILE_ERROR  		=> 'Compile Error',
			E_COMPILE_WARNING 		=> 'Compile Warning',
			                           
			E_USER_NOTICE    		=> 'User Notice',
			E_USER_WARNING   		=> 'User Warning',
			E_USER_ERROR     		=> 'User Error',
			                           
			E_STRICT         		=> 'Strict Notice',
			E_RECOVERABLE_ERROR  	=> 'Recoverable Error'
			
		);

		public static function initialise(Log $Log=NULL){
			self::$enabled = true;
			self::$_enabledErrorTypes = NULL;
			
			if(!is_null($Log)){
				self::$_Log = $Log;
			}
			
			set_error_handler(array(__CLASS__, 'handler'));
		}
		
		public static function isEnabled(){
			return (bool)ini_get('error_reporting') AND self::$enabled;
		}

		public static function handler($code, $message, $file=NULL, $line=NULL){

			if(!in_array($code, array(E_NOTICE, E_STRICT)) && self::$_Log instanceof Log){
				self::$_Log->pushToLog(
					sprintf(
						'%s - %s%s%s', $code, $message, ($file ? " in file $file" : NULL), ($line ? " on line $line" : NULL)
					), $code, true
				);
			}
			
			if(self::isEnabled() !== true || self::isErrorsEnabled($code) !== true) return;
			GenericExceptionHandler::handler(new ErrorException($message, 0, $code, $file, $line));
		}
		
		// Thanks to 'DarkGool' for inspiring this function
		// http://www.php.net/manual/en/function.error-reporting.php#55985
		public static function isErrorsEnabled($type){
			
			if(is_null(self::$_enabledErrorTypes)){
				self::$_enabledErrorTypes = array();
				$bit = ini_get('error_reporting');

				while ($bit > 0) { 
				    for($i = 0, $n = 0; $i <= $bit; $i = 1 * pow(2, $n), $n++) { 
				        $end = $i; 
				    } 
				    self::$_enabledErrorTypes[] = $end; 
				    $bit = $bit - $end; 
				}
			}
			
			return in_array($type, self::$_enabledErrorTypes);
		}
	}