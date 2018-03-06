<?php

/**
 * @package toolkit
 */

/**
 * The DatabaseTransactionException class extends a normal Exception.
 * Used when a DatabaseTransaction is about to enter an invalid state.
 */
class DatabaseTransactionException extends Exception
{
    /**
     * Constructor takes a message and an associative array to set to
     * `$_error`. Before the message is passed to the default Exception constructor,
     * it tries to translate the message.
     * @param string $message
     *  The exception's message
     * @param Throwable $previous
     *  If the database raised an exception, it will be added to the exception chain
     */
    public function __construct($message, $previous = null)
    {
        parent::__construct(__($message), 0, $previous);
    }
}
