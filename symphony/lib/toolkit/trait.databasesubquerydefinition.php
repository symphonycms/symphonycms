<?php

/**
 * @package toolkit
 */

/**
 * @since Symphony 3.1.0
 */
trait DatabaseSubQueryDefinition
{
    /**
     * Internal sub query counter
     * @var integer
     */
    private $selectQueryCount = 0;

    /**
     * Factory method that creates a new `SELECT ...` statement to be
     * safely used inside the current instance of self.
     *
     * @param array $values
     *  The columns to select. By default, it's `*` if no projection gets added.
     * @return self
     */
    public function select(array $values = [])
    {
        $this->selectQueryCount++;
        return new DatabaseSubQuery($this->getDB(), $this->selectQueryCount, $values);
    }
}
