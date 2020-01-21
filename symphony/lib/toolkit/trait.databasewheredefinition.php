<?php

/**
 * @package toolkit
 */

/**
 * @since Symphony 3.0.0
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
     * Sub queries are first class citizens
     *
     * ('in', $stm->select()->from()->where())
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
     * @throws DatabaseStatementException
     * @return string
     *  The SQL part containing logical comparison
     */
    final public function buildSingleWhereClauseFromArray($k, $c)
    {
        $op = '=';
        if (is_object($c)) {
            if (!($c instanceof DatabaseSubQuery)) {
                $type = get_class($c);
                throw new DatabaseStatementException("Object of type `$type` can not be used in a where clause");
            }
        } elseif (is_array($c)) {
            $vk = current(array_keys($c));
            // key is a logical operator
            if ($k === 'or' || $k === 'and') {
                $K = strtoupper($k);
                return '(' . implode(" $K ", array_map(function ($k) use ($c) {
                    return $this->buildSingleWhereClauseFromArray($k, $c[$k]);
                }, array_keys($c))) . ')';
            // key is the VALUES_DELIMITER (i.e. a comma `,`)
            } elseif ($k === self::VALUES_DELIMITER) {
                return implode(self::LIST_DELIMITER, General::array_map(function ($k, $c) {
                    return $this->buildSingleWhereClauseFromArray($k, $c);
                }, $c));
            // first value key is the IN() function
            } elseif ($vk === 'in' || $vk === 'not in') {
                $op = strtoupper($vk);
                $values = current(array_values($c));
                if (is_array($values)) {
                    $values = array_unique($values);
                    if (empty($values)) {
                        throw new DatabaseStatementException("Values passed to `$op` must not be empty");
                    }
                    foreach ($values as $v) {
                        if (is_array($v)) {
                            throw new DatabaseStatementException(
                                "Too many nested arrays: `$vk` operator can not use an array as a parameter value"
                            );
                        }
                    }
                    if (!$this->isUsingPlaceholders()) {
                        $pc = [];
                        foreach ($values as $v) {
                            $this->appendValues([$k => $v]);
                            $pc[] = $this->asPlaceholdersList([$k => $v]);
                        }
                        $pc = implode(self::LIST_DELIMITER, $pc);
                    } else {
                        $this->appendValues($values);
                        $pc = $this->asPlaceholdersList($values);
                    }
                } elseif ($values instanceof DatabaseSubQuery) {
                    if ($this->isUsingPlaceholders() !== $values->isUsingPlaceholders()) {
                        throw new DatabaseStatementException('The IN() function only accepts DatabaseSubQuery that uses the same placeholders mode as the parent query');
                    }
                    foreach ($values->getValues() as $ck => $cv) {
                        $this->appendValues([$ck => $cv]);
                    }
                    $pc = $values->finalize()->generateSQL();
                } else {
                    throw new DatabaseStatementException('The IN() function accepts array of scalars or a DatabaseSubQuery');
                }
                $tk = $this->replaceTablePrefix($k);
                $tk = $this->asTickedString($tk);
                return "$tk $op ($pc)";
            // first value key is the BETWEEN expression
            } elseif ($vk === 'between') {
                $c = current(array_values($c));
                if (count($c) !== 2 || !isset($c[0]) || !isset($c[1])) {
                    throw new DatabaseStatementException("The BETWEEN expression must be provided 2 values");
                }
                $p = $this->convertToParameterName($k, implode('-', $c));
                $this->validateFieldName($p);
                if ($this->isUsingPlaceholders()) {
                    $this->appendValues($c);
                } else {
                    $this->appendValues([
                        "{$p}l" => $c[0],
                        "{$p}u" => $c[1],
                    ]);
                }
                $p1 = $this->asPlaceholderString("{$p}l", $c[0]);
                $p2 = $this->asPlaceholderString("{$p}u", $c[1]);
                $tk = $this->replaceTablePrefix($k);
                $tk = $this->asTickedString($tk);
                return "($tk BETWEEN $p1 AND $p2)";
            // first value key is the boolean expression (full-text boolean match)
            } elseif ($vk === 'boolean') {
                $c = current(array_values($c));
                $this->appendValues([$k => $c]);
                $pk = $this->asPlaceholderString($k, $c);
                $tk = $this->replaceTablePrefix($k);
                $tk = $this->asTickedString($tk);
                return "MATCH ($tk) AGAINST ($pk IN BOOLEAN MODE)";
            // first key is date
            } elseif ($vk === 'date') {
                $c = current(array_values($c));
                if (empty($c['start']) && empty($c['end'])) {
                    throw new DatabaseStatementException('`date` operator needs at least a start or end date');
                }
                $conditions = [];
                $inclusive = !isset($c['strict']) || !$c['strict'];
                $start = isset($c['start']) ? $c['start'] : null;
                if ($start && DateTimeObj::validate($start)) {
                    $conditions[] = [$k => [$inclusive ? '>=' : '>' => $start]];
                }
                $end = isset($c['end']) ? $c['end'] : null;
                if ($end && DateTimeObj::validate($end)) {
                    $conditions[] = [$k => [$inclusive ? '<=' : '<' => $end]];
                }
                if (empty($conditions)) {
                    throw new DatabaseStatementException('No valid start or end date found for `date` operator');
                } elseif (count($conditions) > 1) {
                    $conditions = ['and' => $conditions];
                }
                return $this->buildWhereClauseFromArray($conditions);
            // key is numeric
            } elseif (General::intval($k) !== -1) {
                return $this->buildWhereClauseFromArray($c);
            }
            // key is an [op => value] structure
            $op = null;
            if (in_array($vk, ['<', '>', '=', '<=', '>=', '!=', 'like', 'not like', 'regexp', 'not regexp'])) {
                $op = strtoupper($vk);
                $c = $c[$vk];
            }
            if (!$op) {
                throw new DatabaseStatementException("Operation `$vk` not valid");
            }
        }
        if (!is_string($k)) {
            throw new DatabaseStatementException('Cannot use a number as a column name');
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
        } elseif ($c instanceof DatabaseSubQuery) {
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
            $k = $this->asPlaceholderString($k, $c);
            // Handle null equalities
            if ($c === null && isset($this->enableIsNullSyntax) && $this->enableIsNullSyntax) {
                if ($op === '=') {
                    $op = 'IS';
                } elseif ($op === '!=') {
                    $op = 'IS NOT';
                }
            }
        }
        return "$tk $op $k";
    }

    /**
     * @internal
     * This method maps all $conditions [$k => $c] pairs on `buildSingleWhereClauseFromArray()`.
     * It also makes sure $conditions is not empty, which would create invalid SQL.
     *
     * @param array $conditions
     * @throws DatabaseStatementException
     * @return string
     */
    final public function buildWhereClauseFromArray(array $conditions)
    {
        if (empty($conditions)) {
            throw new DatabaseStatementException('Can not build where clause with an empty array');
        }
        return implode(
            self::STATEMENTS_DELIMITER,
            General::array_map(
                [$this, 'buildSingleWhereClauseFromArray'],
                $conditions
            )
        );
    }
}
