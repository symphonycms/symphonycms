<?php

    /**
     * @package toolkit
     */

    class DatabaseStatement
    {
        /**
         * The database connection.
         *
         * @var Database
         */
        public $database;

        /**
         * The internal PDOStatement.
         *
         * @var PDOStatement
         */
        public $statement;

        /**
         * Wrap a PDOStatement object so that we can log its queries
         * and handle its errors.
         *
         * @param Database $database
         * @param PDOStatement $statement
         */
        public function __construct(Database $database, PDOStatement $statement)
        {
            $this->database = $database;
            $this->statement = $statement;
        }

        /**
         * Call a method on the internal PDOStatement object.
         *
         * @link    http://php.net/manual/en/class.pdostatement.php
         *  For a list of available methods.
         * @param   string $name
         * @param   array $arguments
         * @return  mixed
         */
        public function __call($name, $arguments)
        {
            return $this->statement->{$name}(...$arguments);
        }

        /**
         * Get the value of a property on the internal PDOStatement.
         *
         * @link    http://php.net/manual/en/class.pdostatement.php
         *  For a list of available properties.
         * @param   string $name
         * @return  mixed
         *  The value of the property or null if the property does not exist.
         */
        public function __get($name)
        {
            return (
            isset($this->statement->{$name})
                ? $this->statement->{$name}
                : null
            );
        }

        /**
         * Set a property on the internal PDOStatement.
         *
         * @link    http://php.net/manual/en/class.pdostatement.php
         *  For a list of available properties.
         * @param   string $name
         * @param   mixed $value
         */
        public function __set($name, $value)
        {
            $this->statement->{$name} = $value;
        }

        /**
         * Executes the internal PDOStatement.
         *
         * @link    http://php.net/manual/en/pdostatement.execute.php
         *  For complete documentation.
         * @param array $arguments
         * @return bool
         */
        public function execute(...$arguments)
        {
            $start = precision_timer();

            $this->database->flush();

            $query = $this->statement->queryString;
            $hash = md5($query . $start);

            try {
                $result = $this->statement->execute(...$arguments);
            } catch (PDOException $error) {
                $this->database->throwError($error, $query, $hash);
            }

            $this->database->logQuery($query, $hash, precision_timer('stop', $start));

            return $result;
        }
    }
