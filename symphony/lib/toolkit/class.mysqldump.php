<?php

	

	Class MySQLDump{
	
		const DATA_ONLY = 1;
		const STRUCTURE_ONLY = 2;
		const ALL = 3;
		const CRLF = "\r\n";

		private $_connection;
	
		function __construct(&$connection){
			$this->_connection = $connection;
		}

		public function export($match=null, $flag=self::ALL, $condition=NULL){
			$data = '';

			$tables = $this->__getTables($match);
			foreach ($tables as $name => $info){
			
				if($flag == self::ALL || $flag == self::STRUCTURE_ONLY){
					$data .= self::CRLF . "-- *** STRUCTURE: `$name` ***" . self::CRLF;
					$data .= "DROP TABLE IF EXISTS `$name`;" . self::CRLF;
					$data .= $this->__dumpTableSQL($name, $info['type'], $info['fields'], $info['indexes']);
				}
			
				if($flag == self::ALL || $flag == self::DATA_ONLY){
					$data .= self::CRLF . "-- *** DATA: `$name` ***" . self::CRLF;
					if(strtoupper($info['type']) == 'INNODB'){
						$data .= "SET FOREIGN_KEY_CHECKS = 0;" . self::CRLF;
					}
				
					$data .= $this->__dumpTableData ($name, $info['fields'], $condition);
					if(strtoupper($info['type']) == 'INNODB'){
						$data .= "SET FOREIGN_KEY_CHECKS = 1;" . self::CRLF;
					}
				}
			}

			return $data;
		}
	
		private function __dumpTableData($name, $fields, $condition=NULL){
			$fieldList = join (', ', array_map (create_function ('$x', 'return "`$x`";'), array_keys ($fields)));
			$query = 'SELECT ' . $fieldList;
			$query .= ' FROM `' . $name . '`';
			if($condition != NULL) $query .= ' WHERE ' . $condition;
			$rows = $this->_connection->fetch ($query);
			$value = '';

			if(!is_array($rows) || empty($rows)) return NULL;

			foreach ($rows as $row){
				$value .= 'INSERT INTO `' . $name . '` (' . $fieldList . ") VALUES (";
				$fieldValues = array();
			
				foreach ($fields as $fieldName => $info){
					$fieldValue = $row[$fieldName];

					if($info['null'] == 1 && trim($fieldValue) == ''){
						$fieldValues[] = "NULL";
					
					}elseif(substr($info['type'], 0, 4) == 'enum'){
						$fieldValues[] = "'".$fieldValue."'";
						
					}elseif(is_numeric ($fieldValue)){
						$fieldValues[] = $fieldValue;
					
					}else {
						$fieldValues[] = "'" . mysql_real_escape_string ($fieldValue) . "'";
					}
				}

				$value .= join (', ', $fieldValues) . ");" . self::CRLF;

			}

			return $value;
		}
	
		private function __dumpTableSQL($table, $type, $fields, $indexes){

			$query = 'SHOW CREATE TABLE `' . $table . '`';
			$result = $this->_connection->fetch($query);
			$result = array_values($result[0]);
			return $result[1] . ";" . self::CRLF;
		}

		private function __getTables($match=null){
			$query = 'SHOW TABLES' . ($match ? " LIKE '$match'" : '');
		
			$rows = $this->_connection->fetch ($query);
			$rows = array_map (create_function ('$x', 'return array_values ($x);'), $rows);
			$tables = array_map (create_function ('$x', 'return $x[0];'), $rows);

			$result = array();

			foreach ($tables as $table){
				$result[$table]            = array();
				$result[$table]['fields']  = $this->__getTableFields ($table);
				$result[$table]['indexes'] = $this->__getTableIndexes ($table);
				$result[$table]['type']    = $this->__getTableType ($table);
			}

			return $result;
		}

		private function __getTableType($table){
			$query = "SHOW TABLE STATUS LIKE '" . addslashes($table) . "'";
			$info = $this->_connection->fetch ($query);
			return $info[0]['Type'];
		}

		private function __getTableFields($table){
			$result = array();
			$query  = 'DESC `' . $table . '`';
			$fields = $this->_connection->fetch($query);

			foreach ($fields as $field){
				$name    = $field['Field'];
				$type    = $field['Type'];
				$null    = (strtoupper ($field['Null']) == 'YES');
				$default = $field['Default'];
				$extra   = $field['Extra'];

				$field = array(
					'type'    => $type,
					'null'    => $null,
					'default' => $default,
					'extra'   => $extra
				);
			
				$result[$name] = $field;
			}

			return $result;
		}

		private function __getTableIndexes($table){
			$result  = array();
			$query   = "SHOW INDEX FROM `$table`";
			$indexes = $this->_connection->fetch($query);

			foreach ($indexes as $index){
				$name     = $index['Key_name'];
				$unique   = !$index['Non_unique'];
				$column   = $index['Column_name'];
				$sequence = $index['Seq_in_index'];
				$length   = $index['Cardinality'];

				if(!isset ($result[$name])){
					$result[$name] = array();
					$result[$name]['columns'] = array();
					if(strtoupper ($name) == 'PRIMARY'){
						$result[$name]['type'] = 'PRIMARY KEY';
					}
					elseif($unique){
						$result[$name]['type'] = 'UNIQUE';
					}
					else {
						$result[$name]['type'] = 'INDEX';
					}
				}

				$result[$name]['columns'][$sequence-1] = array('name' => $column, 'length' => $length);
			}

			return $result;
		}
	}
