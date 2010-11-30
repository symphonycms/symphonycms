<?php
	/**
	 * @package core
	 */

	/**
	 * GenericExceptionHandler will handle any uncaught exceptions thrown in Symphony.
	 * Additionally, all errors in Symphony are raised to execptions to be handled by this class.
	 * It is possible for Exceptions to be caught by their own ExceptionHandler which can
	 * override the render function so that it can be displayed to the user appropriately
	 */
	Class GenericExceptionHandler{

		/**
		 * @var boolean Whether the GenericExceptionHandler should handle exceptions
		 *  defaults to true
		 */
		public static $enabled = true;

		/**
		 * The initialise function will set the exception_handler to the this class's
		 * handler function
		 */
		public static function initialise(){
			set_exception_handler(array(__CLASS__, 'handler'));
		}

		/**
		 * Retreives a window of lines before and after the line where the error
		 * occured so that a developer can help debug the exception
		 *
		 * @param integer $line
		 *  The line where the error occured.
		 * @param string $file
		 *  The file that holds the logic that caused the error.
		 * @param integer $window
		 *  The number of lines either side of the line where the error occured
		 *  to show
		 * @return array
		 */
		protected static function __nearbyLines($line, $file, $window=5){
			return array_slice(file($file), ($line - 1) - $window, $window * 2, true);
		}

		/**
		 * The handler function is given an Exception and will call it's render
		 * function to display the Exception to a user. After calling the render
		 * function, the output is displayed and then exitted to prevent any further
		 * logic from occuring.
		 *
		 * @param Exception $e
		 *  The Exception object
		 * @return string
		 *  The result of the Exception's render function
		 */
		public static function handler($e){
			try{

				if(self::$enabled !== true) return;

				$exception_type = get_class($e);
				if(class_exists("{$exception_type}Handler") && method_exists("{$exception_type}Handler", 'render')){
					$class = "{$exception_type}Handler";
				}
				else {
					$class = __CLASS__;
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
				exit;
			}
		}

		/**
		 * The render function will take an Exception and output a HTML page
		 *
		 * @param Exception $e
		 *  The Exception object
		 * @return string
		 *  An HTML string
		 */
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
			border-radius: 20px;

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

	/**
	 * GenericErrorHandler will catch any warnings or notices thrown by PHP and
	 * raise the errors to Exceptions so they can be dealt with by the
	 * GenericExceptionHandler. If the error raised is not a E_NOTICE or E_STRICT,
	 * it will be written to the Symphony log. Symphony will raise Exceptions for errors
	 * thrown based on the error_reporting level set in PHP
	 */
	Class GenericErrorHandler{

		/**
		 * @var boolean Whether the error handler is enabled or not, defaults to true
		 *  Setting to false will prevent any Symphony error handling from occuring
		 */
		public static $enabled = true;

		/**
		 * @var Log An instance of the Symphony Log class, used to write errors to the
		 *  log
		 */
		private static $_Log = null;

		/**
		 * @var array An array of all the error constants that Symphony will throw an
		 *  exception for. These constants map directly to PHP's error constants.
		 *
		 * @see http://www.php.net/manual/en/errorfunc.constants.php
		 */
		protected static $_enabledErrorTypes = array();

		/**
		 * @var array An associative array with the PHP error constant as a key, and
		 *  a string describing that constant as the value
		 */
		public static $errorTypeStrings = array (
			E_NOTICE         				=> 'Notice',
			E_WARNING        				=> 'Warning',
			E_ERROR          				=> 'Error',
			E_PARSE          					=> 'Parsing Error',

			E_CORE_ERROR     			=> 'Core Error',
			E_CORE_WARNING   			=> 'Core Warning',
			E_COMPILE_ERROR  			=> 'Compile Error',
			E_COMPILE_WARNING 		=> 'Compile Warning',

			E_USER_NOTICE    			=> 'User Notice',
			E_USER_WARNING   			=> 'User Warning',
			E_USER_ERROR     			=> 'User Error',

			E_STRICT         					=> 'Strict Notice',
			E_RECOVERABLE_ERROR  	=> 'Recoverable Error'
		);

		/**
		 * Initialise will set the error handler to be the __CLASS__ handler
		 * function and will set this $_Log variable to a Log instance
		 *
		 * @param Log $Log (optional)
		 *  An instance of a Symphony Log object to write errors to
		 */
		public static function initialise(Log $Log = null){
			if(!is_null($Log)){
				self::$_Log = $Log;
			}

			set_error_handler(array(__CLASS__, 'handler'));
		}

		/**
		 * Determines if the error handler is enabled by checking that error_reporting
		 * is set in the php config and that $enabled is true
		 *
		 * @return boolean
		 */
		public static function isEnabled(){
			return (bool)ini_get('error_reporting') AND self::$enabled;
		}

		/**
		 * Parses the error_reporting variable and builds an array of the PHP error
		 * constants that the variable decodes to. This is because error_reporting can
		 * be set using bitwise operators to combine/exclude different error levels.
		 * Thanks to 'DarkGool' for inspiring this function.
		 *
		 * @author DarkGool
		 * @see http://www.php.net/manual/en/function.error-reporting.php#55985
		 */
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

		/**
		 * The handler function will write the error to the Log if it is not E_NOTICE
		 * or E_STRICT before checking that the error handler is enabled and if the
		 * error should be raised as an Exception
		 *
		 * @param integer $code
		 *  The error code, one of the PHP error constants
		 * @param string $message
		 *  The message of the error, this will be written to the log and
		 *  displayed as the exception message
		 * @param string $file
		 *  The file that holds the logic that caused the error. Defaults to null
		 * @param integer $line
		 *  The line where the error occured.
		 * @return string
		 *  Usually a string of HTML that will displayed to a user
		 */
		public static function handler($code, $message, $file = null, $line = null){

			if(!in_array($code, array(E_NOTICE, E_STRICT)) && self::$_Log instanceof Log){
				self::$_Log->pushToLog(
					sprintf(
						'%s - %s%s%s', $code, $message, ($file ? " in file $file" : null), ($line ? " on line $line" : null)
					), $code, true
				);
			}

			if(self::isEnabled() !== true || self::isErrorsEnabled($code) !== true) return;
			GenericExceptionHandler::handler(new ErrorException($message, 0, $code, $file, $line));
		}

	}
