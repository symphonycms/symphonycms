<?php
	
	##### USE THIS CLASS TO INSTANCIATE BASED ON THE EXISTING SYMPHONY DB OBJECT ###
	# This is specific to Symphony.
	# $db = DBCLoader::instance();
	#
	Class DBCLoader{
		
		static private $_connection;
		static private $_connectionProfiled;

		static public function instance($enableProfiling=false){
			
			if($enableProfiling === true){
			
				if(!(self::$_connectionProfiled instanceof DBCMySQLProfiler)){
					self::$_connectionProfiled = self::__init(true);
				}
				
				return self::$_connectionProfiled;
			}
			
			
			if(!(self::$_connection instanceof DBCMySQL)){
				self::$_connection = self::__init();
			}
			
			return self::$_connection;			
		}

		static private function __init($enableProfiling=false){
			
			$details = (object)Symphony::$Configuration->get('database');

			$driver = 'DBCMySQL';
			if($enableProfiling) $driver .= 'Profiler';

			$db = new $driver;

			if($details->runtime_character_set_alter == 'yes'){
				$db->character_encoding = $details->character_encoding;
				$db->character_set = $details->character_set;
			}
	
			$connection_string = sprintf('mysql://%s:%s@%s:%s/%s/',
											$details->user, 
											$details->password, 
											$details->host, 
											$details->port, 
											$details->db);

			$db->connect($connection_string, Symphony::Database()->getConnectionResource());
			$db->prefix = $details->tbl_prefix;


			$db->force_query_caching = NULL;
			if(!is_null($details->disable_query_caching)) $db->force_query_caching = ($details->disable_query_caching == 'yes' ? true : false);

			return $db;			
		}

	}
	##### END SYMPHONY SPECIFIC LOAD CLASS #####
	
	
	
	Abstract Class Database{ 

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
		abstract public function insert(array $fields, $table);
		abstract public function update(array $fields, $table, $where=NULL);
		abstract public function query($query);	
		abstract public function truncate($table);		
		abstract public function delete($table, $where);
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
		
		public function __destruct(){
			@mysql_free_result($this->_result);
		}

		public function next(){
			$this->_position++;
		}
		
		public function position(){
			return $this->_position;
		}
		
		public function rewind(){
			@mysql_data_seek($this->_result, 0);
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
					
	}
	
	Final Class DatabaseUtilities{
		public static function resultColumn(DatabaseResultIterator $records, $column){
			$result = array();
			$records->rewind();
			
			if(!$records->valid()) return;
			
			$records->resultOutput = DatabaseResultIterator::RESULT_OBJECT;
			
			foreach($records as $r) $result[] = $r->$column;
			
			$records->rewind();
			return $result;
		}
		
		public static function resultValue(DatabaseResultIterator $records, $key, $offset=0){
			
			if($offset == 0) $records->rewind();
			else $records->position($offset);
			
			if(!$records->valid()) return;
			
			$records->resultOutput = DatabaseResultIterator::RESULT_OBJECT;
			
			return $records->current()->$key;
						
		}
	}
	
	 Class DBCMySQLResult extends DatabaseResultIterator{
		
		public function __construct(Database &$db, $result){
			parent::__construct($db, $result);
			$this->_length = (integer)@mysql_num_rows($this->_result);
			$this->resultOutput = self::RESULT_OBJECT;
		}
				
		public function current(){
			if($this->_lastPosition != NULL && $this->position() != ($this->_lastPosition + 1)){
				@mysql_data_seek($this->_result, $this->position());
			}

			$this->_current = ($this->resultOutput == self::RESULT_OBJECT ? mysql_fetch_object($this->_result) : mysql_fetch_assoc($this->_result)); 
			
			return $this->_current;
		}
	
	}
	
	Class DBCMySQL extends Database{ 

	    public function connected(){ 
	        if(is_resource($this->_connection)) return true;
			return false;
	    } 

	    public function affectedRows(){ 
	        return @mysql_affected_rows($this->_connection); 
	    } 

		private function __prepareQuery($query){
			
			$query = trim($query);
			
			if($this->prefix != 'tbl_') $query = preg_replace('/tbl_([^\b`]+)/i', $this->prefix . '\\1', $query);
			if(isset($details->force_query_caching)) $query = preg_replace('/^SELECT\s+/i', 'SELECT SQL_'.(!$details->force_query_caching ? 'NO_' : NULL).'CACHE ', $query);
			
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
		
	        if(is_null($details->path)) throw new Exception('MySQL database not selected'); 
	
	        if(is_null($details->host)) throw new Exception('MySQL hostname not set'); 
			
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
			if(!mysql_select_db($database, $this->_connection)) throw new Exception('Could not select database "'.$database.'"'); 
		}
		
		public function insert(array $fields, $table){
			foreach($this->cleanFields($fields) as $key => $val) 
				$rows[] = " `$key` = $val";
				
			$this->query("INSERT INTO $table SET " . implode(', ', $rows));
			
			return mysql_insert_id($this->_connection);
		}

		public function update(array $fields, $table, $where=NULL){
			foreach($this->cleanFields($fields) as $key => $val) 
				$rows[] = " `$key` = $val";
				
			return $this->query("UPDATE $table SET " . implode(', ', $rows) . ($where != NULL ? " WHERE $where" : NULL));
		}
		
		public function delete($table, $where){
			return $this->query("DELETE FROM `$table` WHERE $where");
		}	

		public function truncate($table){
			return $this->query("TRUNCATE TABLE `{$table}`");
		}

	    public function query($query, $returnType='DBCMySQLResult'){ 
	        if(!$this->connected()) throw new Exception('No Database Connection Found.'); 

			$query = $this->__prepareQuery($query);
		
			$this->_last_query = $query;
		
			$result = mysql_query($query, $this->_connection); 
	        if($result === false) throw new Exception('An error occurred while attempting to run query: ' . $query);

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
	}
	
	Final Class DBCMySQLProfiler extends DBCMySQL{
		
		private $_query_log;
		
		private static function __precisionTimer($action = 'start', $start_time = null){		
			list($time, $micro) = explode(' ', microtime());

			$currtime = $time + $micro;

			if(strtolower($action) == 'stop')
				return number_format(abs($currtime - $start_time), 4, '.', ',');	

			return $currtime;
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
		
		public function query($query, $returnType='DBCMySQLResult'){ 
			$start = self::__precisionTimer();
			$result = parent::query($query, $returnType);
			
			$query = preg_replace(array('/[\r\n]/', '/\s{2,}/'), ' ', $query);
			
			$this->_query_log[] = array($query, self::__precisionTimer('stop', $start));

			return $result;
		}
		
	}
