<?php
/**
 * @package core
 */

/**
 * GenericExceptionHandler will handle any uncaught exceptions thrown in
 * Symphony. Additionally, all errors in Symphony that are raised to Exceptions
 * will be handled by this class.
 * It is possible for Exceptions to be caught by their own `ExceptionHandler` which can
 * override the `render` function so that it can be displayed to the user appropriately.
 */
class GenericExceptionHandler
{
    /**
     * Whether the `GenericExceptionHandler` should handle exceptions. Defaults to true
     * @var boolean
     */
    public static $enabled = true;

    /**
     * An instance of the Symphony Log class, used to write errors to the log
     * @var Log
     */
    private static $_Log = null;

    /**
     * Initialise will set the error handler to be the `__CLASS__::handler` function.
     *
     * @param Log $Log
     *  An instance of a Symphony Log object to write errors to
     */
    public static function initialise(Log $Log = null)
    {
        if (!is_null($Log)) {
            self::$_Log = $Log;
        }

        set_exception_handler(array(__CLASS__, 'handler'));
        register_shutdown_function(array(__CLASS__, 'shutdown'));
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
    protected static function __nearbyLines($line, $file, $window = 5)
    {
        if (!file_exists($file)) {
            return array();
        }

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
    public static function handler(Exception $e)
    {
        $output = '';

        try {
            // Instead of just throwing an empty page, return a 404 page.
            if (self::$enabled !== true) {
                $e = new FrontendPageNotFoundException();
            };

            $exception_type = get_class($e);

            if (class_exists("{$exception_type}Handler") && method_exists("{$exception_type}Handler", 'render')) {
                $class = "{$exception_type}Handler";
            } else {
                $class = __CLASS__;
            }

            // Exceptions should be logged if they are not caught.
            if (self::$_Log instanceof Log) {
                self::$_Log->pushExceptionToLog($e, true);
            }

            cleanup_session_cookies();

            $output = call_user_func(array($class, 'render'), $e);

        // If an exception was raised trying to render the exception, fall back
        // to the generic exception handler
        } catch (Exception $e) {
            try {
                $output = call_user_func(array('GenericExceptionHandler', 'render'), $e);

            // If the generic exception handler couldn't do it, well we're in bad
            // shape, just output a plaintext response!
            } catch (Exception $e) {
                echo "<pre>";
                echo 'A severe error occurred whilst trying to handle an exception, check the Symphony log for more details' . PHP_EOL;
                echo $e->getMessage() . ' on ' . $e->getLine() . ' of file ' . $e->getFile() . PHP_EOL;
                exit;
            }
        }

        // Pending nothing disasterous, we should have `$e` 
        // and `$output` values here.
        if (!headers_sent()) {
            $httpStatus = null;
            if ($e instanceof SymphonyErrorPage) {
                $httpStatus = $e->getHttpStatusCode();
            } else if ($e instanceof FrontendPageNotFoundException) {
                $httpStatus = Page::HTTP_STATUS_NOT_FOUND;
            }
            if (!$httpStatus || $httpStatus == Page::HTTP_STATUS_OK) {
                $httpStatus = Page::HTTP_STATUS_ERROR;
            }
            Page::renderStatusCode($httpStatus);
            header('Content-Type: text/html; charset=utf-8');
        }

        echo $output;
        exit;
    }

    /**
     * Returns the path to the error-template by looking at the
     * `WORKSPACE/template/` directory, then at the `TEMPLATES`
     * directory for the convention `*.tpl`. If the template
     * is not found, false is returned
     *
     * @since Symphony 2.3
     * @param string $name
     *  Name of the template
     * @return mixed
     *  String, which is the path to the template if the template is found,
     *  false otherwise
     */
    public static function getTemplate($name)
    {
        $format = '%s/%s.tpl';

        if (file_exists($template = sprintf($format, WORKSPACE . '/template', $name))) {
            return $template;
        } elseif (file_exists($template = sprintf($format, TEMPLATE, $name))) {
            return $template;
        } else {
            return false;
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
    public static function render(Exception $e)
    {
        $lines = null;

        foreach (self::__nearByLines($e->getLine(), $e->getFile()) as $line => $string) {
            $lines .= sprintf(
                '<li%s><strong>%d</strong> <code>%s</code></li>',
                (($line+1) == $e->getLine() ? ' class="error"' : null),
                ++$line,
                str_replace("\t", '&nbsp;&nbsp;&nbsp;&nbsp;', htmlspecialchars($string))
            );
        }

        $trace = null;

        foreach ($e->getTrace() as $t) {
            $trace .= sprintf(
                '<li><code><em>[%s:%d]</em></code></li><li><code>&#160;&#160;&#160;&#160;%s%s%s();</code></li>',
                (isset($t['file']) ? $t['file'] : null),
                (isset($t['line']) ? $t['line'] : null),
                (isset($t['class']) ? $t['class'] : null),
                (isset($t['type']) ? $t['type'] : null),
                $t['function']
            );
        }

        $queries = null;

        if (is_object(Symphony::Database())) {
            $debug = Symphony::Database()->debug();

            if (!empty($debug)) {
                foreach ($debug as $query) {
                    $queries .= sprintf(
                        '<li><em>[%01.4f]</em><code> %s;</code> </li>',
                        (isset($query['execution_time']) ? $query['execution_time'] : null),
                        htmlspecialchars($query['query'])
                    );
                }
            }
        }

        return self::renderHtml(
            'fatalerror.generic',
            ($e instanceof ErrorException ? GenericErrorHandler::$errorTypeStrings[$e->getSeverity()] : 'Fatal Error'),
            $e->getMessage(),
            $e->getFile(),
            $e->getLine(),
            $lines,
            $trace,
            $queries
        );
    }

    /**
     * The shutdown function will capture any fatal errors and format them as a
     * usual Symphony page.
     *
     * @since Symphony 2.4
     */
    public static function shutdown()
    {
        $last_error = error_get_last();

        if (!is_null($last_error) && $last_error['type'] === E_ERROR) {
            $code = $last_error['type'];
            $message = $last_error['message'];
            $file = $last_error['file'];
            $line = $last_error['line'];

            try {
                // Log the error message
                if (self::$_Log instanceof Log) {
                    self::$_Log->pushToLog(sprintf(
                        '%s %s: %s%s%s',
                        __CLASS__,
                        $code,
                        $message,
                        ($line ? " on line $line" : null),
                        ($file ? " of file $file" : null)
                    ), $code, true);
                }

                ob_clean();

                // Display the error message
                echo self::renderHtml(
                    'fatalerror.fatal',
                    'Fatal Error',
                    $message,
                    $file,
                    $line
                );
            } catch (Exception $e) {
                echo "<pre>";
                echo 'A severe error occurred whilst trying to handle an exception, check the Symphony log for more details' . PHP_EOL;
                echo $e->getMessage() . ' on ' . $e->getLine() . ' of file ' . $e->getFile() . PHP_EOL;
            }
        }
    }

    /**
     * This function will fetch the desired `$template`, and output the
     * Exception in a user friendly way.
     *
     * @since Symphony 2.4
     * @param string $template
     *  The template name, which should correspond to something in the TEMPLATE
     *  directory, eg `fatalerror.fatal`.
     * @param string $heading
     * @param string $message
     * @param string $file
     * @param string $line
     * @param string $lines
     * @param string $trace
     * @param string $queries
     * @return string
     *  The HTML of the formatted error message.
     */
    public static function renderHtml($template, $heading, $message, $file = null, $line = null, $lines = null, $trace = null, $queries = null)
    {
        $html = sprintf(
            file_get_contents(self::getTemplate($template)),
            $heading,
            General::unwrapCDATA($message),
            $file,
            $line,
            $lines,
            $trace,
            $queries
        );

        $html = str_replace('{ASSETS_URL}', ASSETS_URL, $html);
        $html = str_replace('{SYMPHONY_URL}', SYMPHONY_URL, $html);
        $html = str_replace('{URL}', URL, $html);

        return $html;
    }
}

/**
 * `GenericErrorHandler` will catch any warnings or notices thrown by PHP and
 * raise the errors to Exceptions so they can be dealt with by the
 * `GenericExceptionHandler`. The type of errors that are raised to Exceptions
 * depends on the `error_reporting` level. All errors raised, except
 * `E_NOTICE` and `E_STRICT` are written to the Symphony log.
 */
class GenericErrorHandler
{
    /**
     * Whether the error handler is enabled or not, defaults to true.
     * Setting to false will prevent any Symphony error handling from occurring
     * @var boolean
     */
    public static $enabled = true;

    /**
     * An instance of the Log class, used to write errors to the log
     * @var Log
     */
    private static $_Log = null;

    /**
     * Whether to log errors or not.
     * This one is to be used temporarily, e.g., when PHP function is
     * supposed to error out and throw warning and log should be kept clean.
     *
     * @since Symphony 2.2.2
     * @var boolean
     * @example
     *  GenericErrorHandler::$logDisabled = true;
     *  DoSomethingThatEndsWithWarningsYouDoNotWantInLogs();
     *  GenericErrorHandler::$logDisabled = false;
     */
    public static $logDisabled = false;

    /**
     * An associative array with the PHP error constant as a key, and
     * a string describing that constant as the value
     * @var array
     */
    public static $errorTypeStrings = array (
        E_ERROR                 => 'Fatal Error',
        E_WARNING               => 'Warning',
        E_PARSE                 => 'Parsing Error',
        E_NOTICE                => 'Notice',

        E_CORE_ERROR            => 'Core Error',
        E_CORE_WARNING          => 'Core Warning',
        E_COMPILE_ERROR         => 'Compile Error',
        E_COMPILE_WARNING       => 'Compile Warning',

        E_USER_NOTICE           => 'User Notice',
        E_USER_WARNING          => 'User Warning',
        E_USER_ERROR            => 'User Error',

        E_STRICT                => 'Strict Notice',
        E_RECOVERABLE_ERROR     => 'Recoverable Error',
        E_DEPRECATED            => 'Deprecated Warning'
    );

    /**
     * Initialise will set the error handler to be the `__CLASS__::handler`
     * function.
     *
     * @param Log|null $Log (optional)
     *  An instance of a Symphony Log object to write errors to
     */
    public static function initialise(Log $Log = null)
    {
        if (!is_null($Log)) {
            self::$_Log = $Log;
        }

        set_error_handler(array(__CLASS__, 'handler'), error_reporting());
    }

    /**
     * Determines if the error handler is enabled by checking that error_reporting
     * is set in the php config and that $enabled is true
     *
     * @return boolean
     */
    public static function isEnabled()
    {
        return (bool)error_reporting() && self::$enabled;
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
     * @throws ErrorException
     * @return string
     *  Usually a string of HTML that will displayed to a user
     */
    public static function handler($code, $message, $file = null, $line = null)
    {
        // Only log if the error won't be raised to an exception and the error is not `E_STRICT`
        if (!self::$logDisabled && !in_array($code, array(E_STRICT)) && self::$_Log instanceof Log) {
            self::$_Log->pushToLog(sprintf(
                '%s %s: %s%s%s',
                __CLASS__,
                $code,
                $message,
                ($line ? " on line $line" : null),
                ($file ? " of file $file" : null)
            ), $code, true);
        }

        if (self::isEnabled()) {
            throw new ErrorException($message, 0, $code, $file, $line);
        }
    }
}
