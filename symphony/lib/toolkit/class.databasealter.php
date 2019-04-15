<?php

/**
 * @package toolkit
 */

/**
 * This DatabaseStatement specialization class allows creation of ALTER TABLE statements.
 */
final class DatabaseAlter extends DatabaseStatement
{
    use DatabaseColumnDefinition;
    use DatabaseKeyDefinition;

    /**
     * Creates a new DatabaseAlter statement on table $table, with an optional
     * optimizer value.
     *
     * @see Database::alter()
     * @param Database $db
     *  The underlying database connection
     * @param string $table
     *  The name of the table to act on, including the tbl prefix which will be changed
     *  to the Database table prefix.
     */
    public function __construct(Database $db, $table)
    {
        parent::__construct($db, 'ALTER TABLE');
        $table = $this->replaceTablePrefix($table);
        $table = $this->asTickedString($table);
        $this->unsafeAppendSQLPart('table', $table);
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
            'table',
            'convert',
            'engine',
            [
                'add columns',
                'first',
                'after',
                'drop columns',
                'change columns',
                'modify columns',
                'add key',
                'drop key',
                'add index',
                'drop index',
                'add primary key',
                'drop primary key',
            ],
            'default',
        ];
    }

    /**
     * Gets the proper separator string for the given $type SQL part type, when
     * generating a formatted SQL statement.
     *
     * @see DatabaseStatement::getSeparatorForPartType()
     * @param string $type
     *  The SQL part type.
     * @return string
     *  The string to use to separate the formatted SQL parts.
     */
    public function getSeparatorForPartType($type)
    {
        General::ensureType([
            'type' => ['var' => $type, 'type' => 'string'],
        ]);
        if (!in_array($type, ['statement', 'table'])) {
            return self::FORMATTED_PART_DELIMITER;
        }
        return self::STATEMENTS_DELIMITER;
    }

    /**
     * Checks if a 'add', 'drop' or 'change' statement was already add to the statement.
     *
     * @uses getStatementStructure()
     * @return bool
     */
    public function containsAddDropOrChange()
    {
        return $this->containsSQLParts($this->getStatementStructure()[4]);
    }

    /**
     * Appends a ENGINE clause.
     *
     * @param string $engine
     * @return DatabaseAlter
     *  The current instance
     */
    public function engine($engine)
    {
        General::ensureType([
            'engine' => ['var' => $engine, 'type' => 'string'],
        ]);
        $this->unsafeAppendSQLPart('engine', "ENGINE = :engine");
        $this->appendValues(['engine' => $engine]);
        return $this;
    }

    /**
     * Appends the FIRST keyword
     *
     * @return DatabaseAlter
     *  The current instance
     */
    public function first()
    {
        $this->unsafeAppendSQLPart('first', "FIRST");
        return $this;
    }

    /**
     * Appends a AFTER `column` clause
     *
     * @param string|array $column
     *  The column to use with the AFTER keyword
     * @return DatabaseAlter
     *  The current instance
     */
    public function after($column)
    {
        General::ensureType([
            'column' => ['var' => $column, 'type' => 'string'],
        ]);
        $column = $this->asTickedString($column);
        $this->unsafeAppendSQLPart('after', "AFTER $column");
        return $this;
    }

    /**
     * Appends a default character set and collate at the end of the ALTER statement.
     * Uses previously set collate and character set.
     *
     * @see charset()
     * @see collate()
     * @return DatabaseAlter
     *  The current instance
     */
    public function defaults()
    {
        if (!$this->charset && !$this->collate) {
            throw new DatabaseStatementException('Cannot use defaults() without values');
        }
        if ($this->charset) {
            $this->unsafeAppendSQLPart('default', "DEFAULT CHARACTER SET $this->charset");
        }
        if ($this->collate) {
            $this->unsafeAppendSQLPart('default', "DEFAULT COLLATE $this->collate");
        }
        return $this;
    }

    /**
     * Appends a convert to clause in the ALTER statement.
     * Uses previously set collate and character set.
     *
     * @see charset()
     * @see collate()
     * @return DatabaseAlter
     *  The current instance
     */
    public function convertTo()
    {
        if (!$this->charset && !$this->collate) {
            throw new DatabaseStatementException('Cannot use convertTo() without values');
        }
        $sql = 'CONVERT TO';
        if ($this->charset) {
            $sql .= " CHARACTER SET $this->charset";
        }
        if ($this->collate) {
            $sql .= " COLLATE $this->collate";
        }
        $this->unsafeAppendSQLPart('convert', $sql);
        return $this;
    }

    /**
     * Appends multiple ADD COLUMN `column` clause.
     *
     * @see DatabaseColumnDefinition::buildColumnDefinitionFromArray()
     * @param string|array $column
     *  The column to use with the AFTER keyword
     * @return DatabaseAlter
     *  The current instance
     */
    public function add(array $columns)
    {
        $columns = implode(self::LIST_DELIMITER, General::array_map(function ($k, $column) {
            $column = $this->buildColumnDefinitionFromArray($k, $column);
            return "ADD COLUMN $column";
        }, $columns));
        if ($this->containsAddDropOrChange()) {
            $columns  = self::LIST_DELIMITER .  $columns;
        }
        $this->unsafeAppendSQLPart('add columns', $columns);
        return $this;
    }

    /**
     * Appends one or multiple DROP COLUMN `column` clause.
     *
     * @param array|string $columns
     *  Array of columns names
     * @return DatabaseAlter
     *  The current instance
     */
    public function drop($columns)
    {
        if (!is_array($columns)) {
            $columns = [$columns];
        }
        $columns = implode(self::LIST_DELIMITER, array_map(function ($column) {
            $column = $this->asTickedString($column);
            return "DROP COLUMN $column";
        }, $columns));
        if ($this->containsAddDropOrChange()) {
            $columns  = self::LIST_DELIMITER . $columns;
        }
        $this->unsafeAppendSQLPart('drop columns', $columns);
        return $this;
    }

    /**
     * Appends a CHANGE COLUMN `old_column` `new_column` clause.
     *
     * @see DatabaseColumnDefinition::buildColumnDefinitionFromArray()
     * @param array|string $old_columns
     *  The name of the old columns to change. Their new version must be specified at the same
     *  index in $new_columns
     * @param array $new_columns
     *  The new columns definitions
     * @return DatabaseAlter
     *  The current instance
     */
    public function change($old_columns, array $new_columns)
    {
        if (!is_array($old_columns)) {
            $old_columns = [$old_columns];
        }
        $new_columns_keys = array_keys($new_columns);
        $columns = implode(self::LIST_DELIMITER, General::array_map(function ($index, $column) use ($new_columns_keys, $new_columns) {
            $old_column = $this->asTickedString($column);
            $new_column = $this->buildColumnDefinitionFromArray(
                $new_columns_keys[$index],
                $new_columns[$new_columns_keys[$index]]
            );
            return "CHANGE COLUMN $old_column $new_column";
        }, $old_columns));
        if ($this->containsAddDropOrChange()) {
            $columns  = self::LIST_DELIMITER . $columns;
        }
        $this->unsafeAppendSQLPart('change columns', $columns);
        return $this;
    }

    /**
     * Appends a MODIFY COLUMN `column` clause.
     *
     * @see DatabaseColumnDefinition::buildColumnDefinitionFromArray()
     * @param array $columns
     *  The new columns definitions
     * @return DatabaseAlter
     *  The current instance
     */
    public function modify(array $columns)
    {
        $columns = implode(self::LIST_DELIMITER, General::array_map(function ($k, $column) {
            $column = $this->buildColumnDefinitionFromArray($k, $column);
            return "MODIFY COLUMN $column";
        }, $columns));
        if ($this->containsAddDropOrChange()) {
            $columns  = self::LIST_DELIMITER . $columns;
        }
        $this->unsafeAppendSQLPart('modify columns', $columns);
        return $this;
    }

    /**
     * Appends one or multiple ADD KEY `key` clause.
     *
     * @see DatabaseKeyDefinition::buildKeyDefinitionFromArray()
     * @param array|string $keys
     *  The key definitions to append
     * @return DatabaseAlter
     *  The current instance
     */
    public function addKey($keys)
    {
        if (!is_array($keys)) {
            $keys = [$keys => 'key'];
        }
        $keys = implode(self::LIST_DELIMITER, General::array_map(function ($k, $column) {
            $key = $this->buildKeyDefinitionFromArray($k, $column);
            return "ADD $key";
        }, $keys));
        if ($this->containsAddDropOrChange()) {
            $keys  = self::LIST_DELIMITER . $keys;
        }
        $this->unsafeAppendSQLPart('add key', $keys);
        return $this;
    }

    /**
     * Appends one or multiple DROP KEY `key` clause.
     *
     * @param array|string $keys
     *  The key definitions to drop
     * @return DatabaseAlter
     *  The current instance
     */
    public function dropKey($keys)
    {
        if (!is_array($keys)) {
            $keys = [$keys];
        }
        $keys = implode(self::LIST_DELIMITER, array_map(function ($key) {
            $key = $this->asTickedString($key);
            return "DROP KEY $key";
        }, $keys));
        if ($this->containsAddDropOrChange()) {
            $keys  = self::LIST_DELIMITER . $keys;
        }
        $this->unsafeAppendSQLPart('drop key', $keys);
        return $this;
    }

    /**
     * Appends one or multiple ADD INDEX `index` clause.
     *
     * @see DatabaseKeyDefinition::buildKeyDefinitionFromArray()
     * @param array|string $keys
     *  The index definitions to append
     * @return DatabaseAlter
     *  The current instance
     */
    public function addIndex($keys)
    {
        if (!is_array($keys)) {
            $keys = [$keys => 'index'];
        }
        $keys = implode(self::LIST_DELIMITER, General::array_map(function ($k, $column) {
            $key = $this->buildKeyDefinitionFromArray($k, $column);
            return "ADD $key";
        }, $keys));
        if ($this->containsAddDropOrChange()) {
            $keys  = self::LIST_DELIMITER . $keys;
        }
        $this->unsafeAppendSQLPart('add index', $keys);
        return $this;
    }

    /**
     * Appends one or multiple DROP INDEX `index` clause.
     *
     * @param array|string $keys
     *  The index definitions to drop
     * @return DatabaseAlter
     *  The current instance
     */
    public function dropIndex($keys)
    {
        if (!is_array($keys)) {
            $keys = [$keys];
        }
        $keys = implode(self::LIST_DELIMITER, array_map(function ($key) {
            $key = $this->asTickedString($key);
            return "DROP INDEX $key";
        }, $keys));
        if ($this->containsAddDropOrChange()) {
            $keys  = self::LIST_DELIMITER . $keys;
        }
        $this->unsafeAppendSQLPart('drop index', $keys);
        return $this;
    }

    /**
     * Appends one and only one ADD PRIMARY KEY `key` clause.
     *
     * @see DatabaseKeyDefinition::buildKeyDefinitionFromArray()
     * @param array|string $keys
     *  One or more columns inclued in the primary key
     * @return DatabaseAlter
     *  The current instance
     */
    public function addPrimaryKey($keys)
    {
        if (!is_array($keys)) {
            $keys = [$keys => 'primary'];
        }
        $keys = implode(self::LIST_DELIMITER, General::array_map(function ($k, $column) {
            $key = $this->buildKeyDefinitionFromArray($k, $column);
            return "ADD $key";
        }, $keys));
        if ($this->containsAddDropOrChange()) {
            $keys  = self::LIST_DELIMITER . $keys;
        }
        $this->unsafeAppendSQLPart('add primary key', $keys);
        return $this;
    }

    /**
     * Appends one and only one DROP PRIMARY KEY clause.
     *
     * @return DatabaseAlter
     *  The current instance
     */
    public function dropPrimaryKey()
    {
        $keys = 'DROP PRIMARY KEY';
        if ($this->containsAddDropOrChange()) {
            $keys  = self::LIST_DELIMITER . $keys;
        }
        $this->unsafeAppendSQLPart('drop primary key', $keys);
        return $this;
    }
}
