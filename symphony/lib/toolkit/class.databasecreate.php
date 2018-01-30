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
     * The default charset option value for this statement
     *
     * @var string
     */
    private $charset;

    /**
     * The default collate option value for this statement
     *
     * @var string
     */
    private $collate;

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
     * @see Database::createIfNotExists()
     * @param Database $db
     *  The underlying database connection
     * @param string $table
     *  The name of the table to act on, including the tbl prefix which will be changed
     *  to the Database table prefix.
     * @param string $optimizer
     *  An optional optimizer hint.
     *  Currently, only IF NOT EXISTS is supported
     */
    public function __construct(Database $db, $table, $optimizer = null)
    {
        parent::__construct($db, 'CREATE TABLE');
        if ($optimizer === 'IF NOT EXISTS') {
            $this->unsafeAppendSQLPart('optimizer', 'IF NOT EXISTS');
        }
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
            'optimizer',
            'table',
            '(',
            'fields',
            ',',
            'keys',
            ')',
            'engine',
            'charset',
            'collate',
        ];
    }

    /**
     * Sets the charset to use in this table.
     *
     * @param string $charset
     *  The charset to use
     * @return DatabaseCreate
     *  The current instance
     */
    public function charset($charset)
    {
        $this->charset = $charset;
        return $this;
    }

    /**
     * Sets the default collate to use for all textual columns being created.
     *
     * @param string $collate
     *  The collate to use by default
     * @return DatabaseCreate
     *  The current instance
     */
    public function collate($collate)
    {
        $this->collate = $collate;
        return $this;
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
        $prefix = '';
        if ($this->containsSQLParts('fields')) {
            $prefix = ', ';
        }
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
        $prefix = '';
        if ($this->containsSQLParts('keys')) {
            $prefix = ',';
        }
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

    /**
     * @internal This method is not meant to be called directly. Use execute().
     * This method validates all the SQL parts currently stored.
     * It makes sure that there is only one part of each types.
     *
     * @see DatabaseStatement::validate()
     * @return DatabaseCreate
     * @throws DatabaseException
     */
    public function validate()
    {
        parent::validate();
        if (count($this->getSQLParts('optimizer')) > 1) {
            throw new DatabaseException('DatabaseCreate can only hold one or zero optimizer part');
        }
        if (count($this->getSQLParts('table')) !== 1) {
            throw new DatabaseException('DatabaseCreate can only hold one table part');
        }
        return $this;
    }
}
