i<?php
/**
 * @package core
 */

/**
 * ExceptionHandler will handle any uncaught exceptions thrown in
 * Symphony. Additionally, all errors in Symphony that are raised to Exceptions
 * will be handled by this class.
 * It is possible for Exceptions to be caught by their own `ExceptionHandler` which can
 * override the `render` function so that it can be displayed to the user appropriately.
 */
class ExceptionHandler
{
    /**
     * Whether the `ExceptionHandler` should handle exceptions. Defaults to true.
     * @since Symphony 3.0.0
     *  When disabled, exception are now rendered using the fatalerror.disabled template,
     *  to prevent leaking debug data.
     * @var boolean
     */
    public static $enabled = true;

    /**
     * An instance of the Symphony Log class, used to write errors to the log
     * @var Log
     */
    private static $_Log = null;

    /**
     * Whether to log errors or not.
     * This one is to be used temporarily, e.g., when PHP function is
     * supposed throw Exception and log should be kept clean.
     *
     * @since Symphony 2.6.4
     * @var boolean
     * @example
     *  ExceptionHandler::$logDisabled = true;
     *  DoSomethingThatEndsWithWarningsYouDoNotWantInLogs();
     *  ExceptionHandler::$logDisabled = false;
     */
    public static $logDisabled = false;

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
     * This function's goal is to validate the `$e` parameter in order to ensure
     * that the object is an `Exception` or a `Throwable` instance.
     * @since Symphony 2.7.0
     *
     * @param Throwable $e
     *  The Throwable object that will be validated
     * @return boolean
     *  true when valid, false otherwise
     */
    public static function isValidThrowable($e)
    {
        return $e instanceof Exception || $e instanceof Throwable;
    }

    /**
     * The handler function is given an Throwable and will call it's render
     * function to display the Throwable to a user. After calling the render
     * function, the output is displayed and then exited to prevent any further
     * logic from occurring.
     *
     * @since Symphony 2.7.0
     *  This function works with both Exception and Throwable
     *  Supporting both PHP 5.6 and 7 forces use to not qualify the $e parameter
     *
     * @since Symphony 3.0.0
     *  The method is final
     *
     * @param Throwable $e
     *  The Throwable object
     * @return string
     *  The result of the Throwable's render function
     */
    final public static function handler($e)
    {
        $output = '';

        try {

            // Validate the type, resolve to a 404 if not valid
            if (!static::isValidThrowable($e)) {
                $e = new FrontendPageNotFoundException();
            }

            $exception_type = get_class($e);

            if (class_exists("{$exception_type}Handler") && method_exists("{$exception_type}Handler", 'render')) {
                $class = "{$exception_type}Handler";
            } else {
                $class = __CLASS__;
            }

            // Exceptions should be logged if they are not caught.
            if (!self::$logDisabled && self::$_Log instanceof Log) {
                self::$_Log->pushExceptionToLog($e, true);
            }

            $output = call_user_func(array($class, 'render'), $e);

        // If an exception was raised trying to render the exception, fall back
        // to the generic exception handler
        } catch (Exception $e) {
            try {
                $output = call_user_func(array('ExceptionHandler', 'render'), $e);

            // If the generic exception handler couldn't do it, well we're in bad
            // shape, just output a plaintext response!
            } catch (Exception $e) {
                echo "<pre>";
                echo 'A severe error occurred whilst trying to handle an exception, check the Symphony log for more details' . PHP_EOL;
                if (self::$enabled === true) {
                    echo $e->getMessage() . ' on ' . $e->getLine() . ' of file ' . $e->getFile() . PHP_EOL;
                }
                echo "</pre>";
                exit;
            }
        }

        // Pending nothing disasterous, we should have `$e` and `$output` values here.
        self::sendHeaders($e);

        echo $output;
        exit;
    }

    /**
     * Sends out the proper HTTP headers when rendering an error page.
     * It sets the page status to the proper code, depending on the Throwable received.
     * If the Throwable is a SymphonyException, additional headers are also sent.
     *
     * @uses SymphonyException::getAdditional()
     * @uses SymphonyException::getHttpStatusCode()
     * @param Throwable $e
     *  The Throwable object
     * @return void
     */
    protected static function sendHeaders($e)
    {
        if (!headers_sent()) {
            cleanup_session_cookies();

            // Inspect the exception to determine the best status code
            $httpStatus = null;
            if ($e instanceof SymphonyException) {
                $httpStatus = $e->getHttpStatusCode();
                if (isset($e->getAdditional()->header)) {
                    header($e->getAdditional()->header);
                }
            } elseif ($e instanceof FrontendPageNotFoundException) {
                $httpStatus = Page::HTTP_STATUS_NOT_FOUND;
            }

            if (!$httpStatus || $httpStatus == Page::HTTP_STATUS_OK) {
                $httpStatus = Page::HTTP_STATUS_ERROR;
            }

            Page::renderStatusCode($httpStatus);
            header('Content-Type: text/html; charset=utf-8');
        }
    }

    /**
     * Returns the path to the error-template by looking at the `WORKSPACE/template/`
     * directory, then at the `TEMPLATES`  directory for the convention `*.tpl`. If
     * the template is not found, `false` is returned
     *
     * @since Symphony 2.3
     * @param string $name
     *  Name of the template
     * @return string|false
     *  String, which is the path to the template if the template is found,
     *  false otherwise
     */
    public static function getTemplate($name)
    {
        $format = '%s/%s.tpl';

        if (!self::$enabled) {
            if (!file_exists($template = sprintf($format, TEMPLATE, 'fatalerror.disabled'))) {
                return false;
            }
            return $template;
        }
        else if (file_exists($template = sprintf($format, WORKSPACE . '/template', $name))) {
            return $template;
        }
        else if (file_exists($template = sprintf($format, TEMPLATE, $name))) {
            return $template;
        }
        else {
            return false;
        }
    }

    /**
     * The render function will take an Throwable and output a HTML page
     *
     * @since Symphony 2.7.0
     *  This function works with both Exception and Throwable
     *
     * @param Throwable $e
     *  The Throwable object
     * @return string
     *  An HTML string
     */
    public static function render($e)
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
            $debug = Symphony::Database()->getLogs();

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
            ($e instanceof ErrorException ? ErrorHandler::$errorTypeStrings[$e->getSeverity()] : 'Fatal Error'),
            $e->getMessage() .
                ($e->getPrevious()
                    ? '<br />' . __('Previous exception: ') . $e->getPrevious()->getMessage()
                    : ''
                ),
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
                if (!self::$logDisabled && self::$_Log instanceof Log) {
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
                if (self::$enabled === true) {
                    echo $e->getMessage() . ' on ' . $e->getLine() . ' of file ' . $e->getFile() . PHP_EOL;
                }
                echo "</pre>";
            }
        }
    }

    /**
     * This function will fetch the desired `$template`, and output the
     * Throwable in a user friendly way.
     *
     * @since Symphony 2.4
     * @since Symphony 2.6.4 the method is protected
     * @param string $template
     *  The template name, which should correspond to something in the TEMPLATE
     *  directory, eg `fatalerror.fatal`.
     *
     * @since Symphony 2.7.0
     *  This function works with both Exception and Throwable
     *
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
    protected static function renderHtml($template, $heading, $message, $file = null, $line = null, $lines = null, $trace = null, $queries = null)
    {
        $html = sprintf(
            file_get_contents(self::getTemplate($template)),
            $heading,
            !self::$enabled ? 'Something unexpected occurred.' : General::unwrapCDATA($message),
            !self::$enabled ? '' : $file,
            !self::$enabled ? '' : $line,
            !self::$enabled ? null : $lines,
            !self::$enabled ? null : $trace,
            !self::$enabled ? null : $queries
        );

        $html = str_replace('{ASSETS_URL}', ASSETS_URL, $html);
        $html = str_replace('{SYMPHONY_URL}', SYMPHONY_URL, $html);
        $html = str_replace('{URL}', URL, $html);
        $html = str_replace('{PHP}', PHP_VERSION, $html);
        $html = str_replace(
            '{MYSQL}',
            !Symphony::Database() ? 'N/A' : Symphony::Database()->getVersion(),
            $html
        );

        return $html;
    }
}
