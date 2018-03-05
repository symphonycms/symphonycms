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
     *  The name of the table to act on, including the tbl prefix which will be changed
     *  to the Database table prefix.
     */
    public function __construct(Database $db, $table)
    {
        parent::__construct($db, 'INSERT INTO');
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
            'cols',
            'values',
            'on duplicate',
        ];
    }

    /**
     * Appends one or multiple values into the insert statements.
     * Can only be called once in the lifetime of the object.
     *
     * @throws DatabaseStatementException
     * @param array $values
     *  The values to append. Keys are columns names and values will be added
     *  to the value array and be substituted with SQL parameters.
     * @return DatabaseInsert
     *  The current instance
     */
    public function values(array $values)
    {
        if ($this->containsSQLParts('values')) {
            throw new DatabaseStatementException('DatabaseInsert can not hold more than one values clause');
        }
        $cols = '(' . $this->asTickedList(array_keys($values)) . ')';
        $this->unsafeAppendSQLPart('cols', $cols);
        $v = 'VALUES (' . $this->asPlaceholdersList($values) . ')';
        $this->unsafeAppendSQLPart('values', $v);
        $this->appendValues($values);
        return $this;
    }

    /**
     * Creates a ON DUPLICATE KEY UPDATE statement, based on values already appended.
     * Can only be called once in the lifetime of the object.
     *
     * @throws DatabaseStatementException
     * @return DatabaseInsert
     *  The current instance
     */
    public function updateOnDuplicateKey()
    {
        if ($this->containsSQLParts('on duplicate')) {
            throw new DatabaseStatementException('DatabaseInsert can not hold more than one on duplicate clause');
        }
        $update = implode(self::LIST_DELIMITER, General::array_map(function ($key, $value) {
            $key = $this->asTickedString($key);
            return "$key = VALUES($key)";
        }, $this->getValues()));
        $this->unsafeAppendSQLPart('on duplicate', "ON DUPLICATE KEY UPDATE $update");
        return $this;
    }
}
