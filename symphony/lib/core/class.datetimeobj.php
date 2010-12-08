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
		 * timezone. If the timezone provided is invalid, a Warning will be
		 * raised.
		 *
		 * @link http://php.net/manual/en/function.date-default-timezone-set.php
		 * @link http://www.php.net/manual/en/timezones.php		 *
		 * @param string $timezone
		 *  A valid timezone identifier, such as UTC or Europe/Lisbon
		 */
		public static function setDefaultTimezone($timezone){
			if(!@date_default_timezone_set($timezone)) trigger_error(__("Invalid timezone '{$timezone}'"), E_USER_WARNING);
		}

		/**
		 * Given a <code>$format</code>, and a <code>$timestamp</code>,
		 * return the date in the format provided. This function is a basic wrapper
		 * for PHP's date function. If the <code>$timestamp</code> is omitted,
		 * the current timestamp will be used. Optionally, you pass a
		 * timezone indentifer with this function to localise the output
		 *
		 * @link http://www.php.net/manual/en/function.date.php
		 * @param string $format
		 *  A valid PHP date format
		 * @param integer $timestamp (optional)
		 *  A unix timestamp to format. 'now' or omitting this parameter will
		 *  result in the current time being used
		 * @param string $timezone (optional)
		 *  The timezone associated with the timestamp
		 * @return string
		 *  The formatted date.
		 */
		public static function get($format, $timestamp = null, $timezone = null){
			if(is_null($timestamp) || $timestamp == 'now') $timestamp = time();
			if(is_null($timezone)) $timezone = date_default_timezone_get();

			$current_timezone = date_default_timezone_get();

			if($current_timezone != $timezone) self::setDefaultTimezone($timezone);

			$ret = date($format, $timestamp);

			if($current_timezone != $timezone) self::setDefaultTimezone($current_timezone);

			return $ret;
		}

		/**
		 * A wrapper for get, this function will force the GMT timezone.
		 *
		 * @param string $format
		 *  A valid PHP date format
		 * @param integer $timestamp (optional)
		 *  A unix timestamp to format. 'now' or omitting this parameter will
		 *  result in the current time being used
		 * @return string
		 *  The formatted date in GMT
		 */
		public static function getGMT($format, $timestamp=NULL){
			return self::get($format, $timestamp, 'GMT');
		}

		/**
		 * A wrapper for get, this function will return a HTML string representing
		 * an <abbr> element which contained the formatted date of now, and an
		 * RFC 2822 formatted date (Thu, 21 Dec 2000 16:01:07 +0200) as the title
		 * attribute. Symphony uses this in it's status messages so that it can
		 * dynamically update how long ago the action took place using Javascript.
		 *
		 * @param string $format
		 *  A valid PHP date format
		 * @return string
		 *  A HTML string of an <abbr> element with a class of 'timeago' and the current
		 * date (RFC 2822) as the title element. The value is the current time as
		 * specified by the <code>$format</code>.
		 */
		public static function getTimeAgo($format){
			return '<abbr class="timeago" title="'.self::get('r').'">'.self::get($format).'</abbr>';
		}

	}
