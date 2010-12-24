<?php

	/**
	 * @package toolkit
	 */    
	/**
	 * The Profiler class tracks various performance metrics while a Symphony
	 * page is being generated. It provides a basic stopwatch functionality and
	 * memory usage statistics. Profiling occurs in both the Frontend and
	 * Administration execution. The Profiler implements the Singleton interface.
	 */

	Class Profiler implements Singleton {

		/**
		 * Holds the timestamp from when the profiler was first initialised
		 * @var integer
		 */
		protected static $_starttime  = 0;

		/**
		 * An array of arrays containing profiling samples. A record contains a
		 * profile message, the time since `$_starttime` timestamp, the end timestamp,
		 * the group for this record, the number of SQL queries and the result of
		 * memory_get_usage()
		 * @var array
		 */
		protected static $_samples = array();

		/**
		 * A seed holds a start time to be used in profiling. If this is not null
		 * the profiler will use this as the start time instead of `$_starttime`. This
		 * is set with the seed function.
		 * @var integer
		 * @see seed()
		 */
		protected static $_seed = null;

		/**
		 * An instance of the Profiler class
		 * @var Profiler
		 */
		protected static $_instance = null;

		/**
		 * Returns the Profiler instance, creating one if it does not exist
		 *
		 * @return Profiler
		 */
		public static function instance(){
			if(!(Profiler::$_instance instanceof Profiler)) {
				Profiler::$_instance = new self;
			}

			return Profiler::$_instance;
		}
		/**
		 * The constructor for the profile function sets the start time
		 */
		protected function __construct(){
			Profiler::$_starttime = precision_timer();
		}

		/**
		 * Sets the seed to be a timestamp so that time profiling will use this
		 * as a starting point
		 *
		 * @param integer $time
		 *  The time in seconds
		 */
		public static function seed($time = null){
			Profiler::$_seed = (is_null($time)) ? precision_timer() : $time;
		}

		/**
		 * This function creates a new report in the `$_samples` array where the message
		 * is the name of this report. By default, all samples are compared to the `$_starttime`
		 * but if the `PROFILE_LAP` constant is passed, it will be compared to specific `$_seed`
		 * timestamp. Samples can grouped by type (ie. Datasources, Events), but by default
		 * are grouped by 'General'. Optionally, the number of SQL queries that have occurred
		 * since either `$_starttime` or `$_seed` can be passed. Memory usage is taken with each
		 * sample which measures the amount of memory used by this script by PHP at the
		 * time of sampling.
		 *
		 * @param string $msg
		 *  A description for this sample
		 * @param integer $type
		 *  Either `PROFILE_RUNNING_TOTAL` or `PROFILE_LAP`
		 * @param string $group
		 *  Allows samples to be grouped together, defaults to General.
		 * @param integer $queries
		 *  The number of MySQL queries that occurred since the `$_starttime` or `$_seed`
		 */
		public function sample($msg, $type=PROFILE_RUNNING_TOTAL, $group='General', $queries=NULL){

			if($type == PROFILE_RUNNING_TOTAL) {
				Profiler::$_samples[] = array($msg, precision_timer('stop', Profiler::$_starttime), precision_timer(), $group, $queries, memory_get_usage());
			}
			else{
				if(!is_null(Profiler::$_seed)){
					$start = Profiler::$_seed;
					Profiler::$_seed = null;
				}
				else {
					$start = null;
				}

				$prev = Profiler::retrieveLast();
				Profiler::$_samples[] = array($msg, precision_timer('stop', ($start ? $start : $prev[2])), precision_timer(), $group, $queries, memory_get_usage());
			}
		}

		/**
		 * Given an index, return the sample at that position otherwise just
		 * return all samples.
		 *
		 * @param integer $index
		 *  The array index to return the sample for
		 * @return array
		 *  If no `$index` is passed an array of all the sample arrays are returned
		 *  otherwise just the sample at the given `$index` will be returned.
		 */
		public function retrieve($index = null){
			return !is_null($index) ? Profiler::$_samples[$index] : Profiler::$_samples;
		}

		/**
		 * Returns a sample by message, if no sample is found, an empty
		 * array is returned
		 *
		 * @param string $msg
		 *  The name of the sample to return
		 * @return array
		 */
		public function retrieveByMessage($msg){
			foreach(Profiler::$_samples as $record){
				if($record[0] == $msg) return $record;
			}

			return array();
		}

		/**
		 * Returns all the samples that belong to a particular group.
		 *
		 * @param string $group
		 * @return array
		 */
		public function retrieveGroup($group){
			$result = array();

			foreach(Profiler::$_samples as $record){
				if($record[3] == $group) $result[] = $record;
			}

			return $result;
		}

		/**
		 * Returns the last record from the `$_records` array
		 *
		 * @return array
		 */
		public static function retrieveLast(){
			return end(Profiler::$_samples);
		}

		/**
		 * Returns the difference between when the Profiler was initialised
		 * (aka `$_starttime`) and the last record the Profiler has.
		 *
		 * @return integer
		 */
		public function retrieveTotalRunningTime(){
			$last = Profiler::retrieveLast();

			return $last[1];
		}

		/**
		 * Returns the total memory usage from all samples taken by comparing
		 * each sample to the base memory sample.
		 *
		 * @return integer
		 *  Memory usage in bytes.
		 */
		public function retrieveTotalMemoryUsage(){
			$base = $this->retrieve(0);
			$total = $last = 0;
			foreach($this->retrieve() as $item){
				$total += max(0, (($item[5]-$base[5]) - $last));
				$last = $item[5]-$base[5];
			}
			return $total;
		}

	}

    /**
	 * Defines a constant for when the Profiler should be a complete snapshot of
	 * the page load, from the very start, to the very end.
	 * @var integer
	 */
	define_safe('PROFILE_RUNNING_TOTAL', 0);

	/**
	 * Defines a constant for when a snapshot should be between two points,
	 * usually when a start time has been given
	 * @var integer
	 */
	define_safe('PROFILE_LAP', 1);
