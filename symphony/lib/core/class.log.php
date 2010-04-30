<?php
	
	require_once(CORE . '/class.datetimeobj.php');
		
	Class Log{

		const NOTICE = E_NOTICE;
		const WARNING = E_WARNING;
		const ERROR = E_ERROR;

		const APPEND = 10;
		const OVERWRITE = 11;

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
			
			if(file_exists($this->_log_path) && !is_writable($this->_log_path)){
				$this->pushToLog('Could Not Write To Log. It is not readable.', self::ERROR);
				return false;
			}
			return file_put_contents($this->_log_path, $message . ($addbreak ? "\r\n" : ''), FILE_APPEND);
	
		}
		
		public function getLog(){
			return $this->_log;
		}
		
		public function open($flag=self::APPEND, $mode=0777){
			
			if(!file_exists($this->_log_path)) $flag = self::OVERWRITE;
			
			if($flag == self::APPEND && file_exists($this->_log_path) && is_readable($this->_log_path)){
				if($this->_max_size > 0 && filesize($this->_log_path) > $this->_max_size){
					$flag = self::OVERWRITE;
					
					if($this->_archive){
						$file = LOGS . '/main.'.DateTimeObj::get('Ymdh').'.gz';
						$handle = gzopen($file,'w9');
						gzwrite($handle, file_get_contents($this->_log_path));
						gzclose($handle);
						chmod($file, intval($mode, 8));
					}
				}
			}

			if($flag == self::OVERWRITE){
				if(file_exists($this->_log_path) && is_writable($this->_log_path)){
					unlink($this->_log_path);
				}
			
				$this->writeToLog('============================================', true);
				$this->writeToLog('Log Created: ' . DateTimeObj::get('c'), true);
				$this->writeToLog('============================================', true);
				

				chmod($this->_log_path, intval($mode, 8));
			
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
	
