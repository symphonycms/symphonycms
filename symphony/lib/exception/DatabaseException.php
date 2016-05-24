<?php

    /**
     * @package exception
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
         *
         * @var array
         */
        private $_error = array();

        /**
         * Constructor takes a message and an associative array to set to
         * `$_error`. The message is passed to the default Exception constructor
         *
         * @param string $message
         * @param array $error
         * @param Exception $exception
         */
        public function __construct($message, array $error = null, Exception $exception = null)
        {
            parent::__construct($message, (int)$error['num'], $exception);

            $this->_error = $error;
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
         * @return integer
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
