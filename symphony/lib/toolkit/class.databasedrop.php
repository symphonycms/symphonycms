<?php

/**
 * @package toolkit
 */

/**
 * This DatabaseStatement specialization class allows creation of DROP TABLE statements.
 */
final class DatabaseDrop extends DatabaseStatement
{
    /**
     * Creates a new DatabaseDrop statement on table $table, with an optional
     * optimizer value.
     *
     * @see Database::drop()
     * @see Database::dropIfExists()
     * @param Database $db
     *  The underlying database connection
     * @param string $table
     *  The name of the table to act on, including the tbl prefix which will be changed
     *  to the Database table prefix.
     * @param string $optimizer
     *  An optional optimizer hint.
     *  Currently, only IF EXISTS is supported
     */
    public function __construct(Database $db, $table, $optimizer = null)
    {
        parent::__construct($db, 'DROP TABLE');
        if ($optimizer === 'IF EXISTS') {
            $this->unsafeAppendSQLPart('optimizer', 'IF EXISTS');
        }
        $table = $this->replaceTablePrefix($table);
        $table = $this->asTickedString($table);
        $this->unsafeAppendSQLPart('table', $table);
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
            'optimizer',
            'table',
        ];
    }

    /**
     * Appends the name of the table to drop.
     *
     * @param string $table
     * @return DatabaseDrop
     *  The current instance
     */
    public function table($table)
    {
        $table = $this->replaceTablePrefix($table);
        $table = $this->asTickedString($table);
        $this->unsafeAppendSQLPart('table', ", $table");
        return $this;
    }
}
