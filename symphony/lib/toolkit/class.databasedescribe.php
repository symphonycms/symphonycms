<?php

/**
 * @package toolkit
 */

final class DatabaseDescribe extends DatabaseStatement
{
    public function __construct(Database $db, $table)
    {
        parent::__construct($db, 'DESC');
        $table = $this->replaceTablePrefix($table);
        $table = $this->asTickedString($table);
        $this->unsafeAppendSQLPart('table', $table);
    }

    public function field($field)
    {
        General::ensureType([
            'field' => ['var' => $field, 'type' => 'string'],
        ]);
        $this->unsafeAppendSQLPart('field', $this->asTickedString($field));
        return $this;
    }

    public function fields(array $fields)
    {
        $this->unsafeAppendSQLPart('fields', $this->asTickedList($fields));
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
