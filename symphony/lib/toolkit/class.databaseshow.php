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
    use DatabaseCacheableExecutionDefinition;

    /**
     * Creates a new DatabaseSet statement on table $table.
     *
     * @see Database::show()
     * @param Database $db
     *  The underlying database connection.
     * @param string $show
     *  Configure what to show, either TABLES, COLUMNS or INDEX. Defaults to TABLES.
     * @param string $modifier
     *  Configure the statement to output wither FULL or EXTENDED information.
     */
    public function __construct(Database $db, $show = 'TABLES', $modifier = null)
    {
        if ($show !== 'TABLES' && $show !== 'COLUMNS' && $show !== 'INDEX') {
            throw new DatabaseStatementException('Can only show TABLES, COLUMNS or INDEX');
        }
        if ($modifier) {
            if ($modifier !== 'FULL' && $modifier !== 'EXTENDED') {
                throw new DatabaseStatementException('Can modify with FULL or EXTENDED');
            } else {
                $show = "$modifier $show";
            }
        }
        parent::__construct($db, "SHOW $show");
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
            'like',
            'where',
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
        if (in_array($type, ['like', 'where'])) {
            return self::FORMATTED_PART_DELIMITER;
        }
        return self::STATEMENTS_DELIMITER;
    }

    /**
     * Appends a FROM `table` clause.
     * Can only be called once in the lifetime of the object.
     *
     * @see alias()
     * @throws DatabaseStatementException
     * @param string $table
     *  The name of the table to act on, including the tbl prefix which will be changed
     *  to the Database table prefix.
     * @return DatabaseShow
     *  The current instance
     */
    public function from($table)
    {
        if ($this->containsSQLParts('table')) {
            throw new DatabaseStatementException('DatabaseShow can not hold more than one table clause');
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
     * Uses the named parameter 'like' to hold the value when not using placeholders.
     *
     * @see replaceTablePrefix()
     * @throws DatabaseStatementException
     * @param string $value
     *  The LIKE search pattern to look for
     * @return DatabaseShow
     *  The current instance
     */
    public function like($value)
    {
        if ($this->containsSQLParts('like')) {
            throw new DatabaseStatementException('DatabaseShow can not hold more than one like clause');
        }
        $value = $this->replaceTablePrefix($value);
        if ($this->isUsingPlaceholders()) {
            $this->appendValues([$value]);
            $this->unsafeAppendSQLPart('like', 'LIKE ?');
        } else {
            $this->appendValues(['like' => $value]);
            $this->unsafeAppendSQLPart('like', 'LIKE :like');
        }
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
     * @param bool $success
     *  If the DatabaseStatement creating this instance succeeded or not.
     * @param PDOStatement $stm
     *  The PDOStatement created by the execution of the DatabaseStatement.
     * @return DatabaseTabularResult
     *  The wrapped result
     */
    public function results($success, PDOStatement $stm)
    {
        General::ensureType([
            'success' => ['var' => $success, 'type' => 'bool'],
        ]);
        return new DatabaseTabularResult($success, $stm);
    }
}
