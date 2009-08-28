<?php
	
	require_once(CORE . '/class.datetimeobj.php');
		
	Class Log{

		const kNOTICE = E_NOTICE;
		const kWARNING = E_WARNING;
		const kERROR = E_ERROR;

		const kAPPEND = 10;
		const kOVERWRITE = 11;

		private static $__errorTypeStrings = array (
			
			E_NOTICE         		=> 'NOTICE',
			E_WARNING        		=> 'WARNING',
			E_ERROR          		=> 'ERROR',
			E_PARSE          		=> 'PARSING ERROR',
                                       
			E_CORE_ERROR     		=> 'CORE ERROR',
			E_CORE_WARNING   		=> 'CORE WARNING',
			E_COMPILE_ERROR  		=> 'COMPILE ERROR',
			E_COMPILE_WARNING 		=> 'COMPILE WARNING',
			                           
			E_USER_NOTICE    		=> 'USER NOTICE',
			E_USER_WARNING   		=> 'USER WARNING',
			E_USER_ERROR     		=> 'USER ERROR',
			                           
			E_STRICT         		=> 'STRICT NOTICE',
			E_RECOVERABLE_ERROR  	=> 'RECOVERABLE ERROR'
			
		);

		private $_log_path;
		private $_log;
		private $_max_size;
		private $_archive;
	
		function __construct($logpath){
			$this->setLogPath($logpath);
			$this->setArchive(false);
			$this->setMaxSize(-1);
		}
	
		public function setLogPath($path){
			$this->_log_path = $path;
		}
	
		public function getLogPath(){
			return $this->_log_path;
		}
		
		public function setArchive($bool){
			$this->_archive = $bool;
		}
		
		public function setMaxSize($size){
			$this->_max_size = $size;
		}
		
		private function __defineNameString($type){
		
			if(isset(self::$__errorTypeStrings[$type])){
				return self::$__errorTypeStrings[$type];
			}

			return 'UNKNOWN';
			
		}
		
		public function pushToLog($message, $type=E_NOTICE, $writeToLog=false, $addbreak=true, $append=false){
			
			if(empty($this->_log) && !is_array($this->_log))
				$this->_log = array();
			
			if($append){
				$this->_log[count($this->_log) - 1]['message'] =  $this->_log[count($this->_log) - 1]['message'] . $message;
			
			}
			
			else{
				array_push($this->_log, array('type' => $type, 'time' => time(), 'message' => $message));
				$message = DateTimeObj::get('Y/m/d H:i:s') . ' > ' . $this->__defineNameString($type) . ': ' . $message;
			}
			
			if($writeToLog) $this->writeToLog($message, $addbreak);
			
		}
		
		public function popFromLog(){
			if(count($this->_log) != 0)
				return array_pop($this->_log);
				
			return false;
		}
		
		public function writeToLog($message, $addbreak=true){
			
			if(!$handle = @fopen($this->_log_path, 'a')) {
				$this->pushToLog("Could Not Open Log File '".$this->_log_path."'", self::kERROR);
				return false;
			}
	
			if(@fwrite($handle, $message . ($addbreak ? "\r\n" : '')) === FALSE) {
				$this->pushToLog('Could Not Write To Log', self::kERROR);
				return false;
			}
			
			@fclose($handle);
			
			return true;
	
		}
		
		public function getLog(){
			return $this->_log;
		}
		
		public function open($mode = self::kAPPEND){			
			
			if(!is_file($this->_log_path)) $mode = self::kOVERWRITE;
			
			if($mode == self::kAPPEND){
				if($this->_max_size > 0 && @filesize($this->_log_path) > $this->_max_size){
					$mode = self::kOVERWRITE;
					
					if($this->_archive){
						$handle = gzopen(LOGS . '/main.'.DateTimeObj::get('Ymdh').'.gz','w9');
						gzwrite($handle, @file_get_contents($this->_log_path));
						gzclose($handle);				
					}
				}
			}
			
			if($mode == self::kOVERWRITE){
				@unlink($this->_log_path);
			
				$this->writeToLog('============================================', true);
				$this->writeToLog('Log Created: ' . DateTimeObj::get('c'), true);
				$this->writeToLog('============================================', true);
			
				return 1;
			}
			
			return 2;
			
		}
		
		public function close(){
		
			$this->writeToLog('============================================', true);
			$this->writeToLog('Log Closed: ' . DateTimeObj::get('c'), true);
			$this->writeToLog("============================================\r\n\r\n\r\n", true);
				
		}
	}
	
