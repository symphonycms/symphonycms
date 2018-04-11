<?php

/**
 * @package toolkit
 */

/**
 * This DatabaseStatement specialization class allows creation of TRUNCATE TABLE statements.
 */
final class DatabaseTruncate extends DatabaseStatement
{
    /**
     * Creates a new DatabaseTruncate statement on table $table.
     *
     * @see Database::truncate()
     * @param Database $db
     *  The underlying database connection
     * @param string $table
     *  The name of the table to act on, including the tbl prefix which will be changed
     *  to the Database table prefix.
     */
    public function __construct(Database $db, $table)
    {
        parent::__construct($db, 'TRUNCATE TABLE');
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
        ];
    }
}
