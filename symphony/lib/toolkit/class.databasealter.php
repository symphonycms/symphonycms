<?php

/**
 * @package toolkit
 */

final class DatabaseAlter extends DatabaseStatement
{
    private $collate;

    public function __construct(Database $db, $table, $optimizer = null)
    {
        parent::__construct($db, 'ALTER TABLE');
        $table = $this->replaceTablePrefix($table);
        $table = $this->asTickedString($table);
        $this->unsafeAppendSQLPart('table', $table);
    }

    public function collate($charset, $default = true)
    {
        $this->collate = $collate;
        return $this;
    }

    protected function getOption(array $options, $key)
    {
        return (isset($options[$key]) && !empty($options[$key]) ? $options[$key] : $this->{$key});
    }

    public function first()
    {
        $this->unsafeAppendSQLPart('first', "FIRST");
        return $this;
    }

    public function after($column)
    {
        General::ensureType([
            'column' => ['var' => $column, 'type' => 'string'],
        ]);
        $column = $this->asTickedString($column);
        $this->unsafeAppendSQLPart('after', "AFTER $column");
        return $this;
    }

    public function add(array $columns)
    {
        $columns = implode(self::LIST_DELIMITER, General::array_map(function ($k, $column) {
            $column = $this->buildColumnDefinitionFromArray($k, $column);
            return "ADD COLUMN $column";
        }, $columns));
        $this->unsafeAppendSQLPart('add columns', $columns);
        return $this;
    }

    public function drop($columns)
    {
        if (!is_array($columns)) {
            $columns = [$columns];
        }
        $columns = implode(self::LIST_DELIMITER, array_map(function ($column) {
            $column = $this->asTickedString($column);
            return "DROP COLUMN $column";
        }, $columns));
        $this->unsafeAppendSQLPart('drop columns', $columns);
        return $this;
    }

    public function change($old_columns, array $new_columns)
    {
        if (!is_array($old_columns)) {
            $old_columns = [$old_columns];
        }
        $new_columns_keys = array_keys($new_columns);
        $columns = implode(self::LIST_DELIMITER, General::array_map(function ($index, $column) use ($new_columns_keys, $new_columns) {
            $old_column = $this->asTickedString($column);
            $new_column = $this->buildColumnDefinitionFromArray(
                $new_columns_keys[$index],
                $new_columns[$new_columns_keys[$index]]
            );
            return "CHANGE COLUMN $old_column $new_column";
        }, $old_columns));
        $this->unsafeAppendSQLPart('change columns', $columns);
        return $this;
    }

    public function addKey($keys)
    {
        if (!is_array($keys)) {
            $keys = [$keys => 'key'];
        }
        $keys = implode(self::LIST_DELIMITER, General::array_map(function ($k, $column) {
            $key = $this->buildKeyDefinitionFromArray($k, $column);
            return "ADD $key";
        }, $keys));
        $this->unsafeAppendSQLPart('add key', $keys);
        return $this;
    }

    public function dropKey($keys)
    {
        if (!is_array($keys)) {
            $keys = [$keys];
        }
        $keys = implode(self::LIST_DELIMITER, array_map(function ($key) {
            $key = $this->asTickedString($key);
            return "DROP KEY $key";
        }, $keys));
        $this->unsafeAppendSQLPart('drop key', $keys);
        return $this;
    }

    public function addIndex($keys)
    {
        if (!is_array($keys)) {
            $keys = [$keys => 'index'];
        }
        $keys = implode(self::LIST_DELIMITER, General::array_map(function ($k, $column) {
            $key = $this->buildKeyDefinitionFromArray($k, $column);
            return "ADD $key";
        }, $keys));
        $this->unsafeAppendSQLPart('add index', $keys);
        return $this;
    }

    public function dropIndex($keys)
    {
        if (!is_array($keys)) {
            $keys = [$keys];
        }
        $keys = implode(self::LIST_DELIMITER, array_map(function ($key) {
            $key = $this->asTickedString($key);
            return "DROP INDEX $key";
        }, $keys));
        $this->unsafeAppendSQLPart('drop index', $keys);
        return $this;
    }

    public function addPrimaryKey($keys)
    {
        if (!is_array($keys)) {
            $keys = [$keys => 'primary'];
        }
        $keys = implode(self::LIST_DELIMITER, General::array_map(function ($k, $column) {
            $key = $this->buildKeyDefinitionFromArray($k, $column);
            return "ADD $key";
        }, $keys));
        $this->unsafeAppendSQLPart('add primary key', $keys);
        return $this;
    }

    public function dropPrimaryKey()
    {
        $this->unsafeAppendSQLPart('drop primary key', 'DROP PRIMARY KEY');
        return $this;
    }

}
