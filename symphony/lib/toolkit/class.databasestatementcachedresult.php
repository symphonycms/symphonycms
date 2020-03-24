<?php
/**
 * @package toolkit
 */
/**
 * The DatabaseStatementCachedResult class is a wrapper for DatabaseStatementResult
 * that will cached data if they are available or forwards the call to the underlying
 * result object.
 * It currently offers almost the same API as the DatabaseTabularResult class.
 *
 * @since Symphony 3.0.0
 */
class DatabaseStatementCachedResult
{
    /**
     * The underlying DatabaseStatementResult, if any
     * @var DatabaseStatementResult
     */
    private $result;

    /**
     * The underlying DatabaseCache instance
     * @var DatabaseCache
     */
    private $cache;

    /**
     * The DatabaseStatement's hash key
     * @var string
     */
    private $stmKey;

    /**
     * The current cursor position
     * @var integer
     */
    private $position = -1;

    /**
     * Creates a new instance of a DatabaseStatementCachedResult that contains either
     * a fresh DatabaseStatementResult or values from a DatabaseCache instance.
     *
     * @param DatabaseStatementResult $result
     * @param DatabaseCache $cache
     * @param string $stmKey
     */
    public function __construct($result, $cache, $stmKey)
    {
        General::ensureType([
            'stmKey' => ['var' => $stmKey, 'type' => 'string'],
        ]);
        $this->result = $result;
        $this->cache = $cache;
        $this->stmKey = $stmKey;
    }

    /**
     * Retrieves the record at the current offset, if available.
     * It also advances the current offset in the specified orientation.
     * The record will be either an array or an object depending on the specified type.
     *
     * @uses DatabaseTabularResult::next()
     * @throws DatabaseStatementException
     * @return array|object
     *  The next available record.
     *  null if there are no more available record or an error happened.
     *  After the first null return, the cursor is marked as EOF.
     *  Subsequent calls will throw an exception.
     */
    public function next()
    {
        if ($this->result) {
            $next = $this->result->next();
            if ($next) {
                $this->cache->append($this->stmKey, $next);
            }
            return $next;
        }
        $rows = $this->cache->get($this->stmKey);
        return $rows[++$this->position];
    }

    /**
     * Retrieves all remaining rows.
     *
     * @uses next()
     * @uses DatabaseTabularResult::remainingRows()
     * @throws DatabaseStatementException
     * @return array
     *  An array of objects or arrays
     */
    public function remainingRows()
    {
        if ($this->result) {
            $remainingRows = $this->result->remainingRows();
            $this->cache->appendAll($this->stmKey, $remainingRows);
            return $remainingRows;
        }
        $rows = $this->cache->get($this->stmKey);
        $remain = [];
        while (isset($rows[++$this->position]) && $next = $rows[$this->position]) {
            $remain[] = $next;
        }
        return $remain;
    }

    /**
     * Retrieves all rows, by making sure no records were read prior to this call.
     *
     * @uses remainingRows()
     * @throws DatabaseStatementException
     * @return array
     *  An array of objects or arrays
     */
    public function rows()
    {
        if ($this->position !== -1) {
            $consumed = $this->position + 1;
            throw new DatabaseStatementException("Can not retrieve all rows, $consumed were already consumed");
        }
        return $this->remainingRows();
    }

    /**
     * Retrieves the number of available columns in each record.
     * If the cache is being used, results may not be accurate in the case
     * the result if empty or if data are already consumed.
     *
     * @throws DatabaseStatementException
     * @return int
     *  The number of available columns
     */
    public function columnCount()
    {
        if ($this->result) {
            if (!is_callable([$this->result, 'columnCount'])) {
                throw new DatabaseStatementException(
                    'columnCount is not implemented for this underlying DatabaseStatementResult'
                );
            }
            return $this->result->columnCount();
        }
        $rows = $this->cache->get($this->stmKey);
        if (empty($rows)) {
            return 0;
        }
        return count(array_values(current($rows)));
    }

    /**
     * Creates a new reducer for all remaining rows.
     *
     * @uses remainingRows()
     * @throws DatabaseStatementException
     * @return ArrayReducer
     *  A newly created ArrayReducer object
     */
    public function reducer()
    {
        return new ArrayReducer(
            $this->remainingRows()
        );
    }

    /**
     * Retrieves all values for all rows for the specified column.
     *
     * @uses ArrayReducer::column
     * @param string|int $col
     * @throws DatabaseStatementException
     * @throws Exception
     * @return array
     *  An array containing all the values for the specified column
     */
    public function column($col)
    {
        return $this->reducer()->column($col);
    }

    /**
     * Retrieves all available rows, indexed with the values of the
     * specified column. The value of the column must be unique.
     *
     * @uses ArrayReducer::rowsIndexedByColumn
     * @param string|int $col
     * @throws DatabaseStatementException
     * @throws Exception
     * @return array
     *  An array of rows containing all the values indexed by the specified column
     */
    public function rowsIndexedByColumn($col)
    {
        return $this->reducer()->rowsIndexedByColumn($col);
    }

    /**
     * Retrieves all available rows, grouped with the values of the
     * specified column.
     *
     * @uses ArrayReducer::rowsGroupedByColumn
     * @param string|int $col
     * @throws DatabaseStatementException
     * @throws Exception
     * @return array
     *  An array of arrays containing all the values grouped by the specified column
     */
    public function rowsGroupedByColumn($col)
    {
        return $this->reducer()->rowsGroupedByColumn($col);
    }

    /**
     * Retrieve the value of the specified column in the next available record.
     * Note: this method can return null even if there are more records.
     *
     * @uses ArrayReducer::variable
     * @param string|int $col
     * @throws DatabaseStatementException
     * @throws Exception
     * @return mixed
     *  The value of the column
     */
    public function variable($col)
    {
        return $this->reducer()->variable($col);
    }

    /**
     * int returning version of variable()
     *
     * @uses ArrayReducer::integer
     * @param string|int $col
     * @throws DatabaseStatementException
     * @throws Exception
     * @return int
     *  The value of the column
     */
    public function integer($col)
    {
        return $this->reducer()->integer($col);
    }

    /**
     * float returning version of variable()
     *
     * @uses ArrayReducer::float
     * @param string|int $col
     * @throws DatabaseStatementException
     * @throws Exception
     * @return float
     *  The value of the column
     */
    public function float($col)
    {
        return $this->reducer()->float($col);
    }

    /**
     * float returning version of variable()
     * If it is a bool, returns it as is.
     * If is is a string, checks for 'yes', 'true' and '1'.
     * If it is an int, returns true is it is not equal to 0.
     * Otherwise, returns false.
     *
     * @uses ArrayReducer::boolean
     * @see variable()
     * @param string|int $col
     * @throws DatabaseStatementException
     * @throws Exception
     * @return bool
     *  The value of the column
     */
    public function boolean($col)
    {
        return $this->reducer()->boolean($col);
    }

    /**
     * string returning version of variable()
     *
     * @uses ArrayReducer::string
     * @see variable()
     * @param string|int $col
     * @throws DatabaseStatementException
     * @throws Exception
     * @return string
     *  The value of the column
     */
    public function string($col)
    {
        return $this->reducer()->string($col);
    }
}
