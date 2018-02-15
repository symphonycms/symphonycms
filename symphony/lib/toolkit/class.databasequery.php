<?php

/**
 * @package toolkit
 */

/**
 * This DatabaseStatement specialization class allows creation of SELECT FROM statements.
 */
class DatabaseQuery extends DatabaseStatement
{
    use DatabaseWhereDefinition;

    private $selectQueryCount = 0;

     /**
     * Creates a new DatabaseQuery statement on table $table, with and optional projection $values
     * and an optional optimizer value.
     *
     * @see Database::select()
     * @see Database::selectCount()
     * @param Database $db
     *  The underlying database connection
     * @param string $projection
     *  The columns names for include in the projection.
     *  Default to an empty projection
     */
    public function __construct(Database $db, array $projection = [])
    {
        parent::__construct($db, 'SELECT');
        $this->unsafeAppendSQLPart('cache', $db->isCachingEnabled() ? 'SQL_CACHE' : 'SQL_NO_CACHE');
        if (!empty($projection)) {
            $this->unsafeAppendSQLPart('projection', $this->asProjectionList($projection));
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
            'group by',
            'having',
            'order by',
            'limit',
            'offset',
        ];
    }

    /**
     * Add the DISTINCT optimizer to the query.
     *
     * @return DatabaseQuery
     *  The current instance
     */
    public function distinct()
    {
        if ($this->containsSQLParts('optimizer')) {
            throw new DatabaseSatementException('Cannot add multiple optimizer clauses');
        }
        return $this->unsafeAppendSQLPart('optimizer', 'DISTINCT');
    }

    /**
     * Appends values to the projection.
     *
     * @param string $projection
     *  The columns names for include in the projection
     * @return DatabaseQuery
     *  The current instance
     */
    public function projection(array $projection = [])
    {
        $op = $this->containsSQLParts('projection') ? ', ' : '';
        return $this->unsafeAppendSQLPart('projection', $op . $this->asProjectionList($projection));
    }

    /**
     * Appends a FROM `table` clause.
     * Can only be called once in the lifetime of the object.
     *
     * @see alias()
     * @throws DatabaseSatementException
     * @param string $table
     *  The name of the table to act on, including the tbl_ prefix, which will be changed
     *  to the Database table prefix.
     * @param string $alias
     *  An optional alias for the table. Defaults to null, i.e. no alias.
     * @return DatabaseQuery
     *  The current instance
     */
    public function from($table, $alias = null)
    {
        if ($this->containsSQLParts('table')) {
            throw new DatabaseSatementException('DatabaseQuery can not hold more than one table clause');
        }
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
     * Can only be called once in the lifetime of the object.
     *
     * @throws DatabaseSatementException
     * @param string $alias
     *  The name of the alias
     * @return DatabaseQuery
     *  The current instance
     */
    public function alias($alias)
    {
        if ($this->containsSQLParts('as')) {
            throw new DatabaseSatementException('DatabaseQuery can not hold more than one as clause');
        }
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
     * @param string $table
     *  The name of the table to act on, including the tbl prefix which will be changed
     *  to the Database table prefix.
     * @param string $alias
     *  An optional alias for the table. Defaults to null, i.e. no alias.
     * @return DatabaseQueryJoin
     *  A new instance of DatabaseQueryJoin linked to the current DatabaseQuery instance
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
     * @param string $table
     *  The name of the table to act on, including the tbl prefix which will be changed
     *  to the Database table prefix.
     * @param string $alias
     *  An optional alias for the table. Defaults to null, i.e. no alias.
     * @return DatabaseQueryJoin
     *  A new instance of DatabaseQueryJoin linked to the current DatabaseQuery instance
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
     * @param string $table
     *  The name of the table to act on, including the tbl prefix which will be changed
     *  to the Database table prefix.
     * @param string $alias
     *  An optional alias for the table. Defaults to null, i.e. no alias.
     * @return DatabaseQueryJoin
     *  A new instance of DatabaseQueryJoin linked to the current DatabaseQuery instance
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
     * @param string $table
     *  The name of the table to act on, including the tbl prefix which will be changed
     *  to the Database table prefix.
     * @param string $alias
     *  An optional alias for the table. Defaults to null, i.e. no alias.
     * @return DatabaseQueryJoin
     *  A new instance of DatabaseQueryJoin linked to the current DatabaseQuery instance
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
     * @param string $table
     *  The name of the table to act on, including the tbl prefix which will be changed
     *  to the Database table prefix.
     * @param string $alias
     *  An optional alias for the table. Defaults to null, i.e. no alias.
     * @return DatabaseQueryJoin
     *  A new instance of DatabaseQueryJoin linked to the current DatabaseQuery instance
     */
    public function outerJoin($table, $alias = null)
    {
        $table = $this->replaceTablePrefix($table);
        $table = $this->asTickedString($table);
        return new DatabaseQueryJoin($this, 'outer join', "OUTER JOIN $table", $alias);
    }

    /**
     * Appends one or multiple WHERE clauses.
     * Calling this method multiple times will join the WHERE clauses with a AND.
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
            if ($col === 'RAND()') {
                $orders[] = 'RAND()';
                continue;
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
        $op = $this->containsSQLParts('order by') ? ',' : 'ORDER BY';
        $this->unsafeAppendSQLPart('order by', "$op $orders");
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
        $group = $this->asTickedList($columns);
        $op = $this->containsSQLParts('group by') ? ',' : 'GROUP BY';
        $this->unsafeAppendSQLPart('group by', "$op $group");
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
        $op = $this->containsSQLParts('having') ? 'AND' : 'HAVING';
        $where = $this->buildWhereClauseFromArray($conditions);
        $this->unsafeAppendSQLPart('having', "$op $where");
        return $this;
    }

    /**
     * Appends one and only one LIMIT clause.
     * Can only be called once in the lifetime of the object.
     *
     * @throws DatabaseSatementException
     * @param int $limit
     *  The maximum number of records to return
     * @return DatabaseQuery
     *  The current instance
     */
    public function limit($limit)
    {
        if ($this->containsSQLParts('limit')) {
            throw new DatabaseSatementException('DatabaseQuery can not hold more than one limit clause');
        }
        $limit = General::intval($limit);
        if ($limit === -1) {
            throw new DatabaseSatementException("Invalid limit value: `$limit`");
        }
        $this->unsafeAppendSQLPart('limit', "LIMIT $limit");
        return $this;
    }

    /**
     * Appends one and only one OFFSET clause.
     * Can only be called once in the lifetime of the object.
     *
     * @throws DatabaseSatementException
     * @param int $offset
     *  The number at which to start returning results
     * @return DatabaseQuery
     *  The current instance
     */
    public function offset($offset)
    {
        if ($this->containsSQLParts('offset')) {
            throw new DatabaseSatementException('DatabaseQuery can not hold more than one offset clause');
        }
        $offset = General::intval($offset);
        if ($offset === -1) {
            throw new DatabaseSatementException("Invalid offset value: `$offset`");
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

    /**
     * Factory method that creates a new `SELECT ...` statement to be
     * safely used inside the current instance of DatabaseQuery.
     *
     * @param array $values
     *  The columns to select. By default, it's `*`.
     * @return DatabaseSubQuery
     */
    public function select(array $values = ['*'])
    {
        $this->selectQueryCount++;
        return new DatabaseSubQuery($this->getDB(), $this->selectQueryCount, $values);
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
    private $alias;

    /**
     * Creates a new DatabaseQueryJoin object linked to the $q DatabaseQuery.
     *
     * @param DatabaseQuery $q
     * @param string $type
     *  The SQL part type to be added
     * @param string $join
     *  The requested JOIN syntax
     * @param string $alias
     *  An optional alias for the joined table
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

    /**
     * Appends a AS `alias` clause.
     * Can only be called once in the lifetime of the object.
     *
     * @throws DatabaseSatementException
     * @param string $alias
     *  The name of the alias
     * @return DatabaseQueryJoin
     *  The current instance
     */
    public function alias($alias)
    {
        if ($this->alias) {
            throw new DatabaseSatementException('DatabaseQueryJoin can not hold more than one as clause');
        }
        General::ensureType([
            'alias' => ['var' => $alias, 'type' => 'string'],
        ]);
        $alias = $this->q->asTickedString($alias);
        $this->alias = " AS $alias";
        return $this;
    }

    /**
     * Appends one an only one ON condition clause to the underlying DatabaseQuery.
     * Can only be called once in the lifetime of the object.
     *
     * @see DatabaseWhereDefinition::buildWhereClauseFromArray()
     * @param array $conditions
     *  The logical comparison conditions
     * @return DatabaseQuery
     *  The underlying DatabaseQuery instance
     */
    public function on(array $conditions)
    {
        $conditions = $this->q->buildWhereClauseFromArray($conditions);
        $this->q->unsafeAppendSQLPart($this->type, "{$this->join}{$this->alias} ON $conditions");
        $q = $this->q;
        $this->q = null;
        return $q;
    }
}

class DatabaseSubQuery extends DatabaseQuery
{
    private $id;

    public function __construct(Database $db, $id, array $values = [])
    {
        General::ensureType([
            'id' => ['var' => $id, 'type' => 'integer'],
        ]);
        parent::__construct($db, $values);
        $this->id = $id;
    }

    /**
     * @internal
     * Formats the given $parameter name to be used as SQL parameter.
     * In this context, the $id of the sub query is prepended to the actual parameter name.
     *
     * @param string $parameter
     *  The parameter name
     * @return string
     *  The formatted parameter name
     */
    protected function formatParameterName($parameter)
    {
        return "i{$this->id}_{$parameter}";
    }
}
