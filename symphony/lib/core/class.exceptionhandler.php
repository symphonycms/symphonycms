<?php
/**
 * @package core
 */

/**
 * ExceptionHandler will handle any uncaught exceptions thrown in
 * Symphony. Additionally, all errors in Symphony that are raised to Exceptions
 * will be handled by this class.
 * It is possible for Exceptions to be caught by their own `ExceptionRenderer` which can
 * provide the `render` function so that it can be displayed to the user appropriately.
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
    private static $log = null;

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
     * Enables the shutdown function.
     * Defaults to true.
     *
     * @var boolean
     */
    public static $shutdownEnabled = true;

    /**
     * Initialise will set the error handler to be the `__CLASS__::handler` function.
     *
     * @param Log $Log
     *  An instance of a Symphony Log object to write errors to
     */
    public static function initialise(Log $Log = null)
    {
        if (!is_null($Log)) {
            self::$log = $Log;
        }

        set_exception_handler(array(__CLASS__, 'handler'));
        register_shutdown_function(array(__CLASS__, 'shutdown'));
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
        $class = 'ExceptionRenderer';

        try {
            // Validate the type, resolve to a 404 if not valid
            if (!static::isValidThrowable($e)) {
                $e = new FrontendPageNotFoundException();
            }

            $exception_type = get_class($e);

            if (class_exists("{$exception_type}Handler") && method_exists("{$exception_type}Handler", 'render')) {
                $class = "{$exception_type}Handler";
            } elseif (class_exists("{$exception_type}Renderer") && method_exists("{$exception_type}Renderer", 'render')) {
                $class = "{$exception_type}Renderer";
            }

            // Exceptions should be logged if they are not caught.
            if (!self::$logDisabled && self::$log instanceof Log) {
                self::$log->pushExceptionToLog($e, true);
            }

            // Send headers
            call_user_func([$class, 'sendHeaders'], $e);
            // Get output
            $output = call_user_func([$class, 'render'], $e);

        // If an exception was raised trying to render the exception, fall back
        // to the generic exception handler
        } catch (Exception $e) {
            try {
                if ($class != 'ExceptionRenderer') {
                    $output = call_user_func(['ExceptionRenderer', 'render'], $e);
                } else {
                    throw $e;
                }

            // If the generic exception handler couldn't do it, well we're in bad
            // shape, just output a plaintext response!
            } catch (Exception $e) {
                self::echoRendererError($e);
                exit;
            }
        }

        // Pending nothing disasterous, we should have `$output` values here.
        echo $output;
        exit;
    }

    /**
     * Writes an error to stdout.
     * This is used as the last attempt to display and error to the end user,
     * if our custom error handling code failed.
     *
     * @param Throwable $e
     * @return void
     */
    final public static function echoRendererError($e)
    {
        echo "<pre>";
        echo 'A severe error occurred whilst trying to handle an exception, check the Symphony log for more details';
        echo PHP_EOL;
        if (self::$enabled === true) {
            echo $e->getMessage() . ' on ' . $e->getLine() . ' of file ' . $e->getFile() . PHP_EOL;
        }
        echo "</pre>";
    }

    /**
     * The shutdown function will capture any fatal errors and format them as a
     * usual Symphony page.
     *
     * @since Symphony 2.4
     *
     * @since Symphony 3.0.0 the shutdown function can be disabled with $shutdownEnabled
     */
    public static function shutdown()
    {
        if (!self::$shutdownEnabled) {
            return;
        }

        $last_error = error_get_last();

        if (!is_null($last_error) && $last_error['type'] === E_ERROR) {
            $code = $last_error['type'];
            $message = $last_error['message'];
            $file = $last_error['file'];
            $line = $last_error['line'];

            try {
                // Log the error message
                if (!self::$logDisabled && self::$log instanceof Log) {
                    self::$log->pushToLog(sprintf(
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
                echo ExceptionRenderer::renderHtml(
                    'fatalerror.fatal',
                    'Fatal Error',
                    $message,
                    $file,
                    $line
                );
            } catch (Exception $e) {
                self::echoRendererError($e);
            }
        }
    }
}
