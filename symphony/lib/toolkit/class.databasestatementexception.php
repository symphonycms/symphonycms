<?php

/**
 * @package toolkit
 */

/**
 * The DatabaseStatementException class extends a normal Exception to add in
 * debugging information when a DatabaseStatement is about to enter an invalid state
 */
class DatabaseStatementException extends Exception
{
    /**
     * Constructor takes a message.
     * Before the message is passed to the default Exception constructor,
     * it tries to translate the message.
     */
    public function __construct($message)
    {
        parent::__construct(__($message));
    }
}
