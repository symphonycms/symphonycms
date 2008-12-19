<?php

	## Provide an interface for translations
	function __($string, array $tokens=NULL){
		return Lang::Dictionary()->translate($string, $tokens);
	}
	
	## Provice an interface for transliterations
	/*
	function _t($str){
		
		$patterns = array_keys(Lang::Transliterations());
		$values = array_values(Lang::Transliterations());
		
		$str = preg_replace($patterns, $values, $str);
		
		return $str;
	}
	*/
	
	Class Dictionary{
		private $_strings;
		
		public function __construct(array $strings){
			$this->_strings = $strings;
		}
		
		public function translate($string, array $tokens=NULL){
			$translated = $this->find($string);
			
			if($translated === false) $translated = $string;
			
			if(!is_null($tokens) && is_array($tokens) && !empty($tokens)){
				$translated = vsprintf($translated, $tokens);
			}
		
			return $translated;
			
		}
		
		public function find($string){
			if(isset($this->_strings[$string])){
				return $this->_strings[$string];
			}
			
			return false;
		}
		
		public function add($string){
			$this->_strings[] = $string;
		}
		
		public function remove($string){
			unset($this->_strings[$string]);
		}
	}
	
	Class Lang{
		
		private static $_dictionary;
		private static $_transliterations;		
		private static $_instance;
		
		private function __load($path, $lang){
			
			$include = sprintf($path, $lang);
			
			if(!file_exists($include)){ 
				throw new Exception(sprintf('Lang file "%s" could not be loaded. Please check path.', $include));
			}
			
			require(sprintf($path, $lang));
			
			self::$_dictionary = new Dictionary($dictionary);
			self::$_transliterations = $transliterations;
		}
		
		public static function init($path, $lang){
			
			if(!(self::$_instance instanceof self)){
				self::$_instance = new self;
			}
			
			self::__load($path, $lang);
			
			return self::$_instance;
		}

		public static function Transliterations(){
			return self::$_transliterations;
		}
				
		public static function Dictionary(){
			return self::$_dictionary;
		}
		
		/***
		
		Method: createHandle
		Description: given a string, this will clean it for use as a Symphony handle
		Param: $string - string to clean
		       $max_length - the maximum number of characters in the handle
			   $delim - all non-valid characters will be replaced with this
			   $uriencode - force the resultant string to be uri encoded making it safe for URL's
			   $apply_transliteration - If true, this will run the string through an array of substitution characters
		Return: resultant handle

		***/					
		public static function createHandle($string, $max_length=50, $delim='-', $uriencode=false, $apply_transliteration=true, $additional_rule_set=NULL){

			## Use the transliteration table if provided
			if($apply_transliteration) $string = _t($string);

			$max_length = intval($max_length);
			
			## Strip out any tag
			$string = strip_tags($string);
			
			## Remove punctuation
			$string = preg_replace('/([\\.\'"]++)/', '', $string);	
						
			## Trim it
			if($max_length != NULL && is_numeric($max_length)) $string = General::limitWords($string, $max_length);
								
			## Replace spaces (tab, newline etc) with the delimiter
			$string = preg_replace('/([\s]++)/', $delim, $string);					
								
			## Replace underscores and other non-word, non-digit characters with $delim
			//$string = preg_replace('/[^a-zA-Z0-9]++/', $delim, $string);
			$string = preg_replace('/[<>?@:!-\/\[-`ëí;‘’]++/', $delim, $string);
			
			## Allow for custom rules
			if(is_array($additional_rule_set) && !empty($additional_rule_set)){
				foreach($additional_rule_set as $rule => $replacement) $string = preg_replace($rule, $replacement, $string);
			}
			
			## Remove leading or trailing delim characters
			$string = trim($string, $delim);
				
			## Encode it for URI use
			if($uriencode) $string = urlencode($string);	
					
			## Make it lowercase
			$string = strtolower($string);		

			return $string;
			
		}
		
		/***

		Method: createFilename
		Description: given a string, this will clean it for use as a filename. Preserves multi-byte characters
		Param: $string - string to clean
			   $delim - all non-valid characters will be replaced with this
			   $apply_transliteration - If true, this will run the string through an array of substitution characters
		Return: resultant filename

		***/					
		public static function createFilename($string, $delim='-', $apply_transliteration=true){

			## Use the transliteration table if provided
			if($apply_transliteration) $string = _t($string);

			## Strip out any tag
			$string = strip_tags($string);				

			## Find all legal characters
			preg_match_all('/[\p{L}\w:;.,+=~]+/', $string, $matches);

			## Join only legal character with the $delim
			$string = implode($delim, $matches[0]);

			return $string;

		}
		
	}
	
