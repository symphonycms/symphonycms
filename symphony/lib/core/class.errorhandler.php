<?php

	Class GenericExceptionHandler{
		
		public static $enabled;
		
		public static function initialise(){
			self::$enabled = true;
			set_exception_handler(array(__CLASS__, 'handler'));
		}
		
		protected static function __nearbyLines($line, $file, $isString=false, $window=5, $normalise_tabs=true){
			if($isString === false) $result = array_slice(file($file), max(0, ($line - 1) - $window), $window*2, true);
			else $result = array_slice(preg_split('/[\r\n]+/', $file), max(0, ($line - 1) - $window), $window*2, true);

			if($normalise_tabs == true && !empty($result)){
				$length = NULL;
				foreach($result as $string){
					preg_match('/^\t+/', $string, $match);
					if(strlen(trim($string)) > 0 && (is_null($length) || strlen($match[0]) < $length)) $length = strlen($match[0]);
				}
				
				if(!is_null($length) && $length > 0){
					foreach($result as $index => $string){
						$result[$index] = preg_replace('/^\t{'.$length.'}/', NULL, $string);
					}
				}
			}
			
			return $result;
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
				
			}
			catch(Exception $e){
				header('Content-Type: text/plain; charset=utf-8');
				print "<h1>Looks like the Exception handler crapped out</h1>";
				print_r($e);
			}
			
			exit;
		}
		
		public static function render($e){
			
			$xml = new DOMDocument('1.0', 'utf-8');
			$xml->formatOutput = true;
			
			$root = $xml->createElement('data');
			$xml->appendChild($root);
			
			$details = $xml->createElement('details', $e->getMessage());
			$details->setAttribute('type', General::sanitize(
				($e instanceof ErrorException ? GenericErrorHandler::$errorTypeStrings[$e->getSeverity()] : 'Fatal Error')
			));
			$details->setAttribute('file', General::sanitize($e->getFile()));
			$details->setAttribute('line', $e->getLine());
			$root->appendChild($details);
			
			$nearby_lines = self::__nearByLines($e->getLine(), $e->getFile());

			$lines = $xml->createElement('nearby-lines');
			
			$markdown .= "\t" . $e->getMessage() . "\n";
			$markdown .= "\t" . $e->getFile() . " line " . $e->getLine() . "\n\n";
			
			foreach($nearby_lines as $line_number => $string){
				
				$markdown .= "\t{$string}";
				
				$string = trim(str_replace("\t", '&nbsp;&nbsp;&nbsp;&nbsp;', General::sanitize($string)));
				$item = $xml->createElement('item', (strlen($string) == 0 ? '&nbsp;' : $string));
				$item->setAttribute('number', $line_number + 1); 
				$lines->appendChild($item);
				
			}
			$root->appendChild($lines);
			
			$element = $xml->createElement('markdown'); //, General::sanitize($markdown)));
			$element->appendChild($xml->createCDATASection($markdown));
			$root->appendChild($element);
			
			
			$trace = $xml->createElement('backtrace');
			
			foreach($e->getTrace() as $t){

				$item = $xml->createElement('item');
				
				if(isset($t['file'])) $item->setAttribute('file', General::sanitize($t['file']));
				if(isset($t['line'])) $item->setAttribute('line', $t['line']);
				if(isset($t['class'])) $item->setAttribute('class', General::sanitize($t['class']));
				if(isset($t['type'])) $item->setAttribute('type', $t['type']);
				$item->setAttribute('function', General::sanitize($t['function']));
				
				$trace->appendChild($item);	
			}
			$root->appendChild($trace);

			if(is_object(Symphony::Database())){
				
				/* TODO: Implement Error Handling
				$debug = Symphony::Database()->debug();

				if(count($debug['query']) > 0){

					$queries = $xml->createElement('query-log');

					foreach($debug['query'] as $query){
						$item = $xml->createElement('item', General::sanitize($query['query']));
						if(isset($query['time'])) $item->setAttribute('time', $query['time']);
						$queries->appendChild($item);	
					}
					
					$root->appendChild($queries);
				}
				*/
			}
			
			return self::__transform($xml);
			
		}

		protected static function __transform(DOMDocument $xml, $template='exception.generic.xsl'){

			$path = TEMPLATES . '/'. $template;
			if(file_exists(MANIFEST . '/templates/' . $template)){
				$path = MANIFEST . '/templates/' . $template;
			}

			return XSLProc::transform($xml, file_get_contents($path), XSLProc::XML, array('root' => URL));

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