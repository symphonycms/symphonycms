<?php
	/**
	 * @package core
	 */

	require_once(CORE . '/class.log.php');

	/**
	 * GenericExceptionHandler will handle any uncaught exceptions thrown in Symphony.
	 * Additionally, all errors in Symphony are raised to exceptions to be handled by this class.
	 * It is possible for Exceptions to be caught by their own `ExceptionHandler` which can
	 * override the render function so that it can be displayed to the user appropriately
	 */
	Class GenericExceptionHandler{

		/**
		 * Whether the `GenericExceptionHandler` should handle exceptions defaults to true
		 * @var boolean
		 */
		public static $enabled = true;

		/**
		 * An instance of the Symphony Log class, used to write errors to the log
		 * @var Log
		 */
		private static $_Log = null;

		/**
		 * The initialise function will set the exception_handler to the this class's
		 * handler function
		 * @param Log|null $log
		 *  An instance of a Symphony Log object to write errors to
		 */
		public static function initialise(Log $Log = null){
			if(!is_null($Log)){
				self::$_Log = $Log;
			}
			set_exception_handler(array(__CLASS__, 'handler'));
		}

		/**
		 * Retrieves a window of lines before and after the line where the error
		 * occurred so that a developer can help debug the exception
		 *
		 * @param integer $line
		 *  The line where the error occurred.
		 * @param string $file
		 *  The file that holds the logic that caused the error.
		 * @param integer $window
		 *  The number of lines either side of the line where the error occurred
		 *  to show
		 * @return array
		 */
		protected static function __nearbyLines($line, $file, $window=5){
			return array_slice(file($file), ($line - 1) - $window, $window * 2, true);
		}

		/**
		 * The handler function is given an Exception and will call it's render
		 * function to display the Exception to a user. After calling the render
		 * function, the output is displayed and then exited to prevent any further
		 * logic from occurring.
		 *
		 * @param Exception $e
		 *  The Exception object
		 * @return string
		 *  The result of the Exception's render function
		 */
		public static function handler(Exception $e){
			try{
				// Exceptions should be logged if they are not caught.
				if(self::$_Log instanceof Log){
					self::$_Log->pushToLog(
						sprintf(
							'%s%s%s', $e->getMessage(), ($e->getFile() ? " in file " .  $e->getFile() : null), ($e->getLine() ? " on line " . $e->getLine() : null)
						), $e->getCode(), true
					);
				}

				// Instead of just throwing an empty page, return a 404 page.
				if(self::$enabled !== true){
					require_once(CORE . '/class.frontend.php');
					$e = new FrontendPageNotFoundException();
				};

				$exception_type = get_class($e);
				if(class_exists("{$exception_type}Handler") && method_exists("{$exception_type}Handler", 'render')){
					$class = "{$exception_type}Handler";
				}
				else {
					$class = __CLASS__;
				}

				$output = call_user_func(array($class, 'render'), $e);

				if(!headers_sent()) {
					header('Content-Type: text/html; charset=utf-8');
					header(sprintf('Content-Length: %d', strlen($output)));
				}

				echo $output;
				exit;
			}
			catch(Exception $e){
				try {
					$output = call_user_func(array('GenericExceptionHandler', 'render'), $e);

					if(!headers_sent()) {
						header('Content-Type: text/html; charset=utf-8');
						header(sprintf('Content-Length: %d', strlen($output)));
					}

					echo $output;
					exit;
				}
				catch(Exception $e) {
					echo "<pre>";
					echo 'A severe error occurred whilst trying to handle an exception:' . PHP_EOL;
					echo $e->getMessage() . ' on ' . $e->getLine() . ' of file ' . $e->getFile();
					exit;
				}
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
		public static function render(Exception $e){

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

			return sprintf(file_get_contents(TEMPLATE . '/errorhandler.tpl'),
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
	 * `GenericErrorHandler` will catch any warnings or notices thrown by PHP and
	 * raise the errors to Exceptions so they can be dealt with by the
	 * `GenericExceptionHandler`. If the error raised is not a `E_NOTICE` or `E_STRICT`,
	 * it will be written to the Symphony log. Symphony will raise Exceptions for errors
	 * thrown based on the error_reporting level set in PHP
	 */
	Class GenericErrorHandler{

		/**
		 * Whether the error handler is enabled or not, defaults to true.
		 * Setting to false will prevent any Symphony error handling from occurring
		 * @var boolean
		 */
		public static $enabled = true;

		/**
		 * Whether to raise all Warnings to Exceptions or not.
		 * @var boolean
		 */
		public static $raise = false;

		/**
		 * An instance of the Symphony Log class, used to write errors to the log
		 * @var Log
		 */
		private static $_Log = null;

		/**
		 * An associative array with the PHP error constant as a key, and
		 * a string describing that constant as the value
		 * @var array
		 */
		public static $errorTypeStrings = array (
			E_NOTICE				=> 'Notice',
			E_WARNING				=> 'Warning',
			E_ERROR					=> 'Error',
			E_PARSE					=> 'Parsing Error',

			E_CORE_ERROR			=> 'Core Error',
			E_CORE_WARNING			=> 'Core Warning',
			E_COMPILE_ERROR			=> 'Compile Error',
			E_COMPILE_WARNING		=> 'Compile Warning',

			E_USER_NOTICE			=> 'User Notice',
			E_USER_WARNING			=> 'User Warning',
			E_USER_ERROR			=> 'User Error',

			E_STRICT				=> 'Strict Notice',
			E_RECOVERABLE_ERROR		=> 'Recoverable Error'
		);

		/**
		 * Initialise will set the error handler to be the `__CLASS__` handler
		 * function and will set this `$_Log` variable to a Log instance
		 *
		 * @param Log|null $Log (optional)
		 *  An instance of a Symphony Log object to write errors to
		 * @param string $raise
		 *  Whether to raise E_WARNING as an Exception
		 */
		public static function initialise(Log $Log = null, $raise = false){
			if(!is_null($Log)){
				self::$_Log = $Log;
			}
			self::$raise = ($raise == 'yes') ? true : false;

			set_error_handler(array(__CLASS__, 'handler'), error_reporting());
		}

		/**
		 * Determines if the error handler is enabled by checking that error_reporting
		 * is set in the php config and that $enabled is true
		 *
		 * @return boolean
		 */
		public static function isEnabled(){
			return (bool)error_reporting() AND self::$enabled;
		}

		/**
		 * The handler function will write the error to the `$Log` if it is not `E_NOTICE`
		 * or `E_STRICT` before raising the error as an Exception. This allows all `E_WARNING`
		 * to actually be captured by an Exception handler.
		 *
		 * @param integer $code
		 *  The error code, one of the PHP error constants
		 * @param string $message
		 *  The message of the error, this will be written to the log and
		 *  displayed as the exception message
		 * @param string $file
		 *  The file that holds the logic that caused the error. Defaults to null
		 * @param integer $line
		 *  The line where the error occurred.
		 * @return string
		 *  Usually a string of HTML that will displayed to a user
		 */
		public static function handler($code, $message, $file = null, $line = null){

			// Only log if the error won't be raised to an exception and the error is not `E_STRICT`
			if(!self::$raise && !in_array($code, array(E_STRICT)) && self::$_Log instanceof Log){
				self::$_Log->pushToLog(
					sprintf(
						'%s %s - %s%s%s',
						__CLASS__, $code, $message, ($file ? " in file $file" : null), ($line ? " on line $line" : null)
					), $code, true
				);
			}

			if(self::$raise || $code != E_WARNING) {
				throw new ErrorException($message, 0, $code, $file, $line);
			}
		}

	}
