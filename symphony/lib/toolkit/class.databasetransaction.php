<?php

    /**
     * @package toolkit
     */
    
    class DatabaseTransaction
    {
        /**
         * The number of currently open transactions.
         *
         * @var integer
         */
        protected static $transactions = 0;

        /**
         * The database connection.
         *
         * @var PDO
         */
        protected $connection;

        /**
         * Has this transaction completed.
         *
         * @var boolean
         */
        protected $completed;

        /**
         * A nested database transaction manager.
         *
         * @param PDO $connection
         */
        public function __construct(PDO $connection)
        {
            $this->completed = false;
            $this->connection = $connection;

            if (0 === self::$transactions) {
                $this->connection->beginTransaction();
            } else {
                $this->connection->exec('savepoint trans' . self::$transactions);
            }

            self::$transactions++;
        }

        /**
         * Commit the transaction
         *
         * @return boolean
         */
        public function commit()
        {
            if ($this->completed) {
                return false;
            }

            self::$transactions--;
            $this->completed = true;

            if (0 === self::$transactions) {
                return $this->connection->commit();
            } else {
                return (boolean)$this->connection->exec('release savepoint trans' . self::$transactions);
            }
        }

        /**
         * Rollback this transaction
         *
         * @return boolean
         */
        public function rollBack()
        {
            if ($this->completed) {
                return false;
            }

            self::$transactions--;
            $this->completed = true;

            if (0 === self::$transactions) {
                return $this->connection->rollBack();
            } else {
                return (boolean)$this->connection->exec('rollback to savepoint trans' . self::$transactions);
            }
        }
    }
