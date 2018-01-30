<?php

/**
 * @package toolkit
 */

/**
 * This DatabaseStatement specialization class allows creation of SHOW TABLES statements.
 */
final class DatabaseShow extends DatabaseStatement
{
    use DatabaseWhereDefinition;

    /**
     * Creates a new DatabaseSet statement on table $table.
     *
     * @see Database::show()
     * @param Database $db
     *  The underlying database connection
     */
    public function __construct(Database $db)
    {
        parent::__construct($db, 'SHOW TABLES');
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
            'like',
            'where',
        ];
    }

    /**
     * Appends a LIKE clause.
     * This clause will likely be a table name, so it calls replaceTablePrefix().
     *
     * @see replaceTablePrefix()
     * @param string $value
     *  The LIKE search pattern to look for
     * @return void
     */
    public function like($value)
    {
        $value = $this->replaceTablePrefix($value);
        $this->usePlaceholders();
        $this->appendValues([$value]);
        $this->unsafeAppendSQLPart('like', "LIKE ?");
        return $this;
    }

    /**
     * Appends one or multiple WHERE clauses.
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
