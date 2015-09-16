<?php

/**
 * @package core
 */
/**
 * The Log class acts a simple wrapper to write errors to a file so that it can
 * be read at a later date. There is one Log file in Symphony, stored in the main
 * `LOGS` directory.
 */
use Psr\Log\LogLevel;
use Monolog\Logger;

class Log
{
    /**
     * The actual Monolog Logger instance
     * @var Logger
     */
    private $log = null;

    /**
     * Maps error levels to the appropriate log level
     *
     * @var array
     */
    private $errorLevelMap = array(
        E_ERROR             => LogLevel::CRITICAL,
        E_WARNING           => LogLevel::WARNING,
        E_PARSE             => LogLevel::ALERT,
        E_NOTICE            => LogLevel::NOTICE,
        E_CORE_ERROR        => LogLevel::CRITICAL,
        E_CORE_WARNING      => LogLevel::WARNING,
        E_COMPILE_ERROR     => LogLevel::ALERT,
        E_COMPILE_WARNING   => LogLevel::WARNING,
        E_USER_ERROR        => LogLevel::ERROR,
        E_USER_WARNING      => LogLevel::WARNING,
        E_USER_NOTICE       => LogLevel::NOTICE,
        E_STRICT            => LogLevel::NOTICE,
        E_RECOVERABLE_ERROR => LogLevel::ERROR,
        E_DEPRECATED        => LogLevel::NOTICE,
        E_USER_DEPRECATED   => LogLevel::NOTICE,
    );

    /**
     * Accepts an instance of the desired Logger.
     *
     * @param Logger $logger
     */
    public function __construct(Logger $logger)
    {
        $this->log = $logger;
    }

    /**
     * Accessor for `$log`.
     *
     * @return array
     */
    public function getLog()
    {
        return $this->log;
    }

    /**
     * Magic method allows developers to target the underlying Logger methods if they
     * like.
     *
     * @throws BadMethodCallException
     * @param string $method
     * @param array $args
     * @return mixed
     */
    public function __call($method, $args)
    {
        if (is_callable(array($this->log, $method))) {
            return call_user_func_array(array($this->log, $method), $args);
        }

        throw new BadMethodCallException('The Log has no callable ' . $method);
    }

    /**
     * Provides an old style accessor for pushing messages into the Log. It is
     * deprecated, and it's recommended to use the direct PSR-3 log methods instead.
     *
     * @param string $message
     *  The message to add to the Log
     * @param integer $type
     *  A PHP error constant for this message, defaults to E_NOTICE
     * @param array $context
     */
    public function pushToLog($message, $type = E_NOTICE, $context = [])
    {
        $level = isset($this->errorLevelMap[$type]) ? $this->errorLevelMap[$type] : LogLevel::CRITICAL;
        $this->log->log($level, $message, is_array($context) ? $context : []);
    }

    /**
     * Given an Exception, this function will add it to the internal `$_log`
     * so that it can be written to the Log.
     *
     * @since Symphony 2.3.2
     * @param Exception $exception
     */
    public function pushExceptionToLog(Exception $exception)
    {
        $message = sprintf(
            '%s %s - %s on line %d of %s',
            get_class($exception),
            $exception->getCode(),
            $exception->getMessage(),
            $exception->getLine(),
            $exception->getFile()
        );

        return $this->pushToLog($message, $exception->getCode(), ['exception' => $exception]);
    }
}
