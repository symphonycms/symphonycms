<?php

/**
 * @package toolkit
 */

/**
 * This class holds all the required data to facilitate management of database transactions.
 *
 * @see DatabaseTransactionException
 */
class DatabaseTransaction
{
    /**
     * Database object reference
     * @var Database
     */
    private $db;

    /**
     * The code to execute in the transaction
     * @var callable
     */
    private $tx;

    /**
     * Creates a new DatabaseTransaction object, linked to the $db parameter, in which
     * to execute the statements contained in the $tx callable.
     * $tx will be called with a single parameter: the instance of the linked Database object.
     *
     * @param Database $db
     *  The Database reference
     * @param callable $tx
     *  The code to execute in the transaction
     */
    public function __construct(Database $db, $tx)
    {
        if (!is_callable($tx)) {
            throw new DatabaseTransactionException('Parameter $tx must be callable');
        }
        $this->db = $db;
        $this->tx = $tx;
    }

    /**
     * Getter for the underlying database object.
     *
     * @return Database
     */
    final protected function getDB()
    {
        return $this->db;
    }

    /**
     * Send the transaction operation to the  the server for execution.
     *
     * @return DatabaseTransactionResult
     * @throws DatabaseTransactionException
     */
    final public function execute()
    {
        $success = false;
        if (!$this->getDB()->beginTransaction()) {
            throw new DatabaseTransactionException('Failed to begin the transaction');
        }
        try {
            call_user_func($this->tx, $this->getDB());
            $success = $this->getDB()->commit();
        } catch (Throwable $ex) {
            $this->getDB()->rollBack();
            throw $ex;
        } catch (Exception $ex) {
            $this->getDB()->rollBack();
            throw $ex;
        }
        return new DatabaseTransactionResult($success);
    }
}
