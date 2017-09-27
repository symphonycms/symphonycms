<?php

/**
 * @package toolkit
 */

final class DatabaseShow extends DatabaseStatement
{
    public function __construct(Database $db)
    {
        parent::__construct($db, 'SHOW TABLES');
    }

    public function like($value)
    {
        $this->usePlaceholders();
        $this->appendValues([$value]);
        $this->unsafeAppendSQLPart('like', "LIKE ?");
        return $this;
    }

    public function where(array $conditions)
    {
        $where = $this->buildWhereClauseFromArray($conditions);
        $this->unsafeAppendSQLPart('where', "WHERE $where");
        return $this;
    }

    public function results($result, PDOStatement $stm)
    {
        General::ensureType([
            'result' => ['var' => $result, 'type' => 'boolean'],
        ]);
        return new DatabaseQueryResult($result, $stm);
    }
}
