<?php

/**
 * @package toolkit
 */

final class DatabaseInsert extends DatabaseStatement
{
    public function __construct(Database $db, $table)
    {
        parent::__construct($db, 'INSERT INTO');
        $table = $this->replaceTablePrefix($table);
        $table = $this->asTickedString($table);
        $this->unsafeAppendSQLPart('table', $table);
    }

    public function values(array $values)
    {
        $cols = '(' . $this->asTickedList(array_keys($values)) . ')';
        $this->unsafeAppendSQLPart('cols', $cols);
        $v = 'VALUES (' . $this->asPlaceholdersList($values) . ')';
        $this->unsafeAppendSQLPart('values', $v);
        $this->appendValues($values);
        return $this;
    }

    public function updateOnDuplicateKey()
    {
        $update = implode(self::LIST_DELIMITER, General::array_map(function ($key, $value) {
            $key = $this->asTickedString($key);
            return "$key = VALUES($key)";
        }, $this->getValues()));
        $this->unsafeAppendSQLPart('values', "ON DUPLICATE KEY UPDATE $update");
        return $this;
    }
}
