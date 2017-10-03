<?php

/**
 * @package toolkit
 */

/**
 * This DatabaseStatement specialization class allows creation of CREATE TABLE statements.
 */
final class DatabaseCreate extends DatabaseStatement
{
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
     *  The name of the table to act on.
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
     * This method checks if the $key index is not empty in the $options array.
     * If it is not empty, it will return its value. If is it, it will lookup a
     * member variable on the current instance.
     *
     * @see DatabaseStatement::getOption()
     * @param array $options
     * @param string|int $key.
     * @return mixed
     */
    protected function getOption(array $options, $key)
    {
        return (isset($options[$key]) && !empty($options[$key]) ? $options[$key] : $this->{$key});
    }

    /**
     * Appends one or multiple columns definitions clauses.
     *
     * @see buildColumnDefinitionFromArray
     * @param array $fields
     *  The field definitions to append
     * @return DatabaseCreate
     *  The current instance
     */
    public function fields(array $fields)
    {
        if ($this->getOpenParenthesisCount() === 0) {
            $this->appendOpenParenthesis();
        }
        $fields = implode(self::LIST_DELIMITER, General::array_map(function ($k, $field) {
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
        $preamble = '';
        if ($this->getOpenParenthesisCount() === 0) {
            $this->appendOpenParenthesis();
        } else {
            $preamble = self::LIST_DELIMITER;
        }
        $keys = $preamble . implode(self::LIST_DELIMITER, General::array_map(function ($key, $options) {
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
        parent::finalize();
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
