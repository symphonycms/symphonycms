<?php
	
	Class MySQL {
			
		const __WRITE_OPERATION__ = 0;
		const __READ_OPERATION__ = 1;
			
	    private $_connection = array();
	    private $_log;
	    private $_result;
	    private $_lastResult = array();
	    private $_lastQuery;
	    private $_affectedRows;
	    private $_insertID;
		private $_dumpTables = array();
		private $_client_info;
		private $_client_encoding;
		private $_query_count;
		
		private $_cache;
		private $_logEverything;
		
	    function __construct(){
			$this->_query_count = 0;
			$this->_cache = NULL;
			$this->_logEverything = NULL;
			$this->flushLog();
	    }

	    function __destruct(){
	        $this->flush();
	        $this->close();
	    }
	    
		public function toggleCaching(){
			$this->_cache = !$this->_cache;
		}
	
		public function enableCaching(){
			$this->_cache = true;
		}
		
		public function disableCaching(){
			$this->_cache = false;
		}

		public function isCachingEnabled(){
			return $this->_cache;
		}

		public function toggleLogging(){
			$this->_logEverything = !$this->_logEverything;
		}

		public function enableLogging(){
			$this->_logEverything = true;
		}

		public function disableLogging(){
			$this->_logEverything = false;
		}

		public function isLogging(){
			return $this->_logEverything;
		}
	
		public function setPrefix($prefix){
	        $this->_connection['tbl_prefix'] = $prefix;
	    }
	
		public function isConnected(){
	        return (isset($this->_connection['id']) && is_resource($this->_connection['id']));
	    }
	    
		public function getSelected(){
	        return $this->_connection['database'];
	    }
		
		public function getConnectionResource(){
			return $this->_connection['id'];
		}
		
		public function connect($host=NULL, $user=NULL, $password=NULL, $port ='3306'){
			
			$this->_connection['id'] = NULL;
			
	        if($host) $this->_connection['host'] = $host;
	        if($user) $this->_connection['user'] = $user;
	        if($password) $this->_connection['pass'] = $password;
	        if($port) $this->_connection['port'] = $port;
	        
	        $this->_connection['id'] = @mysql_connect($this->_connection['host'] . ':' . $this->_connection['port'], $this->_connection['user'], $this->_connection['pass']);
	        
	        if(!$this->isConnected()){
	            $this->__error();
	            return false;
	        }
	        
	        $this->_client_info = mysql_get_client_info();
			$this->_client_encoding = mysql_client_encoding($this->_connection['id']);

	        return true;
	
	    }
	    
	    public function setCharacterSet($set='utf8'){
		    $this->query("SET CHARACTER SET '$set'");
	    }
	    
	    public function setCharacterEncoding($set='utf8'){
		    $this->query("SET NAMES '$set'");    	  
	    }
			
	    public function select($db=NULL){
			
	        if($db) $this->_connection['database'] = $db;
					
	        if(!@mysql_select_db($this->_connection['database'], $this->_connection['id'])){
	            $this->__error();
	            $this->_connection['database'] = null;
	            return false;
	        }
	            
	        return true;
	    }
		
		public function cleanValue($value) {
			if (function_exists('mysql_real_escape_string')) {
				return mysql_real_escape_string($value);
				
			} else {
				return addslashes($value);
			}
		}
		
		public function cleanFields(&$array){
			foreach($array as $key => $val){				
				if($val == '') $array[$key] = 'NULL';				
				else $array[$key] = "'".(function_exists('mysql_real_escape_string') ? mysql_real_escape_string($val) : addslashes($val))."'";
			}
		}
		
		public function insert($fields, $table, $updateOnDuplicate=false){
	
		/*

			$result = true;

			if(is_array(current($fields))){
				foreach($fields as $key => $array){

					if($updateOnDuplicate) 
						if(!$this->insert($array, $table, $updateOnDuplicate)) return false;

					else{

						$sql  = "INSERT INTO `$table` (`".implode('`, `', array_keys(current($fields))).'`) VALUES ';

						foreach($fields as $key => $array){
							$this->cleanFields($array);
							$rows[] = '('.implode(', ', $array).')';
						}

						$sql .= implode(", ", $rows);

					}					

				}

			}

			else{
				$this->cleanFields($fields);
				$sql  = "INSERT INTO `$table` (`".implode('`, `', array_keys($fields)).'`) VALUES ('.implode(', ', $fields).')';

				if($updateOnDuplicate){

					$sql .= ' ON DUPLICATE KEY UPDATE ';

					foreach($fields as $key => $value) $sql .= " `$key` = $value,";

					$sql = trim($sql, ',');

				}

			}

			return $this->query($sql);


		*/		
	
			// Multiple Insert
			if(is_array(current($fields))){

				$sql  = "INSERT INTO `$table` (`".implode('`, `', array_keys(current($fields))).'`) VALUES ';
				
				foreach($fields as $key => $array){
					$this->cleanFields($array);
					$rows[] = '('.implode(', ', $array).')';
				}
				
				$sql .= implode(", ", $rows);
				
			} 
			
			// Single Insert
			else{
				$this->cleanFields($fields);
				$sql  = "INSERT INTO `$table` (`".implode('`, `', array_keys($fields)).'`) VALUES ('.implode(', ', $fields).')';
				
				if($updateOnDuplicate){
					
					$sql .= ' ON DUPLICATE KEY UPDATE ';
					
					foreach($fields as $key => $value) $sql .= " `$key` = $value,";
					
					$sql = trim($sql, ',');
				}
			}

			return $this->query($sql);
		}
		
		public function update($fields, $table, $where=NULL){
			$this->cleanFields($fields);
			$sql = "UPDATE $table SET ";
			
			foreach($fields as $key => $val)
				$rows[] = " `$key` = $val";
			
			$sql .= implode(', ', $rows) . ($where != NULL ? ' WHERE ' . $where : NULL);
			
			return $this->query($sql);
		}
		
		public function delete($table, $where){
			$this->query("DELETE FROM $table WHERE $where");
		}
		
	    public function close(){
	        if($this->isConnected()) return @mysql_close($this->_connection['id']);	
	    }
	
		public function determineQueryType($query){
			return (preg_match('/^(insert|replace|delete|update|optimize|truncate)/i', $query) ? self::__WRITE_OPERATION__ : self::__READ_OPERATION__);
		}
			
	    public function query($query){
			
		    if(empty($query)) return false;
		    
			$query = trim($query);
			
			$query_type = $this->determineQueryType($query);
			
			if($query_type == self::__READ_OPERATION__ && $this->isCachingEnabled() !== NULL && !preg_match('/^SELECT\s+SQL(_NO)?_CACHE/i', $query)){
				if($this->isCachingEnabled() === false) $query = preg_replace('/^SELECT\s+/i', 'SELECT SQL_NO_CACHE ', $query);
				elseif($this->isCachingEnabled() === true) $query = preg_replace('/^SELECT\s+/i', 'SELECT SQL_CACHE ', $query);
			}
			
	        if($this->_connection['tbl_prefix'] != 'tbl_'){
	            $query = preg_replace('/tbl_(\S+?)([\s\.,]|$)/', $this->_connection['tbl_prefix'].'\\1\\2', $query);
	        }

			$query_hash = md5($query.time());
			
			$this->_log['query'][$query_hash] = array('query' => $query, 'start' => precision_timer());

	        $this->flush();
	        $this->_lastQuery = $query;

			$this->_result = @mysql_query($query, $this->_connection['id']);	

			$this->_query_count++;

	        if(@mysql_error()){        
	            $this->__error();
	            return false;
	        }

	        while ($row = @mysql_fetch_object($this->_result)){	            
	            @array_push($this->_lastResult, $row);
	        }
				
	        if($query_type == self::__WRITE_OPERATION__){
					
	            $this->_affectedRows = @mysql_affected_rows();
					
	            if(stristr($query, 'insert') || stristr($query, 'replace')){
	                $this->_insertID = @mysql_insert_id($this->_connection['id']);
	            }
						
	        }
				
	        @mysql_free_result($this->_result);
			
			$this->_log['query'][$query_hash]['time'] = precision_timer('stop', $this->_log['query'][$query_hash]['start']);
			if($this->_logEverything) $this->_log['query'][$query_hash]['lastResult'] = $this->_lastResult;

	        return true;
				
	    }
		
		public function extractTargetTablesFromQuery($query){			
			if(!preg_match('/\\s+FROM\\s+(([\\w\\d\\-`_]+(,(\\s+)?)?)+)/i', $query, $matches)) return 'DUAL';
			return $matches[1];
		}
			
	    public function numOfRows(){
	        return count($this->_lastResult);	
	    }
			
	    public function getInsertID(){
	        return $this->_insertID;
	    }
	
		public function queryCount(){
			return $this->_query_count;
		}
	
	    public function fetch($query=NULL, $index_by_field=NULL){
			
	        if($query) $this->query($query);
	
	        elseif($this->_lastResult == NULL){
	            return array();
	        }

			$newArray = array();
	        foreach ($this->_lastResult as $row){
	            $newArray[] = get_object_vars($row);
	        }		
			
			if($index_by_field && isset($newArray[0][$index_by_field])){
			
			  $n = array();
			  
			  foreach($newArray as $ii)
			      $n[$ii[$index_by_field]] = $ii;
			      
			  $newArray = $n;  
			
			}
			
	        return $newArray;
			
	    }
			
	    public function fetchRow($offset=0, $query=NULL){
	
	        $arr = $this->fetch($query);
	        return (empty($arr) ? array() : $arr[$offset]);
	
	    }
			
	    public function fetchCol ($name, $query = NULL){
	
	        $arr = $this->fetch($query);
	        
		    if(empty($arr)) return array(); 
				
	        foreach ($arr as $row){
	            $result[] = $row[$name];
	        }
				
	        return $result;
	
	    }	
			
	    public function fetchVar ($varName, $offset = 0, $query = NULL){
	
	        $arr = $this->fetch($query);
	        return (empty($arr) ? NULL : $arr[$offset][$varName]);
	        
	    }
			
	    public function flush(){
	
	        $this->_result = NULL;
	        $this->_lastResult = array();
	        $this->_lastQuery = NULL;
	
	    }
	
		public function flushLog(){
			$this->_log = array('error' => array(), 'query' => array());
		}
			
	    private function __error($msg = NULL){

	        if(!$msg){
	            $msg = @mysql_error();
	            $errornum = @mysql_errno();
	        }
				
	        $this->_log['error'][] = array ('query' => $this->_lastQuery,
	                               			'msg' => $msg,
	                               			'num' => $errornum);

			trigger_error(__('MySQL Error (%1$s): %2$s in query "%3$s"', array($errornum, $msg, $this->_lastQuery)), E_USER_WARNING);
	    }
			
	    public function debug($section=NULL){			
	        if(!$section) return $this->_log;
	
			return ($section == 'error' ? $this->_log['error'] : $this->_log['query']);
	    }
	
		public function getLastError(){
			@rewind($this->_log['error']);
			return current($this->_log['error']);
		}
		
		public function getStatistics(){
			
			$stats = array();
			
			$query_log = $this->debug('query');
			$query_timer = 0.0;
			$slow_queries = array();
			foreach($query_log as $key => $val)	{
				$query_timer += floatval($val['time']);
				if($val['time'] > 0.0999) $slow_queries[] = $val;
			}				

			return array('queries' => $this->queryCount(),
						 'slow-queries' => $slow_queries,
						 'total-query-time' => number_format($query_timer, 4, '.', ''));

		}
	    
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

