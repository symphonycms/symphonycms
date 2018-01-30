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
     * Returns the parts statement structure for this specialized statement.
     *
     * @return array
     */
    protected function getStatementStructure()
    {
        return [
            'statement',
            'cache',
            'optimizer',
            'projection',
            'table',
            'as',
            [
                'join',
                'inner join',
                'left join',
                'right join',
                'outer join',
            ],
            'where',
            'order by',
            'group by',
            'having',
            'limit',
            'offset',
        ];
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
        return new DatabaseQueryJoin($this, 'join', "JOIN $table", $alias);
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
        return new DatabaseQueryJoin($this, 'inner join', "INNER JOIN $table", $alias);
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
        return new DatabaseQueryJoin($this, 'left join', "LEFT JOIN $table", $alias);
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
        return new DatabaseQueryJoin($this, 'right join', "RIGHT JOIN $table", $alias);
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
        return new DatabaseQueryJoin($this, 'outer join', "OUTER JOIN $table", $alias);
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
        $op = $this->containsSQLParts('where') ? 'AND' : 'WHERE';
        $where = $this->buildWhereClauseFromArray($conditions);
        $this->unsafeAppendSQLPart('where', "$op $where");
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
        $this->unsafeAppendSQLPart('order by', "ORDER BY $orders");
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
        $this->unsafeAppendSQLPart('group by', "GROUP BY $group");
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

/**
 * Class responsible to hold all the data needed to create a JOIN x AS y ON z clause.
 * The data needs to be encapsulated until all the operations are completed.
 * Only then is it possible to append the part in the underlying DatabaseStatement.
 */
class DatabaseQueryJoin
{
    private $q;
    private $join;
    private $type;

    /**
     * Creates a new DatabaseQueryJoin object linked to the $q DatabaseQuery.
     *
     * @param DatabaseQuery $q
     * @param string $type
     * @param string $join
     * @param string $alias
     */
    public function __construct(DatabaseQuery $q, $type, $join, $alias = null)
    {
        $this->q = $q;
        $this->join = $join;
        $this->type = $type;
        if ($alias) {
            $this->alias($alias);
        }
    }

    public function alias($alias)
    {
        General::ensureType([
            'alias' => ['var' => $alias, 'type' => 'string'],
        ]);
        $alias = $this->q->asTickedString($alias);
        $this->join .= " AS $alias";
        return $this;
    }

    public function done()
    {
        $this->q->unsafeAppendSQLPart($this->type, $this->join);
        $q = $this->q;
        $this->q = null;
        return $q;
    }

    /**
     * Appends one an only one ON condition clause to the underlying DatabaseQuery.
     *
     * @see DatabaseWhereDefinition::buildWhereClauseFromArray()
     * @param array $conditions
     *  The logical comparison conditions
     * @return DatabaseQuery
     *  The current instance
     */
    public function on(array $conditions)
    {
        $conditions = $this->q->buildWhereClauseFromArray($conditions);
        $this->q->unsafeAppendSQLPart($this->type, $this->join . " ON $conditions");
        $q = $this->q;
        $this->q = null;
        return $q;
    }
}
