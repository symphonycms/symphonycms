<?php

/**
 * @package toolkit
 */

/**
 * This class hold the data created by the execution of a specialized DatabaseStatement class.
 * Statements that needs this class returns tabular data.
 * It implements the IteratorAggregate interface but also provide its own API with more control
 * built in.
 */
final class DatabaseQueryResult extends DatabaseStatementResult implements IteratorAggregate
{
    /**
     * The read offset.
     *
     * @var int
     */
    private $offset = 0;

    /**
     * The type of variable that should be returned.
     *
     * @var int
     */
    private $type = PDO::FETCH_ASSOC;

    /**
     * The orientation of the offset.
     *
     * @var int
     */
    private $orientation = PDO::FETCH_ORI_NEXT;

    /**
     * Implements the IteratorAggregate getIterator function by delegating it to
     * the PDOStatement.
     *
     * @return Traversable
     */
    public function getIterator()
    {
        return $this->stm;
    }

    /**
     * Sets the offset value
     *
     * @param int $offset
     *  A positive number by which to limit the number of results
     * @return DatabaseQueryResult
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
     * @throws DatabaseSatementException
     * @return DatabaseQueryResult
     *  The current instance
     */
    public function type($type)
    {
        General::ensureType([
            'type' => ['var' => $type, 'type' => 'int'],
        ]);
        if ($type !== PDO::FETCH_ASSOC && $type !== PDO::FETCH_OBJ) {
            throw new DatabaseSatementException('Invalid fetch type');
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
     * @throws DatabaseSatementException
     * @return DatabaseQueryResult
     *  The current instance
     */
    public function orientation($orientation)
    {
        General::ensureType([
            'orientation' => ['var' => $orientation, 'type' => 'int'],
        ]);
        if ($type !== PDO::FETCH_ORI_NEXT && $type !== PDO::FETCH_ORI_ABS) {
            throw new DatabaseSatementException('Invalid orientation type');
        }
        $this->type = $type;
        return $this;
    }

    /**
     * Retrieves the the next available record.
     *
     * @see type()
     * @see orientation()
     * @see offset()
     * @return array|object
     *  The next available record.
     *  null if there are not more available records.
     */
    public function next()
    {
        return $this->statement()->fetch(
            $this->type,
            $this->orientation,
            $this->offset
        );
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
     * Retrieves the number of available records.
     *
     * @return int
     *  The number of available records
     */
    public function rowCount()
    {
        return $this->statement()->rowCount();
    }

    /**
     * Retrieves all values for the specified column.
     *
     * @param string|int $col
     * @throws DatabaseSatementException
     * @return array
     *  An array containing all the values for the specified column
     */
    public function column($col)
    {
        if (!is_string($col) && !is_int($col)) {
            throw new DatabaseSatementException('`$col must be a string or an integer');
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
     * @throws DatabaseSatementException
     * @return array
     *  An array containing all the values indexed by the specified column
     */
    public function rowsIndexedByColumn($col)
    {
        if (!is_string($col) && !is_int($col)) {
            throw new DatabaseSatementException('`$col must be a string or an integer');
        }
        $rows = $this->rows();
        $index = [];
        foreach ($rows as &$row) {
            if (is_int($col)) {
                $row = array_values($row);
            }
            if (!isset($row[$col])) {
                throw new DatabaseSatementException("Row does not have column `$col`");
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
     * @throws DatabaseSatementException
     * @return mixed
     *  The value of the column
     */
    public function variable($col)
    {
        if (!is_string($col) && !is_int($col)) {
            throw new DatabaseSatementException('`$col must be a string or an integer');
        }
        if ($row = $this->next()) {
            if ($this->type === PDO::FETCH_OBJ) {
                return $row->{$col};
            } else {
                if (is_int($col)) {
                    $row = array_values($row);
                }
                return $row[$col];
            }
        }
        return null;
    }
}
