<?php

/**
 * @package toolkit
 */

/**
 * This DatabaseStatement specialization class allows creation of INSERT INTO statements.
 */
final class DatabaseInsert extends DatabaseStatement
{
    /**
     * Creates a new DatabaseInsert statement on table $table.
     *
     * @see Database::insert()
     * @param Database $db
     *  The underlying database connection
     * @param string $table
     *  The name of the table to act on.
     */
    public function __construct(Database $db, $table)
    {
        parent::__construct($db, 'INSERT INTO');
        $table = $this->replaceTablePrefix($table);
        $table = $this->asTickedString($table);
        $this->unsafeAppendSQLPart('table', $table);
    }

    /**
     * Appends one or multiple values into the insert statements.
     *
     * @param array $values
     *  The values to append. Keys are columns names and values will be added
     *  to the value array and be substituted with SQL parameters.
     * @return DatabaseInsert
     *  The current instance
     */
    public function values(array $values)
    {
        $cols = '(' . $this->asTickedList(array_keys($values)) . ')';
        $this->unsafeAppendSQLPart('cols', $cols);
        $v = 'VALUES (' . $this->asPlaceholdersList($values) . ')';
        $this->unsafeAppendSQLPart('values', $v);
        $this->appendValues($values);
        return $this;
    }

    /**
     * Creates a ON DUPLICATE KEY UPDATE statement, based on values already appended.
     *
     * @return DatabaseInsert
     *  The current instance
     */
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
