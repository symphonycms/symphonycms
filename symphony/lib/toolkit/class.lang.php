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
		private static $_dates;
		private static $_instance;
		
		/**
		 * Load specified language file
		 */				
		public static function load($path, $lang, $clear=false) {

			// Clear dictionary
			if((bool)$clear === true || !(self::$_dictionary instanceof Dictionary)) {
				self::clear();
			}

			// Load dictionary
			$include = sprintf($path, $lang);	
			if(file_exists($include)){
				require($include);
			}

			// Define default dates
			if(empty(self::$_dates)) {
				$dates = array(
					'yesterday', 'today', 'tomorrow', 'now',
					'Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday',
					'Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat',
					'January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December',
					'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'
				);
				foreach($dates as $date) {
					self::$_dates[$date] = $date;
				}
			}

			// Merge dictionaries
			if(isset($dictionary) && is_array($dictionary)) {
				self::$_dictionary->merge($dictionary);

				// Add date translations
				foreach(self::$_dates as $date) {
					self::$_dates[$date] = __($date);
				}

			}
			
			// Populate transliterations
			if(isset($transliterations) && is_array($transliterations)) self::$_transliterations = array_merge(self::$_transliterations, $transliterations);
			if(empty(self::$_transliterations)) {
				include(TOOLKIT . '/include.transliterations.php');
				self::$_transliterations = $transliterations;
			}

		}
		
		/**
		 * Clear the current dictionary, transliteration and date arrays
		 */
		public static function clear() {
			self::$_dictionary = new Dictionary(array());
			self::$_transliterations = array();
			self::$_dates = array();
		}
		
		/**
		 * Load all language files (core and extensions)
		 *
		 * It may be possible that there are only translations for a single extension, 
		 * so don't stop if there is no core translation as Symphony will always display the English strings in this case.
		 */		
		public static function loadAll() {
			
			// Load core localisations
			$file = self::findLanguagePath(Symphony::lang()) . '/lang.%s.php';
			$path = sprintf($file, Symphony::lang());
			if(file_exists($path)) {
				self::load($file, Symphony::lang(), true);
			}

			// There is no need to load localisations for extensions during installation
			// so check existence of Extension Manager first
			if(class_exists(ExtensionManager)) {
				$ExtensionManager = new ExtensionManager(Administration::instance());
			
				// Load extension localisations
				foreach($ExtensionManager->listAll() as $handle => $e){
					$path = $ExtensionManager->__getClassPath($handle) . '/lang/lang.%s.php';
					if($e['status'] == EXTENSION_ENABLED && file_exists(sprintf($path, Symphony::lang()))){
						self::load($path, Symphony::lang());
					}			
				}
			}
		}
		
		/**
		 * Find the correct path to the core translations based on the language code
		 *
		 * The default English language strings are stored in /symphony/lib/lang whereas
		 * the localisation files for other languages are stored in the extension folder.
		 */		
		public static function findLanguagePath($lang) {
			$file = sprintf('/lang.%s.php', $lang);
			
			// Check language extensions
			if(!file_exists(LANG . $file)) {
				$extensions = General::listStructure(EXTENSIONS, array(), false, 'asc', EXTENSIONS);
				foreach($extensions['dirlist'] as $name) {
				
					// Explicitly match localisation extensions
					if(strpos($name, 'lang_') === false) continue;
					
					// Check language availability
					$path = EXTENSIONS . '/' . $name . '/lang';
					if(file_exists($path . $file)) {
						return $path;
					}
				}
			}
			
			// Default to Symphony's core language folder
			else {
				return LANG;
			}

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
		 * Get codes of available languages
		 *
		 * Return all available languages (core and extensions)
		 * @return array language codes, e. g. 'en'
		 */
		public static function getAvailableLanguages() {
			$languages = array();
			
			// Get core translation files
			$languages = self::getLanguageCodes(LIBRARY . '/lang', $languages);
			
			// Get extension translation files
			$extensions = General::listStructure(EXTENSIONS, array(), false, 'asc', EXTENSIONS);
			foreach($extensions['dirlist'] as $name) {
				$path = EXTENSIONS . '/' . $name . '/lang';
				if(file_exists($path)) $languages = self::getLanguageCodes($path, $languages);
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
			
				// Extract language code
				if(!$file->isDot() && preg_match('/^lang\.(\w+(-\w+)?)\.php$/', $file->getFilename(), $matches)) {
				
					// Get language name
					if(!isset($languages[$matches[1]])) {
						include($file->getPathname());
						$languages[$matches[1]] = $about['name'];
					}
				}
			}
			return $languages;
		}
		
		/**
		 * Check if Symphony is localised
		 *
		 * @return boolean
		 */
		public function isLocalized() {
			return (Symphony::lang() != 'en');
		}
		
		/**
		 * Localize dates
		 *
		 * Return the given date with translated month and day names
		 * @param string $string standard date that should be localized
		 * @return string
		 */
		public static function localizeDate($string) {
		
			// Only translate dates in localized environments
			if(self::isLocalized()) {
				foreach(self::$_dates as $english => $locale) {
					$string = str_replace($english, $locale, $string);
				}
			}
		
			return $string;
		}
		
		/**
		 * Standardize dates
		 *
		 * Return the given date with English month and day names
		 * @param string $string localized date that should be standardized
		 * @return string
		 */
		public static function standardizeDate($string) {
		
			// Only standardize dates in localized environments
			if(self::isLocalized()) {
				foreach(self::$_dates as $english => $locale) {
					$string = str_replace($locale, $english, $string);
				}
			}
		
			return $string;
		}
		
	}
	
