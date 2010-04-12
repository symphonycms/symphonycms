<?php
	
	Class TextFormatterException extends Exception {}

	Class TextFormatterFilterIterator extends FilterIterator{
		public function __construct($path){
			parent::__construct(new DirectoryIterator($path));
		}

		public function accept(){
			if($this->isDir() == false && preg_match('/^.+\.php$/i', $this->getFilename())){
				return true;
			}
			return false;
		}
	}	

	Class TextFormatterIterator implements Iterator{

		private $position;
		private $formatters;

		public function __construct(){
			
			$this->formatters = array();

			foreach(new DirectoryIterator(EXTENSIONS) as $dir){
				if(!$dir->isDir() || $dir->isDot() || !is_dir($dir->getPathname() . '/text-formatters')) continue;
				
				foreach(new TextFormatterFilterIterator($dir->getPathname() . '/text-formatters') as $file){
					$this->formatters[] = $file->getPathname();
				}
			}
			
		}
		
		public function length(){
			return count($this->formatters);
		}
		
		public function rewind(){
			$this->position = 0;
		}

		public function current(){
			return $this->formatters[$this->position];
		}

		public function key(){
			return $this->position;
		}

		public function next(){
			++$this->position;
		}

		public function valid(){
			return isset($this->formatters[$this->position]);
		}
	}

	Abstract Class TextFormatter{
		
		private static $iterator;
		private static $formatters;
		
		abstract public function run($string);
		
		public static function getHandleFromFilename($filename){
			return preg_replace('/.php$/i', NULL, $filename);
		}
		
		public static function load($path){
			
			if(!is_array(self::$formatters)){
				self::$formatters = array();
			}
			
			if(!file_exists($path)){
				throw new TextFormatterException("No such Formatter '{$path}'");
			}
			
			if(!isset(self::$formatters[$path])){
				self::$formatters[$path] = require_once($path);
			}
			
			return new self::$formatters[$path];
		}
		
		public static function loadFromHandle($handle){
			
			if(!is_array(self::$formatters)){
				self::$formatters = array();
			}
			
			if(!(self::$iterator instanceof TextFormatterIterator)){
				self::$iterator = new TextFormatterIterator;
			}
			
			self::$iterator->rewind();
			
			if(in_array($handle, array_values(self::$formatters))){
				$tmp = array_flip(self::$formatters);
				return new $tmp[$handle];
			}
			
			foreach(self::$iterator as $tf){
				if(basename($tf) == "{$handle}.php"){
					return self::load($tf);
				}
			}
			
			throw new TextFormatterException("No such Formatter '{$handle}'");
		}
	}
	
