<?php

/**
 * @package toolkit
 */

/**
 * The DatabaseException class extends a normal Exception to add in
 * debugging information when a SQL query fails such as the internal
 * database error code and message in additional to the usual
 * Exception information. It allows a DatabaseException to contain a human
 * readable error, as well more technical information for debugging.
 */
class DatabaseException extends Exception
{
    /**
     * An associative array with three keys, 'query', 'msg' and 'num'
     * @var array
     */
    private $_error = array();

    /**
     * Constructor takes a message and an associative array to set to
     * `$_error`. Before the message is passed to the default Exception constructor,
     * it tries to translate the message.
     * @param string $message
     *  The exception's message
     * @param array $error
     *  The database error information
     * @param Throwable $previous
     *  If the database raised an exception, it will be added to the exception chain
     */
    public function __construct($message, array $error = null, $previous = null)
    {
        parent::__construct(__($message), 0, $previous);
        $this->_error = $error;
        $this->code = !$previous ? 0 : $previous->getCode();
    }

    /**
     * Accessor function for the original query that caused this Exception
     *
     * @return string
     */
    public function getQuery()
    {
        return $this->_error['query'];
    }

    /**
     * Accessor function for the Database error code for this type of error
     *
     * @return int
     */
    public function getDatabaseErrorCode()
    {
        return $this->_error['num'];
    }

    /**
     * Accessor function for the Database message from this Exception
     *
     * @return string
     */
    public function getDatabaseErrorMessage()
    {
        return $this->_error['msg'];
    }
}
