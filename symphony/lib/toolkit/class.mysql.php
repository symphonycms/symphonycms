<?php

	/**
	 * @package toolkit
	 */

	/**
	 * The DatabaseException class extends a normal Exception to add in
	 * debugging information when a SQL query fails such as the internal
	 * database error code and message in additional to the usual
	 * Exception information. It allows a DatabaseException to contain a human
	 * readable error, as well more technical information for debugging.
	 */
	Class DatabaseException extends Exception{

		/**
		 * An associative array with three keys, 'query', 'msg' and 'num'
		 * @var array
		 */
		private $_error = array();

		/**
		 * Constructor takes a message and an associative array to set to
		 * `$_error`. The message is passed to the default Exception constructor
		 */
		public function __construct($message, array $error=NULL){
			parent::__construct($message);
			$this->_error = $error;
		}

		/**
		 * Accessor function for the original query that caused this Exception
		 *
		 * @return string
		 */
		public function getQuery(){
			return $this->_error['query'];
		}

		/**
		 * Accessor function for the Database error code for this type of error
		 *
		 * @return string
		 */
		public function getDatabaseErrorCode(){
			return $this->_error['num'];
		}

		/**
		 * Accessor function for the Database message from this Exception
		 *
		 * @return string
		 */
		public function getDatabaseErrorMessage(){
			return $this->_error['msg'];
		}
	}

	/**
	 * The MySQL class acts as a wrapper for connecting to the Database
	 * in Symphony. It utilises mysql_* functions in PHP to complete the usual
	 * querying. As well as the normal set of insert, update, delete and query
	 * functions, some convenience functions are provided to return results
	 * in different ways. Symphony uses a prefix to namespace it's tables in a
	 * database, allowing it play nice with other applications installed on the
	 * database. An errors that occur during a query throw a DatabaseException.
	 * By default, Symphony logs all queries to be used for Profiling and Debug
	 * devkit extensions.
	 */
	Class MySQL {

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
		 * Sets the current `$_log` to be an associative array with 'error'
		 * and 'query' keys and empty array values.
		 *
		 * @var array
		 */
		private static $_log = array(
			'error' => array(),
			'query' => array()
		);

		/**
		 * The number of queries this class has executed, defaults to 0.
		 *
		 * @var integer
		 */
		private static $_query_count = 0;

		/**
		 * Whether query caching is enabled or not. By default this set
		 * to true which will use SQL_CACHE to cache the results of queries
		 *
		 * @var boolean
		 */
		private static $_cache = true;

		/**
		 * An associative array of connection properties for this MySQL
		 * database including the host, port, username, password and
		 * selected database.
		 *
		 * @var array
		 */
		private static $_connection = array();

		/**
		 * The resource of the last result returned from mysql_query
		 *
		 * @var resource
		 */
		private $_result = null;

		/**
		 * The last query that was executed by the class
		 */
		private $_lastQuery  = null;

		/**
		 * By default, an array of arrays or objects representing the result set
		 * from the `$this->_lastQuery`
		 */
		private $_lastResult = array();

		/**
		 * Magic function that will flush the MySQL log and close the MySQL
		 * connection when the MySQL class is removed or destroyed.
		 *
		 * @link http://php.net/manual/en/language.oop5.decon.php
		 */
		public function __destruct(){
			$this->flush();
			$this->close();
		}

		/**
		 * Resets the result, `$this->_lastResult` and `$this->_lastQuery` to their empty
		 * values. Called on each query and when the class is destroyed.
		 */
		public function flush(){
			$this->_result = null;
			$this->_lastResult = array();
			$this->_lastQuery = null;
		}

		/**
		 * Sets the current `$_log` to be an associative array with 'error'
		 * and 'query' keys and empty array values.
		 */
		public static function flushLog(){
			self::$_log = array(
				'error' => array(),
				'query' => array()
			);
		}

		/**
		 * Returns the last error that occurred while querying MySQL
		 *
		 * @return array
		 *  An associative array with the last query, error number and
		 *  error message from MySQL.
		 */
		public static function getLastError(){
			return current(self::$_log['error']);
		}

		/**
		 * Returns the number of queries that has been executed
		 *
		 * @return integer
		 */
		public static function queryCount(){
			return self::$_query_count;
		}

		/**
		 * Sets query caching to true, this will prepend all READ_OPERATION
		 * queries with SQL_CACHE. Symphony be default enables caching. It
		 * can be turned off by setting the query_cache parameter to 'off' in the
		 * Symphony config file.
		 *
		 * @link http://dev.mysql.com/doc/refman/5.1/en/query-cache.html
		 */
		public static function enableCaching(){
			MySQL::$_cache = true;
		}

		/**
		 * Sets query caching to false, this will prepend all READ_OPERATION
		 * queries will SQL_NO_CACHE.
		 */
		public static function disableCaching(){
			MySQL::$_cache = false;
		}

		/**
		 * Returns boolean if query caching is enabled or not
		 *
		 * @return boolean
		 */
		public static function isCachingEnabled(){
			return MySQL::$_cache;
		}

		/**
		 * Symphony uses a prefix for all it's database tables so it can live peacefully
		 * on the same database as other applications. By default this is sym_, but it
		 * can be changed when Symphony is installed.
		 *
		 * @param string $prefix
		 *  The table prefix for Symphony, by default this is sym_
		 */
		public function setPrefix($prefix){
			MySQL::$_connection['tbl_prefix'] = $prefix;
		}

		/**
		 * Determines if a connection has been made to the MySQL server
		 *
		 * @return boolean
		 */
		public function isConnected(){
			return (isset(MySQL::$_connection['id']) && is_resource(MySQL::$_connection['id']));
		}

		/**
		 * Called when the script has finished executing, this closes the MySQL
		 * connection
		 *
		 * @return boolean
		 */
		public function close(){
			if($this->isConnected()) return mysql_close(MySQL::$_connection['id']);
		}

		/**
		 * Creates a connect to the database server given the credentials. If an
		 * error occurs, a DatabaseException is thrown, otherwise true is returned
		 *
		 * @param string $host
		 *  Defaults to null, which MySQL assumes as localhost.
		 * @param string $user
		 *  Defaults to null
		 * @param string $password
		 *  Defaults to null
		 * @param string $port
		 *  Defaults to 3306.
		 * @return boolean
		 */
		public function connect($host = null, $user = null, $password = null, $port ='3306', $database = null){

			MySQL::$_connection = array(
				'host' => $host,
				'user' => $user,
				'pass' => $password,
				'port' => $port,
				'database' => $database
			);

			MySQL::$_connection['id'] = mysql_connect(
				MySQL::$_connection['host'] . ":" . MySQL::$_connection['port'], MySQL::$_connection['user'], MySQL::$_connection['pass']
			);

			if(!$this->isConnected() || (!is_null($database) && !mysql_select_db(MySQL::$_connection['database'], MySQL::$_connection['id']))) {
				$this->__error();
			}

			return true;
		}

		/**
		 * Accessor for the current MySQL resource from PHP. May be
		 * useful for developers who want complete control over their
		 * database queries and don't want anything abstract by the MySQL
		 * class.
		 *
		 * @return resource
		 */
		public static function getConnectionResource() {
			return MySQL::$_connection['id'];
		}

		/**
		 * This function selects a MySQL database. Only used by installation
		 * and must exists for compatibility reasons. But might be removed
		 * in future versions. Not recommended its usage by developers.
		 *
		 * @link http://au2.php.net/manual/en/function.mysql-select-db.php
		 * @param string $db
		 *  The name of the database that is to be selected, defaults to null.
		 * @return boolean
		 */
		public function select($db=NULL){
			if ($db) MySQL::$_connection['database'] = $db;

			if (!mysql_select_db(MySQL::$_connection['database'], MySQL::$_connection['id'])) {
				$this->__error();
				MySQL::$_connection['database'] = null;
				return false;
			}

			return true;
		}

		/**
		 * This will set the character encoding of the connection for sending and
		 * receiving data. This function will only run if 'runtime_character_set_alter'
		 * is set to 'true' in the Symphony config. This is set to true by default during
		 * the Symphony installation. If no character encoding is provided, UTF-8
		 * is assumed.
		 *
		 * @link http://au2.php.net/manual/en/function.mysql-set-charset.php
		 * @param string $set
		 *  The character encoding to use, by default this 'utf8'
		 */
		public function setCharacterEncoding($set='utf8'){
			mysql_set_charset($set, MySQL::$_connection['id']);
		}

		/**
		 * This function will set the character encoding of the database so that any
		 * new tables that are created by Symphony use this character encoding
		 *
		 * @link http://dev.mysql.com/doc/refman/5.0/en/charset-connection.html
		 * @param string $set
		 *  The character encoding to use, by default this 'utf8'
		 */
		public function setCharacterSet($set='utf8'){
			$this->query("SET character_set_connection = '$set', character_set_database = '$set', character_set_server = '$set'");
			$this->query("SET CHARACTER SET '$set'");
		}

		/**
		 * This function will clean a string using the mysql_real_escape_string function
		 * taking into account the current database character encoding. Note that this
		 * function does not encode _ or %. If mysql_real_escape_string doesn't exist
		 * addslashes will be used as a backup option
		 *
		 * @param string $value
		 *  The string to be encoded into an escaped SQL string
		 * @return string
		 *  The escaped SQL string
		 */
		public static function cleanValue($value) {
			if (function_exists('mysql_real_escape_string')) {
				return mysql_real_escape_string($value);

			} else {
				return addslashes($value);
			}
		}

		/**
		 * This function will apply the cleanValue function to an associative
		 * array of data, encoding only the value, not the key. This function
		 * can handle recursive arrays. This function manipulates the given
		 * parameter by reference.
		 *
		 * @param array $array
		 *  The associative array of data to encode, this parameter is manipulated
		 *  by reference.
		 */
		public static function cleanFields(array &$array){
			foreach($array as $key => $val){

				// Handle arrays with more than 1 level
				if(is_array($val)){
					self::cleanFields($val);
					continue;
				}
				elseif(strlen($val) == 0){
					$array[$key] = 'NULL';
				}
				else{
					$array[$key] = "'" . self::cleanValue($val) . "'";
				}
			}
		}

		/**
		 * Determines whether this query is a read operation, or if it is a write operation.
		 * A write operation is determined as any query that starts with CREATE, INSERT,
		 * REPLACE, UPDATE, DELETE, OPTIMIZE or TRUNCATE. Anything else is
		 * considered to be a read operation which are subject to query caching.
		 *
		 * @return integer
		 *  `MySQL::__WRITE_OPERATION__` or `MySQL::__READ_OPERATION__`
		 */
		public function determineQueryType($query){
			return (preg_match('/^(create|insert|replace|delete|update|optimize|truncate)/i', $query) ? MySQL::__WRITE_OPERATION__ : MySQL::__READ_OPERATION__);
		}

		/**
		 * Takes an SQL string and executes it. This function will apply query
		 * caching if it is a read operation and if query caching is set. Symphony
		 * will convert the `tbl_` prefix of tables to be the one set during installation.
		 * A type parameter is provided to specify whether `$this->_lastResult` will be an array
		 * of objects or an array of associative arrays. The default is objects. This
		 * function will return boolean, but set `$this->_lastResult` to the result.
		 *
		 * @param string $query
		 *  The full SQL query to execute.
		 * @param string $type
		 *  Whether to return the result as objects or associative array. Defaults
		 *  to OBJECT which will return objects. The other option is ASSOC. If $type
		 *  is not either of these, it will return objects.
		 * @return boolean
		 *  True if the query executed without errors, false otherwise
		 */
		public function query($query, $type = "OBJECT"){

			if(empty($query)) return false;

			$query = trim($query);
			$query_type = $this->determineQueryType($query);

			if($query_type == MySQL::__READ_OPERATION__ && !is_null($this->isCachingEnabled()) && !preg_match('/^SELECT\s+SQL(_NO)?_CACHE/i', $query)){
				if($this->isCachingEnabled() === false) $query = preg_replace('/^SELECT\s+/i', 'SELECT SQL_NO_CACHE ', $query);
				elseif($this->isCachingEnabled() === true) $query = preg_replace('/^SELECT\s+/i', 'SELECT SQL_CACHE ', $query);
			}

			if(MySQL::$_connection['tbl_prefix'] != 'tbl_'){
				$query = preg_replace('/tbl_(\S+?)([\s\.,]|$)/', MySQL::$_connection['tbl_prefix'].'\\1\\2', $query);
			}

			//	TYPE is deprecated since MySQL 4.0.18, ENGINE is preferred
			if($query_type == MySQL::__WRITE_OPERATION__) {
				$query = preg_replace('/TYPE=(MyISAM|InnoDB)/i', 'ENGINE=$1', $query);
			}

			$query_hash = md5($query.microtime());

			self::$_log['query'][$query_hash] = array('query' => $query, 'start' => precision_timer());

			$this->flush();
			$this->_lastQuery = $query;

			$this->_result = mysql_query($query, MySQL::$_connection['id']);

			self::$_query_count++;

			if(mysql_error()){
				$this->__error();
			}
			else if(is_resource($this->_result)){
				if($type == "ASSOC") {
					while ($row = mysql_fetch_assoc($this->_result)){
						$this->_lastResult[] = $row;
					}
				}
				else {
					while ($row = mysql_fetch_object($this->_result)){
						$this->_lastResult[] = $row;
					}
				}

				mysql_free_result($this->_result);
			}

			self::$_log['query'][$query_hash]['time'] = precision_timer('stop', self::$_log['query'][$query_hash]['start']);

			return true;
		}

		/**
		 * Returns the last insert ID from the previous query. This is
		 * the value from an auto_increment field.
		 *
		 * @return integer
		 *  The last interested row's ID
		 */
		public function getInsertID(){
			return mysql_insert_id(MySQL::$_connection['id']);
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
		 * @return boolean
		 */
		public function insert(array $fields, $table, $updateOnDuplicate=false){

			// Multiple Insert
			if(is_array(current($fields))){
				$sql  = "INSERT INTO `$table` (`".implode('`, `', array_keys(current($fields))).'`) VALUES ';

				foreach($fields as $key => $array){
					// Sanity check: Make sure we dont end up with ',()' in the SQL.
					if(!is_array($array)) continue;

					self::cleanFields($array);
					$rows[] = '('.implode(', ', $array).')';
				}

				$sql .= implode(", ", $rows);
			}
			// Single Insert
			else{
				self::cleanFields($fields);
				$sql  = "INSERT INTO `$table` (`".implode('`, `', array_keys($fields)).'`) VALUES ('.implode(', ', $fields).')';

				if($updateOnDuplicate){
					$sql .= ' ON DUPLICATE KEY UPDATE ';

					foreach($fields as $key => $value) $sql .= " `$key` = $value,";

					$sql = trim($sql, ',');
				}
			}

			return $this->query($sql);
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
		 * @return boolean
		 */
		public function update($fields, $table, $where = null) {
			self::cleanFields($fields);
			$sql = "UPDATE $table SET ";

			foreach($fields as $key => $val)
				$rows[] = " `$key` = $val";

			$sql .= implode(', ', $rows) . (!is_null($where) ? ' WHERE ' . $where : null);

			return $this->query($sql);
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
		 * @return boolean
		 */
		public function delete($table, $where = null){
			$this->query("DELETE FROM $table WHERE $where");
		}

		/**
		 *
		 * @param string $query
		 *  The full SQL query to execute. Defaults to null, which will
		 *  use the _lastResult
		 * @param string $index_by_column
		 *  The name of a column in the table to use it's value to index
		 *  the result by. If this is omitted (and it is by default), an
		 *  array of associative arrays is returned, with the key being the
		 *  column names
		 * @return array
		 *  An associative array with the column names as the keys
		 */
		public function fetch($query = null, $index_by_column = null){

			if(!is_null($query))$this->query($query, "ASSOC");

			elseif(is_null($this->_lastResult)) {
				return array();
			}

			$result = $this->_lastResult;

			if(!is_null($index_by_column) && isset($result[0][$index_by_column])){
				$n = array();

				foreach($result as $ii) {
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
		 * @param integer $offset
		 *  The row to return from the SQL query. For instance, if the second
		 *  row from the result was required, the offset would be 1, because it
		 *  is zero based.
		 * @param string $query
		 *  The full SQL query to execute. Defaults to null, which will
		 *  use the `$this->_lastResult`
		 * @return array
		 *  If there is no row at the specified `$offset`, an empty array will be returned
		 *  otherwise an associative array of that row will be returned.
		 */
		public function fetchRow($offset = 0, $query = null){
			$result = $this->fetch($query);
			return (empty($result) ? array() : $result[$offset]);
		}

		/**
		 * Returns an array of values for a specified column in a given query.
		 * If no query is given, it will use the `$this->_lastResult`.
		 *
		 * @param string $column
		 *  The column name in the query to return the values for
		 * @param string $query
		 *  The full SQL query to execute. Defaults to null, which will
		 *  use the `$this->_lastResult`
		 * @return array
		 *  If there is no results for the `$query`, an empty array will be returned
		 *  otherwise an array of values for that given `$column` will be returned
		 */
		public function fetchCol($column, $query = null){
			$result = $this->fetch($query);

			if(empty($result)) return array();

			foreach ($result as $row){
				$return[] = $row[$column];
			}

			return $return;
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
		 * @return string
		 *  Returns the value of the given column, if it doesn't exist, null will be
		 *  returned
		 */
		public function fetchVar ($column, $offset = 0, $query = null){

			$result = $this->fetch($query);
			return (empty($result) ? null : $result[$offset][$column]);

		}

		/**
		 * If an error occurs in a query, this function is called which logs
		 * the last query and the error number and error message from MySQL
		 * before throwing a new DatabaseException
		 *
		 * @return DatabaseException
		 */
		private function __error() {
			$msg = mysql_error();
			$errornum = mysql_errno();

			self::$_log['error'][] = array(
				'query' => $this->_lastQuery,
				'msg' => $msg,
				'num' => $errornum
			);

			throw new DatabaseException(__('MySQL Error (%1$s): %2$s in query "%3$s"', array($errornum, $msg, $this->_lastQuery)), end(self::$_log['error']));
		}

		/**
		 * Returns all the log entries by type. There are two valid types,
		 * error and debug. If no type is given, the entire log is returned,
		 * otherwise only log messages for that type are returned
		 *
		 * @return array
		 *  An array of associative array's. Log entries of the error type
		 *  return the query the error occurred on and the error number and
		 *  message from MySQL. Log entries of the debug type return the
		 *  the query and the start/stop time to indicate how long it took
		 *  to run
		 */
		public function debug($type = null){
			if(!$type) return self::$_log;

			return ($type == 'error' ? self::$_log['error'] : self::$_log['query']);
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
		public function getStatistics(){
			$stats = array();
			$query_timer = 0.0;
			$slow_queries = array();

			$query_log = $this->debug('query');

			foreach($query_log as $key => $val)	{
				$query_timer += $val['time'];
				if($val['time'] > 0.0999) $slow_queries[] = $val;
			}

			return array(
				'queries' => MySQL::queryCount(),
				'slow-queries' => $slow_queries,
				'total-query-time' => number_format($query_timer, 4, '.', '')
			);
		}

		/**
		 * Convenience function to allow you to execute multiple SQL queries at once
		 * by providing a string with the queries delimited with a `;`
		 *
		 * @param string $sql
		 *  A string containing SQL queries delimited by `;`
		 * @return boolean
		 *  If one of the queries fails, false will be returned and no further queries
		 *  will be executed, otherwise true will be returned.
		 */
		public function import($sql){

			$queries = preg_split('/;[\\r\\n]+/', $sql, -1, PREG_SPLIT_NO_EMPTY);

			if(is_array($queries) && !empty($queries)){
				foreach($queries as $sql){
					if(trim($sql) != '') $result = $this->query($sql);
					if(!$result) return false;
				}
			}

			return true;
		}

	}
