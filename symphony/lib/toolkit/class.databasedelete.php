<?php

/**
 * @package toolkit
 */

/**
 * This DatabaseStatement specialization class allows creation of DELETE FROM statements.
 */
final class DatabaseDelete extends DatabaseStatement
{
    use DatabaseWhereDefinition;

    /**
     * Flag to prevent Delete query without a where clause.
     *
     * @var boolean
     */
    private $allowAll = false;

    /**
     * Creates a new DatabaseDelete statement on table $table.
     *
     * @see Database::delete()
     * @param Database $db
     *  The underlying database connection
     * @param string $table
     *  The name of the table to act on, including the tbl prefix which will be changed
     *  to the Database table prefix.
     */
    public function __construct(Database $db, $table)
    {
        parent::__construct($db, 'DELETE FROM');
        $table = $this->replaceTablePrefix($table);
        $table = $this->asTickedString($table);
        $this->unsafeAppendSQLPart('table', $table);
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
            'table',
            'where',
            'limit',
        ];
    }

    /**
     * Gets the proper separator string for the given $type SQL part type, when
     * generating a formatted SQL statement.
     *
     * @see DatabaseStatement::getSeparatorForPartType()
     * @param string $type
     *  The SQL part type.
     * @return string
     *  The string to use to separate the formatted SQL parts.
     */
    public function getSeparatorForPartType($type)
    {
        General::ensureType([
            'type' => ['var' => $type, 'type' => 'string'],
        ]);
        if (in_array($type, ['where', 'limit'])) {
            return self::FORMATTED_PART_DELIMITER;
        }
        return self::STATEMENTS_DELIMITER;
    }

    /**
     * Appends one or multiple WHERE clauses.
     *
     * @see DatabaseWhereDefinition::buildWhereClauseFromArray()
     * @param array $conditions
     *  The logical comparison conditions
     * @return DatabaseDelete
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
     * Appends one and only one LIMIT clause.
     * Can only be called once in the lifetime of the object.
     *
     * @throws DatabaseStatementException
     * @param int $limit
     *  The maximum number of records to return
     * @return DatabaseDelete
     *  The current instance
     */
    public function limit($limit)
    {
        if ($this->containsSQLParts('limit')) {
            throw new DatabaseStatementException('DatabaseDelete can not hold more than one limit clause');
        }
        $limit = General::intval($limit);
        if ($limit === -1) {
            throw new DatabaseStatementException("Invalid limit value: `$limit`");
        }
        $this->unsafeAppendSQLPart('limit', "LIMIT $limit");
        return $this;
    }

    /**
     * Allows the DELETE statement to be issued without a WHERE clause.
     *
     * @return DatabaseDelete
     *  The current instance
     */
    public function all()
    {
        $this->allowAll = true;
        return $this;
    }

    /**
     * Makes sure the DatabaseDelete contains a where clause if it is not allowed
     * to delete all records
     *
     * @see DatabaseStatement::finalize()
     * @throws DatabaseStatementException
     * @return DatabaseDelete
     *  The current instance
     */
    public function finalize()
    {
        if (!$this->allowAll && !$this->containsSQLParts('where')) {
            throw new DatabaseStatementException('This DatabaseDelete Statement is not allowed to delete all rows');
        }
        return $this;
    }
}
