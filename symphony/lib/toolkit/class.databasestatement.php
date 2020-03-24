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
 * @see DatabaseStatementException
 */
class DatabaseStatement
{
    /**
     * List of element delimiter
     * @var string
     */
    const LIST_DELIMITER = ', ';

    /**
     * The SQL values delimiter
     */
    const VALUES_DELIMITER = ',';

    /**
     * The SQL part delimiter
     * @var string
     */
    const STATEMENTS_DELIMITER = ' ';

    /**
     * The SQL part end of line
     */
    const FORMATTED_PART_EOL = "\n";

    /**
     * The SQL part tab character
     */
    const FORMATTED_PART_TAB = "\t";

    /**
     * The SQL part delimiter
     */
    const FORMATTED_PART_DELIMITER = self::FORMATTED_PART_EOL . self::FORMATTED_PART_TAB;

    /**
     * Regular Expression that matches SQL functions
     * @var string
     */
    const FCT_PATTERN = '/^([A-Za-z_]+)\((.*)\)$/';

    /**
     * The SQL functions arguments delimiter
     */
    const FCT_ARGS_DELIMITER = ',';

    /**
     * Regular Expression that matches SQL operators +, -, *, /
     * @var string
     */
    const OP_PATTERN = '/\s+([\-\+\*\/])\s+/';

    /**
     * Database object reference
     * @var Database
     */
    private $db;

    /**
     * SQL parts array
     * @var array
     */
    private $sql = [];

    /**
     * SQL values array
     * @see appendValues()
     * @var array
     */
    private $values = [];

    /**
     * SQL parameters cache
     * @see convertToParameterName()
     * @var array
     */
    private $parameters = [];

    /**
     * Placeholder flag: Developer should check if the statement supports name
     * parameters, which is on by default.
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
     * Merges the ordered SQL parts array into a string, joined with the content of the
     * `STATEMENTS_DELIMITER` constant.
     * The order in which the part are merged are given by getStatementStructure().
     *
     * @see generateOrderedSQLParts()
     * @see getStatementStructure()
     * @return string
     *  The resulting SQL string
     */
    final public function generateSQL()
    {
        return implode(self::STATEMENTS_DELIMITER, array_map(function ($part) {
            return current($part);
        }, $this->generateOrderedSQLParts()));
    }

    /**
     * Merges the ordered SQL parts array into a string, joined with specific string in
     * order to create a formatted, human friendly representation of the resulting SQL.
     * The order in which the part are merged are given by getStatementStructure().
     * The string used for each SQL part is given by getSeparatorForPartType().
     *
     * @see FORMATTED_PART_DELIMITER
     * @see FORMATTED_PART_EOL
     * @see FORMATTED_PART_TAB
     * @see getSeparatorForPartType()
     * @see generateOrderedSQLParts()
     * @see getStatementStructure()
     * @return string
     *  The resulting formatted SQL string
     */
    final public function generateFormattedSQL()
    {
        $parts = $this->generateOrderedSQLParts();
        return array_reduce($parts, function ($memo, $part) {
            $type = current(array_keys($part));
            $value = current($part);
            $sep = $this->getSeparatorForPartType($type);
            if (!$memo) {
                return $value;
            }
            return "{$memo}{$sep}{$value}";
        }, null);
    }

    /**
     * Gets the proper separator string for the given $type SQL part type, when
     * generating a formatted SQL statement.
     * The default implementation simply returns value of the `STATEMENTS_DELIMITER` constant.
     *
     * @see generateFormattedSQL()
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
        return self::STATEMENTS_DELIMITER;
    }

    /**
     * Creates the ordered SQL parts array.
     * The order in which the parts are sorted is given by getStatementStructure().
     *
     * @see getStatementStructure()
     * @return array
     *  The sorted SQL parts array
     */
    final public function generateOrderedSQLParts()
    {
        $allParts = $this->getStatementStructure();
        $orderedParts = [];
        foreach ($allParts as $ti => $type) {
            if (in_array($type, ['(', ')'])) {
                $orderedParts[] = [$type => $type];
                continue;
            } elseif ($type === self::VALUES_DELIMITER) {
                $before = $this->getSQLParts($allParts[$ti - 1]);
                $after = $this->getSQLParts($allParts[$ti + 1]);
                if (!empty($before) && !empty($after)) {
                    $orderedParts[] = [$type => $type];
                }
                continue;
            }
            $parts = $this->getSQLParts($type);
            foreach ($parts as $pt => $part) {
                $orderedParts[] = $part;
            }
        }
        return $orderedParts;
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
     * @throws DatabaseStatementException
     */
    final public function unsafeAppendSQLPart($type, $part)
    {
        General::ensureType([
            'type' => ['var' => $type, 'type' => 'string'],
            'part' => ['var' => $part, 'type' => 'string'],
        ]);
        if (!General::in_array_multi($type, $this->getStatementStructure())) {
            $class = get_class($this);
            throw new DatabaseStatementException("SQL Part type `$type` is not valid for class `$class`");
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
     * Statement parameter setter. This function bypasses the automatic parameter generation
     * to allow the developer to set values as if using PDO directly.
     * This is sometimes needed when dealing with complex custom queries.
     * You should rather consider to sub class the DatabaseStatement and use appendValues() instead.
     *
     * @param mixed $key
     *  The key of the value, either its index or name
     * @param mixed $value
     *  The actual user provided value
     * @return DatabaseStatement
     *  The current instance
     * @throws DatabaseStatementException
     *  If the key is not the proper type: numeric when using place holders, string if not.
     *  If the key is already set.
     */
    final public function setValue($key, $value)
    {
        if ($this->isUsingPlaceholders()) {
            $key = General::intval($key);
            if ($key === -1) {
                throw new DatabaseStatementException(
                    'Can not use string index when using placeholders. Please use a numeric index.'
                );
            }
        } elseif (!is_string($key)) {
            throw new DatabaseStatementException('Key parameter must be a string');
        }
        if (isset($this->values[$key])) {
            throw new DatabaseStatementException("Value for parameter `$key` is already defined");
        }
        $this->values[$key] = $value;
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
        if (!empty($this->getValues())) {
            throw new DatabaseStatementException(
                'Can not use placeholders if values have already been added.'
            );
        }
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
     * Computes a md5 hash of the current statement object, using only minimal
     * information. The goal is to be able to compare two instances of the class
     * and see if they are the same.
     *
     * @return string
     */
    final public function computeHash()
    {
        return md5(serialize([
            $this->sql,
            $this->values,
            $this->safe,
            $this->usePlaceholders,
        ]));
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
     * @uses finalize()
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
     * @param bool $success
     *  If the DatabaseStatement creating this instance succeeded or not.
     * @param PDOStatement $stm
     *  The PDOStatement created by the execution of the DatabaseStatement.
     * @return DatabaseStatementResult
     */
    public function results($success, PDOStatement $stm)
    {
        General::ensureType([
            'success' => ['var' => $success, 'type' => 'bool'],
        ]);
        return new DatabaseStatementResult($success, $stm);
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
            $key = $this->convertToParameterName($key, $value);
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
     * @return string
     */
    final public function asPlaceholdersList(array $values)
    {
        return implode(self::LIST_DELIMITER, General::array_map([$this, 'asPlaceholderString'], $values));
    }

    /**
     * @internal Actually does the tick formatting on the $value string.
     * It makes sure all ticks are removed before validating the value.
     * If the string contains a dot, it will explode it before adding the ticks.
     *
     * @uses validateTickedString()
     * @param string $value
     *  The value to surrounded with ticks
     * @return string
     *  The value surrounded by ticks
     */
    final public function tickString($value)
    {
        General::ensureType([
            'value' => ['var' => $value, 'type' => 'string'],
        ]);
        $value = str_replace('`', '', $value);
        if (strpos($value, '.') !== false) {
            return implode('.', array_map([$this, 'asTickedString'], explode('.', $value)));
        }
        $this->validateTickedString($value);
        return "`$value`";
    }

    /**
     * @internal Splits the arguments of function calls.
     * Arguments are only separated: no formatting is made.
     * Each value should to pass to asTickedString() before being used in SQL queries.
     *
     * @param string $arguments
     *  The argument string to parse
     * @return array
     *  The arguments array
     */
    final public function splitFunctionArguments($arguments)
    {
        General::ensureType([
            'arguments' => ['var' => $arguments, 'type' => 'string'],
        ]);
        $arguments = str_split($arguments);
        $current = [];
        $args = [];
        $openParenthesisCount = 0;
        foreach ($arguments as $char) {
            // Ignore whitespace
            if (General::strlen(trim($char)) === 0) {
                continue;
            } elseif ($openParenthesisCount === 0 && $char === self::FCT_ARGS_DELIMITER) {
                if (!empty($current)) {
                    $args[] = implode('', $current);
                }
                $current = [];
                continue;
            }
            $current[] = $char;
            if ($char === '(') {
                $openParenthesisCount++;
            } elseif ($char === ')') {
                $openParenthesisCount--;
            }
        }
        if ($openParenthesisCount !== 0) {
            throw new DatabaseStatementException('Imbalanced number of parenthesis in function arguments');
        }
        if (!empty($current)) {
            $args[] = implode('', $current);
        }
        return $args;
    }

    /**
     * @internal
     * Given some value, it will create a ticked string, i.e. "`string`".
     * If the $value parameter is:
     *  1. an array, it will call asPlaceholdersList();
     *  2. the string '*', it will keep it as is;
     *  3. a string matching a function call, it will parse it;
     *  4. a string with a mathematical operator (+, -, *, /), it will parse it;
     *  5. a string with comma, it will explode that string and call
     *     asTickedList() with the resulting array;
     *  6. a string starting with a colon, it will be used as named parameter;
     *  7. a plain string, it will surround all words with ticks.
     *
     * For other type of value, it will throw an Exception.
     *
     * @see asTickedList()
     * @uses tickString()
     * @uses splitFunctionArguments()
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
        // 1. deal with array
        if (is_array($value)) {
            return $this->asTickedList($value);
        }
        General::ensureType([
            'value' => ['var' => $value, 'type' => 'string'],
        ]);

        $fctMatches = [];
        $opMatches = [];
        $value = trim($value);

        // 2. '*'
        if ($value === '*') {
            return $value;
        // 3. function
        } elseif (preg_match(self::FCT_PATTERN, $value, $fctMatches) === 1) {
            $args = $this->splitFunctionArguments($fctMatches[2]);
            $fxCall = $fctMatches[1] . '(' . $this->asTickedList($args) . ')';
            if ($alias) {
                $alias = $this->tickString($alias);
                return "$fxCall AS $alias";
            }
            return $fxCall;
        // 4. op
        } elseif (preg_match(self::OP_PATTERN, $value, $opMatches) === 1) {
            $op = $opMatches[1];
            if (!$op) {
                throw new DatabaseStatementException("Failed to parse operator in `$value`");
            }
            $parts = array_map('trim', explode($op, $value, 2));
            $parts = array_map(function ($p) {
                // TODO: add support for params
                $ip = General::intval($p);
                return $ip === -1 ? $this->asTickedString($p) : "$ip";
            }, $parts);
            $value = implode(" $op ", $parts);
            if ($alias) {
                $alias = $this->tickString($alias);
                return "($value) AS $alias";
            }
            return $value;
        // 5. comma
        } elseif (strpos($value, self::VALUES_DELIMITER) !== false) {
            return $this->asTickedList(explode(self::VALUES_DELIMITER, $value));
        // 6. colon
        } elseif (strpos($value, ':') === 0) {
            $this->validateFieldName(substr($value, 1));
            return $value;
        }

        // 7. plain string
        $value = $this->tickString($value);
        if ($alias) {
            $alias = $this->tickString($alias);
            return "$value AS $alias";
        }
        return $value;
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
            if (General::intval($key) === -1) {
                return $this->asTickedString($key, $value);
            }
            return $this->asTickedString($value);
        }, $values));
    }

    /**
     * @internal
     * This method validates that the string $field is a valid field name
     * in SQL. If it is not, it throws DatabaseStatementException
     *
     * @param string $field
     * @return void
     * @throws DatabaseStatementException
     * @throws Exception
     */
    final protected function validateFieldName($field)
    {
        General::ensureType([
            'field' => ['var' => $field, 'type' => 'string'],
        ]);
        if (!preg_match('/^[0-9a-zA-Z_]+$/', $field)) {
            throw new DatabaseStatementException(
                "Field name '$field' is not valid since it contains illegal characters"
            );
        }
    }

    /**
     * @internal
     * This method validates that the string $value is a valid string to tick
     * in SQL. If it is not, it throws DatabaseStatementException
     *
     * @param string $value
     * @return void
     * @throws DatabaseStatementException
     * @throws Exception
     */
    final protected function validateTickedString($value)
    {
        General::ensureType([
            'value' => ['var' => $value, 'type' => 'string'],
        ]);
        if ($value === '*') {
            return;
        }
        if (!preg_match('/^[a-zA-Z_][0-9a-zA-Z_\-]*$/', $value)) {
            throw new DatabaseStatementException(
                "Value '$value' is not valid since it contains illegal characters"
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
        // Special case for null
        if ($value === null) {
            $fieldLookup = "_null_";
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
