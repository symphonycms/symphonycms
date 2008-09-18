<?php

	if(!defined('__IN_SYMPHONY__')) die('<h2>Symphony Error</h2><p>You cannot directly access this file</p>');
	
	## Provide an interface for translations
	function __($str){
		include(LANG . '/lang.' . __LANG__ . '.php');
		$translated = trim($dictionary[$str]);

		return ($translated ? $translated : $str);
	}
	
	## Provice an interface for transliterations
	function _t($str){
		include(LANG . '/lang.' . __LANG__ . '.php');
		return strtr($str, $transliterations);
	}
	
	Class Lang{

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
		function createHandle($string, $max_length=50, $delim='-', $uriencode=false, $apply_transliteration=true, $additional_rule_set=NULL){

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
		Description: given a string, this will clean it for use as a filename
		Param: $string - string to clean
			   $delim - all non-valid characters will be replaced with this
			   $apply_transliteration - If true, this will run the string through an array of substitution characters
		Return: resultant filename

		***/					
		function createFilename($string, $delim='-', $apply_transliteration=true){

			## Use the transliteration table if provided
			if($apply_transliteration) $string = _t($string);
			
			## Strip out any tag
			$string = strip_tags($string);				
								
			## Replace underscores and other non-word, non-digit characters with $delim
			$string = preg_replace('/[^a-z0-9\+=\-\._]/i', $delim, $string);

			## Remove leading or trailing delim characters
			$string = trim($string, $delim);	

			## Make it lowercase
			$string = strtolower($string);		
			
			$string = preg_replace('/'.$delim.'{2,}/', $delim, $string);

			return $string;
			
		}

		
	}
	
