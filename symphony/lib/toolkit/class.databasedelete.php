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
     * @return array
     */
    protected function getStatementStructure()
    {
        return [
            'statement',
            'table',
            'where',
        ];
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
        $where = $this->buildWhereClauseFromArray($conditions);
        $this->unsafeAppendSQLPart('where', "WHERE $where");
        return $this;
    }

    /**
     * @internal This method is not meant to be called directly. Use execute().
     * This method validates all the SQL parts currently stored.
     * It makes sure that there is only one part of each types.
     *
     * @see DatabaseStatement::validate()
     * @return DatabaseDelete
     * @throws DatabaseException
     */
    public function validate()
    {
        parent::validate();
        if (count($this->getSQLParts('table')) !== 1) {
            throw new DatabaseException('DatabaseDelete can only hold one table part');
        }
        if (count($this->getSQLParts('where')) > 1) {
            throw new DatabaseException('DatabaseDelete can only hold one or zero where part');
        }
        return $this;
    }
}
