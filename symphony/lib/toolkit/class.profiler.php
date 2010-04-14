<?php

	define_safe('PROFILE_RUNNING_TOTAL', 0);
	define_safe('PROFILE_LAP', 1);

	Class Profiler{

		var $_starttime;
		var $_records;
		var $_seed;

		function Profiler(){
			$this->_records = array();
			$this->_starttime = precision_timer();
			$this->_seed = NULL;
		}

		function retrieveLast(){
			return end($this->_records);
		}

		function retrieveTotalRunningTime(){
			$last = $this->retrieveLast();

			return $last[1];
		}

		function retrieveTotalMemoryUsage(){
			$base = $this->retrieve(0);
			$total = $last = 0;
			foreach($this->retrieve() as $item){
				$total += max(0, (($item[5]-$base[5]) - $last));
				$last = $item[5]-$base[5];
			}

			return $total;
		}

		function sample($msg, $type=PROFILE_RUNNING_TOTAL, $group='General', $queries=NULL){

			$start = NULL;

			if($type == PROFILE_RUNNING_TOTAL)
				$this->_records[] = array($msg, precision_timer('stop', $this->_starttime), precision_timer(), $group, $queries, memory_get_usage());

			else{

				if($this->_seed){
					$start = $this->_seed;
					$this->_seed = NULL;
				}

				$prev = end($this->_records);
				$this->_records[] = array($msg, precision_timer('stop', ($start ? $start : $prev[2])), precision_timer(), $group, $queries, memory_get_usage());
			}
		}

		function retrieve($index=NULL){
			return ($index !== NULL ? $this->_records[$index] : $this->_records);
		}

		function retrieveByMessage($msg){
			if(!is_array($this->_records) || empty($this->_records)) return array();

			foreach($this->_records as $record){
				if($record[0] == $msg) return $record;
			}

			return array();
		}

		function retrieveGroup($group){
			if(!is_array($this->_records) || empty($this->_records)) return array();

			$result = array();

			foreach($this->_records as $record){
				if($record[3] == $group) $result[] = $record;
			}

			return $result;
		}

		function seed($time=NULL){
			$this->_seed = ($time ? $time : precision_timer());
		}
	}

