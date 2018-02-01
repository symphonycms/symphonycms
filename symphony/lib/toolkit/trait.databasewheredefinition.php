<?php

/**
 * @package toolkit
 */

trait DatabaseWhereDefinition
{
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
     * @param string|array|DatabaseSubQuery $c
     *  Can be a single value, a list of values or nested list of valid ($k, $c) pairs.
     *  Can also be a DatabaseSubQuery object to use as a sub-query.
     * @return string
     *  The SQL part containing logical comparison
     */
    final public function buildSingleWhereClauseFromArray($k, $c)
    {
        $op = '=';
        if (is_object($c)) {
            if (!($c instanceof DatabaseSubQuery)) {
                $type = get_class($c);
                throw new DatabaseException("Object of type `$type` can not be used in a where clause.");
            }
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
                if (is_array($values)) {
                    $this->appendValues($values);
                    $this->usePlaceholders();
                    $pc = $this->asPlaceholdersList($values);
                } elseif ($values instanceof DatabaseSubQuery) {
                    foreach ($values->getValues() as $ck => $cv) {
                        $this->appendValues([$ck => $cv]);
                    }
                    $pc = $values->finalize()->generateSQL();
                } else {
                    throw new DatabaseException("The IN() function accepts array of scalars or a DatabaseSubQuery");
                }
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
        //      3. Sub query
        //      4. Function call
        $tk = $this->replaceTablePrefix($k);
        $tk = $this->asTickedString($tk);
        // 4. Function call
        if (is_string($c) && preg_match(self::FCT_PATTERN, $c) === 1) {
            $k = $this->asTickedString($c);
        // 3. Sub query
        } elseif (is_object($c)) {
            foreach ($c->getValues() as $ck => $cv) {
                $this->appendValues([$ck => $cv]);
            }
            $k = '(' . $c->finalize()->generateSQL() . ')';
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
    final public function buildWhereClauseFromArray(array $conditions)
    {
        return implode(
            self::STATEMENTS_DELIMITER,
            General::array_map(
                [$this, 'buildSingleWhereClauseFromArray'],
                $conditions
            )
        );
    }
}
