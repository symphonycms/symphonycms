<?php

	/**
	 * Symphony Language Class
	 *
	 * Provides an interface for translations and transliterations
	 */

	// Translations
	function __($string, array $tokens=NULL){
		if(!class_exists('Lang') || !(Lang::Dictionary() instanceof Dictionary)) return vsprintf($string, $tokens);
		return Lang::Dictionary()->translate($string, $tokens);
	}
	
	// Transliterations
	function _t($str){
		
		$patterns = array_keys(Lang::Transliterations());
		$values = array_values(Lang::Transliterations());
		
		$str = preg_replace($patterns, $values, $str);
		
		return $str;
	}
	
	/**
	 * Dictionary Class
	 *
	 * Contains the dictionary for the current language and provides the translate function
	 */
	Class Dictionary {
	
		private $_strings;
		
		public function __construct(array $strings) {
			$this->_strings = $strings;
		}
		
		public function translate($string, array $tokens=NULL) {
			$translated = $this->find($string);
			
			if($translated === false) $translated = $string;
			
			if(!is_null($tokens) && is_array($tokens) && !empty($tokens)) {
				$translated = vsprintf($translated, $tokens);
			}
		
			return $translated;
			
		}
		
		public function find($string) {
			if(isset($this->_strings[$string])) {
				return $this->_strings[$string];
			}
			
			return false;
		}
		
		public function add($from, $to) {
			$this->_strings[$from] = $to;
		}

		public function merge($strings) {
			if(is_array($strings)) $this->_strings = array_merge($this->_strings, $strings);
		}
		
		public function remove($string) {
			unset($this->_strings[$string]);
		}
		
	}
	
	/**
	 * Lanuage Class
	 *
	 * Loads and manages languages
	 */
	Class Lang {
		
		private static $_dictionary;
		private static $_transliterations;		
		private static $_instance;
		
		/**
		 * Load specified language file
		 */				
		public static function load($path, $lang, $clear=false) {

			if((bool)$clear === true || !(self::$_dictionary instanceof Dictionary)) {
				Lang::clear();
			}

			$include = sprintf($path, $lang);
		
			if(file_exists($include)){
				require($include);
			}

			if(isset($dictionary) && is_array($dictionary)) self::$_dictionary->merge($dictionary);
			if(isset($transliterations) && is_array($transliterations)) self::$_transliterations = array_merge(self::$_transliterations, $transliterations);

			if(empty(self::$_transliterations)) {
				include(TOOLKIT . '/include.transliterations.php');
				self::$_transliterations = $transliterations;
			}
		}
		
		/**
		 * Clear the current dictionary and transliteration arrays
		 */
		public static function clear() {
			self::$_dictionary = new Dictionary(array());
			self::$_transliterations = array();
		}
		
		/**
		 * Load all language files (core and extensions)
		 *
		 * It may be possible that there are only translations for an extension, 
		 * so don't stop if there is no core translation as Symphony will always display the English strings.
		 */		
		public static function loadAll($ExtensionManager) {		
			// Load localisation file for the Symphony core
			$file = Lang::findLanguagePath(Symphony::lang(), $ExtensionManager) . '/lang.%s.php';
			$path = sprintf($file, Symphony::lang());
			if(file_exists($path)) {
				Lang::load($file, Symphony::lang(), true);
			}

			// Load localisation files for extensions
			foreach($ExtensionManager->listAll() as $handle => $e){
				$path = $ExtensionManager->__getClassPath($handle) . '/lang/lang.%s.php';
				if($e['status'] == EXTENSION_ENABLED && file_exists(sprintf($path, Symphony::lang()))){
					Lang::add($path, Symphony::lang());
				}			
			}
		}
		
		/**
		 * Find the correct path to the core translations based on the language code
		 *
		 * The default English language strings are stored in /symphony/lib/lang whereas
		 * the localisation files for other languages are stored in the extension folder.
		 */		
		public static function findLanguagePath($lang, $ExtensionManager) {
			$file = sprintf('/lang.%s.php', $lang);
			if(!file_exists(LANG . $file)) {
				foreach($ExtensionManager->listAll() as $extension => $about) {
					// Explicitly match localisation extensions
					if(strpos($about['handle'], 'lang_') === false) continue;
					$path = EXTENSIONS . '/' . $about['handle'] . '/lang';
					if(file_exists($path . $file)) {
						return $path;
					}
				}
			}
			else {
				return LANG;
			}
		}
		
		public static function add($path, $lang) {
			self::load($path, $lang);
		}

		public static function Transliterations() {
			return self::$_transliterations;
		}
				
		public static function Dictionary() {
			return self::$_dictionary;
		}
		
		/**
		 * Create handle
		 *
		 * Given a string, this will clean it for use as a Symphony handle. Preserves multi-byte characters
		 * @param string $string
		 * @param int $max_length the maximum number of characters in the handle
		 * @param string $delim all non-valid characters will be replaced with this
		 * @param boolean $uriencode force the resultant string to be uri encoded making it safe for URL's
		 * @param boolean $apply_transliteration if true, this will run the string through an array of substitution characters
		 * @return string resultant handle
		 */					
		public static function createHandle($string, $max_length=255, $delim='-', $uriencode=false, $apply_transliteration=true, $additional_rule_set=NULL) {

			// Use the transliteration table if provided
			if($apply_transliteration == true) $string = _t($string);

			$max_length = intval($max_length);
			
			// Strip out any tag
			$string = strip_tags($string);
			
			// Remove punctuation
			$string = preg_replace('/[\\.\'"]+/', NULL, $string);	
						
			// Trim it
			if($max_length != NULL && is_numeric($max_length)) $string = General::limitWords($string, $max_length);
								
			// Replace spaces (tab, newline etc) with the delimiter
			$string = preg_replace('/[\s]+/', $delim, $string);

			// Find all legal characters
			preg_match_all('/[^<>?@:!-\/\[-`ëí;‘’…]+/u', $string, $matches);
			
			// Join only legal character with the $delim
			$string = implode($delim, $matches[0]);
			
			// Allow for custom rules
			if(is_array($additional_rule_set) && !empty($additional_rule_set)){
				foreach($additional_rule_set as $rule => $replacement) $string = preg_replace($rule, $replacement, $string);
			}
			
			// Remove leading or trailing delim characters
			$string = trim($string, $delim);
				
			// Encode it for URI use
			if($uriencode) $string = urlencode($string);	
					
			// Make it lowercase
			$string = strtolower($string);		

			return $string;
			
		}
		
		/**
		 * Create filename
		 *
		 * Given a string, this will clean it for use as a filename. 
		 * Preserves multi-byte characters.
		 * @param string $string string to clean
		 * @param string $delim replacement for invalid characters
		 * @param boolean $apply_transliteration if true, umlauts and special characters will be substituted
		 * @return string created filename
		 */
		public static function createFilename($string, $delim='-', $apply_transliteration=true) {

			// Use the transliteration table if provided
			if($apply_transliteration == true) $string = _t($string);

			// Strip out any tag
			$string = strip_tags($string);

			// Find all legal characters
			$count = preg_match_all('/[\p{L}\w:;.,+=~]+/u', $string, $matches);
			if($count <= 0 || $count == false){
				preg_match_all('/[\w:;.,+=~]+/', $string, $matches);
			}

			// Join only legal character with the $delim
			$string = implode($delim, $matches[0]);
			
			// Remove leading or trailing delim characters
			$string = trim($string, $delim);
			
			// Make it lowercase
			$string = strtolower($string);
			
			return $string;

		}
		
		/**
		 * Get browser languages
		 *
		 * Return languages accepted by browser as an array sorted by priority
		 * @return array language codes, e. g. 'en'
		 */	 
		public static function getBrowserLanguages() {
			static $languages;
			if(is_array($languages)) return $languages;

			$languages = array();

			if(strlen(trim($_SERVER['HTTP_ACCEPT_LANGUAGE'])) < 1) return $languages;
			if(!preg_match_all('/(\w+(?:-\w+)?,?)+(?:;q=(?:\d+\.\d+))?/', preg_replace('/\s+/', '', $_SERVER['HTTP_ACCEPT_LANGUAGE']), $matches)) return $languages;

			$priority = 1.0;
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
			// return list sorted by descending priority, e.g., array('en-gb','en');
			return $languages;
		}

		/**
         * Get codes of available languages
         *
		 * Return all available languages (core and extensions)
		 * @return array language codes, e. g. 'en'
		 */
		public static function getAvailableLanguages($ExtensionManager=false) {
			$languages = array();
			// Get core translation files
			$languages = self::getLanguageCodes(LIBRARY . '/lang', $languages);
			// Get extension translation files
			if($ExtensionManager) {
				foreach ($ExtensionManager->listAll() as $extension => $about) {
					$path = EXTENSIONS . '/' . $about['handle'] . '/lang';
					if(file_exists($path)) $languages = self::getLanguageCodes($path, $languages);
				}
			}
			// Return languages codes	
			return $languages;
		}
		
		/**
		 * Get languages
		 *
		 * Extract language codes and files
		 * @return array language codes and files
		 */
		public static function getLanguageCodes($path, $languages) {
			$iterator = new DirectoryIterator($path);
			foreach($iterator as $file) {
				if(!$file->isDot() && preg_match('/^lang\.(\w+(-\w+)?)\.php$/', $file->getFilename(), $matches)) {
					if(!isset($languages[$matches[1]])) {
						include($file->getPathname());
						$languages[$matches[1]] = $about['name'];
					}
				}
			}
			return $languages;
		}
		
	}
	
