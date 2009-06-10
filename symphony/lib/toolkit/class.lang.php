<?php

	## Provide an interface for translations
	function __($string, array $tokens=NULL){
		return Lang::Dictionary()->translate($string, $tokens);
	}
	
	## Provice an interface for transliterations
	function _t($str){
		
		$patterns = array_keys(Lang::Transliterations());
		$values = array_values(Lang::Transliterations());
		
		$str = preg_replace($patterns, $values, $str);
		
		return $str;
	}
	
	
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
		
		public function add($from, $to){
			$this->_strings[$from] = $to;
		}

		public function merge($strings){
			if(is_array($strings)) $this->_strings = array_merge($this->_strings, $strings);
		}
		
		public function remove($string){
			unset($this->_strings[$string]);
		}
	}
	
	Class Lang{
		
		private static $_dictionary;
		private static $_transliterations;		
		private static $_instance;
		
		private function __load($path, $lang, $clear=false){
			
			if((bool)$clear === true || !(self::$_dictionary instanceof Dictionary)){
				self::$_dictionary = new Dictionary(array());
				self::$_transliterations = array();
			}

			$include = sprintf($path, $lang);
			if(file_exists($include)) require($include);

			if(is_array($dictionary)) self::$_dictionary->merge($dictionary);
			if(is_array($transliterations)) self::$_transliterations = array_merge(self::$_transliterations, $transliterations);

			if(empty(self::$_transliterations)){
				include(TOOLKIT . '/include.transliterations.php');
				self::$_transliterations = $transliterations;
			}
		}
		
		public static function init($path, $lang){
			
			if(!(self::$_instance instanceof self)){
				self::$_instance = new self;
			}

			self::__load($path, $lang, true);
			
			return self::$_instance;
		}

		public static function add($path, $lang){
			self::__load($path, $lang);
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
		public static function createHandle($string, $max_length=255, $delim='-', $uriencode=false, $apply_transliteration=true, $additional_rule_set=NULL){

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
		
		/***

		Method: getBrowserLanguages
		Description: gets languages accepted by browser and returns array of them (sorted by priority when possible)
		Return: array of language codes

		***/
		public static function getBrowserLanguages() {
			static $languages;
			if(is_array($languages)) return $languages;

			$languages = array();

			if(strlen(trim($_SERVER['HTTP_ACCEPT_LANGUAGE'])) < 1) return $languages;
			if(!preg_match_all('/(\w+(?:-\w+)?,?)+(?:;q=(?:\d+\.\d+))?/', preg_replace('/\s+/', '', $_SERVER['HTTP_ACCEPT_LANGUAGE']), $matches)) return $languages;

			$priority=1.0;
			$languages = array();
			foreach($matches[0] as $def){
				list($list, $q) = explode(';q=', $def);
				if(!empty($q)) $priority=floatval($q);
				$list = explode(',', $list);
				foreach($list as $lang){
					$languages[$lang] = $priority;
					$priority -= 0.000000001;
				}
			}
			arsort($languages);
			$languages = array_keys($languages);
			## return list sorted by descending priority, e.g., array('en-gb','en');
			return $languages;
		}

		/***

		Method: getAvailableLanguages
		Description: gets languages available in symphony/lib/lang directory
		Return: array of language codes

		***/
		public static function getAvailableLanguages() {
			$languages = array();
			$iterator = new DirectoryIterator('./symphony/lib/lang');
			foreach($iterator as $file){
				if(!$file->isDot() && preg_match('/lang\.(\w+(-\w+)?)\.php$/', $file->getFilename(), $matches)){
					$languages[$matches[1]] = $file;
				}
			}
			return array_keys($languages);
		}
	}
	
