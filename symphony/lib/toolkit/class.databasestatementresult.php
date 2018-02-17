<?php

/**
 * @package toolkit
 */

/**
 * This class hold the data created by the execution of a DatabaseStatement class.
 * If offers methods to be able to use those data easily with the chaining API.
 * Specialized DatabaseStatement should create their own specialized DatabaseStatementResult class.
 */
class DatabaseStatementResult
{
    /**
     * Flag to indicate if the execution was successful.
     *
     * @var boolean
     */
    private $success;

    /**
     * The PDOStatement result of the execution.
     *
     * @var PDOStatement
     */
    private $stm;

    /**
     * Creates a new DatabaseStatementResult object, containing its $success parameter
     * and the resulting $stm statement.
     *
     * @param bool $success
     *  If the DatabaseStatement creating this instance succeeded or not.
     * @param PDOStatement $stm
     *  The PDOStatement created by the execution of the DatabaseStatement.
     */
    public function __construct($success, PDOStatement $stm)
    {
        General::ensureType([
            'success' => ['var' => $success, 'type' => 'bool'],
        ]);
        $this->success = $success;
        $this->stm = $stm;
    }

    /**
     * Closes the statement's cursor.
     */
    public function __destruct()
    {
        if ($this->stm) {
            $this->stm->closeCursor();
        }
        unset($this->stm);
    }

    /**
     * Getter for the success of the execution
     *
     * @return boolean
     */
    public function success()
    {
        return $this->success;
    }

    /**
     * Getter for the statement of the execution
     *
     * @return PDOStatement
     */
    public function statement()
    {
        return $this->stm;
    }
}
