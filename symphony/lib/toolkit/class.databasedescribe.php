<?php

/**
 * @package toolkit
 */

/**
 * This DatabaseStatement specialization class allows creation of DESC statements.
 */
final class DatabaseDescribe extends DatabaseStatement
{
    use DatabaseCacheableExecutionDefinition;

    /**
     * Creates a new DatabaseDescribe statement on table $table.
     *
     * @see Database::describe()
     * @param Database $db
     *  The underlying database connection
     * @param string $table
     *  The name of the table to act on, including the tbl prefix which will be changed
     *  to the Database table prefix.
     */
    public function __construct(Database $db, $table)
    {
        parent::__construct($db, 'DESC');
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
            'field',
        ];
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
        $prefix = $this->containsSQLParts('field') ? self::LIST_DELIMITER : '';
        $this->unsafeAppendSQLPart('field', $prefix . $this->asTickedString($field));
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
        $this->unsafeAppendSQLPart('field', $this->asTickedList($fields));
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
