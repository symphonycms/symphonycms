<?php

/**
 * @package toolkit
 */

/**
 * This DatabaseStatement specialization class allows creation of SHOW TABLES/COLUMNS statements.
 */
final class DatabaseShow extends DatabaseStatement
{
    use DatabaseWhereDefinition;

    /**
     * Creates a new DatabaseSet statement on table $table.
     *
     * @see Database::show()
     * @param Database $db
     *  The underlying database connection.
     * @param string $show
     *  Configure what to show, either TABLES or COLUMNS. Defaults to TABLES.
     */
    public function __construct(Database $db, $show = 'TABLES')
    {
        if ($show !== 'COLUMNS' && $show !== 'TABLES') {
            throw new DatabaseSatementException('Can only show TABLES or COLUMNS');
        }
        parent::__construct($db, "SHOW $show");
    }

    /**
     * Returns the parts statement structure for this specialized statement.
     *
     * @return array
     */
    protected function getStatementStructure()
    {
        return [
            'statement',
            'table',
            'like',
            'where',
        ];
    }

    /**
     * Appends a FROM `table` clause.
     * Can only be called once in the lifetime of the object.
     *
     * @see alias()
     * @throws DatabaseSatementException
     * @param string $table
     *  The name of the table to act on, including the tbl prefix which will be changed
     *  to the Database table prefix.
     * @return DatabaseShow
     *  The current instance
     */
    public function from($table)
    {
        if ($this->containsSQLParts('table')) {
            throw new DatabaseSatementException('DatabaseShow can not hold more than one table clause');
        }
        $table = $this->replaceTablePrefix($table);
        $table = $this->asTickedString($table);
        $this->unsafeAppendSQLPart('table', "FROM $table");
        return $this;
    }

    /**
     * Appends a LIKE clause.
     * This clause will likely be a table name, so it calls replaceTablePrefix().
     * Can only be called once in the lifetime of the object.
     *
     * @see replaceTablePrefix()
     * @throws DatabaseSatementException
     * @param string $value
     *  The LIKE search pattern to look for
     * @return DatabaseShow
     *  The current instance
     */
    public function like($value)
    {
        if ($this->containsSQLParts('like')) {
            throw new DatabaseSatementException('DatabaseShow can not hold more than one like clause');
        }
        $value = $this->replaceTablePrefix($value);
        $this->usePlaceholders();
        $this->appendValues([$value]);
        $this->unsafeAppendSQLPart('like', "LIKE ?");
        return $this;
    }

    /**
     * Appends one or multiple WHERE clauses.
     * Calling this method multiple times will join the WHERE clauses with a AND.
     *
     * @see DatabaseWhereDefinition::buildWhereClauseFromArray()
     * @param array $conditions
     *  The logical comparison conditions
     * @return DatabaseShow
     *  The current instance
     */
    public function where(array $conditions)
    {
        $op = $this->containsSQLParts('where') ? 'AND' : 'WHERE';
        $where = $this->buildWhereClauseFromArray($conditions);
        $this->unsafeAppendSQLPart('where', "$op $where");
        return $this;
    }

    /**
     * Creates a specialized version of DatabaseStatementResult to hold
     * result from the current statement.
     *
     * @see DatabaseStatement::execute()
     * @param bool $result
     *  The success of the execution
     * @param PDOStatement $st
     *  The resulting PDOStatement returned by the execution
     * @return DatabaseQueryResult
     *  The wrapped result
     */
    public function results($result, PDOStatement $stm)
    {
        General::ensureType([
            'result' => ['var' => $result, 'type' => 'bool'],
        ]);
        return new DatabaseQueryResult($result, $stm);
    }
}
