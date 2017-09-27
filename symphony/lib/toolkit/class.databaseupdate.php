<?php

/**
 * @package toolkit
 */

final class DatabaseUpdate extends DatabaseStatement
{
    public function __construct(Database $db, $table)
    {
        parent::__construct($db, 'UPDATE');
        $table = $this->replaceTablePrefix($table);
        $table = $this->asTickedString($table);
        $this->unsafeAppendSQLPart('table', $table);
    }

    public function set(array $values)
    {
        $v = $this->buildWhereClauseFromArray([',' => $values]);
        $this->unsafeAppendSQLPart('values', "SET $v");
        $this->appendValues($values);
        return $this;
    }

    public function where(array $conditions)
    {
        $where = $this->buildWhereClauseFromArray($conditions);
        $this->unsafeAppendSQLPart('where', "WHERE $where");
        return $this;
    }
}
