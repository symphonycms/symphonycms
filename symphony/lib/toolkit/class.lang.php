<?php
	/**
	 * @package toolkit
	 */
	/**
	 * The translation function accepts an English string and returns its translation
	 * to the active system language. If the given string is not available in the
	 * current dictionary the original English string will be returned. Given an optional
	 * `$inserts` array, the function will replace translation placeholders using `vsprintf()`.
	 * Since Symphony 2.3, it is also possible to have multiple translation of the same string
	 * according to the page namespace (i.e. the `value returned by `Symphony`s getPageNamespace()
	 * method). In your lang file, use the `$dictionary` key as namespace and its value as an array
	 * of context-aware translations, as shown below:
	 *
	 * $dictionary = array(
	 * 		[...]
	 * 		'Create new' => 'Translation for Create New',
	 * 		'/blueprints/datasources' => array(
	 * 			'Create new' =>
	 * 			'If we are inside a /blueprints/datasources/* page, this translation will be returned for the string'
	 * 		),
	 * 		[...]
	 *	);
	 *
	 * @see core.Symphony#getPageNamespace()
	 * @param string $string
	 *  The string that should be translated
	 * @param array $inserts (optional)
	 *  Optional array used to replace translation placeholders, defaults to NULL
	 * @return
	 *  Returns the translated string
	 */
	function __($string, $inserts=NULL) {
		return Lang::translate($string, $inserts);
	}

	/**
	 * The transliteration function replaces special characters.
	 *
	 * @param string $string
	 *  The string that should be cleaned-up
	 * @return
	 *  Returns the transliterated string
	 */
	function _t($string) {
		$patterns = array_keys(Lang::Transliterations());
		$values = array_values(Lang::Transliterations());
		return preg_replace($patterns, $values, $string);
	}

	/**
	 * The Lang class loads and manages languages
	 */
	Class Lang {

		/**
		 * Array of transliterations
		 * @var array
		 */
		private static $_transliterations;

		/**
		 * Code of active language
		 * @var string
		 */
		private static $_lang;

		/**
		 * Context information of all available languages
		 * @var array
		 */
		private static $_languages;

		/**
		 * Instance of the current Dictionary
		 * @var Dictionary
		 */
		private static $_dictionary;

		/**
		 * Array of months and weekday for localized date output
		 * @var array
		 */
		private static $_datetime_dictionary;

		/**
		 * Get dictionary
		 *
		 * @return array
		 *	Return the current dictionary
		 */
		public static function Dictionary() {
			return self::$_dictionary;
		}

		/**
		 * Get languages
		 *
		 * @return array
		 *	Return the array of languages
		 */
		public static function Languages() {
			return self::$_languages;
		}

		/**
		 * Get transliterations
		 *
		 * @return array
		 *	Returns the array of transliterations
		 */
		public static function Transliterations() {
			return self::$_transliterations;
		}

		/**
		 * Initialize dictionary, transliterations and dates array
		 */
		public static function initialize() {
			self::$_dictionary = array();

			// Load default datetime strings
			if(empty(self::$_datetime_dictionary)) {
				require(TEMPLATE . '/lang.datetime.php');

				foreach($datetime_strings as $string) {
					self::$_datetime_dictionary[$string] = $string;
				}
			}

			// Load default transliterations
			if(empty(self::$_transliterations)) {
				require(TEMPLATE . '/lang.transliterations.php');

				self::$_transliterations = $transliterations;
			}

		}

		/**
		 * This function is an internal alias for `__()`.
		 *
		 * @see toolkit.__()
		 * @param string $string
		 *  The string that should be translated
		 * @param array $inserts (optional)
		 *  Optional array used to replace translation placeholders, defaults to NULL
		 * @param string $namespace (optional)
		 *  Optional string used to define the namespace, defaults to NULL.
		 * @return string
		 *  Returns the translated string
		 */
		public function translate($string, array $inserts = null, $namespace = NULL) {
			$translated = self::find($string, $namespace);

			// Replace translation placeholders
			if(is_array($inserts) && !empty($inserts)) {
				$translated = vsprintf($translated, $inserts);
			}

			return $translated;
		}

		/**
		 * Given a string, return its translation.
		 *
		 * @param string $string
		 *  The string to look for
		 * @param string $namespace (optional)
		 *  Optional string used to define the namespace, defaults to NULL.
		 * @return string
		 *  Returns either the translation of the string or the original string if it
		 *  could not be found
		 */
		private function find($string, $namespace = NULL) {
			if(is_null($namespace)) $namespace = Symphony::getPageNamespace();

			if(isset($namespace) && trim($namespace) !== '' && isset(self::$_dictionary[$namespace][$string])) {
				return self::$_dictionary[$namespace][$string];
			}
			else if(isset(self::$_dictionary[$string])) {
				return self::$_dictionary[$string];
			}
			else {
				return $string;
			}
		}

		/**
		 * Set system language.
		 *
		 * @param string $lang
		 *	Language code, e. g. 'en' or 'pt-br'
		 * @param boolean $enabled
		 */
		public static function set($lang, $enabled = true) {
			if($lang && $lang != self::get()) {

				// Store current language code
				self::$_lang = $lang;

				// Activate language
				self::activate($enabled);

			}
		}

		/**
		 * Get current language
		 *
		 * @return string
		 */
		public static function get() {
			return self::$_lang;
		}

		/**
		 * Activate language, load translations for core and extensions. If the specified language
		 * cannot be found, Symphony will default to English. If no language is available at all,
		 * Symphony will throw an error.
		 *
		 * Note: Beginning with Symphony 2.2 translations bundled with extensions will only be loaded
		 * when the core dictionary of the specific language is available.
		 *
		 * @param boolean $enabled
		 */
		private static function activate($enabled = true) {

			// Fetch all available languages
			if(empty(self::$_languages)) {
				self::fetch();
			}

			// Language file available
			$current = self::$_languages[self::get()];
			if(is_array($current) && ($current['status'] == LANGUAGE_ENABLED || $enabled == false)) {

				// Load core translations
				self::load($current['path'], true);

				// Load extension translations
				foreach($current['extensions'] as $handle => $path) {
					self::load($path);
				}
			}

			// Language file unavailable
			else {

				// Use default language
				self::$_lang = 'en';
				$default = self::$_languages['en'];
				if(is_array($default)) {
					self::load($default['path'], true);

					// Log error
					if(class_exists('Symphony')) {
						Symphony::Log()->pushToLog(
							__('The selected language could not be found. Using default English dictionary instead.'),
							E_ERROR,
							true
						);
					}

				}

				// No language file available at all
				else {
					throw new Exception('Symphony needs at least one language file.');
				}

			}
		}

		/**
		 * Fetch all languages available in the core language folder and the language extensions.
		 * The function stores all language information in the public variable `$_languages`.
		 * It contains an array with the name, source, path and status of each language.
		 * Furthermore it add an array of all extensions available in a specific language. The language
		 * status (enabled/disabled) can only be determined when the Extension Manager has been
		 * initialized before. During installation all extension status are set to disabled.
		 */
		private static function fetch() {
			self::$_languages = array();

			// Fetch list of active extensions
			$enabled = array();
			if(class_exists('Symphony')) {
				$enabled = Symphony::ExtensionManager()->listInstalledHandles();
			}

			// Fetch core languages
			$directory = General::listStructure(LANG);
			foreach($directory['filelist'] as $file) {
				self::$_languages = array_merge(self::$_languages, self::fetchLanguage('core', LANG, $file, $enabled));
			}

			// Fetch extensions
			$extensions = new DirectoryIterator(EXTENSIONS);

			// Language extensions
			foreach($extensions as $extension) {
				$folder = $extension->getPathname() . '/lang';
				$directory = General::listStructure($folder);
				if(is_array($directory['filelist']) && !empty($directory['filelist'])) {
					foreach($directory['filelist'] as $file) {
						$temp = self::fetchLanguage($extension->getFilename(), $folder, $file, $enabled);
						$lang = key($temp);

						// Core translations
						if(strpos($extension->getFilename(), 'lang_') !== false) {

							// Prepare merging
							if(array_key_exists($lang, self::$_languages)) {
								unset($temp[$lang]['name']);
								unset(self::$_languages[$lang]['status']);
							}

							// Merge
							self::$_languages = array_merge_recursive(self::$_languages, $temp);
						}

						// Extension translations
						else {

							// Create language if not exists
							if(!array_key_exists($lang, self::$_languages)) {
								$language = array(
									$lang => array(
										'name' => $temp[$lang]['name'],
										'status' => LANGUAGE_DISABLED,
										'extensions' => array()
									)
								);
								self::$_languages = array_merge(self::$_languages, $language);
							}

							// Merge
							self::$_languages[$lang]['extensions'][$temp[$lang]['source']] = $temp[$lang]['path'];
						}
					}
				}
			}
		}

		/**
		 * Fetch language information for a single language.
		 *
		 * @param string $source
		 *	The filename of the extension driver where this language
		 *	file was found
		 * @param string $folder
		 *	The folder where this language file exists
		 * @param string $file
		 *	The filename of the language
		 * @param array $enabled
		 *	An associative array of enabled extensions from `tbl_extensions`
		 * @return array
		 *	Returns a multidimensional array of language information
		 */
		private static function fetchLanguage($source, $folder, $file, $enabled) {

			// Fetch language file
			$path = $folder . '/' . $file;
			if(file_exists($path)) {
				include($path);
			}

			// Get language code
			$lang = explode('.', $file);
			$lang = $lang[1];

			// Get status
			$status = LANGUAGE_DISABLED;
			if($source == 'core' || (!empty($enabled) && in_array($source, $enabled))) {
				$status = LANGUAGE_ENABLED;
			}

			// Save language information
			return array(
				$lang => array(
					'name' => $about['name'],
					'source' => $source,
					'path' => $path,
					'status' => $status,
					'extensions' => array()
				)
			);
		}

		/**
		 * Load language file. Each language file contains three arrays:
		 * about, dictionary and transliterations.
		 *
		 * @param string $path
		 *	Path of the language file that should be loaded
		 * @param boolean $clear
		 *	True, if the current dictionary should be cleared, defaults to false
		 */
		private static function load($path, $clear = false) {

			// Clear dictionary if requested
			if($clear === true) {
				self::$_dictionary = array();
			}

			// Load language file
			if(file_exists($path)) {
				require($path);
			}

			// Merge dictionaries ($dictionary is declared inside $path)
			if(isset($dictionary) && is_array($dictionary)) {
				self::$_dictionary = array_merge(self::$_dictionary, $dictionary);

				// Add date translations
				foreach(self::$_datetime_dictionary as $key => $value) {
					self::$_datetime_dictionary[$key] = __($key);
				}

			}

			// Populate transliterations ($transliterations is declared inside $path)
			if(isset($transliterations) && is_array($transliterations)) {
				self::$_transliterations = array_merge(self::$_transliterations, $transliterations);
			}

		}

		/**
		 * Get an array of the codes and names of all languages that are available system wide.
		 *
		 * Note: Beginning with Symphony 2.2 language files are only available
		 * when the language extension is explicitly enabled.
		 *
		 * @param boolean $enabled
		 * @return array
		 *	Returns an associative array of language codes and names, e. g. 'en' => 'English'
		 */
		public static function getAvailableLanguages($enabled = true) {
			$languages = array();

			// Get available languages
			foreach(self::$_languages as $key => $language) {
				if(($language['status'] == LANGUAGE_ENABLED || $enabled == false) && array_key_exists('path', $language)) {
					$languages[$key] = $language['name'];
				}
			}

			// Return languages codes
			return $languages;
		}

		/**
		 * Check if Symphony is localised.
		 *
		 * @return boolean
		 *	Returns true for localized system, false for English system
		 */
		public function isLocalized() {
			return (self::get() != 'en');
		}

		/**
		 * Localize dates.
		 *
		 * @param string $string
		 *	Standard date that should be localized
		 * @return string
		 *	Return the given date with translated month and day names
		 */
		public static function localizeDate($string) {

			// Only translate dates in localized environments
			if(self::isLocalized()) {
				foreach(self::$_datetime_dictionary as $english => $locale) {
					$string = preg_replace('/\b' . $english . '\b/i', $locale, $string);
				}
			}

			return $string;
		}

		/**
		 * Standardize dates.
		 *
		 * @param string $string
		 *	Localized date that should be standardized
		 * @return string
		 *	Returns the given date with English month and day names
		 */
		public static function standardizeDate($string) {

			// Only standardize dates in localized environments
			if(self::isLocalized()) {

				// Translate names to English
				foreach(self::$_datetime_dictionary as $english => $locale) {
					// We do not use $locale in the regexp as it might be empty
					// (i.e. you don't want to translate that string)
					$string = preg_replace('/\b' . $english . '\b/i', $english, $string);
				}

				// Replace custom date and time separator with space:
				// This is important, otherwise the `DateTime` constructor may break
				$separator = Symphony::Configuration()->get('datetime_separator', 'region');
				if($separator != ' ') {
					$string = str_replace($separator, ' ', $string);
				}
			}

			return $string;
		}

		/**
		 * Given a string, this will clean it for use as a Symphony handle. Preserves multi-byte characters.
		 *
		 * @param string $string
		 *	String to be cleaned up
		 * @param int $max_length
		 *	The maximum number of characters in the handle
		 * @param string $delim
		 *	All non-valid characters will be replaced with this
		 * @param boolean $uriencode
		 *	Force the resultant string to be uri encoded making it safe for URLs
		 * @param boolean $apply_transliteration
		 *	If true, this will run the string through an array of substitution characters
		 * @param array $additional_rule_set
		 *	An array of REGEX patterns that should be applied to the `$string`. This
		 *	occurs after the string has been trimmed and joined with the `$delim`
		 * @return string
		 *	Returns resultant handle
		 */
		public static function createHandle($string, $max_length = 255, $delim = '-', $uriencode = false, $apply_transliteration = true, $additional_rule_set = NULL) {
			// Use the transliteration table if provided
			if($apply_transliteration == true) $string = _t($string);

			return General::createHandle($string, $max_length, $delim, $uriencode, $additional_rule_set);
		}

		/**
		 * Given a string, this will clean it for use as a filename. Preserves multi-byte characters.
		 *
		 * @param string $string
		 *	String to be cleaned up
		 * @param string $delim
		 *	Replacement for invalid characters
		 * @param boolean $apply_transliteration
		 *	If true, umlauts and special characters will be substituted
		 * @return string
		 *	Returns created filename
		 */
		public static function createFilename($string, $delim='-', $apply_transliteration = true) {
			// Use the transliteration table if provided
			if($apply_transliteration == true) $string = _t($string);

			return General::createFilename($string, $delim);
		}

		/**
		 * Returns boolean if PHP has been compiled with unicode support. This is
		 * useful to determine if unicode modifier's can be used in regular expression's
		 *
		 * @link http://stackoverflow.com/questions/4509576/detect-if-pcre-was-built-without-the-enable-unicode-properties-or-enable-utf8
		 * @since Symphony 2.2.2
		 * @return boolean
		 */
		public static function isUnicodeCompiled() {
			return (@preg_match('/\pL/u', 'a') == 1 ? true : false);
		}

	}

	/**
	 * Status when a language is installed and enabled
	 * @var integer
	 */
	define_safe('LANGUAGE_ENABLED', 10);

	/**
	 * Status when a language is disabled
	 * @var integer
	 */
	define_safe('LANGUAGE_DISABLED', 11);
