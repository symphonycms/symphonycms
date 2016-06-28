<?php

/**
 * @package toolkit
 */
Class Database {

    /**
     * Constant to indicate whether the query is a write operation.
     *
     * @var integer
     */
    const __WRITE_OPERATION__ = 0;

    /**
     * Constant to indicate whether the query is a write operation
     *
     * @var integer
     */
    const __READ_OPERATION__ = 1;

    /**
     * An instance of the current PDO object
     * @var PDO
     */
    public $conn = null;

    /**
     * Sets the current `$_log` to be an empty array
     *
     * @var array
     */
    public $log = array();

    /**
     * The number of queries this class has executed, defaults to 0.
     *
     * @var integer
     */
    protected $_query_count = 0;

    /**
     * The table prefix for this connection. Queries to be written using
     * a `tbl_table_name` syntax, where `tbl_` will be replaced by this
     * variable. By default it is `sym_` but it configured in the configuration
     *
     * @var string
     */
    protected $_prefix = 'sym_';

    /**
     * Whether query caching is enabled or not. By default this set
     * to true which will use SQL_CACHE to cache the results of queries
     *
     * @var boolean
     */
    protected $_cache = true;

    /**
     * Whether to log this query in the internal `$log`.
     * Defaults to true
     *
     * @var boolean
     */
    protected $_logging = true;

    /**
     * @var PDOStatement
     */
    protected $_result = null;

    /**
     * @var array
     */
    protected $_lastResult = array();

    /**
     * @var string
     */
    protected $_lastQuery = null;

    /**
     * @var string
     */
    protected $_lastQueryHash = null;

    /**
     * Creates a new Database object given an associative array of configuration
     * parameters in `$config`. If `$config` contains a key, `pdo` then this
     * `Database` instance will use that PDO connection. Otherwise, `$config`
     * should include `driver`, `host`, `port`, `user`, `password` and an optional
     * array of PDO options in `options`.
     *
     * @param array $config
     */
    public function __construct(array $config = array())
    {
        // If we have an existing PDO object
        if(isset($config['pdo'])) {
            $this->conn = $config['pdo'];
        }
        // Otherwise create a PDO object from parameters
        else {
            $this->connect(sprintf('%s:dbname=%s;host=%s;port=%d;charset=%s', $config['driver'], $config['db'], $config['host'], $config['port'], $config['charset']),
                $config['user'],
                $config['password'],
                $config['options']
            );
        }
    }

    /**
     * Magic function that will flush the MySQL log and close the MySQL
     * connection when the MySQL class is removed or destroyed.
     *
     * @link http://php.net/manual/en/language.oop5.decon.php
     */
    public function __destruct()
    {
        unset($this->conn);
        $this->flush();
    }

    /**
     * Resets the `result`, `lastResult`, `lastQuery` and lastQueryHash properties to `null`.
     * Called on each query and when the class is destroyed.
     */
    public function flush()
    {
        $this->_result = null;
        $this->_lastResult = array();
        $this->_lastQuery = null;
        $this->_lastQueryHash = null;
    }

    /**
     * Creates a PDO connection to the desired database given the parameters.
     * This will also set the error mode to be exceptions (handled by this class)
     *
     * @link http://www.php.net/manual/en/pdo.drivers.php
     * @param string $dsn
     * @param string $username
     * @param string $password
     * @param array $options
     * @return boolean
     */
    public function connect($dsn = null, $username = null, $password = null, array $options = array())
    {
        try {
            $this->conn = new PDO($dsn, $username, $password, $options);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        }
        catch (PDOException $ex) {
            $this->error($ex);

            return false;
        }

        return true;
    }

    /**
     * Returns the number of queries that has been executed
     *
     * @return integer
     */
    public function queryCount()
    {
        return $this->_query_count;
    }

    /**
     * Sets query caching to true, this will prepend all READ_OPERATION
     * queries with SQL_CACHE. Symphony be default enables caching. It
     * can be turned off by setting the query_cache parameter to 'off' in the
     * Symphony config file.
     *
     * @link http://dev.mysql.com/doc/refman/5.1/en/query-cache.html
     */
    public function enableCaching()
    {
        $this->_cache = true;
    }

    /**
     * Sets query caching to false, this will prepend all READ_OPERATION
     * queries will SQL_NO_CACHE.
     */
    public function disableCaching()
    {
        $this->_cache = false;
    }

    /**
     * Returns boolean if query caching is enabled or not
     *
     * @return boolean
     */
    public function isCachingEnabled()
    {
        return $this->_cache;
    }

    /**
     * Enables the logging of queries
     */
    public function enableLogging()
    {
        $this->_logging = true;
    }

    /**
     * Sets logging to false
     */
    public function disableLogging()
    {
        $this->_logging = false;
    }

    /**
     * Returns boolean if logging is enabled or not
     *
     * @return boolean
     */
    public function isLoggingEnabled()
    {
        return $this->_logging;
    }

    /**
     * Symphony uses a prefix for all it's database tables so it can live peacefully
     * on the same database as other applications. By default this is sym_, but it
     * can be changed when Symphony is installed.
     *
     * @param string $prefix
     *  The table prefix for Symphony, by default this is sym_
     */
    public function setPrefix($prefix)
    {
        $this->_prefix = $prefix;
    }

    /**
     * Returns the prefix used by Symphony for this Database instance.
     *
     * @since Symphony 2.4
     * @return string
     */
    public function getPrefix()
    {
        return $this->_prefix;
    }

    /**
     * Given a string, replace the default table prefixes with the
     * table prefix for this database instance.
     *
     * @param string $query
     * @return string
     */
    public function replaceTablePrefix($query)
    {
        if($this->_prefix !== 'tbl_'){
            $query = preg_replace('/tbl_(\S+?)([\s\.,]|$)/', $this->_prefix .'\\1\\2', $query);
        }

        return $query;
    }

    /**
     * Function looks over a query to determine if it's a READ or WRITE operation.
     * WRITE operations are any query that starts with: SET, CREATE, INSERT, REPLACE
     * ALTER, DELETE, UPDATE, OPTIMIZE, TRUNCATE or DROP. All other queries are considered
     * READ operations
     *
     * @param string $query
     * @return integer
     */
    public function determineQueryType($query)
    {
        return (preg_match('/^(set|create|insert|replace|alter|delete|update|optimize|truncate|drop)/i', $query)
            ? self::__WRITE_OPERATION__
            : self::__READ_OPERATION__);
    }

    /**
     * @param array $values
     * @return string
     */
    public static function addPlaceholders(array $values = array())
    {
        $placeholders = null;
        if(!empty($values)) {
            $placeholders = str_repeat('?,', count($values) - 1) . '?';
        }

        return $placeholders;
    }

    /**
     * Given a query that has been prepared and an array of values to subsitute
     * into the query, the function will return the result.
     *
     * @param string $query
     * @param array $values
     * @return PDOStatement
     */
    public function insert($query, array $values)
    {
        $result = $this->q($query, $values);

        return $result;
    }

    /**
     * Returns the last insert ID from the previous query. This is
     * the value from an auto_increment field.
     *
     * @return integer
     *  The last interested row's ID
     */
    public function getInsertID()
    {
        return $this->conn->lastInsertId();
    }

    /**
     * Given a query that has been prepared and an array of values to subsitute
     * into the query, the function will return the result.
     *
     * @param string $query
     * @param array $values
     * @return PDOStatement
     */
    public function update($query, array $values)
    {
        $result = $this->q($query, $values);

        return $result;
    }

    /**
     * Given a query that has been prepared and an array of values to subsitute
     * into the query, the function will return the result.
     *
     * @param string $query
     * @param array $values
     * @return PDOStatement
     */
    public function delete($query, array $values)
    {
        $result = $this->q($query, $values);

        return $result;
    }

    /**
     * Given a query that has been prepared and an array of optional
     * parameters, this function will return the results of a query
     * as an array.
     *
     * @param string $query
     * @param array $params
     *   - `fetch-type` = 'ASSOC'/'OBJECT'
     *          Return result as array or an object
     *   - `index` = 'column_name'
     *          The name of a column in the table to use it's value to index
     *          the result by. If this is omitted (and it is by default), an
     *          array of associative arrays is returned, with the key being the
     *          column names
     *      `offset` = `0`
     *          An integer representing the row to return
     * @param array $values
     * @return array
     */
    public function fetch($query = null, array $params = array(), array $values = array())
    {
        if(!is_null($query)) {
            $params['fetch-type'] = 'ASSOC';
            $this->query($query, $params, $values);
        }

        if(empty($this->_lastResult)) {
            return array();
        }

        $result = $this->_lastResult;

        if(isset($params['index']) && isset($result[0][$params['index']])){
            $n = array();

            foreach($result as $ii) {
                $n[$ii[$params['index']]] = $ii;
            }

            $result = $n;
        }

        return $result;
    }

    /**
     * Takes an SQL string and creates a prepared statement.
     *
     * @link http://php.net/manual/en/pdo.prepare.php
     * @param string $query
     * @param array $driver_options
     *  This array holds one or more key=>value pairs to set attribute values
     *  for the DatabaseStatement object that this method returns.
     * @return DatabaseStatement
     */
    public function prepare($query, array $driver_options = array())
    {
        $query = $this->replaceTablePrefix($query);

        return new DatabaseStatement($this, $this->conn->prepare($query, $driver_options));
    }

    /**
     * Create a transaction.
     *
     * @return DatabaseTransaction
     */
    public function transaction()
    {
        return new DatabaseTransaction($this->conn);
    }

    /**
     * Given a query that has been prepared and an array of values to subsitute
     * into the query, the function will return the result. Unlike `insert` and
     * `update`, this function is a bit of a catch all and will be able to populate
     * `$this->_lastResult` with an array of data. This function is usually used
     * via `fetch()`.
     *
     * @see fetch()
     * @param string $query
     * @param array $params
     *  Supports `fetch-type` and `offset` parameters for the moment
     * @param array $values
     *  If the `$query` has placeholders, this parameter will include the data
     *  to subsitute into the placeholders
     * @return boolean
     */
    public function query($query, array $params = array(), array $values = array())
    {
        if(empty($query)) return false;

        $query_type = $this->determineQueryType(trim($query));

        if($query_type === self::__READ_OPERATION__ && !preg_match('/^\s*SELECT\s+SQL(_NO)?_CACHE/i', $query)){
            if($this->isCachingEnabled()) {
                $query = preg_replace('/^\s*SELECT\s+/i', 'SELECT SQL_CACHE ', $query);
            }
            else {
                $query = preg_replace('/^\s*SELECT\s+/i', 'SELECT SQL_NO_CACHE ', $query);
            }
        }

        $this->q($query, $values, false);

        if($this->_result instanceof PDOStatement && $query_type === self::__READ_OPERATION__) {
            if($params['fetch-type'] === "ASSOC") {
                if(isset($params['offset'])) {
                    while ($row = $this->_result->fetch(PDO::FETCH_ASSOC, PDO::FETCH_ORI_ABS, $params['offset'])) {
                        $this->_lastResult = $row;
                    }
                }
                else {
                    while ($row = $this->_result->fetch(PDO::FETCH_ASSOC)) {
                        $this->_lastResult[] = $row;
                    }
                }
            }
            else if($params['fetch-type'] === 'OBJECT') {
                while ($row = $this->_result->fetchObject()) {
                    $this->_lastResult[] = $row;
                }
            }
        }

        return true;
    }

    /**
     * This function is actually responsible for subsituting the values into
     * the query and logging the query for basic profiling/debugging.
     *
     * @param string $query
     * @param array $values
     * @param boolean $close
     *  If true, once the query is executed, the cursor will be closed,
     *  otherwise it'll be left open for further manipulation (as done by
     *  `query()`). Defaults to `true`
     * @return PDOStatement
     */
    private function q($query, $values, $close = true)
    {
        if(empty($query)) return false;

        // Default query preparation
        $query = $this->replaceTablePrefix(trim($query));

        if($this->_logging) {
            $start = precision_timer();
        }

        // Cleanup from last time, set some logging parameters
        $this->flush();
        $this->_lastQuery = $query;
        $this->_lastQueryHash = md5($query.$start);

        // Execute
        try {
            $this->_result = $this->conn->prepare($query);
            $this->_result->execute($values);
            $this->_query_count++;
        }
        catch (PDOException $ex) {
            $this->error($ex);
        }

        if($this->conn->errorCode() !== PDO::ERR_NONE) {
            $this->error();

            return false;
        }
        else if($this->_result instanceof PDOStatement) {
            $this->_lastQuery = $this->_result->queryString;

            if($close) {
                $this->_result->closeCursor();
            }
        }

        if($this->_logging) {
            $this->logQuery($query, $this->_lastQueryHash, precision_timer('stop', $start));
        }

        return $this->_result;
    }

    /**
     * Given an Exception, or called when an error occurs, this function will
     * fire the `QueryExecutionError` delegate and then raise a `DatabaseException`
     *
     * @uses QueryExecutionError
     * @throws DatabaseException
     * @param Exception $ex
     *  The exception thrown while doing something with the Database
     * @return void
     */
    public function error(Exception $ex = null)
    {
        if(isset($ex)) {
            $msg = $ex->getMessage();
            $errornum = $ex->getCode();
        }
        else {
            $error = $this->conn->errorInfo();
            $msg = $error[2];
            $errornum = $error[0];
        }

        /**
         * After a query execution has failed this delegate will provide the query,
         * query hash, error message and the error number.
         *
         * Note that this function only starts logging once the `ExtensionManager`
         * is available, which means it will not fire for the first couple of
         * queries that set the character set.
         *
         * @since Symphony 2.3
         * @delegate QueryExecutionError
         * @param string $context
         * '/frontend/' or '/backend/'
         * @param string $query
         *  The query that has just been executed
         * @param string $query_hash
         *  The hash used by Symphony to uniquely identify this query
         * @param string $msg
         *  The error message provided by MySQL which includes information on why the execution failed
         * @param integer $num
         *  The error number that corresponds with the MySQL error message
         */
        if(Symphony::ExtensionManager() instanceof ExtensionManager) {
            Symphony::ExtensionManager()->notifyMembers('QueryExecutionError', class_exists('Administration') ? '/backend/' : '/frontend/', array(
                'query' => $this->_lastQuery,
                'query_hash' => $this->_lastQueryHash,
                'msg' => $msg,
                'num' => $errornum
            ));
        }

        throw new DatabaseException(__('Database Error (%1$s): %2$s in query: %3$s', array($errornum, $msg, $this->_lastQuery)), array(
            'msg' => $msg,
            'num' => $errornum,
            'query' => $this->_lastQuery
        ), $ex);
    }

    /**
     * Throw a new DatabaseException when given an original exception and a query.
     *
     * @param Exception $error
     * @param string $query
     * @param string $query_hash
     */
    public function throwError(Exception $error, $query, $query_hash)
    {
        $this->_lastQuery = $query;
        $this->_lastQueryHash = $query_hash;

        $this->error($error);
    }

    /**
     * Function is called everytime a query is executed to log it for
     * basic profiling/debugging purposes
     *
     * @uses PostQueryExecution
     * @param string $query
     * @param string $query_hash
     * @param integer $stop
     */
    public function logQuery($query, $query_hash, $stop)
    {
        /**
         * After a query has successfully executed, that is it was considered
         * valid SQL, this delegate will provide the query, the query_hash and
         * the execution time of the query.
         *
         * Note that this function only starts logging once the ExtensionManager
         * is available, which means it will not fire for the first couple of
         * queries that set the character set.
         *
         * @since Symphony 2.3
         * @delegate PostQueryExecution
         * @param string $context
         * '/frontend/' or '/backend/'
         * @param string $query
         *  The query that has just been executed
         * @param string $query_hash
         *  The hash used by Symphony to uniquely identify this query
         * @param float $execution_time
         *  The time that it took to run `$query`
         */
        if(Symphony::ExtensionManager() instanceof ExtensionManager) {
            Symphony::ExtensionManager()->notifyMembers('PostQueryExecution', class_exists('Administration') ? '/backend/' : '/frontend/', array(
                'query' => $query,
                'query_hash' => $query_hash,
                'execution_time' => $stop
            ));

            // If the ExceptionHandler is enabled, then the user is authenticated
            // or we have a serious issue, so log the query.
            if(GenericExceptionHandler::$enabled) {
                $this->_log[$query_hash] = array(
                    'query' => $query,
                    'query_hash' => $query_hash,
                    'execution_time' => $stop
                );
            }
        }

        // Symphony isn't ready yet. Log internally
        else {
            $this->_log[$query_hash] = array(
                'query' => $query,
                'query_hash' => $query_hash,
                'execution_time' => $stop
            );
        }
    }

    /**
     * Returns all the log entries by type. There are two valid types,
     * error and debug. If no type is given, the entire log is returned,
     * otherwise only log messages for that type are returned
     *
     * @param string $type
     * @return array
     * An array of associative array's. Log entries of the error type
     * return the query the error occurred on and the error number and
     * message from MySQL. Log entries of the debug type return the
     * the query and the start/stop time to indicate how long it took
     * to run
     */
    public function debug($type = null)
    {
        if(!$type) return $this->_log;

        return ($type === 'error' ? $this->_log['error'] : $this->_log['query']);
    }

    /**
     * Returns some basic statistics from the MySQL class about the
     * number of queries, the time it took to query and any slow queries.
     * A slow query is defined as one that took longer than 0.0999 seconds
     * This function is used by the Profile devkit
     *
     * @return array
     *  An associative array with the number of queries, an array of slow
     *  queries and the total query time.
     */
    public function getStatistics()
    {
        $query_timer = 0.0;
        $slow_queries = array();

        foreach($this->_log as $key => $val) {
            $query_timer += $val['execution_time'];
            if($val['execution_time'] > 0.0999) $slow_queries[] = $val;
        }

        return array(
            'queries' => $this->queryCount(),
            'slow-queries' => $slow_queries,
            'total-query-time' => number_format($query_timer, 4, '.', '')
        );
    }
}
