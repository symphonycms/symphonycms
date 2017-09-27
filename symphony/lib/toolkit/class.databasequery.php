<?php

/**
 * @package toolkit
 */

/**
 * Class that generates SELECT SQL statements
 */
final class DatabaseQuery extends DatabaseStatement
{
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

    public function alias($alias)
    {
        General::ensureType([
            'alias' => ['var' => $alias, 'type' => 'string'],
        ]);
        $alias = $this->asTickedString($alias);
        $this->unsafeAppendSQLPart('as', "AS $alias");
        return $this;
    }

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

    public function on(array $conditions)
    {
        $conditions = $this->buildWhereClauseFromArray($conditions);
        $this->unsafeAppendSQLPart('on', "ON $conditions");
        return $this;
    }

    public function where(array $conditions)
    {
        $where = $this->buildWhereClauseFromArray($conditions);
        $this->unsafeAppendSQLPart('where', "WHERE $where");
        return $this;
    }

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

    public function groupBy($columns)
    {
        if (!is_array($columns)) {
            $columns = [$columns];
        }
        $group =  $this->asTickedList($columns);
        $this->unsafeAppendSQLPart('groupby', "GROUP BY $group");
        return $this;
    }

    public function having(array $conditions)
    {
        $where = $this->buildWhereClauseFromArray($conditions);
        $this->unsafeAppendSQLPart('having', "HAVING $where");
        return $this;
    }

    public function limit($limit)
    {
        $limit = General::intval($limit);
        $this->unsafeAppendSQLPart('limit', "LIMIT $limit");
        return $this;
    }

    public function offset($offset)
    {
        $offset = General::intval($offset);
        $this->unsafeAppendSQLPart('offset', "OFFSET $offset");
        return $this;
    }

    public function results($result, PDOStatement $stm)
    {
        General::ensureType([
            'result' => ['var' => $result, 'type' => 'boolean'],
        ]);
        return new DatabaseQueryResult($result, $stm);
    }
}
