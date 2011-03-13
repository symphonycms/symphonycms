<?php

	/**
	 * @package core
	 */

	 /**
	  * The DateTimeObj provides static functions regarding dates in Symphony.
	  * Symphony will set the default timezone of the system using it's configuration
	  * values.
	  */
	Class DateTimeObj{

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
			if(!@date_default_timezone_set($timezone)) trigger_error(__("Invalid timezone '{$timezone}'"), E_USER_WARNING);
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
		 * @return string
		 *  The formatted date
		 */
		public static function get($format, $timestamp = 'now', $timezone = null) {
			
			// Parse date
			if(ctype_digit($timestamp)) {
				$timestamp = '@' . $timestamp;
			}
			$date = new DateTime($timestamp);
			
			// Timezone
			if($timezone !== null) {
				$date->setTimezone(new DateTimeZone($timezone));
			}
			
			// Format date
			return $date->format($format);
		}
		
		/**
		 * Formats the given date and time `$string` based on the given `$format`.
		 * Optionally the result will be localized and respect a timezone differing 
		 * from the system default. The default output is ISO 8601.
		 *
		 * @since Symphony 2.2.1
		 * @param string $string (optional)
		 *	A string containing date and time, defaults to the current date and time
		 * @param string $format (optional)
		 *	A valid PHP date format, defaults to ISO 8601
		 * @param boolean $localize (optional)
		 *	Localizes the output, if true, defaults to false
		 * @param string $timezone (optional)
		 *	The timezone associated with the timestamp
		 * @return string
		 *	The formatted date	 
		 */		
		public static function format($string = 'now', $format = 'c', $localize = false, $timezone = null) {
			
			// Timestamp
			if(ctype_digit($string)) {
				$date = DateTime::createFromFormat('U', $string);
			}
		
			// Date string
			else {
		
				// Standardize date
				$string = Lang::standardizeDate($string);
			
				// Apply system date format
				$date = DateTime::createFromFormat(
				    Symphony::$Configuration->get('date_format', 'region') . 
				    Symphony::$Configuration->get('datetime_separator', 'region') . 
				    Symphony::$Configuration->get('time_format', 'region'), 
				    $string
				);
				
				// Handle non-standard dates
				if($date === false) {
				    $date = new DateTime($string);
				}
			}
			
			// Format date
			$date = $date->format($format);

			// Localize date
			if($localize === true) {
				$date = Lang::localizeDate($date);
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
			return self::get($format, $timestamp, 'GMT');
		}

		/**
		 * A wrapper for get, this function will return a HTML string representing
		 * an `<abbr>` element which contained the formatted date of now, and an
		 * RFC 2822 formatted date (Thu, 21 Dec 2000 16:01:07 +0200) as the title
		 * attribute. Symphony uses this in it's status messages so that it can
		 * dynamically update how long ago the action took place using Javascript.
		 *
		 * @param string $format
		 *  A valid PHP date format
		 * @return string
		 *  A HTML string of an `<abbr>` element with a class of 'timeago' and the current
		 * date (RFC 2822) as the title element. The value is the current time as
		 * specified by the `$format`.
		 */
		public static function getTimeAgo($format){
			return '<abbr class="timeago" title="' . self::get('r') . '">' . self::get($format) . '</abbr>';
		}

	}
