<?php

/**
 * @package toolkit
 */

/**
 * The Database class acts as a wrapper for connecting to the Database
 * in Symphony.
 *
 * It provides many methods that maps directly to their PDO equivalent.
 * It also provides many factory methods to help developers creates instances
 * of `DatabaseStatement` and their specialized child classes.
 *
 * Symphony uses a prefix to namespace it's tables in a
 * database, allowing it play nice with other applications installed on the
 * database.
 *
 * An error that occur during a query throw a `DatabaseException`.
 * By default, Symphony logs all queries to be used for Profiling and Debug
 * devkit extensions when a Developer is logged in. When a developer is not
 * logged in, all queries and errors are made available with delegates.
 */
class Database
{
    /**
     * Constant to indicate whether the query is a write operation.
     *
     * @deprecated @since Symphony 3.0.0
     * @var int
     */
    const __WRITE_OPERATION__ = 0;

    /**
     * Constant to indicate whether the query is a write operation
     *
     * @deprecated @since Symphony 3.0.0
     * @var int
     */
    const __READ_OPERATION__ = 1;

    /**
     * An instance of the current PDO object
     *
     * @var PDO
     */
    private $conn = null;

    /**
     * The array of log messages
     *
     * @var array
     */
    private $log = [];

    /**
     * The number of queries this class has executed, defaults to 0.
     *
     * @var int
     */
    private $queryCount = 0;

    /**
     * The default configuration values
     *
     * @var array
     */
    private $config = [
        'host' => null,
        'port' => null,
        'user' => null,
        'password' => null,
        'db' => null,
        'driver' => null,
        'charset' => null,
        'collate' => null,
        'engine' => null,
        'tbl_prefix' => null,
        'query_caching' => null,
        'query_logging' => null,
        'options' => [],
    ];

    /**
     * The last executed query
     *
     * @var string;
     */
    private $lastQuery;

    /**
     * The md5 hash of the last executed query
     *
     * @var string;
     */
    private $lastQueryHash;

    /**
     * Creates a new Database object given an associative array of configuration
     * parameters in `$config`, which should include
     * `driver`, `host`, `port`, `user`, `password`, `db` and an optional
     * array of PDO options in `options`.
     *
     * @param array $config
     */
    public function __construct(array $config = [])
    {
        $this->config = array_merge($this->config, $config);
    }

    /**
     * Magic function that will flush the logs and close the underlying database
     * connection when the Database class is destroyed.
     *
     * @link http://php.net/manual/en/language.oop5.decon.php
     */
    public function __destruct()
    {
        unset($this->conn);
        $this->flush();
    }

    /**
     * Getter for all the log entries.
     *
     * @return array
     */
    public function getLogs()
    {
        return $this->log;
    }

    /**
     * Resets `$this->lastQueryHash ` and `$this->lastQuery` to their empty
     * values. Called on each query and when the class is destroyed.
     *
     * @return Database
     *  The current instance.
     */
    public function flush()
    {
        $this->lastQuery = null;
        $this->lastQueryHash = null;
        return $this;
    }

    /**
     * Based on the configuration values set in the constructor,
     * this method will properly format the values to get a valid DSN
     * connection string.
     *
     * @return string
     *  The generated DNS connection string
     */
    public function getDSN()
    {
        $config = &$this->config;
        return sprintf(
            '%s:dbname=%s;host=%s;port=%d;charset=%s',
            $config['driver'],
            $config['db'],
            $config['host'],
            $config['port'],
            $config['charset']
        );
    }

    /**
     * Creates a PDO connection to the desired database given the current config.
     * This will also set the error mode to be exceptions,
     * which are handled by this class.
     *
     * @link http://www.php.net/manual/en/pdo.drivers.php
     * @param array $options
     * @return Database
     *  The current instance if connection was successful.
     * @throws DatabaseException
     */
    public function connect()
    {
        try {
            $config = $this->config;
            $this->conn = new PDO(
                $this->getDSN(),
                $config['user'],
                $config['password'],
                is_array($config['options']) ? $config['options'] : []
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);
        } catch (PDOException $ex) {
            $this->throwDatabaseError($ex);
        }

        return $this;
    }

    /**
     * Checks if the connection was already made successfully.
     *
     * @return boolean
     *  true if the connection was made, false otherwise
     */
    public function isConnected()
    {
        return $this->conn && $this->conn instanceof PDO;
    }

    /**
     * Issues a call to `connect()` if the current instance is not already
     * connected. Does nothing if already connected.
     *
     * @see isConnected()
     * @return Database
     *  The current instance.
     * @throws DatabaseException
     */
    private function autoConnect()
    {
        if (!$this->isConnected()) {
            $this->connect();
        }
        return $this;
    }

    /**
     * Returns the number of queries that has been executed since
     * the creation of the object.
     *
     * @return int
     *  The total number of query executed.
     */
    public function queryCount()
    {
        return $this->queryCount;
    }

    /**
     * Sets query caching to true. This will prepend all SELECT
     * queries with SQL_CACHE. Symphony by default enables caching. It
     * can be turned off by setting the query_cache parameter to 'off' in the
     * Symphony config file.
     *
     * @link https://dev.mysql.com/doc/refman/5.1/en/query-cache.html
     * @deprecated The query cache is deprecated as of MySQL 5.7.20,
     * and is removed in MySQL 8.0.
     * @link https://dev.mysql.com/doc/refman/5.7/en/query-cache-in-select.html
     * @return Database
     *  The current instance
     */
    public function enableCaching()
    {
        $this->config['query_caching'] = true;
        return $this;
    }

    /**
     * Sets query caching to false. This will prepend all SELECT
     * queries will SQL_NO_CACHE.
     *
     * @deprecated The query cache is deprecated as of MySQL 5.7.20,
     * and is removed in MySQL 8.0.
     * @link https://dev.mysql.com/doc/refman/5.7/en/query-cache-in-select.html
     * @return Database
     *  The current instance
     */
    public function disableCaching()
    {
        $this->config['query_caching'] = false;
        return $this;
    }

    /**
     * Returns boolean if query caching is enabled or not.
     *
     * @deprecated The query cache is deprecated as of MySQL 5.7.20,
     * and is removed in MySQL 8.0.
     * @link https://dev.mysql.com/doc/refman/5.7/en/query-cache-in-select.html
     * @return boolean
     */
    public function isCachingEnabled()
    {
        return in_array($this->config['query_caching'], ['on', true], true);
    }

    /**
     * Symphony uses a prefix for all it's database tables so it can live peacefully
     * on the same database as other applications. By default this is sym_, but it
     * can be changed when Symphony is installed.
     *
     * @param string $prefix
     *  The table prefix for Symphony, by default this is sym_
     * @return Database
     *  The current instance
     */
    public function setPrefix($prefix)
    {
        $this->config['tbl_prefix'] = $prefix;
        return $this;
    }

    /**
     * Returns the prefix used by Symphony for this Database instance.
     *
     * @see __construct()
     * @since Symphony 2.4
     * @return string
     */
    public function getPrefix()
    {
        return $this->config['tbl_prefix'];
    }

    /**
     * Sets query logging to true.
     *
     * @return Database
     *  The current instance
     */
    public function enableLogging()
    {
        $this->config['query_logging'] = true;
        return $this;
    }

    /**
     * Sets query logging to false.
     *
     * @return Database
     *  The current instance
     */
    public function disableLogging()
    {
        $this->config['query_logging'] = false;
        return $this;
    }

    /**
     * Returns true if logging of queries is enabled.
     *
     * @return boolean
     */
    public function isLoggingEnabled()
    {
        return in_array($this->config['query_logging'], ['on', true], true);
    }

    /**
     * Sets the Database connection to use this timezone instead of the default
     * Database server timezone.
     *
     * @throws DatabaseException
     * @link https://dev.mysql.com/doc/refman/5.6/en/time-zone-support.html
     * @link https://github.com/symphonycms/symphony-2/issues/1726
     * @since Symphony 2.3.3
     * @param string $timezone
     *  PHP's Human readable timezone, such as Australia/Brisbane.
     * @return boolean
     */
    public function setTimeZone($timezone = null)
    {
        if (!$timezone) {
            return true;
        }

        // What is the time now in the install timezone
        $symphony_date = new DateTime('now', new DateTimeZone($timezone));

        // MySQL wants the offset to be in the format +/-H:I, getOffset returns offset in seconds
        $utc = new DateTime('now ' . $symphony_date->getOffset() . ' seconds', new DateTimeZone("UTC"));

        // Get the difference between the symphony install timezone and UTC
        $offset = $symphony_date->diff($utc)->format('%R%H:%I');

        return $this->set('time_zone')
                    ->value((string)$offset)
                    ->execute()
                    ->success();
    }

    /**
     * This function will clean a string using the `PDO::quote` function
     * taking into account the current database character encoding.
     *
     * If not connected to the database, it will default to PHP's `addslashes`.
     * This is useful for unit tests.
     *
     * This function does not encode _ or %.
     *
     * This function should not be used. Instead, pass your data in the proper
     * function that will delegate to SQL parameters.
     *
     * @see DatabaseStatement::appendValues()
     * @param string $value
     *  The string to be encoded into an escaped SQL string
     * @return string
     *  The escaped SQL string
     */
    public function quote($value)
    {
        if (!$this->isConnected()) {
            return "'" . addslashes($value) . "'";
        }
        return $this->conn->quote($value);
    }

    /**
     * This function will apply the `quote` function to an associative
     * array of data, encoding only the value, not the key. This function
     * can handle recursive arrays. This function manipulates the given
     * parameter by reference.
     *
     * This function should not be used. Instead, pass your data in the proper
     * function that will delegate to SQL parameters.
     *
     * @see quote
     * @param array $array
     *  The associative array of data to encode, this parameter is manipulated
     *  by reference.
     */
    public function quoteFields(array &$array)
    {
        foreach ($array as $key => $val) {
            // Handle arrays with more than 1 level
            if (is_array($val)) {
                $this->quoteFields($val);
            } elseif (!$val || strlen(trim($val)) === 0) {
                $array[$key] = 'null';
            } else {
                $array[$key] = $this->quote($val);
            }
        }
    }

    /**
     * This function takes `$table` and `$field` names and returns true
     * if the `$table` contains a column named `$field`.
     *
     * @since Symphony 2.3
     * @see describe
     * @link  https://dev.mysql.com/doc/refman/en/describe.html
     * @param string $table
     *  The table name
     * @param string $field
     *  The field name
     * @throws DatabaseException
     * @return boolean
     *  true if `$table` contains `$field`, false otherwise
     */
    public function tableContainsField($table, $field)
    {
        return $this->describe($table)
                    ->field($field)
                    ->execute()
                    ->rowCount() > 0;
    }

    /**
     * This function takes `$table` and returns boolean
     * if it exists or not.
     *
     * @since Symphony 2.3.4
     * @see show
     * @link  https://dev.mysql.com/doc/refman/en/show-tables.html
     * @param string $table
     *  The table name
     * @throws DatabaseException
     * @return boolean
     *  true if `$table` exists, false otherwise
     */
    public function tableExists($table)
    {
        return $this->show()
                    ->like($table)
                    ->execute()
                    ->rowCount() > 0;
    }

    /**
     * Factory method that creates a new, empty statement.
     *
     * @param string $action
     *  The SQL clause name. Default to empty string.
     * @return DatabaseStatement
     */
    public function statement($action = '')
    {
        return new DatabaseStatement($this, $action);
    }

    /**
     * Factory method that creates a new `SELECT ...` statement.
     *
     * @param array $values
     *  The columns to select. By default, it's `*`.
     * @return DatabaseQuery
     */
    public function select(array $values = ['*'])
    {
        return new DatabaseQuery($this, $values);
    }

    /**
     * Factory method that creates a new `SELECT DISTINCT ...` statement.
     *
     * @param array $values
     *  The columns to select. By default, it's `*`.
     * @return DatabaseQuery
     */
    public function selectDistinct(array $values = ['*'])
    {
        return new DatabaseQuery($this, $values, 'DISTINCT');
    }

    /**
     * Factory method that creates a new `SELECT COUNT(...)` statement.
     *
     * @see select()
     * @param string $col
     *  The column to count on. Defaults to `*`
     * @return DatabaseQuery
     */
    public function selectCount($col = '*')
    {
        return new DatabaseQuery($this, ["COUNT($col)"]);
    }

    /**
     * Factory method that creates a new `SHOW TABLES` statement.
     *
     * @return DatabaseShow
     */
    public function show()
    {
        return new DatabaseShow($this);
    }

    /**
     * Factory method that creates a new `INSERT` statement.
     *
     * @param string $table
     *  The name of the table to act on, including the tbl prefix which will be changed
     *  to the Database table prefix.
     *  @deprecated Symphony 3.0.0
     *  If $table is an array, it is treated as the fields values
     *  Use DatabaseInsert::values()
     * @param string $table
     *  The name of the table to act on, including the tbl prefix which will be changed
     *  to the Database table prefix.
     *  @deprecated Symphony 3.0.0
     *  This parameter is deprecated and will be removed.
     *  Use the first parameter and DatabaseInsert::values()
     * @param bool $updateOnDuplicate
     *  If set to true, data will updated if any key constraints are found that cause
     *  conflicts. Defaults to false
     *  @deprecated Symphony 3.0.0
     *  This parameter is deprecated and will be removed.
     *  Use DatabaseInsert::updateOnDuplicateKey()
     * @return DatabaseInsert
     */
    public function insert($table, ...$oldParams)
    {
        // Compat layer
        if (is_array($table)) {
            if (isset($oldParams[0]) && isset($oldParams[1])) {
                return $this->_insert($table, $oldParams[0], $oldParams[1]);
            }
            return $this->_insert($table, $oldParams[0]);
        }
        return new DatabaseInsert($this, $table);
    }

    /**
     * Returns the last insert ID from the previous query. This is
     * the value from an auto_increment field.
     * If the lastInsertId is empty or not a valid integer, -1 is returned.
     *
     * @return int
     *  The last interested row's ID
     */
    public function getInsertID()
    {
        return General::intval($this->conn->lastInsertId);
    }

    /**
     * Factory method that creates a new `UPDATE` statement.
     *
     * @param string $table
     *  The name of the table to act on, including the tbl prefix which will be changed
     *  to the Database table prefix.
     * @param string $where
     *  An unsanitized WHERE condition.
     *  @deprecated Symphony 3.0.0
     *  This parameter is deprecated and will be removed.
     *  Use DatabaseUpdate::where()
     * @return DatabaseUpdate
     */
    public function update($table, ...$oldParams)
    {
        // Compat layer
        if (is_array($table)) {
            if (isset($oldParams[0]) && isset($oldParams[1])) {
                return $this->_update($table, $oldParams[0], $oldParams[1]);
            }
            return $this->_update($table, $oldParams[0]);
        }
        return new DatabaseUpdate($this, $table);
    }

    /**
     * Factory method that creates a new `DELETE` statement.
     *
     * @param string $table
     *  The name of the table to act on, including the tbl prefix which will be changed
     *  to the Database table prefix.
     * @param string $where
     *  An unsanitized WHERE condition.
     *  @deprecated Symphony 3.0.0
     *  This parameter is deprecated and will be removed.
     *  Use DatabaseDelete::where()
     * @return DatabaseDelete
     */
    public function delete($table, $where = null)
    {
        $stm = new DatabaseDelete($this, $table);
        // Compat layer
        if ($where) {
            $stm->unsafeAppendSQLPart('unsafe-where', "WHERE $where");
            return $stm->execute()->success();
        }
        return $stm;
    }

    /**
     * Factory method that creates a new `DROP` statement.
     *
     * @param string $table
     * @return DatabaseDrop
     */
    public function drop($table)
    {
        return new DatabaseDrop($this, $table);
    }

    /**
     * Factory method that creates a new `DROP IF EXISTS` statement.
     *
     * @param string $table
     * @return DatabaseDrop
     */
    public function dropIfExists($table)
    {
        return new DatabaseDrop($this, $table, 'IF EXISTS');
    }

    /**
     * Factory method that creates a new `DESCRIBE` statement.
     *
     * @param string $table
     * @return DatabaseDescribe
     */
    public function describe($table)
    {
        return new DatabaseDescribe($this, $table);
    }

    /**
     * Factory method that creates a new `CREATE TABLE` statement.
     * Also sets the charset, collate and engine values using the
     * instance configuration.
     *
     * @param string $table
     * @return DatabaseCreate
     */
    public function create($table)
    {
        return (new DatabaseCreate($this, $table))
            ->charset($this->config['charset'])
            ->collate($this->config['collate'])
            ->engine($this->config['engine']);
    }

    /**
     * Factory method that creates a new `CREATE TABLE IF NOT EXISTS` statement.
     * Also sets the charset, collate and engine values using the
     * instance configuration.
     *
     * @param string $table
     * @return DatabaseCreate
     */
    public function createIfNotExists($table)
    {
        return (new DatabaseCreate($this, $table, 'IF NOT EXISTS'))
            ->charset($this->config['charset'])
            ->collate($this->config['collate'])
            ->engine($this->config['engine']);
    }

    /**
     * Factory method that creates a new `ALTER TABLE` statement.
     * Also sets the collate value using the instance configuration.
     *
     * @param string $table
     * @return DatabaseAlter
     */
    public function alter($table)
    {
        return (new DatabaseAlter($this, $table))
            ->collate($this->config['collate']);
    }

    /**
     * Factory method that creates a new `OPTIMIZE TABLE` statement.
     *
     * @param string $table
     * @return DatabaseOptimize
     */
    public function optimize($table)
    {
        return new DatabaseOptimize($this, $table);
    }

    /**
     * Factory method that creates a new `TRUNCATE TABLE` statement.
     *
     * @param string $table
     * @return DatabaseTruncate
     */
    public function truncate($table)
    {
        return new DatabaseTruncate($this, $table);
    }

    /**
     * Factory method that creates a new `SET` statement.
     *
     * @param string $variable
     * @return DatabaseSet
     */
    public function set($variable)
    {
        return new DatabaseSet($this, $variable);
    }

    /**
     * Begins a new transaction.
     * This method calls `autoConnect()` before forwarding the call to PDO.
     *
     * @return boolean
     */
    public function beginTransaction()
    {
        $this->autoConnect();
        return $this->conn->beginTransaction();
    }

    /**
     * Commits the lastly created transaction.
     * This method calls `autoConnect()` before forwarding the call to PDO.
     *
     * @return boolean
     */
    public function commit()
    {
        $this->autoConnect();
        return $this->conn->commit();
    }

    /**
     * Rollbacks the lastly created transaction.
     * This method calls `autoConnect()` before forwarding the call to PDO.
     *
     * @return boolean
     */
    public function rollBack()
    {
        $this->autoConnect();
        return $this->conn->rollBack();
    }

    /**
     * Check if we are currently in a transaction.
     * This method calls `autoConnect()` before forwarding the call to PDO.
     *
     * @return boolean
     */
    public function inTransaction()
    {
        $this->autoConnect();
        return $this->conn->inTransaction();
    }

    /**
     * Given a DatabaseStatement, it will execute it and return
     * its result, by calling `DatabaseStatement::result()`.
     * Any error will throw a DatabaseException.
     *
     * Developers are encouraged to call `DatabaseStatement::execute()` instead,
     * because it will make sure to set required state properly.
     *
     * @see validateSQLQuery()
     * @see DatabaseStatement::execute()
     * @see DatabaseStatement::result()
     * @param string $query
     * @return DatabaseStatementResult
     * @throws DatabaseException
     */
    public function execute(DatabaseStatement $stm)
    {
        $this->autoConnect();

        if ($this->isLoggingEnabled()) {
            $start = precision_timer();
        }

        $query = $stm->generateSQL();
        $values = $stm->getValues();
        $result = null;

        // Cleanup from last time, set some logging parameters
        $this->flush();
        $this->lastQuery = $query;
        $this->lastQueryHash = md5($query . $start);

        try {
            // Validate the query
            $this->validateSQLQuery($query);
            // Prepare the query
            $pstm = $this->conn->prepare($query);
            $this->lastQuery = $pstm->queryString;
            // Execute it
            $result = $pstm->execute($values);
            $this->queryCount++;
        } catch (PDOException $ex) {
            $this->throwDatabaseError($ex);
            return;
        }

        // Check for errors
        if ($this->conn->errorCode() !== PDO::ERR_NONE) {
            $this->throwDatabaseError();
            return;
        }

        // Log the query
        if ($this->isLoggingEnabled()) {
            $this->logLastQuery(precision_timer('stop', $start));
        }

        return $stm->results($result, $pstm);
    }

    /**
     * @internal
     * This method checks for common pattern of SQL injection, like `--`, `'` and `;`.
     *
     * @see execute()
     * @param string $query
     * @return void
     * @throws DatabaseException
     */
    public function validateSQLQuery($query)
    {
        if (strpos('--', $query) !== false) {
            throw new DatabaseException('Query contains illegal characters: `--`');
        } elseif (strpos('\'', $query) !== false) {
            throw new DatabaseException('Query contains illegal character: `\'`');
        } elseif (strpos(';', $query) !== false) {
            throw new DatabaseException('Query contains illegal character: `;`');
        }
    }

    /**
     * Convenience function to allow you to execute multiple SQL queries at once
     * by providing a string with the queries delimited with a `;`
     *
     * @throws DatabaseException
     * @throws Exception
     * @param string $sql
     *  A string containing SQL queries delimited by `;`
     * @param boolean $force_engine
     *  @deprecated @since Symphony 3.0.0
     *  The default engine is now InnoDb.
     *  The import script should use InnoDb as well.
     *  Before 3.0.0:
     *  If set to true, this will set MySQL's default storage engine to MyISAM.
     *  Defaults to false, which will use MySQL's default storage engine when
     *  tables don't explicitly define which engine they should be created with
     * @return boolean
     *  If one of the queries fails, false will be returned and no further queries
     *  will be executed, otherwise true will be returned.
     */
    public function import($sql, $force_engine = false)
    {
        General::ensureType([
            'sql' => ['var' => $sql, 'type' => 'string'],
        ]);
        $queries = preg_split('/;[\\r\\n]+/', $sql, -1, PREG_SPLIT_NO_EMPTY);

        if (!is_array($queries) || empty($queries) || count($queries) <= 0) {
            throw new Exception('The SQL string contains no queries.');
        }

        foreach ($queries as $sql) {
            if (trim($sql) !== '') {
                $stm = new DatabaseStatement($this);
                $sql = $stm->replaceTablePrefix($sql);
                $stm->unsafeAppendSQLPart('import', $sql);
                if (!$stm->execute()->success()) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Given an Exception, or called when an error occurs, this function will
     * fire the `QueryExecutionError` delegate and then raise a `DatabaseException`
     *
     * @uses QueryExecutionError
     * @throws DatabaseException
     * @param Exception $ex
     *  The exception thrown while doing something with the Database
     */
    private function throwDatabaseError(Exception $ex = null)
    {
        if (isset($ex) && $ex) {
            $msg = $ex->getMessage();
            $errornum = (int)$ex->getCode();
        } else {
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
         * @param int $num
         *  The error number that corresponds with the MySQL error message
         */
        if (Symphony::ExtensionManager() instanceof ExtensionManager) {
            Symphony::ExtensionManager()->notifyMembers(
                'QueryExecutionError',
                class_exists('Administration') ? '/backend/' : '/frontend/',
                [
                    'query' => $this->lastQuery,
                    'query_hash' => $this->lastQueryHash,
                    'msg' => $msg,
                    'num' => $errornum
                ]
            );
        }

        throw new DatabaseException(
            __(
                'Database Error (%1$s): %2$s in query: %3$s',
                [$errornum, $msg, $this->lastQuery]
            ),
            [
                'msg' => $msg,
                'num' => $errornum,
                'query' => $this->lastQuery
            ],
            $ex
        );
    }

    /**
     * Function is called every time a query is executed to log it for
     * basic profiling/debugging purposes
     *
     * @uses PostQueryExecution
     * @param int $stop
     */
    private function logLastQuery($stop)
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
        if (Symphony::ExtensionManager() instanceof ExtensionManager) {
            // TODO: Log unlogged queries
            Symphony::ExtensionManager()->notifyMembers(
                'PostQueryExecution',
                class_exists('Administration') ? '/backend/' : '/frontend/',
                [
                    'query' => $this->lastQuery,
                    'query_hash' => $this->lastQueryHash,
                    'execution_time' => $stop
                ]
            );
        }

        // Keep internal log for easy debugging
        $this->log[] = [
            'query' => $this->lastQuery,
            'query_hash' => $this->lastQueryHash,
            'execution_time' => $stop
        ];
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
        $stats = [];
        $query_timer = 0.0;
        $slow_queries = [];

        foreach ($this->log as $key => $val) {
            $query_timer += $val['execution_time'];
            if ($val['execution_time'] > 0.0999) {
                $slow_queries[] = $val;
            }
        }

        return [
            'queries' => $this->queryCount(),
            'slow-queries' => $slow_queries,
            'total-query-time' => number_format($query_timer, 5, '.', '')
        ];
    }

    //--------------------------------------------------------------------------
    // COMPAT LAYER
    // All the following methods are deprecated and are there for
    // backward compatibility only.
    //--------------------------------------------------------------------------

    /**
     * Returns all the log entries by type. There are two valid types,
     * error and debug. If no type is given, the entire log is returned,
     * otherwise only log messages for that type are returned
     *
     * @deprecated @since Symphony 3.0.0
     * @see Database::getLogs()
     * @param null|string $type
     * @return array
     *  An array of associative array's. Log entries of the error type
     *  return the query the error occurred on and the error number and
     *  message from MySQL. Log entries of the debug type return the
     *  the query and the start/stop time to indicate how long it took
     *  to run
     */
    public function debug($type = null)
    {
        return $this->getLogs();
    }

    /**
     * This function will clean a string using the `PDO::quote` function
     * taking into account the current database character encoding. Note that this
     * function does not encode _ or %.
     *
     * @deprecated @since Symphony 3.0.0
     * @see quote
     * @param string $value
     *  The string to be encoded into an escaped SQL string
     * @return string
     *  The escaped SQL string
     */
    public function cleanValue($value)
    {
        return trim($this->quote($value), "'");
    }

    /**
     * This function will apply the `quote` function to an associative
     * array of data, encoding only the value, not the key. This function
     * can handle recursive arrays. This function manipulates the given
     * parameter by reference.
     *
     * @deprecated @since Symphony 3.0.0
     * @see quoteFields
     * @param array $array
     *  The associative array of data to encode, this parameter is manipulated
     *  by reference.
     */
    public function cleanFields(array &$array)
    {
        $this->quoteFields($array);
    }

    /**
     * Determines whether this query is a read operation, or if it is a write operation.
     * A write operation is determined as any query that starts with CREATE, INSERT,
     * REPLACE, ALTER, DELETE, UPDATE, OPTIMIZE, TRUNCATE, DROP, LOCK or UNLOCK. Anything else is
     * considered to be a read operation which are subject to query caching.
     *
     * @deprecated @since Symphony 3.0.0
     * @param string $query
     * @return int
     *  `self::__WRITE_OPERATION__` or `self::__READ_OPERATION__`
     */
    public function determineQueryType($query)
    {
        return preg_match(
            '/^(create|insert|replace|alter|delete|update|optimize|truncate|drop|lock|unlock)/i',
            $query
        ) === 1 ? self::__WRITE_OPERATION__ : self::__READ_OPERATION__;
    }

    /**
     * Takes an SQL string and executes it. This function will apply query
     * caching if it is a read operation and if query caching is set. Symphony
     * will convert the `tbl_` prefix of tables to be the one set during installation.
     * A type parameter is provided to specify whether `$this->_lastResult` will be an array
     * of objects or an array of associative arrays. The default is objects. This
     * function will return boolean, but set `$this->_lastResult` to the result.
     *
     * @deprecated @since Symphony 3.0.0
     * @see select()
     * @see insert()
     * @see update()
     * @see delete()
     * @see create()
     * @see alter()
     * @see drop()
     * @see truncate()
     * @see optimize()
     * @see set()
     * @see autoConnect()
     * @uses PostQueryExecution
     * @param string $query
     *  The full SQL query to execute.
     * @param string $type
     *  Whether to return the result as objects or associative array. Defaults
     *  to OBJECT which will return objects. The other option is ASSOC. If $type
     *  is not either of these, it will return objects.
     * @throws DatabaseException
     * @return boolean|Traversable
     *  true if the query executed without errors, false otherwise
     */
    public function query($query, $type = "OBJECT")
    {
        General::ensureType([
            'query' => ['var' => $query, 'type' => 'string'],
            'type' => ['var' => $type, 'type' => 'string'],
        ]);
        $this->autoConnect();

        if ($this->isLoggingEnabled()) {
            $start = precision_timer();
        }

        $result = null;
        // Format SQL because PDO does not seem to like it
        $query = trim(str_replace(PHP_EOL, ' ', $query));
        $query = trim(str_replace('\t', ' ', $query));
        while (strpos($query, '  ') !== false) {
            $query = str_replace('  ', ' ', $query);
        }
        if ($this->getPrefix() !== 'tbl_') {
            $query = preg_replace('/tbl_(\S+?)([\s\.,]|$)/', $this->getPrefix().'\\1\\2', $query);
        }

        // Cleanup from last time, set some logging parameters
        $this->flush();
        $this->lastQuery = $query;
        $this->lastQueryHash = md5($query.$start);
        $query_type = $this->determineQueryType($query);

        // TYPE is deprecated since MySQL 4.0.18, ENGINE is preferred
        if ($query_type == self::__WRITE_OPERATION__) {
            $query = preg_replace('/TYPE=(MyISAM|InnoDB)/i', 'ENGINE=$1', $query);
        } elseif ($query_type == self::__READ_OPERATION__) {
            if (preg_match('/^SELECT\s+SQL(_NO)?_CACHE/i', $query) === false) {
                if ($this->isCachingEnabled()) {
                    $query = preg_replace('/^SELECT\s+/i', 'SELECT SQL_CACHE ', $query);
                } else {
                    $query = preg_replace('/^SELECT\s+/i', 'SELECT SQL_NO_CACHE ', $query);
                }
            }
            $fetchType = $type == "OBJECT" ? PDO::FETCH_OBJ : PDO::FETCH_ASSOC;
        }

        try {
            // Execute it
            if ($fetchType) {
                $resultPdo = $this->conn->query($query);
                $result = $resultPdo->fetchAll($fetchType);
                $resultPdo->closeCursor();
            } else {
                $result = $this->conn->exec($query);
            }
            $this->queryCount++;
            $this->_lastResult = $result;
        } catch (PDOException $ex) {
            $this->throwDatabaseError($ex);
            return;
        }

        // Check for errors
        if ($this->conn->errorCode() !== PDO::ERR_NONE) {
            $this->throwDatabaseError();
            return;
        }

        // Log the query
        if ($this->isLoggingEnabled()) {
            $this->logLastQuery(precision_timer('stop', $start));
        }

        return $result !== false;
    }

    /**
     * A convenience method to insert data into the Database. This function
     * takes an associative array of data to input, with the keys being the column
     * names and the table. An optional parameter exposes MySQL's ON DUPLICATE
     * KEY UPDATE functionality, which will update the values if a duplicate key
     * is found.
     *
     * @deprecated @since Symphony 3.0.0
     * @param array $fields
     *  An associative array of data to input, with the key's mapping to the
     *  column names. Alternatively, an array of associative array's can be
     *  provided, which will perform multiple inserts
     * @param string $table
     *  The table name, including the tbl prefix which will be changed
     *  to this Symphony's table prefix in the query function
     * @param boolean $updateOnDuplicate
     *  If set to true, data will updated if any key constraints are found that cause
     *  conflicts. By default this is set to false, which will not update the data and
     *  would return an SQL error
     * @throws DatabaseException
     * @return boolean
     */
    public function _insert(array $fields, $table, $updateOnDuplicate = false) // @codingStandardsIgnoreLine
    {
        $stm = $this->insert($table)->values($fields);
        if ($updateOnDuplicate) {
            $stm->updateOnDuplicateKey();
        }
        return $stm->execute()->success();
    }

    /**
     * A convenience method to update data that exists in the Database. This function
     * takes an associative array of data to input, with the keys being the column
     * names and the table. A WHERE statement can be provided to select the rows
     * to update
     *
     * @deprecated @since Symphony 3.0.0
     * @param array $fields
     *  An associative array of data to input, with the key's mapping to the
     *  column names.
     * @param string $table
     *  The table name, including the tbl prefix which will be changed
     *  to this Symphony's table prefix in the query function
     * @param string $where
     *  A WHERE statement for this UPDATE statement, defaults to null
     *  which will update all rows in the $table
     * @throws DatabaseException
     * @return boolean
     */
    public function _update(array $fields, $table, $where = null) // @codingStandardsIgnoreLine
    {
        $stm = $this->update($table)->set($fields);
        if ($where) {
            $stm->unsafeAppendSQLPart('unsafe-where', "WHERE $where");
        }
        return $stm->execute()->success();
    }

    /**
     * Returns an associative array that contains the results of the
     * given `$query`. Optionally, the resulting array can be indexed
     * by a particular column.
     *
     * @deprecated @since Symphony 3.0.0
     * @param string $query
     *  The full SQL query to execute. Defaults to null, which will
     *  use the _lastResult
     * @param string $index_by_column
     *  The name of a column in the table to use it's value to index
     *  the result by. If this is omitted (and it is by default), an
     *  array of associative arrays is returned, with the key being the
     *  column names
     * @throws DatabaseException
     * @return array
     *  An associative array with the column names as the keys
     */
    public function fetch($query = null, $index_by_column = null)
    {
        if (!is_null($query)) {
            $this->query($query, "ASSOC");
        } elseif (is_null($this->_lastResult)) {
            return array();
        }

        $result = $this->_lastResult;

        if (!is_null($index_by_column) && isset($result[0][$index_by_column])) {
            $n = array();

            foreach ($result as $ii) {
                $n[$ii[$index_by_column]] = $ii;
            }

            $result = $n;
        }

        return $result;
    }

    /**
     * Returns the row at the specified index from the given query. If no
     * query is given, it will use the `$this->_lastResult`. If no offset is provided,
     * the function will return the first row. This function does not imply any
     * LIMIT to the given `$query`, so for the more efficient use, it is recommended
     * that the `$query` have a LIMIT set.
     *
     * @deprecated @since Symphony 3.0.0
     * @param int $offset
     *  The row to return from the SQL query. For instance, if the second
     *  row from the result was required, the offset would be 1, because it
     *  is zero based.
     * @param string $query
     *  The full SQL query to execute. Defaults to null, which will
     *  use the `$this->_lastResult`
     * @throws DatabaseException
     * @return array
     *  If there is no row at the specified `$offset`, an empty array will be returned
     *  otherwise an associative array of that row will be returned.
     */
    public function fetchRow($offset = 0, $query = null)
    {
        $result = $this->fetch($query);
        return (empty($result) ? array() : $result[$offset]);
    }

    /**
     * Returns an array of values for a specified column in a given query.
     * If no query is given, it will use the `$this->_lastResult`.
     *
     * @deprecated @since Symphony 3.0.0
     * @param string $column
     *  The column name in the query to return the values for
     * @param string $query
     *  The full SQL query to execute. Defaults to null, which will
     *  use the `$this->_lastResult`
     * @throws DatabaseException
     * @return array
     *  If there is no results for the `$query`, an empty array will be returned
     *  otherwise an array of values for that given `$column` will be returned
     */
    public function fetchCol($column, $query = null)
    {
        $result = $this->fetch($query);

        if (empty($result)) {
            return array();
        }

        $rows = array();
        foreach ($result as $row) {
            $rows[] = $row[$column];
        }

        return $rows;
    }

    /**
     * Returns the value for a specified column at a specified offset. If no
     * offset is provided, it will return the value for column of the first row.
     * If no query is given, it will use the `$this->_lastResult`.
     *
     * @deprecated @since Symphony 3.0.0
     * @see select
     * @param string $column
     *  The column name in the query to return the values for
     * @param int $offset
     *  The row to use to return the value for the given `$column` from the SQL
     *  query. For instance, if `$column` form the second row was required, the
     *  offset would be 1, because it is zero based.
     * @param string $query
     *  The full SQL query to execute. Defaults to null, which will
     *  use the `$this->_lastResult`
     * @throws DatabaseException
     * @return string|null
     *  Returns the value of the given column, if it doesn't exist, null will be
     *  returned
     */
    public function fetchVar($column, $offset = 0, $query = null)
    {
        $result = $this->fetch($query);
        return (empty($result) ? null : $result[$offset][$column]);
    }
}
