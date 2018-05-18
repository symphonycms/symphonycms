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
    /**
     * The html template to use to render the exception.
     *
     * @var string
     */
    protected $template = 'fatalerror.fatal';

    public function __construct($message, $code, $file, $line)
    {
        parent::__construct($message, $code);
        $this->file = $file;
        $this->line = $line;
    }

    public function getTemplate()
    {
        return $this->template;
    }
}
