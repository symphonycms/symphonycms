<?php

/**
 * @package toolkit
 */
/**
 * This class hold the data created by the execution of a specialized DatabaseStatement classes
 * that returns tabular data.
 * It implements the IteratorAggregate interface but also provide its own API with more control
 * built in.
 */
class DatabaseTabularResult extends DatabaseStatementResult implements IteratorAggregate
{
    /**
     * The read offset.
     * @var int
     */
    private $offset = 0;

    /**
     * The type of variable that should be returned.
     * @var int
     */
    private $type = PDO::FETCH_ASSOC;

    /**
     * The orientation of the offset.
     * @var int
     */
    private $orientation = PDO::FETCH_ORI_NEXT;

    /**
     * The requested pagination.
     * @var array
     */
    private $page;

    /**
     * Implements the IteratorAggregate getIterator function by delegating it to
     * the PDOStatement.
     *
     * @return Traversable
     */
    public function getIterator()
    {
        return $this->statement();
    }

    /**
     * Sets the offset value
     *
     * @param int $offset
     *  A positive number by which to limit the number of results
     * @return DatabaseTabularResult
     *  The current instance
     */
    public function offset($offset)
    {
        General::ensureType([
            'offset' => ['var' => $offset, 'type' => 'int'],
        ]);
        $this->offset = $offset;
        return $this;
    }

    /**
     * Sets the type of the returned structure value.
     *
     * @param int $type
     *  The type to use
     *  Either PDO::FETCH_ASSOC or PDO::FETCH_OBJ
     * @throws DatabaseStatementException
     * @return DatabaseTabularResult
     *  The current instance
     */
    public function type($type)
    {
        General::ensureType([
            'type' => ['var' => $type, 'type' => 'int'],
        ]);
        if ($type !== PDO::FETCH_ASSOC && $type !== PDO::FETCH_OBJ) {
            throw new DatabaseStatementException('Invalid fetch type');
        }
        $this->type = $type;
        return $this;
    }

    /**
     * Sets the orientation value, which controls the way the offset is applied.
     *
     * @param int $orientation
     *  The orientation value to use.
     *  Either PDO::FETCH_ORI_NEXT or PDO::FETCH_ORI_ABS
     * @throws DatabaseStatementException
     * @return DatabaseTabularResult
     *  The current instance
     */
    public function orientation($orientation)
    {
        General::ensureType([
            'orientation' => ['var' => $orientation, 'type' => 'int'],
        ]);
        if ($type !== PDO::FETCH_ORI_NEXT && $type !== PDO::FETCH_ORI_ABS) {
            throw new DatabaseStatementException('Invalid orientation type');
        }
        $this->type = $type;
        return $this;
    }

    /**
     * Retrieves the record at the current offset, if available.
     * It also advances the current offset in the specified orientation.
     * The record will be either an array or an object depending on the specified type.
     *
     * @see type()
     * @see orientation()
     * @see offset()
     * @return array|object
     *  The next available record.
     *  null if there are no more available record or an error happened.
     */
    public function next()
    {
        $next = $this->statement()->fetch(
            $this->type,
            $this->orientation,
            $this->offset
        );
        return $next === false ? null : $next;
    }

    /**
     * Retrieves all available rows.
     *
     * @see type()
     * @see orientation()
     * @see offset()
     * @return array
     *  An array of objects or arrays
     */
    public function rows()
    {
        $rows = [];
        while ($row = $this->next()) {
            $rows[] = $row;
        }
        return $rows;
    }

    /**
     * Retrieves all values for the specified column.
     *
     * @param string|int $col
     * @throws DatabaseStatementException
     * @return array
     *  An array containing all the values for the specified column
     */
    public function column($col)
    {
        if (!is_string($col) && !is_int($col)) {
            throw new DatabaseStatementException('`$col must be a string or an integer');
        }
        $rows = [];
        while ($row = $this->next()) {
            if ($this->type === PDO::FETCH_OBJ) {
                $rows[] = $row->{$col};
            } else {
                if (is_int($col)) {
                    $row = array_values($row);
                }
                $rows[] = $row[$col];
            }
        }
        return $rows;
    }

    /**
     * Retrieves all available rows, indexed with the values of the
     * specified column.
     *
     * @param string|int $col
     * @throws DatabaseStatementException
     * @return array
     *  An array containing all the values indexed by the specified column
     */
    public function rowsIndexedByColumn($col)
    {
        if (!is_string($col) && !is_int($col)) {
            throw new DatabaseStatementException('`$col must be a string or an integer');
        }
        $rows = $this->rows();
        $index = [];
        foreach ($rows as &$row) {
            if (is_int($col)) {
                $row = array_values($row);
            }
            if (!isset($row[$col])) {
                throw new DatabaseStatementException("Row does not have column `$col`");
            }
            $index[$row[$col]] = $row;
        }
        return $index;
    }

    /**
     * Retrieves the number of available columns in each record.
     *
     * @return int
     *  The number of available columns
     */
    public function columnCount()
    {
        $this->statement()->columnCount();
    }

    /**
     * Retrieve the value of the specified column in the next available record.
     * Note: this method can return null even if there are more records.
     *
     * @see type()
     * @see orientation()
     * @see offset()
     * @param string|int $col
     * @throws DatabaseStatementException
     * @return mixed
     *  The value of the column
     */
    public function variable($col)
    {
        if (!is_string($col) && !is_int($col)) {
            throw new DatabaseStatementException('`$col must be a string or an integer');
        }
        if ($row = $this->next()) {
            if ($this->type === PDO::FETCH_OBJ) {
                if (is_int($col)) {
                    throw new DatabaseStatementException('`$col must be a string when using objects');
                }
                return $row->{$col};
            } else {
                if (is_int($col)) {
                    if (!is_array($row)) {
                        $row = $row->get();
                    }
                    $row = array_values($row);
                }
                return $row[$col];
            }
        }
        return null;
    }

    /**
     * int returning version of variable()
     *
     * @see variable()
     * @param string|int $col
     * @throws DatabaseStatementException
     * @return int
     *  The value of the column
     */
    public function integer($col)
    {
        return (int)$this->variable($col);
    }

    /**
     * float returning version of variable()
     *
     * @see variable()
     * @param string|int $col
     * @throws DatabaseStatementException
     * @return float
     *  The value of the column
     */
    public function float($col)
    {
        return (float)$this->variable($col);
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
     * @throws DatabaseStatementException
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
        return (string)$this->variable($col);
    }
}
