<?php

	Class GenericExceptionHandler{
		
		public static $enabled;
		
		public static function initialise(){
			self::$enabled = true;
			set_exception_handler(array(__CLASS__, 'handler'));
		}
		
		protected static function __nearbyLines($line, $file, $window=5){
			return array_slice(file($file), ($line - 1) - $window, $window*2, true);
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
				
				header('Content-Type: text/html; charset=utf-8');
				header(sprintf('Content-Length: %d', strlen($output)));
				echo $output;
				exit;
				
			}
			catch(Exception $e){
				print "Looks like the Exception handler crapped out";
				print_r($e);
				die();
			}
		}
		
		public static function render($e){

			$lines = NULL;
			$odd = true;
			
			$markdown = "\t" . $e->getMessage() . "\n";
			$markdown .= "\t" . $e->getFile() . " line " . $e->getLine() . "\n\n";
			foreach(self::__nearByLines($e->getLine(), $e->getFile()) as $line => $string) {
			    $markdown .= "\t" . ($line+1) . $string;
			}
			
			foreach(self::__nearByLines($e->getLine(), $e->getFile()) as $line => $string){
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
			
			return sprintf('<html>
<head>
	<title>Symphony Error</title>
	<style type="text/css" media="all">
		*{
			margin: 0; padding: 0;
		}
		
		
		body{
			margin: 20px auto;
			width: 95%%;
			min-width: 950px;
			font-family: Helvetica, "MS Trebuchet", Arial, sans-serif;
			background-color: #ccc;
			font-size: 12px;
		}
		
		.bubble{
			background-color: white;
			padding: 22px;
			
			-webkit-border-radius: 20px;
			-moz-border-radius: 20px;
			
			/*
			-webkit-border-top-right-radius: 20px;
			-webkit-border-top-left-radius: 20px;
			
			-moz-border-radius-topright: 20px;
			-moz-border-radius-topleft: 20px;
			*/

			
			border: 2px solid #bbb;
		}
		
		h1{
			font-size: 34px;
			text-shadow: 2px 2px 2px #999;
			margin-bottom: 10px;
		}
		
		h2, h3{
			text-shadow: 2px 2px 2px #ccc;
		}
		
		a.markdown {
			float: right;
			margin-right: 20px;
			font-weight: normal;
			color: blue;
		}
		
		code{
			font-size: 11px;
			font-family: Monaco, "Courier New", Courier;
		}
		
		pre#markdown {
			padding: 10px 0;
		}
		
		ul, pre#markdown{
			list-style: none;
			color: #111;
			margin: 20px;
			border-left: 5px solid #bbb;
			background-color: #efefef;
		}
		
		li{
			background-color: #dedede;
			padding: 1px 5px;
			
			border-left: 1px solid #ddd;
		}
		
		li.odd{
			background-color: #efefef;
		}
		
		li#error{
			background-color: #E8CACA;
			color: #B9191A;			
		}
		
		li small{
			font-size: 10px;
			color: #666;						
		}
		
	</style>
</head>
<body>
	<h1>Symphony %s</h1>
	<div class="bubble">
		
		<a class="markdown" href="#markdown" onclick="javascript:document.getElementById(\'markdown\').style.display = ((document.getElementById(\'markdown\').style.display == \'none\') ? \'block\' : \'none\'); return false;">Show Markdown for copy/paste</a>
		
		<h2>%s</h2>
		<p>An error occurred in <code>%s</code> around line <code>%d</code></p>
		
		<pre id="markdown" style="display: none;">%s</pre>
		
		<ul>%s</ul>
	
		<h3>Backtrace:</h3>
		<ul>%s</ul>
	
		<h3>Database Query Log:</h3>
		<ul>%s</ul>	
			
	</div>
</body>
<html>', 
				($e instanceof ErrorException ? GenericErrorHandler::$errorTypeStrings[$e->getSeverity()] : 'Fatal Error'),
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
		private static $_Log;
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
