<?php

/**
 * @package toolkit
 */

final class DatabaseDelete extends DatabaseStatement
{
    public function __construct(Database $db, $table)
    {
        parent::__construct($db, 'DELETE FROM');
        $table = $this->replaceTablePrefix($table);
        $table = $this->asTickedString($table);
        $this->unsafeAppendSQLPart('table', $table);
    }

    public function where(array $conditions)
    {
        $where = $this->buildWhereClauseFromArray($conditions);
        $this->unsafeAppendSQLPart('where', "WHERE $where");
        return $this;
    }
}
