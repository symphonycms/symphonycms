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
     * @throws DatabaseException
     */
    final public function unsafeAppendSQLPart($type, $part)
    {
        General::ensureType([
            'type' => ['var' => $type, 'type' => 'string'],
            'part' => ['var' => $part, 'type' => 'string'],
        ]);
        if (!General::in_array_multi($type, $this->getStatementStructure(), true)) {
            $class = get_class($this);
            throw new DatabaseException("SQL Part type `$type` is not valid for class `$class`");
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
        $this->values = array_merge($this->values, $values);
        foreach ($this->values as $key => $value) {
            if (is_string($key)) {
                $safeKey = $this->convertToParameterName($key);
                if ($key !== $safeKey) {
                    unset($this->values[$key]);
                    $this->values[$safeKey] = $value;
                }
            }
        }
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
     * @internal This method is not meant to be called directly. Use execute().
     * This method should validate all the SQL parts currently stored.
     * Plain DatabaseStatement instances requires to have only one statement in them.
     * Subclasses are expected to throw DatabaseException when invalid.
     *
     * @see generateSQL()
     * @return DatabaseStatement
     * @throws DatabaseException
     */
    public function validate()
    {
        if (count($this->getSQLParts('statement')) !== 1) {
            throw new DatabaseException('DatabaseStatement can only hold one statement part');
        }
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
            ->validate()
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
     * @return string
     *  The parameter expression
     */
    final public function asPlaceholderString($key)
    {
        if (!$this->isUsingPlaceholders() || General::intval($key) === -1) {
            $this->validateFieldName($key);
            $key = $this->convertToParameterName($key);
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
    final public function asTickedString($value)
    {
        if (!$value) {
            return '';
        }
        if (is_array($value)) {
            return $this->asTickedList($value);
        } elseif (strpos($value, ',') !== false) {
            return $this->asTickedList(explode(',', $value));
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
    final public function asTickedList(array $values)
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
    final protected function validateFieldName($field)
    {
        General::ensureType([
            'field' => ['var' => $field, 'type' => 'string'],
        ]);
        if (preg_match('/^[0-9a-zA-Z_]+$/', $field) === false) {
            throw new DatabaseException(
                "Field name '$field' is not valid since it contains illegal characters"
            );
        }
    }

    /**
     * @internal
     * This function converts a valid field name into a suitable value
     * to use as a SQL parameter name.
     *
     * @see validateFieldName()
     * @see appendValues()
     * @param string $field
     * @return string
     *  The sanitized for parameter name field value
     */
    final public function convertToParameterName($field)
    {
        General::ensureType([
            'value' => ['var' => $field, 'type' => 'string'],
        ]);
        $field = str_replace(['-', '.'], '_', $field);
        $field = preg_replace('/[^0-9a-zA-Z_]+/', '', $field);
        return $field;
    }
}
