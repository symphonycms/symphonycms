<?php

/**
 * @package toolkit
 */

/**
 * This class hold the data created by the execution of a DatabaseTransactionResult class.
 */
class DatabaseTransactionResult
{
    /**
     * Flag to indicate if the execution was successful.
     * @var boolean
     */
    private $success;

    /**
     * Creates a new DatabaseTransactionResult object, containing its $success parameter.
     *
     * @param bool $success
     *  If the DatabaseTransaction creating this instance succeeded or not.
     */
    public function __construct($success)
    {
        General::ensureType([
            'success' => ['var' => $success, 'type' => 'bool'],
        ]);
        $this->success = $success;
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
}
