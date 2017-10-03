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
     * Set the value of the variable to this value.
     *
     * @param string $value
     *  The new value of the variable
     * @return DatabaseSet
     *  The current instance
     */
    public function value($value)
    {
        General::ensureType([
            'value' => ['var' => $value, 'type' => 'string'],
        ]);
        $this->unsafeAppendSQLPart('value', "= :value");
        $this->appendValues(['value' => $value]);
        return $this;
    }
}
