<?php

	if(!defined('__IN_SYMPHONY__')) die('<h2>Symphony Error</h2><p>You cannot directly access this file</p>');	

	Class DateTimeObj{

		public static function setDefaultTimezone($timezone){
			if(!@date_default_timezone_set($timezone)) trigger_error(E_USER_WARNING, "Invalid timezone '$timezone'");
		}
		
		public static function getGMT($format, $timestamp=NULL){
			return self::get($format, $timestamp, 'GMT');
		}
		
		public static function get($format, $timestamp=NULL, $timezone=NULL){
			if(!$timestamp || $timestamp == 'now') $timestamp = time(); //"@$timestamp";
			if(!$timezone) $timezone = date_default_timezone_get();
			
			$current_timezone = date_default_timezone_get();
			if($current_timezone != $timezone) self::setDefaultTimezone($timezone);

			$ret = date($format, $timestamp);
			
			if($current_timezone != $timezone) self::setDefaultTimezone($current_timezone);
			
			return $ret;
			
			//$dateTime = new DateTime($timestamp);
			//$dateTime->setTimeZone(new DateTimeZone($timezone));
			//return $dateTime->format($format);
		}
		
		public static function getRelativeDate($timestamp, $timezone=NULL, $now=NULL){
			
			$date = strtotime(self::get('c', $timestamp));
			$now = strtotime(self::get('c', $now));
			
			$diff = $now - $date;
			
			if($diff < (60 * 60)) return 'Less than an hour ago';
			elseif($diff < (24 * 60 * 60)){
				$hours = round(($diff * (1/60)) * (1/60));
				return $hours . ' hour' . ($hours > 1 ? 's' : NULL) . ' ago';
			}
			
			$age = ($diff * (1/60) * (1/60));

			if($age >= 24 && $age < 48) return 'Yesterday';
			elseif(ceil($age * (1/24)) < 7) return ceil($age * (1/24)) . ' days ago';
			
			return ceil(($age * (1/24)) * (1/7)) . ' week' . (ceil(($age * (1/24)) * (1/7)) > 1 ? 's' : '') . ' ago';			

		}
	
	}

