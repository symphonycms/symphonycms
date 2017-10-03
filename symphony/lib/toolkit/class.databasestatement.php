<?php

/**
 * @package toolkit
 */

/**
 * This class holds all the required data to enable dynamic SQL statement creation.
 * The way it currently works is by keeping an array for SQL parts. Each operation
 * must add their corresponding SQL part.
 *
 * When appending parts, developers should make sure that the SQL is safe.
 * The class also offers methods to sanitize and validate field values.
 *
 * Finally, the class can be inherited by specialized class for particular queries.
 * @see DatabaseQuery
 * @see DatabaseCreate
 * @see DatabaseUpdate
 * @see DatabaseDelete
 * @see DatabaseShow
 * @see DatabaseCreate
 * @see DatabaseAlter
 * @see DatabaseDrop
 * @see DatabaseTruncate
 * @see DatabaseOptimize
 * @see DatabaseSet
 */
class DatabaseStatement
{
    /**
     * List of element delimiter
     *
     * @var string
     */
    const LIST_DELIMITER = ', ';

    /**
     * The SQL part delimiter
     *
     * @var string
     */
    const STATEMENTS_DELIMITER = ' ';

    /**
     * Regular Expression that matches SQL functions
     *
     * @var string
     */
    const FCT_PATTERN = '/^([A-Za-z_]+)\((.+)\)$/';

    /**
     * Database object reference
     *
     * @var Database
     */
    private $db;

    /**
     * SQL parts array
     *
     * @var array
     */
    private $sql = [];

    /**
     * SQL values array
     *
     * @var array
     */
    private $values = [];

    /**
     * Placeholder flag: Developer should check if the statement supports name
     * parameters, which is on by default.
     *
     * @var bool
     */
    private $usePlaceholders = false;

    /**
     * Reference counter of the number of currently opened (hence unclosed)
     * parenthesis. This allow auto-closing open parenthesis
     *
     * @var int
     */
    private $openParenthesisCount = 0;

    /**
     * Creates a new DatabaseStatement object, linked to the $db parameter
     * and containing the optional $statement.
     *
     * @param Database $db
     *  The Database reference
     * @param string $statement
     *  An optional string of SQL that will be appended right from the start.
     *  Defaults to an empty string.
     */
    public function __construct(Database $db, $statement = '')
    {
        General::ensureType([
            'statement' => ['var' => $statement, 'type' => 'string'],
        ]);
        $this->db = $db;
        if (!empty($statement)) {
            $this->unsafeAppendSQLPart('statement', $statement);
        }
    }

    /**
     * Destroys all underlying resources
     */
    public function __destruct()
    {
        $this->db = null;
    }

    /**
     * Getter for the underlying database object.
     *
     * @return Database
     */
    protected final function getDB()
    {
        return $this->db;
    }

    /**
     * Getter for the underlying SQL parts array.
     *
     * @return array
     */
    protected final function getSQL()
    {
        return $this->sql;
    }

    /**
     * Merges the SQL parts array into a string, joined with the content of the
     * `STATEMENTS_DELIMITER` constant.
     *
     * @return string
     *  The resulting SQL string
     */
    public final function generateSQL()
    {
        return implode(self::STATEMENTS_DELIMITER, array_map(function ($part) {
            return current(array_values($part));
        }, $this->sql));
    }

    /**
     * @internal
     * Appends part $part of type $type into the SQL parts array.
     * Type $type is just a tag value, used to classify parts.
     * This can allow things like filtering out some parts.
     *
     * BEWARE: This method does not validate or sanitize anything, except the
     * type of both parameters, which must be string. This method should be
     * used as a last resort or with properly sanitized values.
     *
     * @param string $type
     *  The type value for this part
     * @param string $part
     *  The actual SQL code part
     * @return DatabaseStatement
     *  The current instance
     */
    public final function unsafeAppendSQLPart($type, $part)
    {
        General::ensureType([
            'type' => ['var' => $type, 'type' => 'string'],
            'part' => ['var' => $part, 'type' => 'string'],
        ]);
        $this->sql[] = [$type => $part];
        return $this;
    }

    /**
     * Getter for the number of currently opened (unclosed) parenthesis,
     * amongst all parts. This number represents only parenthesis that are
     * opened using `appendOpenParenthesis()`, not ones present in other parts.
     *
     * @see appendOpenParenthesis()
     * @return int
     */
    public final function getOpenParenthesisCount()
    {
        return $this->openParenthesisCount;
    }

    /**
     * Appends an opening parenthesis as a part of type 'parenthesis'.
     *
     * @return DatabaseStatement
     *  The current instance
     */
    public final function appendOpenParenthesis()
    {
        $this->unsafeAppendSQLPart('parenthesis', '(');
        $this->openParenthesisCount++;
        return $this;
    }

    /**
     * Appends an closing parenthesis as a part of type 'parenthesis'.
     * No validation is made to check if there are currently opened parenthesis.
     *
     * @return DatabaseStatement
     *  The current instance
     */
    public final function appendCloseParenthesis()
    {
        $this->unsafeAppendSQLPart('parenthesis', ')');
        $this->openParenthesisCount--;
        return $this;
    }

    /**
     * Getter for the array of SQL values sent with the statement
     * to the database server.
     *
     * @return array
     */
    public final function getValues()
    {
        return $this->values;
    }

    /**
     * Appends the specified $values to the SQL values array.
     * This is the proper way to send user input, as those values
     * are send along the SQL statement without any concatenation.
     * It is safer and faster.
     *
     * It supports keyed and numeric arrays.
     * When using a keyed arrays, keys should be used as SQL named parameters.
     * When using a numeric array, parameters should be place holders (?)
     *
     * @see usePlaceholders()
     * @param array $values
     *  The values to send to the database
     * @return DatabaseStatement
     *  The current instance
     */
    protected final function appendValues(array $values)
    {
        $this->values = array_merge($this->values, $values);
        return $this;
    }

    /**
     * Enable the use of placeholders (?) in the query instead of named parameters.
     *
     * @return DatabaseStatement
     *  The current instance
     */
    public final function usePlaceholders()
    {
        $this->usePlaceholders = true;
        return $this;
    }

    /**
     * If the current statement uses placeholders (?).
     *
     * @return bool
     *  true is the statement uses placeholders
     */
    public final function isUsingPlaceholders()
    {
        return $this->usePlaceholders;
    }

    /**
     * @internal
     * Merges the $s parameter into the current instance, which get mutated.
     *
     * @unstable TODO: Might get removed (?)
     *
     * @param DatabaseStatement $s
     *  The statement to merge with the current one.
     * @return DatabaseStatement
     *  The current instance
     * @throws DatabaseException
     *  When merging a statement that uses named parameter with one using placeholders
     */
    public final function mergeWith(DatabaseStatement $s)
    {
        if ($this->isUsingPlaceholders() !== $s->isUsingPlaceholders()) {
            throw new DatabaseException('Cannot merge statement that using placeholders with one that does not');
        }
        foreach ($s->getSQL() as $type => $part) {
            $this->unsafeAppendSQLPart($type, $part);
        }
        $this->appendValues($s->getValues());
        return $this;
    }

    /**
     * Closes any remaining part of the statement.
     * Called just before sending the statement to the server.
     *
     * @see execute()
     * @return DatabaseStatement
     *  The current instance
     */
    public function finalize()
    {
        while ($this->getOpenParenthesisCount() > 0) {
            $this->appendCloseParenthesis();
        }
        return $this;
    }

    /**
     * Send the query and all associated values to the server for execution
     *
     * @see Database::execute()
     * @return DatabaseStatementResult
     * @throws DatabaseException
     */
    public final function execute()
    {
        return $this
            ->finalize()
            ->getDB()
            ->execute($this);
    }

    /**
     * Factory function that creates a new DatabaseStatementResult based upon the $result
     * and $stm parameters.
     * Child classes can overwrite this method to return a specialized version of the
     * DatabaseStatementResult class.
     *
     * @param bool $result
     * @param PDOStatement $stm
     * @return DatabaseStatementResult
     */
    public function results($result, PDOStatement $stm)
    {
        General::ensureType([
            'result' => ['var' => $result, 'type' => 'bool'],
        ]);
        return new DatabaseStatementResult($result, $stm);
    }

    /**
     * @internal
     * Given a string, replace the default table prefixes with the
     * table prefix for this database instance.
     *
     * @param string $query
     * @return string
     */
    public final function replaceTablePrefix($table) {
        General::ensureType([
            'table' => ['var' => $table, 'type' => 'string'],
        ]);
        if ($this->getDB()->getPrefix() != 'tbl_'){
            $table = preg_replace('/tbl_(\S+?)([\s\.,]|$)/', $this->getDB()->getPrefix() .'\\1\\2', trim($table));
        }

        return $table;
    }

    /**
     * @internal
     * Given a valid field name, returns its variant as a SQL parameter.
     * If the $key string is numeric, it will default to placeholders.
     * If enabled, it will use named parameters.
     *
     * @see validateFieldName()
     * @see isUsingPlaceholders()
     * @see usePlaceholders()
     * @param string $key
     * @return string
     *  The parameter expression
     */
    public final function asPlaceholderString($key)
    {
        if (!$this->isUsingPlaceholders() || General::intval($key) === -1) {
            $this->validateFieldName($key);
            return ":$key";
        }
        return '?';
    }

    /**
     * Given an array of valid field names, maps `asPlaceholderString` on each
     * keys and then implodes the resulting array using LIST_DELIMITER
     *
     * @see asPlaceholderString()
     * @see LIST_DELIMITER
     * @param array $values
     * @return void
     */
    public final function asPlaceholdersList(array $values)
    {
        return implode(self::LIST_DELIMITER, array_map([$this, 'asPlaceholderString'], array_keys($values)));
    }

    /**
     * @internal
     * Given some value, it will create a ticked string, i.e. "`string`".
     * If the $value parameter is:
     *  1. an array: it will call asPlaceholdersList()
     *  2. a string with comma in it: it will explode that string and call
     *     asTickedList() with the resulting array
     *  3. the string '*': it will keep it as is
     *  4. a string: it will surround all words with ticks
     *
     * For other type of variable, it will throw an Exception.
     *
     * @see asTickedList()
     * @param string|array $value
     * @return string
     *  The resulting ticked string or list
     * @throws Exception
     */
    public final function asTickedString($value)
    {
        if (is_array($value)) {
            return $this->asTickedList($value);
        } elseif (strpos($value, ',') !== false) {
            return $this->asTickedList(explode(',',$value));
        }
        General::ensureType([
            'value' => ['var' => $value, 'type' => 'string'],
        ]);
        $fctMatches = [];
        if ($value === '*') {
            return $value;
        } elseif (preg_match(self::FCT_PATTERN, $value, $fctMatches) === 1) {
            return $fctMatches[1] . '(' . $this->asTickedString($fctMatches[2]) . ')';
        }
        $this->validateFieldName($value);
        $value = str_replace('`', '', $value);
        if (strpos($value, '.') !== false) {
            return implode('.', array_map([$this, 'asTickedString'], explode('.', $value)));
        }
        return "`$value`";
    }

    /**
     * @internal
     * Given an array, this method will call asTickedString() on each values and
     * then implode the results with LIST_DELIMITER.
     *
     * @see asTickedString()
     * @param array $values
     * @return string
     *  The resulting list of ticked strings
     */
    public final function asTickedList(array $values)
    {
        return implode(self::LIST_DELIMITER, array_map([$this, 'asTickedString'], $values));
    }

    /**
     * @internal
     * This method validates that the string $field is a valid field name
     * in SQL. If it is not, it throws DatabaseException
     *
     * @param string $field
     * @return void
     * @throws DatabaseException
     * @throws Exception
     */
    protected function validateFieldName($field)
    {
        General::ensureType([
            'field' => ['var' => $field, 'type' => 'string'],
        ]);
        if (preg_match('/^[0-9a-zA-Z_]+$/', $field) === false) {
            throw new DatabaseException("Field name '$field' is not valid since it contains");
        }
    }

    /**
     * @internal
     * This method checks if the $key index is not empty in the $options array.
     * If it is not empty, it will return its value. If is it, it returns null
     *
     * Specialized statement can override this method to provide default values
     * or check alternate storage space for default values.
     *
     * @param array $options
     * @param string|int $key.
     * @return mixed
     */
    protected function getOption(array $options, $key)
    {
        return !empty($options[$key]) ? $options[$key] : null;
    }

    /**
     * @internal
     * Given a field name valid field $k, this methods build a column definition
     * SQL part from an array of options. It will use the array $options to generate
     * the a complete SQL definition part, with all its possible properties.
     *
     * This method is mostly used for CREATE and ALTER statements.
     *
     * @see validateFieldName()
     * @see getOptions()
     * @see DatabaseCreate
     * @see DatabaseAlter
     * @param string $k
     *  The name of the field
     * @param string|array $options
     *  All the options needed to properly create the column.
     *  The method `getOptions()` is used to get the value of the field.
     *  When the value is a string, it is considered as the column's type.
     * @param string $options.type
     *  The SQL type of the column.
     * @param string $options.collate
     *  The collate to use with this column. Only used for character based columns.
     * @param bool $options.null
     *  If the column should accept NULL. Defaults to false, i.e. NOT NULL.
     * @param string|int $options.default
     *  The default value of the column.
     * @param bool $options.signed
     *  If the column should be signed. Only used for number based columns.
     *  Defaults to false, i.e. UNSIGNED.
     * @param boolean $options.auto
     *  If the column should use AUTO_INCREMENT. Only used for integer based columns.
     *  Defaults to false.
     * @return string
     *  The SQL part containing the column definition.
     * @throws DatabaseException
     */
    public function buildColumnDefinitionFromArray($k, $options)
    {
        if (is_string($options)) {
            $options = ['type' => $options];
        } else if (!is_array($options)) {
            throw new DatabaseException('Field value can only be a string or an array');
        } else if (!isset($options['type'])) {
            throw new DatabaseException('Field type must be defined.');
        }
        $type = strtolower($options['type']);
        $collate = $this->getOption($options, 'collate');
        if ($collate) {
            $collate = ' COLLATE ' . $collate;
        }
        $notNull = !isset($options['null']) || $options['null'] === false;
        $null = $notNull ? ' NOT NULL' : ' DEFAULT NULL';
        $default = $notNull && isset($options['default']) ? " DEFAULT " . $this->getDb()->quote($options['default']) : '';
        $unsigned = !isset($options['signed']) || $options['signed'] === false;
        $stringOptions = $collate . $null . $default;

        if (strpos($type, 'varchar') === 0 || strpos($type, 'text') === 0) {
            $type .= $stringOptions;
        } elseif (strpos($type, 'enum') === 0) {
            if (isset($options['values']) && is_array($options['values'])) {
                $type .= "(" . implode(self::LIST_DELIMITER, array_map([$this->getDb(), 'quote'], $options['values'])) . ")";
            }
            $type .= $stringOptions;
        } elseif (strpos($type, 'int') === 0) {
            if ($unsigned) {
                $type .= ' unsigned';
            }
            $type .= $null . $default;
            if (isset($options['auto']) && $options['auto']) {
                $type .= ' AUTO_INCREMENT';
            }
        } elseif (strpos($type, 'datetime') === 0) {
            $type .= $null . $default;
        }
        $k = $this->asTickedString($k);
        return "$k $type";
    }

    /**
     * @internal
     * Given a field name valid field $k, this methods build a key definition
     * SQL part from an array of options. It will use the array $options to generate
     * the a complete SQL definition part, with all its possible properties.
     *
     * @param string $k
     *  The name of the key
     * @param string|array $options
     *  All the options needed to properly create the key.
     *  When the value is a string, it is considered as the key's type.
     * @param string $options.type
     *  The SQL type of the key.
     *  Valid values are: 'key', 'unique', 'primary', 'fulltext', 'index'
     * @param string|array $options.cols
     *  The list of columns to be included in the key.
     *  If omitted, the name of the key be added as the only column in the key.
     * @return string
     *  The SQL part containing the key definition.
     * @throws DatabaseException
     */
    public function buildKeyDefinitionFromArray($k, $options) {
        if (is_string($options)) {
            $options = ['type' => $options];
        } else if (!is_array($options)) {
            throw new DatabaseException('Key value can only be a string or an array');
        } else if (!isset($options['type'])) {
            throw new DatabaseException('Key type must be defined.');
        }
        $type = strtolower($options['type']);
        $cols = isset($options['cols']) ? $options['cols'] : $k;
        if (!is_array($cols)) {
            $cols = [$cols];
        }
        $k = $this->asTickedString($k);
        $typeIndex = in_array($type, [
            'key', 'unique', 'primary', 'fulltext', 'index'
        ]);
        if ($typeIndex === false) {
            throw new DatabaseException("Key of type `$type` is not valid");
        }
        switch ($type) {
            case 'unique':
                $type = strtoupper($type) . ' KEY';
                break;
            case 'primary':
                // Use the key name as the KEY keyword
                // since the primary key does not have a name
                $k = 'KEY';
                // fall through
            default:
                $type = strtoupper($type);
                break;
        }
        $cols = $this->asTickedList($cols);
        return "$type $k ($cols)";
    }

    /**
     * @internal This method is used to create WHERE clauses. Developers should not call
     * directly this API, but use factory methods for specialized statements
     * which expose the following model.
     *
     * Given an operator or field name $k, this method will generate a logical comparison
     * SQL part from its $c value. This method focuses on expressiveness and shortness.
     * Since array keys cannot contains multiple values, single keys are shifted left, even if
     * it is not the order in which SQL wants it. Multiple nested array can be needed to form a
     * key -> key -> values chain. The way it should be read is OPERATOR on KEY for VALUES.
     *
     * Scalar values are replaced with SQL parameters in the actual resulting SQL.
     *
     * Examples
     *  ('x, 'y') -> `x` = :y
     *  ('<', ['x' => 1]) -> 'x' < 1
     *  ('or', ['x' => 'y', 'y' => 'x']) -> (`x` = :y OR `y` = :x)
     *  ('in', ['x' => ['y', 'z']]) -> `x` IN (:y, :z)
     *
     * Values are by default scalar values.
     * Reference to other SQL field should be denoted with the prefix `$`.
     *
     * ('x', '$id') -> `x` = `id`
     *
     * Function class are also supported
     *
     * ('<=', ['x' => 'SUM(total)']) -> `x` <= SUM(`total`)
     *
     * Everything can be nested
     *
     * ('or', [
     *      'and' => ['x' => 1, 'y' = 2],
     *      '<' => ['x' => 2],
     *      'between' ['x' => [10, 12]]
     * ]) -> (
     *   (`x` = ? AND `y` = ?) OR
     *   `x` < ? OR
     *   `x` BETWEEN ? AND ?
     * )
     *
     * @see DatabaseQuery
     * @see DatabaseDelete
     * @see DatabaseUpdate
     * @param string $k
     *  Can either be an operator or a field name
     * @param string|array $c
     *  Can be a single value, a list of values or nested list of valid ($k, $c) pairs.
     * @return string
     *  The SQL part containing logical comparison
     */
    public final function buildSingleWhereClauseFromArray($k, $c)
    {
        $op = '=';
        if (is_object($c)) {
            throw new DatabaseException('Objects are not allowed right now');
        } elseif (is_array($c)) {
            // key is a logical operator
            if ($k === 'or' || $k === 'and') {
                $K = strtoupper($k);
                return '(' . implode(" $K ", array_map(function ($k) use ($c) {
                    return $this->buildWhereClauseFromArray([$k => $c[$k]]);
                }, array_keys($c))) . ')';
            // key is ,
            } elseif ($k === ',') {
                return implode(self::LIST_DELIMITER, General::array_map(function ($k, $c) {
                    return $this->buildWhereClauseFromArray([$k => $c]);
                }, $c));
            // key is the IN() function
            } elseif ($k === 'in') {
                $values = current(array_values($c));
                $this->appendValues($values);
                $this->usePlaceholders();
                $pc = $this->asPlaceholdersList($values);
                $tk = $this->replaceTablePrefix(current(array_keys($c)));
                $tk = $this->asTickedString($tk);
                return "$tk IN ($pc)";
            // key is the BETWEEN expression
            } elseif ($k === 'between') {
                $this->appendValues(current(array_values($c)));
                $tk = $this->replaceTablePrefix(current(array_keys($c)));
                $tk = $this->asTickedString($tk);
                return "($tk BETWEEN ? AND ?)";
            // key is numeric
            } elseif (General::intval($k) !== -1) {
                return $this->buildWhereClauseFromArray($c);
            }
            // key is an [op => value] structure
            list($op, $c) = array_reduce(
                ['<', '>', '=', '<=', '>=', 'like'],
                function ($memo, $k) use ($c) {
                    if ($memo) {
                        return $memo;
                    }
                    if (!empty($c[$k])) {
                        return [$k, $c[$k]];
                    }
                    return null;
                },
                null
            );
            if (!$op) {
                throw new DatabaseException("Operation `$k` not valid");
            }
        }
        if (!is_string($k)) {
            throw new DatabaseException('Cannot use a number as a column name');
        }
        // When we get here:
        //  $op is a valid SQL operator
        //  $k is a sting representing a column name.
        //  $c is a is not an array so it is a value:
        //      1. Scalar
        //      2. Column name
        //      3. Inner query (TODO)
        //      4. Function call
        //      5. Something forgotten (TODO)
        $tk = $this->replaceTablePrefix($k);
        $tk = $this->asTickedString($tk);
        // 4. Function call
        if (is_string($c) && preg_match(self::FCT_PATTERN, $c) === 1) {
            $k = $this->asTickedString($c);
        // 2. Column name must begin with $
        } elseif (is_string($c) && strpos($c, '$') === 0) {
            $c = substr($c, 1);
            $k = $this->replaceTablePrefix($c);
            $k = $this->asTickedString($k);
        // 1. Use the scalar value
        } else {
            $this->appendValues([$k => $c]);
            $k = $this->asPlaceholderString($k);
        }
        return "$tk $op $k";
    }

    /**
     * @internal
     * This method maps all $conditions [$k => $c] pairs on `buildSingleWhereClauseFromArray()`
     *
     * @param array $conditions
     * @return void
     */
    public final function buildWhereClauseFromArray(array $conditions)
    {
        return implode(self::STATEMENTS_DELIMITER,
            General::array_map(
                [$this, 'buildSingleWhereClauseFromArray'],
                $conditions
            )
        );
    }
}
