<?php

/**
 * @package toolkit
 */

class DatabaseStatementResult
{
    private $success;
    private $stm;

    public function __construct($success, PDOStatement $stm = null)
    {
        General::ensureType([
            'success' => ['var' => $success, 'type' => 'boolean'],
        ]);
        $this->success = $success;
        $this->stm = $stm;
    }

    public function __destruct()
    {
        if ($this->stm) {
            $this->stm->closeCursor();
        }
        unset($this->stm);
    }

    public function success()
    {
        return $this->success;
    }

    public function statement()
    {
        return $this->stm;
    }
}
