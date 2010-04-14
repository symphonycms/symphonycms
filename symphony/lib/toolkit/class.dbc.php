<?php

	Class DatabaseException extends Exception{
		private $_error;
		public function __construct($message, array $error=NULL){
			parent::__construct($message);
			$this->_error = $error;
		}
		public function getQuery(){
			return $this->_error['query'];
		}
		public function getDatabaseErrorMessage(){
			return $this->_error['msg'];
		}
		public function getDatabaseErrorCode(){
			return $this->_error['num'];
		}
	}

	Class DatabaseExceptionHandler extends GenericExceptionHandler{

		public static function render($e){

			require_once(TOOLKIT . '/class.xslproc.php');

			$xml = new DOMDocument('1.0', 'utf-8');
			$xml->formatOutput = true;

			$root = $xml->createElement('data');
			$xml->appendChild($root);

			$details = $xml->createElement('details');
			$details->appendChild($xml->createElement('message', General::sanitize($e->getDatabaseErrorMessage())));
			$details->appendChild($xml->createElement('query', General::sanitize($e->getQuery())));
			$root->appendChild($details);


			$trace = $xml->createElement('backtrace');

			foreach($e->getTrace() as $t){

				$item = $xml->createElement('item');

				if(isset($t['file'])) $item->setAttribute('file', General::sanitize($t['file']));
				if(isset($t['line'])) $item->setAttribute('line', $t['line']);
				if(isset($t['class'])) $item->setAttribute('class', General::sanitize($t['class']));
				if(isset($t['type'])) $item->setAttribute('type', $t['type']);
				$item->setAttribute('function', General::sanitize($t['function']));

				$trace->appendChild($item);
			}
			$root->appendChild($trace);

			if(is_object(Symphony::Database())){

				$debug = Symphony::Database()->debug();

				if(count($debug['query']) > 0){

					$queries = $xml->createElement('query-log');

					foreach($debug['query'] as $query){

						$item = $xml->createElement('item', General::sanitize($query['query']));
						if(isset($query['time'])) $item->setAttribute('time', $query['time']);
						$queries->appendChild($item);
					}

					$root->appendChild($queries);
				}

			}

			return parent::__transform($xml, 'exception.database.xsl');
		}
	}

	Abstract Class Database{
		const UPDATE_ON_DUPLICATE = 1;

	    private $_props;
	    protected $_connection;
		protected $_last_query;

	    public function __set($name, $value){
	        $this->_props[$name] = $value;
	    }

	    public function __get($name){
	        if(isset($this->_props[$name])) return $this->_props[$name];
			return null;
	    }

		abstract public function close();
		abstract public function escape($string);
		abstract public function connect($string);
		abstract public function select($database);
		abstract public function insert($table, array $fields, $flag = null);
		abstract public function update($table, array $fields, array $values = array(), $where = null);
		abstract public function delete($table, array $values = array(), $where = null);
		abstract public function query($query);
		abstract public function truncate($table);
		abstract public function lastError();
		abstract public function connected();

	}

	Abstract Class DatabaseResultIterator implements Iterator{

		const RESULT_ARRAY = 0;
		const RESULT_OBJECT = 1;

		protected $_db;
		protected $_result;
		protected $_position;
		protected $_lastPosition;
		protected $_length;
		protected $_current;

		public $resultOutput;

		public function __construct(&$db, $result){
			$this->_db = $db;
			$this->_result = $result;

			$this->_position = 0;
			$this->_lastPosition = NULL;

			$this->_current = NULL;
		}

		public function next(){
			$this->_position++;
		}

		public function offset($offset) {
			$this->_position = $offset;
		}

		public function position(){
			return $this->_position;
		}

		public function rewind() {
			$this->_position = 0;
		}

		public function key(){
			return $this->_position;
		}

		public function length(){
			return $this->_length;
		}

		public function valid(){
			return $this->_position < $this->_length;
		}

		public function resultColumn($column){
			$result = array();
			$this->rewind();

			if(!$this->valid()) return false;

			$this->resultOutput = DatabaseResultIterator::RESULT_OBJECT;

			foreach($this as $r) $result[] = $r->$column;

			$this->rewind();
			return $result;
		}

		public function resultValue($key, $offset=0){

			if($offset == 0) $this->rewind();
			else $this->offset($offset);

			if(!$this->valid()) return false;

			$this->resultOutput = DatabaseResultIterator::RESULT_OBJECT;

			return $this->current()->$key;

		}

	}

	 Class DBCMySQLResult extends DatabaseResultIterator{

		public function __construct(Database &$db, $result){
			parent::__construct($db, $result);

			if(!is_resource($this->_result)) throw new DatabaseException("Not a valid MySQL Resource.");

			$this->_length = (integer)mysql_num_rows($this->_result);
			$this->resultOutput = self::RESULT_OBJECT;
		}

		public function __destruct(){
			if(is_resource($this->_result)) mysql_free_result($this->_result);
		}

		public function current(){
			// TODO: Finalise Exception Message
			if($this->_length == 0) throw new DatabaseException('Cannot get current, no data returned.');

			if($this->_lastPosition != NULL && $this->position() != ($this->_lastPosition + 1)){
				mysql_data_seek($this->_result, $this->position());
			}

			$this->_current = ($this->resultOutput == self::RESULT_OBJECT
				? mysql_fetch_object($this->_result)
				: mysql_fetch_assoc($this->_result)
			);

			return $this->_current;
		}

		public function rewind(){
			// TODO: Finalise Exception Message
			if($this->_length == 0) throw new DatabaseException('Cannot rewind, no data returned.');

			mysql_data_seek($this->_result, 0);

			$this->_position = 0;
		}

	}

	Class DBCMySQL extends Database{
	    protected $_log;

	    protected function handleError($query) {
			$msg = @mysql_error();
			$num = @mysql_errno();

			$this->_log['error'][] = array(
				'query'	=> $query,
				'msg'	=> $msg,
				'num'	=> $num
			);

			throw new DatabaseException(
				__(
					'MySQL Error (%1$s): %2$s in query "%3$s"',
					array($num, $msg, $query)
				),
				end($this->_log['error'])
			);
	    }

	    public function connected(){
	        if(is_resource($this->_connection)) return true;
			return false;
	    }

	    public function affectedRows(){
	        return @mysql_affected_rows($this->_connection);
	    }

		private function __prepareQuery($query, array $values = array()){
			if ($this->prefix != 'tbl_') {
				$query = preg_replace('/tbl_([^\b`]+)/i', $this->prefix . '\\1', $query);
			}

			// Sanitise values:
			$values = array_map(array($this, 'escape'), $values);

			// Inject values:
			$query = vsprintf(trim($query), $values);

			if (isset($details->force_query_caching)) {
				$query = preg_replace('/^SELECT\s+/i', 'SELECT SQL_'.(!$details->force_query_caching ? 'NO_' : NULL).'CACHE ', $query);
			}

			return $query;
		}

	    public function connect($string, $resource=NULL){

			/*
				stdClass Object
				(
				    [scheme] => mysql
				    [host] => localhost
				    [port] => 8889
				    [user] => root
				    [pass] => root
				    [path] => symphony
				)
			*/

			$details = (object)parse_url($string);
			$details->path = trim($details->path, '/');

	        if(is_null($details->path)) throw new DatabaseException('MySQL database not selected');

	        if(is_null($details->host)) throw new DatabaseException('MySQL hostname not set');

			if(isset($resource) && is_resource($resource)){
				$this->_connection = $resource;
				return true;
			}

	        $this->_connection = @mysql_connect($details->host . ':' . $details->port, $details->user, $details->pass);

	        if($this->_connection === false){
				throw new Exception('There was a problem whilst attempting to establish a database connection. Please check all connection information is correct.');
			}

	        $this->select($details->path);

		    if(!is_null($this->character_encoding)) $this->query("SET CHARACTER SET '{$this->character_encoding}'");
		    if(!is_null($this->character_set)) $this->query("SET NAMES '{$this->character_set}'");

	    }

	    public function close(){
	        @mysql_close($this->_connection);
	        $this->_connection = null;
	    }

		public function escape($string){
			return (function_exists('mysql_real_escape_string')
						? mysql_real_escape_string($string, $this->_connection)
						: addslashes($string));
		}

		public function select($database){
			if(!mysql_select_db($database, $this->_connection)) throw new DatabaseException('Could not select database "'.$database.'"');
		}

		public function insert($table, array $fields, $flag = null) {
			$values = array(); $sets = array();

			foreach ($fields as $key => $value) {
				if (strlen($value) == 0) {
					$sets[] = "`{$key}` = NULL";
				}
				else {
					$values[] = $value;
					$sets[] = "`{$key}` = '%" . count($values) . '$s\'';
				}
			}

			$query = "INSERT INTO `{$table}` SET " . implode(', ', $sets);

			if ($flag == Database::UPDATE_ON_DUPLICATE) {
				$query .= ' ON DUPLICATE KEY UPDATE ' . implode(', ', $sets);
			}

			$this->query($query, $values);

			return mysql_insert_id($this->_connection);
		}

		public function update($table, array $fields, array $values = array(), $where = null){
			$sets = array(); $set_values = array();

			foreach ($fields as $key => $value) {
				if (strlen($value) == 0) {
					$sets[] = "`{$key}` = NULL";
				}
				else {
					$set_values[] = $value;
					$sets[] = "`{$key}` = '%s'";
				}
			}

			if (!is_null($where)) {
				$where = " WHERE {$where}";
			}

			$values = array_merge($set_values, $values);

			$this->query("UPDATE `{$table}` SET " . implode(', ', $sets) . $where, $values);

		}

		public function delete($table, array $values = array(), $where = null){
			return $this->query("DELETE FROM `$table` WHERE {$where}", $values);
		}

		public function truncate($table){
			return $this->query("TRUNCATE TABLE `{$table}`");
		}

	    public function query($query, array $values = array(), $returnType='DBCMySQLResult'){
	        if (!$this->connected()) throw new DatabaseException('No Database Connection Found.');

			$query = $this->__prepareQuery($query, $values);

			$this->_last_query = $query;

			$result = mysql_query($query, $this->_connection);

			if ($result === FALSE) $this->handleError($query);

			if (!is_resource($result)) return $result;

	        return new $returnType($this, $result);
	    }

		public function cleanFields(array $array){

			foreach($array as $key => $val){
				$array[$key] = (strlen($val) == 0 ? 'NULL' : "'".$this->escape(trim($val))."'");
			}

			return $array;
		}

		public function lastInsertID(){
			return mysql_insert_id($this->_connection);
		}

		public function lastError(){
			return array(
				mysql_errno(),
				($this->connected() ? mysql_error($this->_connection) : mysql_error()),
				$this->lastQuery()
			);
		}

		public function lastQuery(){
			return $this->_last_query;
		}

		public function debug() {
			// TODO: This function/look at moving it to Profiler based.
		}
		
		public function getLastError() {
			// TODO: This function
		}
	}

	/*
	**	Look at removing/altering/fixing this ..
	*/
	Final Class DBCMySQLProfiler extends DBCMySQL{

		private $_query_log;

		private static function __precisionTimer($action = 'start', $start_time = null){
			return precision_timer($action, $start_time);
		}

		public function __construct(){
			$this->_query_log = array();
		}

		public function log(){
			return $this->_query_log;
		}

		public function queryCount(){
			return count($this->_query_log);
		}

		public function slowQueryCount($threshold){

			$total = 0;

			foreach($this->_query_log as $q){
				if((float)$q[1] > $threshold) $total++;
			}

			return $total;
		}

		public function slowQueries($threshold){

			$queries = array();

			foreach($this->_query_log as $q){
				if((float)$q[1] > $threshold) $queries[] = $q;
			}

			return $queries;
		}


		public function queryTime(){

			$total = 0.0;

			foreach($this->_query_log as $q){
				$total += (float)$q[1];
			}

			return number_format((float)$total, 4, '.', ',');
		}

		public function query($query, array $values = array(), $returnType='DBCMySQLResult'){
			$start = self::__precisionTimer();
			$result = parent::query($query, $values, $returnType);

			$query = preg_replace(array('/[\r\n]/', '/\s{2,}/'), ' ', $query);

			$this->_query_log[] = array($query, self::__precisionTimer('stop', $start));

			return $result;
		}

	}
