<?php

/**
 * @package toolkit
 */

/**
 * This DatabaseStatement specialization class allows creation of DESC statements.
 */
final class DatabaseDescribe extends DatabaseStatement
{
    /**
     * Creates a new DatabaseDescribe statement on table $table.
     *
     * @see Database::describe()
     * @param Database $db
     *  The underlying database connection
     * @param string $table
     *  The name of the table to act on.
     */
    public function __construct(Database $db, $table)
    {
        parent::__construct($db, 'DESC');
        $table = $this->replaceTablePrefix($table);
        $table = $this->asTickedString($table);
        $this->unsafeAppendSQLPart('table', $table);
    }

    /**
     * Appends a single field to describe
     *
     * @param string $field
     *  The field to describe
     * @return DatabaseDescribe
     *  The current instance
     */
    public function field($field)
    {
        General::ensureType([
            'field' => ['var' => $field, 'type' => 'string'],
        ]);
        $this->unsafeAppendSQLPart('field', $this->asTickedString($field));
        return $this;
    }

    /**
     * Appends one or multiple fields to describe
     *
     * @param array $fields
     *  The field to describe
     * @return DatabaseDescribe
     *  The current instance
     */
    public function fields(array $fields)
    {
        $this->unsafeAppendSQLPart('fields', $this->asTickedList($fields));
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
