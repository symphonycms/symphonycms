<?php

/**
 * @package toolkit
 */

/**
 * This DatabaseStatement specialization class allows creation of DELETE FROM statements.
 */
final class DatabaseDelete extends DatabaseStatement
{
    /**
     * Creates a new DatabaseDelete statement on table $table.
     *
     * @see Database::delete()
     * @param Database $db
     *  The underlying database connection
     * @param string $table
     *  The name of the table to act on.
     */
    public function __construct(Database $db, $table)
    {
        parent::__construct($db, 'DELETE FROM');
        $table = $this->replaceTablePrefix($table);
        $table = $this->asTickedString($table);
        $this->unsafeAppendSQLPart('table', $table);
    }

    /**
     * Appends one or multiple WHERE clauses.
     *
     * @see DatabaseStatement::buildWhereClauseFromArray()
     * @param array $conditions
     *  The logical comparison conditions
     * @return DatabaseDelete
     *  The current instance
     */
    public function where(array $conditions)
    {
        $where = $this->buildWhereClauseFromArray($conditions);
        $this->unsafeAppendSQLPart('where', "WHERE $where");
        return $this;
    }
}
