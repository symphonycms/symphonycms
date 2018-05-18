<?php

/**
 * @package core
 */
/**
 * `ShutdownException` extends the default `Exception` class.
 * It allows the transformation of error found in the shutdown callback into an exception.
 */

class ShutdownException extends Exception
{
    public function __construct($message, $code, $file, $line)
    {
        parent::__construct($message, $code);
        $this->file = $file;
        $this->line = $line;
    }
}
