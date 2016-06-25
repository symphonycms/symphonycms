<?php

/**
 * @package toolkit
 */

/**
 * The MySQL class acts as a wrapper for connecting to the Database
 * in Symphony. It utilises mysqli_* functions in PHP to complete the usual
 * querying. As well as the normal set of insert, update, delete and query
 * functions, some convenience functions are provided to return results
 * in different ways. Symphony uses a prefix to namespace it's tables in a
 * database, allowing it play nice with other applications installed on the
 * database. An errors that occur during a query throw a `DatabaseException`.
 * By default, Symphony logs all queries to be used for Profiling and Debug
 * devkit extensions when a Developer is logged in. When a developer is not
 * logged in, all queries and errors are made available with delegates.
 */
class MySQL
{
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
     * An associative array of connection properties for this MySQL
     * database including the host, port, username, password and
     * selected database.
     *
     * @var Database
     */
    private static $_conn_pdo = null;

    /**
     * Sets the current `$_log` to be an empty array
     */
    public static function flushLog()
    {
        MySQL::$_conn_pdo->log = array();
    }

    /**
     * Returns the number of queries that has been executed
     *
     * @return integer
     */
    public static function queryCount()
    {
        return MySQL::$_conn_pdo->queryCount();
    }

    /**
     * Symphony uses a prefix for all it's database tables so it can live peacefully
     * on the same database as other applications. By default this is `sym_`, but it
     * can be changed when Symphony is installed.
     *
     * @param string $prefix
     *  The table prefix for Symphony, by default this is `sym_`
     */
    public function setPrefix($prefix)
    {
        $this->_prefix = $prefix;
        MySQL::$_conn_pdo->setPrefix($prefix);
    }

    /**
     * Returns the prefix used by Symphony for this Database instance.
     *
     * @since Symphony 2.4
     * @return string
     */
    public function getPrefix()
    {
        return MySQL::$_conn_pdo->getPrefix();
    }

    /**
     * Determines if a connection has been made to the MySQL server
     *
     * @return boolean
     */
    public static function isConnected()
    {
        try {
            $connected = (
                isset(MySQL::$_conn_pdo)
            );
        } catch (Exception $ex) {
            return false;
        }

        return $connected;
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
        MySQL::$_conn_pdo->enableCaching();
    }

    /**
     * Sets query caching to false, this will prepend all READ_OPERATION
     * queries will SQL_NO_CACHE.
     */
    public function disableCaching()
    {
        MySQL::$_conn_pdo->disableCaching();
    }

    /**
     * Returns boolean if query caching is enabled or not
     *
     * @return boolean
     */
    public function isCachingEnabled()
    {
        return MySQL::$_conn_pdo->isCachingEnabled();
    }

    /**
     * Enables query logging and profiling.
     *
     * @since Symphony 2.6.2
     */
    public static function enableLogging()
    {
        MySQL::$_conn_pdo->enableLogging();
    }

    /**
     * Disables query logging and profiling. Use this in low memory environments
     * to reduce memory usage.
     *
     * @since Symphony 2.6.2
     * @link https://github.com/symphonycms/symphony-2/issues/2398
     */
    public static function disableLogging()
    {
        MySQL::$_conn_pdo->disableLogging();
    }

    /**
     * Returns boolean if logging is enabled or not
     *
     * @since Symphony 2.6.2
     * @return boolean
     */
    public static function isLoggingEnabled()
    {
        return MySQL::$_conn_pdo->isLoggingEnabled();
    }

    /**
     * Creates a connect to the database server given the credentials. If an
     * error occurs, a `DatabaseException` is thrown, otherwise true is returned
     *
     * @param string $host
     *  Defaults to null, which MySQL assumes as localhost.
     * @param string $user
     *  Defaults to null
     * @param string $password
     *  Defaults to null
     * @param string $port
     *  Defaults to 3306.
     * @param null $database
     * @throws DatabaseException
     * @return boolean
     */
    public function connect($host = null, $user = null, $password = null, $port = '3306', $database = null)
    {
        $config = [
            'driver' =>     'mysql',
            'db' =>         $database,
            'host' =>       $host,
            'port' =>       $port,
            'user' =>       $user,
            'password' =>   $password,
            'charset' =>    'utf8',
            'options' => [
                // PDO::ATTR_ORACLE_NULLS => PDO::NULL_EMPTY_STRING
            ]
        ];

        MySQL::$_conn_pdo = new Database($config);

        // Ensure that the default storage engine is InnoDB:
        MySQL::$_conn_pdo->conn->exec('SET default_storage_engine = "InnoDB"');

        return true;
    }

    /**
     * Accessor for the current MySQL resource from PHP. May be
     * useful for developers who want complete control over their
     * database queries and don't want anything abstract by the MySQL
     * class.
     *
     * @return PDO
     */
    public static function getConnectionResource()
    {
        return MySQL::$_conn_pdo->conn;
    }

    /**
     * Sets the MySQL connection to use this timezone instead of the default
     * MySQL server timezone.
     *
     * @throws DatabaseException
     * @link https://dev.mysql.com/doc/refman/5.6/en/time-zone-support.html
     * @link https://github.com/symphonycms/symphony-2/issues/1726
     * @since Symphony 2.3.3
     * @param string $timezone
     *  Timezone will human readable, such as Australia/Brisbane.
     */
    public function setTimeZone($timezone = null)
    {
        if (is_null($timezone)) {
            return;
        }

        // What is the time now in the install timezone
        $symphony_date = new DateTime('now', new DateTimeZone($timezone));

        // MySQL wants the offset to be in the format +/-H:I, getOffset returns offset in seconds
        $utc = new DateTime('now ' . $symphony_date->getOffset() . ' seconds', new DateTimeZone("UTC"));

        // Get the difference between the symphony install timezone and UTC
        $offset = $symphony_date->diff($utc)->format('%R%H:%I');

        self::getConnectionResource()->exec("SET time_zone = '$offset'");
    }

    /**
     * This function will clean a string using the `mysqli_real_escape_string` function
     * taking into account the current database character encoding. Note that this
     * function does not encode _ or %. If `mysqli_real_escape_string` doesn't exist,
     * `addslashes` will be used as a backup option
     *
     * @param string $value
     *  The string to be encoded into an escaped SQL string
     * @return string
     *  The escaped SQL string
     */
    public static function cleanValue($value)
    {
        return addslashes($value);
    }

    /**
     * This function will apply the `cleanValue` function to an associative
     * array of data, encoding only the value, not the key. This function
     * can handle recursive arrays. This function manipulates the given
     * parameter by reference.
     *
     * @see cleanValue
     * @param array $array
     *  The associative array of data to encode, this parameter is manipulated
     *  by reference.
     */
    public static function cleanFields(array &$array)
    {
        foreach ($array as $key => $val) {

            // Handle arrays with more than 1 level
            if (is_array($val)) {
                self::cleanFields($val);
                continue;
            } elseif (strlen($val) === 0) {
                $array[$key] = 'null';
            } else {
                $array[$key] = "'" . self::cleanValue($val) . "'";
            }
        }
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
        return MySQL::$_conn_pdo->prepare($query, $driver_options);
    }

    /**
     * Create a transaction.
     *
     * @return DatabaseTransaction
     */
    public function transaction()
    {
        return MySQL::$_conn_pdo->transaction();
    }

    /**
     * Takes an SQL string and executes it. This function will apply query
     * caching if it is a read operation and if query caching is set. Symphony
     * will convert the `tbl_` prefix of tables to be the one set during installation.
         * To automatically sanitize variables being used the query has to be sprintf-formatted
         * and all variables passed on separately using the second parameter.
     * A type parameter is provided to specify whether `$this->_lastResult` will be an array
     * of objects or an array of associative arrays. The default is objects. This
     * function will return boolean, but set `$this->_lastResult` to the result.
     *
     * @uses PostQueryExecution
     * @param string $query
     *  The full SQL query to execute.
     * @param string $type
     *  Whether to return the result as objects or associative array. Defaults
     *  to OBJECT which will return objects. The other option is ASSOC. If $type
     *  is not either of these, it will return objects.
     * @param array $params
     * @throws DatabaseException
     * @return boolean
     *  True if the query executed without errors, false otherwise
     */
    public function query($query, $type = "OBJECT", $params = array())
    {
        if(empty($query)) return false;

        MySQL::$_conn_pdo->query($query, array(
            'fetch-type' => $type
        ), $params);

        return true;
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
        return MySQL::$_conn_pdo->getInsertID();
    }

    /**
     * A convenience method to insert data into the Database. This function
     * takes an associative array of data to input, with the keys being the column
     * names and the table. An optional parameter exposes MySQL's ON DUPLICATE
     * KEY UPDATE functionality, which will update the values if a duplicate key
     * is found.
     *
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
    public function insert(array $fields, $table, $updateOnDuplicate=false)
    {
        // Multiple Insert
        if(is_array(current($fields))) {
            $rows = array();
            $values = array();

            $sql  = "INSERT INTO `$table` (`".implode('`, `', array_keys(current($fields))).'`) VALUES ';

            foreach ($fields as $key => $array) {
                // Sanity check: Make sure we dont end up with ',()' in the SQL.
                if (!is_array($array)) {
                    continue;
                }

                $rows[] = "(" . trim(str_repeat('?,', count($array)), ',') . ")";

                // Increase our data pool
                $values = array_merge($values, array_values($array));
            }
            $sql .= implode(", ", $rows);

        // Single Insert
        } else {
            $values = $fields;
            $sql  = "INSERT INTO `$table` (`".implode('`, `', array_keys($fields)).'`) VALUES ';
            $sql .= "(" . trim(str_repeat('?,', count($fields)),',') . ")";

            // Update duplicate keys
            if($updateOnDuplicate){
                $sql .= ' ON DUPLICATE KEY UPDATE ';

                foreach($fields as $key => $value) {
                    $sql .= " `$key` = ?,";
                }

                $sql = trim($sql, ',');
                // Double our data pool
                $values = array_merge(array_values($values), array_values($values));
            }
        }

        return MySQL::$_conn_pdo->insert($sql, array_values($values));
    }

    /**
     * A convenience method to update data that exists in the Database. This function
     * takes an associative array of data to input, with the keys being the column
     * names and the table. A WHERE statement can be provided to select the rows
     * to update
     *
     * @param array $fields
     *  An associative array of data to input, with the key's mapping to the
     *  column names.
     * @param string $table
     *  The table name, including the tbl prefix which will be changed
     *  to this Symphony's table prefix in the query function
     * @param string $where
     *  A WHERE statement for this UPDATE statement, defaults to null
     *  which will update all rows in the $table
     * @param array $params
     * @return bool
     */
    public function update($fields, $table, $where = null, $params = array())
    {
        $sql = "UPDATE `$table` SET ";

        foreach($fields as $key => $val) {
            $sql .= " `$key` = ?,";
        }

        $sql = trim($sql, ',') . (!is_null($where) ? ' WHERE ' . $where : null);

        return MySQL::$_conn_pdo->update($sql, array_merge(array_values($fields), $params));
    }

    /**
     * Given a table name and a WHERE statement, delete rows from the
     * Database.
     *
     * @param string $table
     *  The table name, including the tbl prefix which will be changed
     *  to this Symphony's table prefix in the query function
     * @param string $where
     *  A WHERE statement for this DELETE statement, defaults to null,
     *  which will delete all rows in the $table
     * @param array $params
     * @throws DatabaseException
     * @return boolean
     */
    public function delete($table, $where = null, array $params = array())
    {
        $sql = "DELETE FROM `$table`";

        if (!is_null($where)) {
            $sql .= " WHERE $where";
        }

        return MySQL::$_conn_pdo->delete($sql, $params);
    }

    /**
     * Returns an associative array that contains the results of the
     * given `$query`. Optionally, the resulting array can be indexed
     * by a particular column.
     *
     * @param string $query
     *  The full SQL query to execute. Defaults to null, which will
     *  use the _lastResult
     * @param string $index_by_column
     *  The name of a column in the table to use it's value to index
     *  the result by. If this is omitted (and it is by default), an
     *  array of associative arrays is returned, with the key being the
     *  column names
     * @param array $params
     *  An array containing parameters to be used in the query. The query has to be
     *  sprintf-formatted. All values will be sanitized before being used in the query.
     *  For sake of backwards-compatibility, the query will only be sprintf-processed
     *  if $params is not empty.
     * @param array $values
     * @throws DatabaseException
     * @return array
     *  An associative array with the column names as the keys
     */
    public function fetch($query = null, $index_by_column = null, array $params = array(), array $values = array())
    {
        if(!is_null($index_by_column)) {
            $params['index'] = $index_by_column;
        }

        return MySQL::$_conn_pdo->fetch($query, $params, $values);
    }

    /**
     * Returns the row at the specified index from the given query. If no
     * query is given, it will use the `$this->_lastResult`. If no offset is provided,
     * the function will return the first row. This function does not imply any
     * LIMIT to the given `$query`, so for the more efficient use, it is recommended
     * that the `$query` have a LIMIT set.
     *
     * @throws DatabaseException
     * @param integer $offset
     *  The row to return from the SQL query. For instance, if the second
     *  row from the result was required, the offset would be 1, because it
     *  is zero based.
     * @param string $query
     *  The full SQL query to execute. Defaults to null, which will
     *  use the `$this->_lastResult`
     * @param array $values
     * @return array
     *  If there is no row at the specified `$offset`, an empty array will be returned
     *  otherwise an associative array of that row will be returned.
     */
    public function fetchRow($offset = 0, $query = null, array $values = array())
    {
        $result = $this->fetch($query, null, array(
            'offset' => $offset
        ), $values);

        return $result;
    }

    /**
     * Returns an array of values for a specified column in a given query.
     * If no query is given, it will use the `$this->_lastResult`.
     *
     * @throws DatabaseException
     * @param string $column
     *  The column name in the query to return the values for
     * @param string $query
     *  The full SQL query to execute. Defaults to null, which will
     *  use the `$this->_lastResult`
     * @param array $values
     * @return array
     *  If there is no results for the `$query`, an empty array will be returned
     *  otherwise an array of values for that given `$column` will be returned
     */
    public function fetchCol($column, $query = null, array $values = array())
    {
        $result = $this->fetch($query, $column, array(), $values);

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
     * @param string $column
     *  The column name in the query to return the values for
     * @param integer $offset
     *  The row to use to return the value for the given `$column` from the SQL
     *  query. For instance, if `$column` form the second row was required, the
     *  offset would be 1, because it is zero based.
     * @param string $query
     *  The full SQL query to execute. Defaults to null, which will
     *  use the `$this->_lastResult`
     * @param array $values
     * @return string
     *  Returns the value of the given column, if it doesn't exist, null will be
     *  returned
     */
    public function fetchVar($column, $offset = 0, $query = null, array $values = array())
    {
        $result = $this->fetchRow($offset, $query, $values);

        return (empty($result) ? null : $result[$column]);
    }

    /**
     * This function takes `$table` and `$field` names and returns boolean
     * if the `$table` contains the `$field`.
     *
     * @since Symphony 2.3
     * @param string $table
     *  The table name
     * @param string $field
     *  The field name
     * @throws DatabaseException
     * @return boolean
     *  True if `$table` contains `$field`, false otherwise
     */
    public function tableContainsField($table, $field)
    {
        $results = $this->fetch("DESC `{$table}` `{$field}`");

        return (is_array($results) && !empty($results));
    }

    /**
     * This function takes `$table` and returns boolean
     * if it exists or not.
     *
     * @since Symphony 2.3.4
     * @param string $table
     *  The table name
     * @throws DatabaseException
     * @return boolean
     *  True if `$table` exists, false otherwise
     */
    public function tableExists($table)
    {
        $results = $this->fetch('SHOW TABLES LIKE ?', null, array(), array(

            $table
        ));

        return (is_array($results) && !empty($results));
    }

    /**
     * If an error occurs in a query, this function is called which logs
     * the last query and the error number and error message from MySQL
     * before throwing a `DatabaseException`
     *
     * @uses QueryExecutionError
     * @throws DatabaseException
     * @return void
     */
    private function __error()
    {
        MySQL::$_conn_pdo->error();
    }

    /**
     * Returns all the log entries by type. There are two valid types,
     * error and debug. If no type is given, the entire log is returned,
     * otherwise only log messages for that type are returned
     *
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
        return MySQL::$_conn_pdo->debug($type);
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
        return MySQL::$_conn_pdo->getStatistics();
    }

    /**
     * Convenience function to allow you to execute multiple SQL queries at once
     * by providing a string with the queries delimited with a `;`
     *
     * @throws DatabaseException
     * @throws Exception
     * @param string $sql
     *  A string containing SQL queries delimited by `;`
     * @return boolean
     *  If one of the queries fails, false will be returned and no further queries
     *  will be executed, otherwise true will be returned.
     */
    public function import($sql)
    {
        if (empty($sql)) {
            throw new Exception('The SQL string contains no queries.');
        }

        $sql = self::$_conn_pdo->replaceTablePrefix($sql);

        $this->getConnectionResource()->exec($sql);
    }
}
