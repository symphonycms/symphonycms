<?php

/**
 * @package toolkit
 */

/**
 * This DatabaseStatement specialization class allows creation of SET statements.
 */
final class DatabaseSet extends DatabaseStatement
{
    /**
     * Creates a new DatabaseSet statement on table $table.
     *
     * @see Database::set()
     * @param Database $db
     *  The underlying database connection
     * @param string $variable
     *  The name of the variable to act on.
     */
    public function __construct(Database $db, $variable)
    {
        General::ensureType([
            'variable' => ['var' => $variable, 'type' => 'string'],
        ]);
        parent::__construct($db, 'SET');
        // TODO: Escape better
        $this->unsafeAppendSQLPart('variable', $variable);
    }

    /**
     * Returns the parts statement structure for this specialized statement.
     *
     * @see DatabaseStatement::getStatementStructure()
     * @return array
     */
    protected function getStatementStructure()
    {
        return [
            'statement',
            'variable',
            'value',
        ];
    }

    /**
     * Set the value of the variable to this value.
     * Can only be called once in the lifetime of the object.
     *
     * @throws DatabaseStatementException
     * @param string|integer $value
     *  The new value of the variable
     * @return DatabaseSet
     *  The current instance
     */
    public function value($value)
    {
        if ($this->containsSQLParts('value')) {
            throw new DatabaseStatementException('DatabaseSet can not hold more than one value clause');
        }
        $this->unsafeAppendSQLPart('value', "= :value");
        $this->appendValues(['value' => $value]);
        return $this;
    }
}
