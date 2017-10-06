<?php

/**
 * @package toolkit
 */

/**
 * This DatabaseStatement specialization class allows creation of UPDATE statements.
 */
final class DatabaseUpdate extends DatabaseStatement
{
    /**
     * Creates a new DatabaseUpdate statement on table $table.
     *
     * @see Database::update()
     * @param Database $db
     *  The underlying database connection
     * @param string $table
     *  The name of the table to act on, including the tbl prefix which will be changed
     *  to the Database table prefix.
     */
    public function __construct(Database $db, $table)
    {
        parent::__construct($db, 'UPDATE');
        $table = $this->replaceTablePrefix($table);
        $table = $this->asTickedString($table);
        $this->unsafeAppendSQLPart('table', $table);
    }

    /**
     * Appends one and only one SET clause, with one or multiple values.
     *
     * @see DatabaseStatement::buildWhereClauseFromArray()
     * @param array $values
     *  The values to set. Array keys are used as column names and values are substituted
     *  by SQL parameters.
     * @return DatabaseUpdate
     *  The current instance
     */
    public function set(array $values)
    {
        $v = $this->buildWhereClauseFromArray([',' => $values]);
        $this->unsafeAppendSQLPart('values', "SET $v");
        $this->appendValues($values);
        return $this;
    }

    /**
     * Appends one or multiple WHERE clauses.
     *
     * @see DatabaseStatement::buildWhereClauseFromArray()
     * @param array $conditions
     *  The logical comparison conditions
     * @return DatabaseUpdate
     *  The current instance
     */
    public function where(array $conditions)
    {
        $where = $this->buildWhereClauseFromArray($conditions);
        $this->unsafeAppendSQLPart('where', "WHERE $where");
        return $this;
    }
}
