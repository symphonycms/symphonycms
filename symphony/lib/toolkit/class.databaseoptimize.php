<?php

/**
 * @package toolkit
 */

/**
 * This DatabaseStatement specialization class allows creation of OPTIMIZE TABLE statements.
 */
final class DatabaseOptimize extends DatabaseStatement
{
    /**
     * Creates a new DatabaseOptimize statement on table $table.
     *
     * @see Database::optimize()
     * @param Database $db
     *  The underlying database connection
     * @param string $table
     *  The name of the table to act on.
     */
    public function __construct(Database $db, $table)
    {
        parent::__construct($db, 'OPTIMIZE TABLE');
        $table = $this->replaceTablePrefix($table);
        $table = $this->asTickedString($table);
        $this->unsafeAppendSQLPart('table', $table);
    }
}
