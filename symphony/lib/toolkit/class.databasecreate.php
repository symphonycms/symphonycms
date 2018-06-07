<?php

/**
 * @package toolkit
 */

/**
 * This DatabaseStatement specialization class allows creation of CREATE TABLE statements.
 */
final class DatabaseCreate extends DatabaseStatement
{
    use DatabaseColumnDefinition;
    use DatabaseKeyDefinition;

    /**
     * The default engine option value for this statement
     *
     * @var string
     */
    private $engine;

    /**
     * Creates a new DatabaseCreate statement on table $table, with an optional
     * optimizer value.
     *
     * @see Database::create()
     * @param Database $db
     *  The underlying database connection
     * @param string $table
     *  The name of the table to act on, including the tbl prefix which will be changed
     *  to the Database table prefix.
     */
    public function __construct(Database $db, $table)
    {
        parent::__construct($db, 'CREATE TABLE');
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
            'optimizer',
            'table',
            '(',
            'fields',
            self::VALUES_DELIMITER,
            'keys',
            ')',
            'engine',
            'charset',
            'collate',
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
        if (in_array($type, ['fields', 'keys'])) {
            return self::FORMATTED_PART_DELIMITER;
        } elseif ($type === ')') {
            return self::FORMATTED_PART_EOL;
        }
        return self::STATEMENTS_DELIMITER;
    }

    /**
     * IF NOT EXISTS
     *
     * @return DatabaseCreate
     *  The current instance
     */
    public function ifNotExists()
    {
        return $this->unsafeAppendSQLPart('optimizer', 'IF NOT EXISTS');
    }

    /**
     * Sets the engine to use in this table.
     *
     * @param string $engine
     *  The engine to use
     * @return DatabaseCreate
     *  The current instance
     */
    public function engine($engine)
    {
        $this->engine = $engine;
        return $this;
    }

    /**
     * Appends one or multiple columns definitions clauses.
     *
     * @see DatabaseColumnDefinition::buildColumnDefinitionFromArray()
     * @param array $fields
     *  The field definitions to append
     * @return DatabaseCreate
     *  The current instance
     */
    public function fields(array $fields)
    {
        $prefix = $this->containsSQLParts('fields') ? self::LIST_DELIMITER : '';
        $fields = $prefix . implode(self::LIST_DELIMITER, General::array_map(function ($k, $field) {
            return $this->buildColumnDefinitionFromArray($k, $field);
        }, $fields));
        $this->unsafeAppendSQLPart('fields', $fields);
        return $this;
    }

    /**
     * Appends one or multiple key definitions clauses.
     *
     * @param array $keys
     *  The key definitions to append
     * @return DatabaseCreate
     *  The current instance
     */
    public function keys(array $keys)
    {
        $prefix = $this->containsSQLParts('keys') ? self::LIST_DELIMITER : '';
        $keys = $prefix . implode(self::LIST_DELIMITER, General::array_map(function ($key, $options) {
            return $this->buildKeyDefinitionFromArray($key, $options);
        }, $keys));
        $this->unsafeAppendSQLPart('keys', $keys);
        return $this;
    }

    /**
     * Appends the ENGINE, DEFAULT CHARSET and COLLATE options to the SQL statement,
     * if they have values.
     *
     * @see DatabaseStatement::finalize()
     * @return DatabaseCreate
     *  The current instance
     */
    public function finalize()
    {
        if ($this->engine) {
            $this->unsafeAppendSQLPart('engine', "ENGINE={$this->engine}");
        }
        if ($this->charset) {
            $this->unsafeAppendSQLPart('charset', "DEFAULT CHARSET={$this->charset}");
        }
        if ($this->collate) {
            $this->unsafeAppendSQLPart('collate', "COLLATE={$this->collate}");
        }
        return $this;
    }
}
