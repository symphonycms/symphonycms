<?php

/**
 * @package toolkit
 */

/**
 * This DatabaseStatement specialization class allows creation of SELECT FROM statements.
 */
final class DatabaseQuery extends DatabaseStatement
{
    use DatabaseWhereDefinition;

     /**
     * Creates a new DatabaseQuery statement on table $table, with and optional projection $values
     * and an optional optimizer value.
     *
     * @see Database::select()
     * @see Database::selectDistinct()
     * @param Database $db
     *  The underlying database connection
     * @param string $values
     *  The columns names for include in the projection
     * @param string $optimizer
     *  An optional optimizer hint.
     *  Currently, only DISTINCT is supported
     */
    public function __construct(Database $db, array $values = [], $optimizer = null)
    {
        parent::__construct($db, 'SELECT');
        $this->unsafeAppendSQLPart('cache', $db->isCachingEnabled() ? 'SQL_CACHE' : 'SQL_NO_CACHE');
        if ($optimizer === 'DISTINCT') {
            $this->unsafeAppendSQLPart('optimizer', 'DISTINCT');
        }
        if (!empty($values)) {
            $this->unsafeAppendSQLPart('projection', $this->asTickedList($values));
        }
    }

    /**
     * Appends a FROM `table` clause
     *
     * @see alias()
     * @param string $table
     *  The name of the table to act on, including the tbl prefix which will be changed
     *  to the Database table prefix.
     * @param string $alias
     *  An optional alias for the table. Defaults to null, i.e. no alias.
     * @return DatabaseQuery
     *  The current instance
     */
    public function from($table, $alias = null)
    {
        $table = $this->replaceTablePrefix($table);
        $table = $this->asTickedString($table);
        $this->unsafeAppendSQLPart('table', "FROM $table");
        if ($alias) {
            $this->alias($alias);
        }
        return $this;
    }

    /**
     * Appends a AS `alias` clause.
     *
     * @param string $alias
     *  The name of the alias
     * @return DatabaseQuery
     *  The current instance
     */
    public function alias($alias)
    {
        General::ensureType([
            'alias' => ['var' => $alias, 'type' => 'string'],
        ]);
        $alias = $this->asTickedString($alias);
        $this->unsafeAppendSQLPart('as', "AS $alias");
        return $this;
    }

    /**
     * Appends a JOIN `table` clause
     *
     * @see alias()
     * @param string $table
     *  The name of the table to act on, including the tbl prefix which will be changed
     *  to the Database table prefix.
     * @param string $alias
     *  An optional alias for the table. Defaults to null, i.e. no alias.
     * @return DatabaseQuery
     *  The current instance
     */
    public function join($table, $alias = null)
    {
        $table = $this->replaceTablePrefix($table);
        $table = $this->asTickedString($table);
        $this->unsafeAppendSQLPart('join', "JOIN $table");
        if ($alias) {
            $this->alias($alias);
        }
        return $this;
    }

    /**
     * Appends a INNER JOIN `table` clause
     *
     * @see alias()
     * @param string $table
     *  The name of the table to act on, including the tbl prefix which will be changed
     *  to the Database table prefix.
     * @param string $alias
     *  An optional alias for the table. Defaults to null, i.e. no alias.
     * @return DatabaseQuery
     *  The current instance
     */
    public function innerJoin($table, $alias = null)
    {
        $table = $this->replaceTablePrefix($table);
        $table = $this->asTickedString($table);
        $this->unsafeAppendSQLPart('innerjoin', "INNER JOIN $table");
        if ($alias) {
            $this->alias($alias);
        }
        return $this;
    }

    /**
     * Appends a LEFT JOIN `table` clause
     *
     * @see alias()
     * @param string $table
     *  The name of the table to act on, including the tbl prefix which will be changed
     *  to the Database table prefix.
     * @param string $alias
     *  An optional alias for the table. Defaults to null, i.e. no alias.
     * @return DatabaseQuery
     *  The current instance
     */
    public function leftJoin($table, $alias = null)
    {
        $table = $this->replaceTablePrefix($table);
        $table = $this->asTickedString($table);
        $this->unsafeAppendSQLPart('leftjoin', "LEFT JOIN $table");
        if ($alias) {
            $this->alias($alias);
        }
        return $this;
    }

    /**
     * Appends a RIGHT JOIN `table` clause
     *
     * @see alias()
     * @param string $table
     *  The name of the table to act on, including the tbl prefix which will be changed
     *  to the Database table prefix.
     * @param string $alias
     *  An optional alias for the table. Defaults to null, i.e. no alias.
     * @return DatabaseQuery
     *  The current instance
     */
    public function rightJoin($table, $alias = null)
    {
        $table = $this->replaceTablePrefix($table);
        $table = $this->asTickedString($table);
        $this->unsafeAppendSQLPart('rightjoin', "RIGHT JOIN $table");
        if ($alias) {
            $this->alias($alias);
        }
        return $this;
    }

    /**
     * Appends a OUTER JOIN `table` clause
     *
     * @see alias()
     * @param string $table
     *  The name of the table to act on, including the tbl prefix which will be changed
     *  to the Database table prefix.
     * @param string $alias
     *  An optional alias for the table. Defaults to null, i.e. no alias.
     * @return DatabaseQuery
     *  The current instance
     */
    public function outerJoin($table, $alias = null)
    {
        $table = $this->replaceTablePrefix($table);
        $table = $this->asTickedString($table);
        $this->unsafeAppendSQLPart('outerjoin', "OUTER JOIN $table");
        if ($alias) {
            $this->alias($alias);
        }
        return $this;
    }

    /**
     * Appends one an only one ON condition clause.
     *
     * @see DatabaseWhereDefinition::buildWhereClauseFromArray()
     * @param array $conditions
     *  The logical comparison conditions
     * @return DatabaseQuery
     *  The current instance
     */
    public function on(array $conditions)
    {
        $conditions = $this->buildWhereClauseFromArray($conditions);
        $this->unsafeAppendSQLPart('on', "ON $conditions");
        return $this;
    }

    /**
     * Appends one or multiple WHERE clauses.
     *
     * @see DatabaseWhereDefinition::buildWhereClauseFromArray()
     * @param array $conditions
     *  The logical comparison conditions
     * @return DatabaseQuery
     *  The current instance
     */
    public function where(array $conditions)
    {
        $where = $this->buildWhereClauseFromArray($conditions);
        $this->unsafeAppendSQLPart('where', "WHERE $where");
        return $this;
    }

    /**
     * Appends one or multiple ORDER BY clauses.
     *
     * @param string|array $cols
     *  The columns to order by. If the key is numeric, the value is used as the columns name.
     *  If not, the column key is used as the columns name, and the value is used as direction.
     * @param string $direction
     *  The default direction to use, for all columns that to not specify a sorting direction
     * @return DatabaseQuery
     *  The current instance
     */
    public function orderBy($cols, $direction = 'ASC')
    {
        $orders = [];
        if (!is_array($cols)) {
            $cols = [$cols => $direction];
        }
        foreach ($cols as $col => $dir) {
            // numeric index
            if (General::intval($col) !== -1) {
                // use value as the col name
                $col = $dir;
                $dir = null;
            }
            $dir = $dir ?: $direction;
            General::ensureType([
                'col' => ['var' => $col, 'type' => 'string'],
                'dir' => ['var' => $dir, 'type' => 'string'],
            ]);
            $col = $this->replaceTablePrefix($col);
            $col = $this->asTickedString($col);
            $orders[] = "$col $dir";
        }
        $orders = implode(self::LIST_DELIMITER, $orders);
        $this->unsafeAppendSQLPart('orderby', "ORDER BY $orders");
        return $this;
    }

    /**
     * Appends one or multiple GROUP BY clauses.
     *
     * @param string|array $columns
     *  The columns to group by on.
     * @return DatabaseQuery
     *  The current instance
     */
    public function groupBy($columns)
    {
        if (!is_array($columns)) {
            $columns = [$columns];
        }
        $group =  $this->asTickedList($columns);
        $this->unsafeAppendSQLPart('groupby', "GROUP BY $group");
        return $this;
    }

    /**
     * Appends one or multiple HAVING clauses.
     *
     * @see DatabaseWhereDefinition::buildWhereClauseFromArray()
     * @param array $conditions
     *  The logical comparison conditions
     * @return DatabaseQuery
     *  The current instance
     */
    public function having(array $conditions)
    {
        $where = $this->buildWhereClauseFromArray($conditions);
        $this->unsafeAppendSQLPart('having', "HAVING $where");
        return $this;
    }

    /**
     * Appends one and only one LIMIT clause.
     *
     * @param int $limit
     *  The maximum number of records to return
     * @return DatabaseQuery
     *  The current instance
     */
    public function limit($limit)
    {
        $limit = General::intval($limit);
        if ($limit === -1) {
            throw new DatabaseException("Invalid limit value: `$limit`");
        }
        $this->unsafeAppendSQLPart('limit', "LIMIT $limit");
        return $this;
    }

    /**
     * Appends one and only one OFFSET clause.
     *
     * @param int $offset
     *  The number at which to start returning results
     * @return DatabaseQuery
     *  The current instance
     */
    public function offset($offset)
    {
        $offset = General::intval($offset);
        if ($offset === -1) {
            throw new DatabaseException("Invalid offset value: `$offset`");
        }
        $this->unsafeAppendSQLPart('offset', "OFFSET $offset");
        return $this;
    }

    /**
     * Creates a specialized version of DatabaseStatementResult to hold
     * result from the current statement.
     *
     * @param bool $result
     * @param PDOStatement $st
     * @return DatabaseQueryResult
     *  The wrapped result
     */
    public function results($result, PDOStatement $stm)
    {
        General::ensureType([
            'result' => ['var' => $result, 'type' => 'bool'],
        ]);
        return new DatabaseQueryResult($result, $stm);
    }
}
