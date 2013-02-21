<?php

	/**
	 * @package core
	 */

	 /**
	  * The DateTimeObj provides static functions regarding dates in Symphony.
	  * Symphony will set the default timezone of the system using the value from
	  * the Configuration values. Alternatively a new settings can be set using the
	  * `setSettings` function. Symphony parses all input dates against the Configuration
	  * date formats by default for better support with non English dates.
	  */
	Class DateTimeObj {

		/**
		 * Holds the various settings for the formats that the `DateTimeObj` should
		 * use when parsing input dates.
		 *
		 * @since Symphony 2.2.4
		 * @var array
		 */
		private static $settings = array();

		/**
		 * This function takes an array of settings for `DateTimeObj` to use when parsing
		 * input dates. The following settings are supported, `time_format`, `date_format`,
		 * `datetime_separator` and `timezone`. This equates to Symphony's default `region`
		 * group set in the `Configuration` class. If any of these values are not provided
		 * the class will fallback to existing `self::$settings` values
		 *
		 * @since Symphony 2.2.4
		 * @param array $settings
		 *  An associative array of formats for this class to use to format
		 *  dates
		 */
		public static function setSettings(array $settings = array()) {
			// Date format
			if(isset($settings['date_format'])) {
				self::$settings['date_format'] = $settings['date_format'];
			}

			// Time format
			if(isset($settings['time_format'])) {
				self::$settings['time_format'] = $settings['time_format'];
			}

			// Datetime separator
			if(isset($settings['datetime_separator'])) {
				self::$settings['datetime_separator'] = $settings['datetime_separator'];
			}
			else if (!isset(self::$settings['datetime_separator'])) {
				self::$settings['datetime_separator'] = ' ';
			}

			// Datetime format
			if(isset($settings['datetime_format'])) {
				self::$settings['datetime_format'] = $settings['datetime_format'];
			}
			else {
				self::$settings['datetime_format'] = self::$settings['date_format'] . self::$settings['datetime_separator'] . self::$settings['time_format'];
			}

			// Timezone
			if(isset($settings['timezone']) && !empty($settings['timezone'])) {
				self::$settings['timezone'] = $settings['timezone'];
				self::setDefaultTimezone($settings['timezone']);
			}
		}

		/**
		 * Accessor function for the settings of the DateTimeObj. Currently
		 * the available settings are `time_format`, `date_format`,
		 * `datetime_format` and `datetime_separator`. If `$name` is not
		 * provided, the entire `$settings` array is returned.
		 *
		 * @since Symphony 2.2.4
		 * @param string $name
		 * @return array|string|null
		 *  If `$name` is omitted this function returns array.
		 *  If `$name` is not set, this fucntion returns `null`
		 *  If `$name` is set, this function returns string
		 */
		public static function getSetting($name = null) {
			if(is_null($name)) return self::$settings;

			if(isset(self::$settings[$name])) return self::$settings[$name];

			return null;
		}

		/**
		 * Uses PHP's date_default_timezone_set function to set the system
		 * timezone. If the timezone provided is invalid, a `E_USER_WARNING` will be
		 * raised.
		 *
		 * @link http://php.net/manual/en/function.date-default-timezone-set.php
		 * @link http://www.php.net/manual/en/timezones.php
		 * @param string $timezone
		 *  A valid timezone identifier, such as UTC or Europe/Lisbon
		 */
		public static function setDefaultTimezone($timezone){
			if(!@date_default_timezone_set($timezone)) trigger_error(__('Invalid timezone %s', array($timezone)), E_USER_WARNING);
		}

		/**
		 * Validate a given date and time string
		 *
		 * @param string $string
		 *	A date and time string or timestamp to validate
		 * @return boolean
		 *	Returns true for valid dates, otherwise false
		 */
		public static function validate($string) {
			try {
				if(is_numeric($string) && (int)$string == $string) {
					$date = new DateTime('@' . $string);
				}
				else {
					$date = self::parse($string);
				}
			}
			catch(Exception $ex) {
				return false;
			}

			// String is empty or not a valid date
			if(empty($string) || $date === false) {
				return false;
			}

			// String is a valid date
			else {
				return true;
			}
		}

		/**
		 * Given a `$format`, and a `$timestamp`,
		 * return the date in the format provided. This function is a basic
		 * wrapper for PHP's DateTime object. If the `$timestamp` is omitted,
		 * the current timestamp will be used. Optionally, you pass a
		 * timezone identifier with this function to localise the output
		 *
		 * If you like to display a date in the backend, please make use
		 * of `DateTimeObj::format()` which allows date and time localization
		 *
		 * @see class.datetimeobj.php#format()
		 * @link http://www.php.net/manual/en/book.datetime.php
		 * @param string $format
		 *  A valid PHP date format
		 * @param integer $timestamp (optional)
		 *  A unix timestamp to format. 'now' or omitting this parameter will
		 *  result in the current time being used
		 * @param string $timezone (optional)
		 *  The timezone associated with the timestamp
		 * @return string|boolean
		 *  The formatted date, of if the date could not be parsed, false.
		 */
		public static function get($format, $timestamp = 'now', $timezone = null) {
			return self::format($timestamp, $format, false, $timezone);
		}

		/**
		 * Formats the given date and time `$string` based on the given `$format`.
		 * Optionally the result will be localized and respect a timezone differing
		 * from the system default. The default output is ISO 8601.
		 * Please note that for best compatibility with European dates it is recommended
		 * that your site be in a PHP5.3 environment.
		 *
		 * @since Symphony 2.2.1
		 * @param string $string (optional)
		 *  A string containing date and time, defaults to the current date and time
		 * @param string $format (optional)
		 *  A valid PHP date format, defaults to ISO 8601
		 * @param boolean $localize (optional)
		 *  Localizes the output, if true, defaults to true
		 * @param string $timezone (optional)
		 *  The timezone associated with the timestamp
		 * @return string|boolean
		 *  The formatted date, or if the date could not be parsed, false.
		 */
		public static function format($string = 'now', $format = DateTime::ISO8601, $localize = true, $timezone = null) {

			// Parse date
			$date = self::parse($string);

			if($date === false) return false;

			// Timezone
			// If a timezone was given, apply it
			if(!is_null($timezone)) {
				$date->setTimezone(new DateTimeZone($timezone));
			}
			// No timezone given, apply the default timezone
			else if (isset(self::$settings['timezone'])) {
				$date->setTimezone(new DateTimeZone(self::$settings['timezone']));
			}

			// Format date
			$date = $date->format($format);

			// Localize date
			// Convert date string from English back to the activated Language
			if($localize === true) {
				$date = Lang::localizeDate($date);
			}

			// Return custom formatted date, use ISO 8601 date by default
			return $date;
		}

		/**
		 * Parses the given string and returns a DateTime object.
		 * Please note that for best compatibility with European dates it is recommended
		 * that your site be in a PHP5.3 environment.
		 *
		 * @since Symphony 2.3
		 * @param string $string (optional)
		 *  A string containing date and time, defaults to the current date and time
		 * @return DateTime|boolean
		 *  The DateTime object, or if the date could not be parsed, false.
		 */
		public static function parse($string) {

			// Current date and time
			if($string == 'now' || empty($string)) {
				$date = new DateTime();
			}

			// Timestamp
			elseif(is_numeric($string)) {
				$date = new DateTime('@' . $string);
			}

			// Attempt to parse the date provided against the Symphony configuration setting
			// in an effort to better support multilingual date formats. Should this fail
			// this block will fallback to just passing the date to DateTime constructor,
			// which will parse the date assuming it's in an American format.
			else {
				// Standardize date
				// Convert date string to English
				$string = Lang::standardizeDate($string);

				// PHP 5.3: Apply Symphony date format using `createFromFormat`
				if(method_exists('DateTime', 'createFromFormat')) {
					$date = DateTime::createFromFormat(self::$settings['datetime_format'], $string);
					if($date === false) {
						$date = DateTime::createFromFormat(self::$settings['date_format'], $string);
					}

					// Handle dates that are in a different format to Symphony's config
					// DateTime is much the same as `strtotime` and will handle relative
					// dates.
					if($date === false) {
						try {
							$date = new DateTime($string);
						}
						catch(Exception $ex) {
							// Invalid date, it can't be parsed
							return false;
						}
					}
				}

				// PHP 5.2: Fallback to DateTime parsing.
				// Note that this parsing will not respect European dates.
				else {
					try {
						$date = new DateTime($string);
					}
					catch(Exception $ex) {
						// Invalid date, it can't be parsed
						return false;
					}
				}

				// If the date is still invalid, just return false.
				if($date === false) {
					return false;
				}
			}

			// Return custom formatted date, use ISO 8601 date by default
			return $date;
		}

		/**
		 * A wrapper for get, this function will force the GMT timezone.
		 *
		 * @param string $format
		 *  A valid PHP date format
		 * @param integer $timestamp (optional)
		 *  A unix timestamp to format. Omitting this parameter will
		 *  result in the current time being used
		 * @return string
		 *  The formatted date in GMT
		 */
		public static function getGMT($format, $timestamp = 'now'){
			return self::format($timestamp, $format, false, 'GMT');
		}

		/**
		 * A wrapper for get, this function will return a HTML string representing
		 * an `<abbr>` element which contained the formatted date of now, and an
		 * RFC 2822 formatted date (Thu, 21 Dec 2000 16:01:07 +0200) as the title
		 * attribute. Symphony uses this in it's status messages so that it can
		 * dynamically update how long ago the action took place using Javascript.
		 *
		 * @deprecated This will be removed in the next version of Symphony
		 * @param string $format
		 *  A valid PHP date format
		 * @return string
		 *  A HTML string of an `<abbr>` element with a class of 'timeago' and the current
		 *  date (RFC 2822) as the title element. The value is the current time as
		 *  specified by the `$format`.
		 */
		public static function getTimeAgo($format = __SYM_TIME_FORMAT__){
			if (!isset($string)) $string = '';
			$time = Widget::Time($string, $format);
			return $time->generate();
		}

		/**
		 * This functions acts as a standard way to get the zones
		 * available on the system. For PHP5.2, these constants are
		 * just copied from PHP5.3
		 *
		 * @since Symphony 2.3
		 * @link http://au2.php.net/manual/en/class.datetimezone.php
		 * @return array
		 */
		public static function getZones() {
			if(PHP_VERSION_ID >= 50300) {
				$ref = new ReflectionClass('DateTimeZone');
				return $ref->getConstants();
			}
			else {
				return array(
					'AFRICA' => 1,
					'AMERICA' => 2,
					'ANTARCTICA' => 4,
					'ARCTIC' => 8,
					'ASIA' => 16,
					'ATLANTIC' => 32,
					'AUSTRALIA' => 64,
					'EUROPE' => 128,
					'INDIAN' => 256,
					'PACIFIC' => 512,
					'UTC' => 1024
				);
			}
		}

		/**
		 * This functions acts as a standard way to get the timezones
		 * regardless of PHP version. It accepts a single parameter,
		 * zone, which returns the timezones associated with that 'zone'
		 *
		 * @since Symphony 2.3
		 * @link http://au2.php.net/manual/en/class.datetimezone.php
		 * @link http://au2.php.net/manual/en/datetimezone.listidentifiers.php
		 * @param string $zone
		 *  The zone for the timezones the field wants. This maps to the
		 *  DateTimeZone constants
		 * @return array
		 */
		public static function getTimezones($zone = null) {
			// PHP5.3 supports the `$what` parameter of the listIdentifiers function
			if(PHP_VERSION_ID >= 50300) {
				return DateTimeZone::listIdentifiers(constant('DateTimeZone::' . $zone));
			}
			else {
				$timezones = DateTimeZone::listIdentifiers();

				foreach($timezones as $index => $timezone) {
					if(stripos($timezone, $zone) === false) unset($timezones[$index]);
				}

				return $timezones;
			}
		}

		/**
		 * Loads all available timezones using `getTimezones()` and builds an
		 * array where timezones are grouped by their region (Europe/America etc.)
		 * The options array that is returned is designed to be used with
		 * `Widget::Select`
		 *
		 * @since Symphony 2.3
		 * @see core.DateTimeObj#getTimezones()
		 * @see core.Widget#Select()
		 * @param string $selected
		 *  A preselected timezone, defaults to null
		 * @return array
		 *  An associative array, for use with `Widget::Select`
		 */
		public static function getTimezonesSelectOptions($selected = null){
			$zones = self::getZones();
			$groups = array();

			foreach($zones as $zone => $value) {
				if($value >= 1024) break;

				$timezones = self::getTimezones($zone);
				$options = array();

				foreach($timezones as $timezone) {
					$tz = new DateTime('now', new DateTimeZone($timezone));

					$options[] = array($timezone, ($timezone == $selected), sprintf("%s %s",
						str_replace('_', ' ', substr(strrchr($timezone, '/'),1)),
						$tz->format('P')
					));
				}

				$groups[] = array('label' => ucwords(strtolower($zone)), 'options' => $options);
			}

			return $groups;
		}

		/**
		 * Returns an array of the date formats Symphony supports. These
		 * formats are a combination of valid PHP format tokens.
		 *
		 * @link http://au2.php.net/manual/en/function.date.php
		 * @since Symphony 2.3
		 * @return array
		 */
		public static function getDateFormats(){
			return array(
				'Y/m/d',	// e. g. 2011/01/20
				'm/d/Y',	// e. g. 01/20/2011
				'm/d/y',	// e. g. 10/20/11
				'Y-m-d',	// e. g. 2011-01-20
				'm-d-Y',	// e. g. 01-20-2011
				'm-d-y',	// e. g. 01-20-11
				'd.m.Y',	// e. g. 20.01.2011
				'j.n.Y',	// e. g. 20.1.2011 - no leading zeros
				'd.m.y',	// e. g. 20.01.11
				'j.n.y',	// e. g. 20.1.11 - no leading zeros
				'd F Y',	// e. g. 20 January 2011
				'd M Y',	// e. g. 20 Jan 2011
				'j. F Y',	// e. g. 20. January 2011 - no leading zeros
				'j. M. Y',	// e. g. 20. Jan. 2011 - no leading zeros
			);
		}

		/**
		 * Returns an array of the date formats Symphony supports by applying
		 * the format to the current datetime. The array returned is for use with
		 * `Widget::Select()`
		 *
		 * @since Symphony 2.3
		 * @see core.Widget#Select()
		 * @param string $selected
		 *  A preselected date format, defaults to null
		 * @return array
		 *  An associative array, for use with `Widget::Select`
		 */
		public static function getDateFormatsSelectOptions($selected = null){
			$formats = self::getDateFormats();
			$options = array();

			foreach($formats as $option) {
				$leadingZero = '';
				if(strpos($option, 'j') !== false || strpos($option, 'n') !== false) {
					$leadingZero = ' (' . __('no leading zeros') . ')';
				}
				$options[] = array($option, $option == $selected, self::format('now', $option) . $leadingZero);
			}

			return $options;
		}

		/**
		 * Returns an array of the time formats Symphony supports. These
		 * formats are a combination of valid PHP format tokens.
		 *
		 * @link http://au2.php.net/manual/en/function.date.php
		 * @since Symphony 2.3
		 * @return array
		 */
		public static function getTimeFormats(){
			return array(
				'H:i:s',	// e. g. 20:45:32
				'H:i',		// e. g. 20:45
				'g:i:s a',	// e. g. 8:45:32 pm
				'g:i a',	// e. g. 8:45 pm
			);
		}

		/**
		 * Returns an array of the time formats Symphony supports by applying
		 * the format to the current datetime. The array returned is for use with
		 * `Widget::Select()`
		 *
		 * @since Symphony 2.3
		 * @see core.Widget#Select()
		 * @param string $selected
		 *  A preselected time format, defaults to null
		 * @return array
		 *  An associative array, for use with `Widget::Select`
		 */
		public static function getTimeFormatsSelectOptions($selected = null){
			$formats = self::getTimeFormats();
			$options = array();

			foreach($formats as $option) {
				$options[] = array($option, $option == $selected, self::get($option));
			}

			return $options;
		}

	}
