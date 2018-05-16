<?php

/**
 * @package toolkit
 */

/**
 * This DatabaseRename specialization class allows creation of RENAME TABLE statements.
 */
final class DatabaseRename extends DatabaseStatement
{

    /**
     * Creates a new DatabaseRename statement on table $table.
     *
     * @param string $table
     *  The table to rename.
     */
    public function __construct(Database $db, $table)
    {
        $table = $this->asTickedString($table);
        parent::__construct($db, "RENAME TABLE $table");
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
            'to',
        ];
    }

    /**
     * Appends a TO `table` clause.
     * Can only be called once in the lifetime of the object.
     *
     * @throws DatabaseStatementException
     * @param string $table
     *  The new name to give to the table.
     * @return DatabaseRename
     *  The current instance
     */
    public function to($table)
    {
        if ($this->containsSQLParts('to')) {
            throw new DatabaseStatementException('DatabaseRename can not hold more than one table clause');
        }
        $table = $this->replaceTablePrefix($table);
        $table = $this->asTickedString($table);
        $this->unsafeAppendSQLPart('to', "TO $table");
        return $this;
    }
}
