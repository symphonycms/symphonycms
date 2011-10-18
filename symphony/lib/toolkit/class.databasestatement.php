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
 *
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
     * @see appendValues()
     * @var array
     */
    private $values = [];

    /**
     * SQL parameters cache
     *
     * @see convertToParameterName()
     * @var array
     */
    private $parameters = [];

    /**
     * Placeholder flag: Developer should check if the statement supports name
     * parameters, which is on by default.
     *
     * @var bool
     */
    private $usePlaceholders = false;

    /**
     * Safe flag: Allows old code to still inject illegal characters in their SQL.
     * @see Database::validateSQLQuery()
     * @var boolean
     */
    private $safe = true;

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
    final protected function getDB()
    {
        return $this->db;
    }

    /**
     * Getter for the underlying SQL parts array.
     *
     * @return array
     */
    final protected function getSQL()
    {
        return $this->sql;
    }

    /**
     * Returns all the parts for the specified type
     *
     * @param string $type
     *  The type value for the parts to retrieve
     * @return array
     */
    final public function getSQLParts($type)
    {
        return array_filter($this->getSQL(), function ($part) use ($type) {
            if (is_array($type)) {
                return in_array(current(array_keys($part)), $type);
            }
            return current(array_keys($part)) === $type;
        });
    }

    /**
     * Returns true if the statement contains the specified part.
     *
     * @see getSQLParts()
     * @param string $type
     *  The type value for the parts to check for
     * @return bool
     */
    final public function containsSQLParts($type)
    {
        return !empty($this->getSQLParts($type));
    }

    /**
     * Returns the order in which parts needs to be generated.
     * Only those parts will be included when calling generateSQL().
     * When multiple parts can share the same order, use a sub-array.
     * Control characters can be used to merge parts together.
     * Those characters are:
     *  - `(` and `)` which wraps one or more parts in parenthesis
     *  - `,` which joins part with a comma if both the preceding and next parts are not empty
     *
     * @see getSQLParts()
     * @see generateSQL()
     * @return array
     */
    protected function getStatementStructure()
    {
        return ['statement'];
    }

    /**
     * Merges the SQL parts array into a string, joined with the content of the
     * `STATEMENTS_DELIMITER` constant.
     * The order in which the part are merged are given by getStatementStructure().
     *
     * @see getStatementStructure()
     * @return string
     *  The resulting SQL string
     */
    final public function generateSQL()
    {
        $allParts = $this->getStatementStructure();
        $orderedParts = [];
        foreach ($allParts as $ti => $type) {
            if (in_array($type, ['(', ')'])) {
                $orderedParts[] = [$type];
                continue;
            } elseif ($type === ',') {
                $before = $this->getSQLParts($allParts[$ti - 1]);
                $after = $this->getSQLParts($allParts[$ti + 1]);
                if (!empty($before) && !empty($after)) {
                    $orderedParts[] = [$type];
                }
                continue;
            }
            $parts = $this->getSQLParts($type);
            foreach ($parts as $pt => $part) {
                $orderedParts[] = $part;
            }
        }
        return implode(self::STATEMENTS_DELIMITER, array_map(function ($part) {
            return current($part);
        }, $orderedParts));
    }

    /**
     * @internal
     * Appends part $part of type $type into the SQL parts array.
     * Type $type is just a tag value, used to classify parts.
     * This can allow things like filtering out some parts.
     *
     * Only allowed parts will be accepted. The only valid part by default is 'statement'.
     *
     * BEWARE: This method does not validate or sanitize anything, except the
     * type of both parameters, which must be string. This method should be
     * used as a last resort or with properly sanitized values.
     *
     * @see getStatementStructure()
     * @param string $type
     *  The type value for this part
     * @param string $part
     *  The actual SQL code part
     * @return DatabaseStatement
     *  The current instance
     * @throws DatabaseSatementException
     */
    final public function unsafeAppendSQLPart($type, $part)
    {
        General::ensureType([
            'type' => ['var' => $type, 'type' => 'string'],
            'part' => ['var' => $part, 'type' => 'string'],
        ]);
        if (!General::in_array_multi($type, $this->getStatementStructure(), true)) {
            $class = get_class($this);
            throw new DatabaseSatementException("SQL Part type `$type` is not valid for class `$class`");
        }
        $this->sql[] = [$type => $part];
        return $this;
    }

    /**
     * Getter for the array of SQL values sent with the statement
     * to the database server.
     *
     * @return array
     */
    final public function getValues()
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
     * @see convertToParameterName()
     * @param array $values
     *  The values to send to the database
     * @return DatabaseStatement
     *  The current instance
     */
    final protected function appendValues(array $values)
    {
        if ($this->isUsingPlaceholders()) {
            $values = array_values($values);
        } else {
            foreach ($values as $key => $value) {
                if (is_string($key)) {
                    $safeKey = $this->convertToParameterName($key, $value);
                    if ($key !== $safeKey) {
                        unset($values[$key]);
                        $values[$safeKey] = $value;
                    }
                }
            }
        }
        $this->values = array_merge($this->values, $values);
        return $this;
    }

    /**
     * Enable the use of placeholders (?) in the query instead of named parameters.
     *
     * @return DatabaseStatement
     *  The current instance
     */
    final public function usePlaceholders()
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
    final public function isUsingPlaceholders()
    {
        return $this->usePlaceholders;
    }

    /**
     * Marks the statement as not safe.
     * This disables strict validation
     *
     * @return DatabaseStatement
     *  The current instance
     */
    final public function unsafe()
    {
        $this->safe = false;
        return $this;
    }

    /**
     * If the current statement is deem safe.
     * Safe statements are validated more strictly
     *
     * @return bool
     *  true is the statement uses placeholders
     */
    final public function isSafe()
    {
        return $this->safe;
    }

    /**
     * @internal This method is not meant to be called directly. Use execute().
     * Appends any remaining part of the statement.
     * Called just before validation and the actual sending of the statement to
     * the SQL server.
     *
     * @see execute()
     * @return DatabaseStatement
     *  The current instance
     */
    public function finalize()
    {
        return $this;
    }

    /**
     * Send the query and all associated values to the server for execution.
     * Calls finalize before sending creating and sending the query to the server.
     *
     * @see Database::execute()
     * @return DatabaseStatementResult
     * @throws DatabaseException
     */
    final public function execute()
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
    final public function replaceTablePrefix($table)
    {
        General::ensureType([
            'table' => ['var' => $table, 'type' => 'string'],
        ]);
        if ($this->getDB()->getPrefix() != 'tbl_') {
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
     * @see convertToParameterName()
     * @param string $key
     *  The key from which to derive the parameter name from
     * @param mixed $value
     *  The associated value for this key
     * @return string
     *  The parameter expression
     */
    final public function asPlaceholderString($key, $value)
    {
        if (!$this->isUsingPlaceholders() && General::intval($key) === -1) {
            $this->validateFieldName($key);
            $key = $this->convertToParameterName($key, $value);
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
    final public function asPlaceholdersList(array $values)
    {
        return implode(self::LIST_DELIMITER, General::array_map([$this, 'asPlaceholderString'], $values));
    }

    /**
     * @internal
     * Given some value, it will create a ticked string, i.e. "`string`".
     * If the $value parameter is:
     *  1. an array: it will call asPlaceholdersList()
     *  2. the string '*': it will keep it as is
     *  3. a string with comma in it: it will explode that string and call
     *     asTickedList() with the resulting array
     *  4. a string matching a function call
     *  5. a string with a mathematical operator (+, -, *, /)
     *  6. a plain string: it will surround all words with ticks
     *
     * For other type of variable, it will throw an Exception.
     *
     * @see asTickedList()
     * @param string|array $value
     *  The value or list of values to surround with ticks.
     * @param string $alias
     *  Used as an alias, create `x` AS `y` expressions.
     * @return string
     *  The resulting ticked string or list
     * @throws Exception
     */
    final public function asTickedString($value, $alias = null)
    {
        if (!$value) {
            return '';
        }
        if (is_array($value)) {
            return $this->asTickedList($value);
        }
        General::ensureType([
            'value' => ['var' => $value, 'type' => 'string'],
        ]);

        $fctMatches = [];
        $value = trim($value);

        if ($value === '*') {
            return $value;
        } elseif (strpos($value, ',') !== false) {
            return $this->asTickedList(explode(',', $value));
        } elseif (preg_match(self::FCT_PATTERN, $value, $fctMatches) === 1) {
            return $fctMatches[1] . '(' . $this->asTickedString($fctMatches[2]) . ')';
        } elseif (($op = strpbrk($value, '+-*/')) !== false && preg_match("/\s{$op{0}}\s/", $value) === 1) {
            $op = $op{0};
            $parts = array_map('trim', explode($op, $value, 2));
            $parts = array_map(function ($p)  {
                $ip = General::intval($p);
                return $ip === -1 ? $this->asTickedString($p) : "$ip";
            }, $parts);
            return implode(" $op ", $parts);
        }

        $this->validateFieldName($value);
        $value = str_replace('`', '', $value);
        if (strpos($value, '.') !== false) {
            return implode('.', array_map([$this, 'asTickedString'], explode('.', $value)));
        }
        if ($alias) {
            $this->validateFieldName($alias);
            return "`$value` AS `$alias`";
        }
        return "`$value`";
    }

    /**
     * @internal
     * Given an array, this method will call asTickedString() on each values and
     * then implode the results with LIST_DELIMITER.
     * If the array contains named keys, they become the value and the value in the array
     * is used as an alias, create `x` AS `y` expressions.
     *
     * @see asTickedString()
     * @param array $values
     * @return string
     *  The resulting list of ticked strings
     */
    final public function asTickedList(array $values)
    {
        return implode(self::LIST_DELIMITER, General::array_map(function ($key, $value) {
            if (!is_int($key)) {
                return $this->asTickedString($key, $value);
            }
            return $this->asTickedString($value);
        }, $values));
    }

    /**
     * @internal
     * Given an array, this method will call asTickedList() on each values and
     * then implode the results with LIST_DELIMITER.
     * If the value is a DatabaseQuery object, the key is used as the alias.
     *
     * @see asTickedList()
     * @param array $values
     * @return string
     *  The resulting list of ticked strings
     */
    final public function asProjectionList(array $values)
    {
        return implode(self::LIST_DELIMITER, General::array_map(function ($key, $value) {
            if ($value instanceof DatabaseSubQuery) {
                $sql = $value->generateSQL();
                $key = $this->asTickedString($key);
                return "($sql) AS $key";
            }
            return $this->asTickedList([$key => $value]);
        }, $values));
    }

    /**
     * @internal
     * This method validates that the string $field is a valid field name
     * in SQL. If it is not, it throws DatabaseSatementException
     *
     * @param string $field
     * @return void
     * @throws DatabaseSatementException
     * @throws Exception
     */
    final protected function validateFieldName($field)
    {
        General::ensureType([
            'field' => ['var' => $field, 'type' => 'string'],
        ]);
        if (preg_match('/^[0-9a-zA-Z_]+$/', $field) === false) {
            throw new DatabaseSatementException(
                "Field name '$field' is not valid since it contains illegal characters"
            );
        }
    }

    /**
     * @internal
     * This function converts a valid field name into a suitable value
     * to use as a SQL parameter name.
     * It also makes sure that the returned parameter name is not currently used
     * for the specified $field, $value pair.
     *
     * @see formatParameterName()
     * @see validateFieldName()
     * @see appendValues()
     * @param string $field
     *  The field name, as passed in the public API of the statement
     * @param mixed $value
     *  The associated value for this field
     * @return string
     *  The sanitized parameter name
     */
    final public function convertToParameterName($field, $value)
    {
        General::ensureType([
            'value' => ['var' => $field, 'type' => 'string'],
        ]);
        $field = str_replace(['-', '.'], '_', $field);
        $field = preg_replace('/[^0-9a-zA-Z_]+/', '', $field);
        $field = $this->formatParameterName($field);

        $uniqueParameterKey = sha1(serialize($field) . serialize($value));
        // Have we seen this (field, value) pair ?
        if (isset($this->parameters[$uniqueParameterKey])) {
            return $this->parameters[$uniqueParameterKey];
        }
        // Have we seen this field ?
        $fieldLookup = $field;
        $fieldCount = 1;
        while (isset($this->parameters[$fieldLookup])) {
            $fieldCount++;
            $fieldLookup = "$field$fieldCount";
        }
        // Saved both for later
        $this->parameters[$uniqueParameterKey] = $this->parameters[$fieldLookup] = $fieldLookup;

        return $fieldLookup;
    }

    /**
     * @internal
     * Formats the given $parameter name to be used as SQL parameter.
     *
     * @param string $parameter
     *  The parameter name
     * @return string
     *  The formatted parameter name
     */
    protected function formatParameterName($parameter)
    {
        return $parameter;
    }
}


/**
 * The DatabaseSatementException class extends a normal Exception to add in
 * debugging information when a DatabaseSatement is about to enter an invalid state
 */
class DatabaseSatementException extends Exception
{

    /**
     * Constructor takes a message.
     * Before the message is passed to the default Exception constructor,
     * it tries to translate the message.
     */
    public function __construct($message)
    {
        parent::__construct(__($message));
    }
}
