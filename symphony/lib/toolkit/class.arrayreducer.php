<?php
/**
 * @package toolkit
 */
/**
 * The ArrayReducer class encapsulates an array of arrays or objects.
 * It provides a friendly API for commonly used extraction patterns, like getting
 * the value of a specific field for all or some rows, or indexing/grouping records.
 *
 * @since Symphony 3.0.0
 */
class ArrayReducer
{
    /**
     * Internal array that will get reduced
     * @var array
     */
    private $rows = [];

    /**
     * Flag for associative array syntax
     * @var boolean
     */
    private $assoc = true;

    /**
     * Constructs a new ArrayReducer that will be able to act on the $rows array.
     *
     * @param array $rows
     *  The array of arrays or objects on which to act
     * @param boolean $assoc
     *  Use the array syntax when true, object when false. Defaults to true.
     */
    public function __construct(array $rows, $assoc = true)
    {
        $this->rows = $rows;
        $this->assoc = $assoc;
    }

    /**
     * Resets the pointer of the internal array
     *
     * @return void
     */
    public function reset()
    {
        reset($this->rows);
    }

    /**
     * Retrieves all values for the specified $col index.
     *
     * @param string|int $col
     * @throws Exception
     * @return array
     *  An array containing all the values for the specified $col index
     */
    public function column($col)
    {
        if (!is_string($col) && !is_int($col)) {
            throw new Exception('`$col must be a string or an integer');
        }
        $rows = [];
        foreach ($this->rows as $row) {
            if (!$this->assoc) {
                $rows[] = isset($row->{$col}) ? $row->{$col} : null;
            } else {
                if (is_int($col)) {
                    $row = array_values($row);
                }
                $rows[] = isset($row[$col]) ? $row[$col] : null;
            }
        }
        return $rows;
    }

    /**
     * Retrieves all available rows, indexed with the values of the
     * specified column.
     *
     * @param string|int $col
     * @throws Exception
     * @return array
     *  An array of rows containing all the values indexed by the specified column
     */
    public function rowsIndexedByColumn($col)
    {
        if (!is_string($col) && !is_int($col)) {
            throw new Exception('`$col must be a string or an integer');
        }
        $index = [];
        foreach ($this->rows as &$row) {
            if (is_int($col)) {
                $row = array_values($row);
            }
            if (!isset($row[$col])) {
                throw new Exception("Row does not have column `$col`");
            }
            if (isset($index[$row[$col]])) {
                throw new Exception("Index `$col` is not unique, can not continue");
            }
            $index[$row[$col]] = $row;
        }
        return $index;
    }

    /**
     * Retrieves all available rows, grouped with the values of the
     * specified column.
     *
     * @param string|int $col
     * @throws Exception
     * @return array
     *  An array of arrays containing all the values grouped by the specified column
     */
    public function rowsGroupedByColumn($col)
    {
        if (!is_string($col) && !is_int($col)) {
            throw new Exception('`$col must be a string or an integer');
        }
        $index = [];
        foreach ($this->rows as &$row) {
            if (is_int($col)) {
                $row = array_values($row);
            }
            if (!isset($row[$col])) {
                throw new Exception("Row does not have column `$col`");
            }
            $index[$row[$col]][] = $row;
        }
        return $index;
    }

    /**
     * Retrieve the value of the specified column in the next available record.
     * Note: this method can return null even if there are more records available.
     *
     * @param string|int $col
     * @throws Exception
     * @return mixed
     *  The value of the column
     */
    public function variable($col)
    {
        if (!is_string($col) && !is_int($col)) {
            throw new Exception('`$col must be a string or an integer');
        }
        $row = current($this->rows);
        if ($row) {
            next($this->rows);
            if (!$this->assoc) {
                if (is_int($col)) {
                    throw new DatabaseStatementException('`$col must be a string when using objects');
                }
                return isset($row->{$col}) ? $row->{$col} : null;
            } else {
                if (is_int($col)) {
                    if (!is_array($row)) {
                        $row = $row->get();
                    }
                    $row = array_values($row);
                }
                return isset($row[$col]) ? $row[$col] : null;
            }
        }
        return null;
    }

    /**
     * int returning version of variable()
     *
     * @see variable()
     * @param string|int $col
     * @throws Exception
     * @return int
     *  The value of the column
     */
    public function integer($col)
    {
        return (int) $this->variable($col);
    }

    /**
     * float returning version of variable()
     *
     * @see variable()
     * @param string|int $col
     * @throws Exception
     * @return float
     *  The value of the column
     */
    public function float($col)
    {
        return (float) $this->variable($col);
    }

    /**
     * float returning version of variable()
     * If it is a bool, returns it as is.
     * If is is a string, checks for 'yes', 'true' and '1'.
     * If it is an int, returns true is it is not equal to 0.
     * Otherwise, returns false.
     *
     * @see variable()
     * @param string|int $col
     * @throws Exception
     * @return bool
     *  The value of the column
     */
    public function boolean($col)
    {
        $v = $this->variable($col);
        if (is_bool($v)) {
            return $v;
        } elseif (is_string($v)) {
            $v = strtolower($v);
            return in_array($v, ['yes', 'true', '1']);
        } elseif (is_int($v)) {
            return $v !== 0;
        }
        return false;
    }

    /**
     * string returning version of variable()
     *
     * @see variable()
     * @param string|int $col
     * @throws DatabaseStatementException
     * @return string
     *  The value of the column
     */
    public function string($col)
    {
        return (string) $this->variable($col);
    }
}
