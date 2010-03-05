<?php

	Class DateTimeObj{

		public static function setDefaultTimezone($timezone){
			if(!@date_default_timezone_set($timezone)) trigger_error(__("Invalid timezone '{$timezone}'"), E_USER_WARNING);
		}
		
		public static function getGMT($format, $timestamp=NULL){
			return self::get($format, $timestamp, 'GMT');
		}
		
		public static function getTimeAgo($format){
			return '<abbr class="timeago" title="'.self::get('r').'">'.self::get($format).'</abbr>';
		}
		
		public static function get($format, $timestamp=NULL, $timezone=NULL){
			if(!$timestamp || $timestamp == 'now') $timestamp = time();
			if(!$timezone) $timezone = date_default_timezone_get();
			
			$current_timezone = date_default_timezone_get();
			if($current_timezone != $timezone) self::setDefaultTimezone($timezone);

			$ret = date($format, $timestamp);
			
			if($current_timezone != $timezone) self::setDefaultTimezone($current_timezone);
			
			return $ret;
			
		}
	
	}
