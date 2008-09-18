<?php

	if(!defined('__IN_SYMPHONY__')) die('<h2>Symphony Error</h2><p>You cannot directly access this file</p>');
	
	Class General{
		
		const CRLF = "\r\n";
		
		/***
		
		Method: sanitize
		Description: Will convert any special characters into their entity equivalents
		Param: $str - a string to operate on
		Return: the encoded version of the string
		
		***/
		public static function sanitize($str){
			return @htmlspecialchars($str);
		}
		
		/***
		
		Method: reverse_sanitize
		Description: Will revert any html entities to their character equivalents
		Param: $str - a string to operate on
		Return: the decoded version of the string
		
		***/		
		public static function reverse_sanitize($str){		 
		   return @htmlspecialchars_decode($str);
		}

		/***
		
		Method: substr_f
		Description: grabs the first character of a string
		Param: $string - a string to operate on
		Return: the first character of the input string
		
		***/		
		public static function substr_f($string){
			return (string)$string{0};
		}
		
		/***
		
		Method: findKeywordsArray
		Description: using any array of strings, this public static function will attempt to find
		             any occurance of a needle
		Param: $needle - string to search for
		       $haystack - the string or array to search
		       $fields (optional) - 
			   $any (optional) - if true, the public static function will return if any match is found
		Return: true or false
		
		***/		
		public static function findKeywordsArray($needle, $haystack, $fields=array(), $any=true){
		
			
			$result = array();
			
			foreach($haystack as $h){
			
				$match = false;
				
				foreach($needle as $n){
					foreach($fields as $f){
					
						if(stripos($h[$f], $n))
							$match = true;
							
						else
							if(!$any) $match = false;
						
					}
				}
				
				if($match) array_push($result, $h);

			}		
			
			return $result;
		
		}

		/***
		
		Method: stripEntities
		Description: will replace any html entity with the replacement string
		Param: $string - string to operate on
		       $replacement - string to replace any entities with
		Return: the resultant string
		
		***/		
		public static function stripEntities($string, $replacement=''){
			return preg_replace('/(&[\\w]{2,6};)/', $replacement, $string);
		}
		
		/***
		
		Method: validateString
		Description: will validate a string against a set of reqular expressions
		Param: $string - string to operate on
		       $rule - a single rule or array of rules
		Return: true or false
		
		***/
		public static function validateString($string, $rule){
		
			if(!is_array($rule) && $rule == '') return true;
			if(!is_array($string) && $string == '') return true;
			
			if(!is_array($rule)) $rule = array($rule);
			if(!is_array($string)) $string = array($string);
						
			foreach($rule as $r){
				foreach($string as $s){
					if(!preg_match($r, $s)) return false;
				}
			}
			return true;
		}
		
		public static function tabsToSpaces($string, $spaces=4){
			return str_replace("\t", str_pad(NULL, $spaces), $string);
		}

		/***
		
		Method: validateXML
		Description: This checks an xml document for well-formedness
		Param: $data - filename or xml document as a string
		       $errors - pointer to an array which will contain any validation errors
		       $isFile (optional) - if this is true, the method will attempt to read
		                            from a file ($data) instead.
			   $xsltProcessor (optional) - If set, the validation will be done using this
										   xslt processor rather than the built in XML parser
			   $encoding (optional) - If no XML header is expected, than this should be set to
			 						  match the encoding of the XML
		Return: true or false
		
		***/		
		public static function validateXML($data, &$errors, $isFile=true, $xsltProcessor=NULL, $encoding='UTF-8') {
			$_parser 	= null;
			$_data	 	= null;
			$_vals		= array();
			$_index		= array();
			
			if($isFile)
				$_data = @file_get_contents($data);
				
			else
				$_data = $data;

			$_data = preg_replace('/(&[\\w]{2,6};)/', '',$_data);
			$_data = preg_replace('/<!DOCTYPE[-.:"\'\/\\w\\s]+>/' , '', $_data);
			
			if(strpos($_data, '<?xml') === false){
				$_data = '<?xml version="1.0" encoding="'.$encoding.'"?><rootelement>'.$_data.'</rootelement>';
			}
			
			if(@is_object($xsltProcessor)){
				
				$xsl = '<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

				<xsl:template match="/"></xsl:template>

				</xsl:stylesheet>';

				$xsltProcessor->process($_data, $xsl, array());

				if($xsltProcessor->isErrors()) {
					$errors = $xsltProcessor->getError(true);
					return false;
				}
				
			}else{
			
				$_parser = xml_parser_create();
				xml_parser_set_option($_parser, XML_OPTION_SKIP_WHITE, 0);
				xml_parser_set_option($_parser, XML_OPTION_CASE_FOLDING, 0);
				
				if(!@xml_parse($_parser, $_data)) {
					$errors = array('error' => xml_get_error_code($_parser) . ': ' . xml_error_string(xml_get_error_code($_parser)), 
									'col' => xml_get_current_column_number($_parser), 
									'line' => (xml_get_current_line_number($_parser) - 2));
					return false;
				}

				xml_parser_free($_parser);
			}
			
			return true;
			
		}
		

		/***
		
		Method: validateURL
		Description: will check that a string is a valid URL
		Param: $string - string to operate on
		Return: true or false
		
		***/		
		public static function validateURL($url){
			if($url != ''){
				if(!preg_match('#^http[s]?:\/\/#i', $url)){
					$url = 'http://' . $url;
				}
		
				if(!preg_match('/^[^\s:\/?#]+:(?:\/{2,3})?[^\s.\/?#]+(?:\.[^\s.\/?#]+)*(?:\/[^\s?#]*\??[^\s?#]*(#[^\s#]*)?)?$/', $url)){
					$url = '';
				}
			}
			
			return $url;
		}

		/***
		
		Method: nullIfZero
		Description: mainly for SQL statements, this will convert a 0 value to 'IS NULL'
		Param: $value - string to operate on
		Return: the formatted string suitable for use in the SQL statement
		
		***/		
		public static function nullIfZero ($value) { 
			if(intval ($value) == 0) { 
				return 'IS NULL'; 
			}else { 
				return '= ' . $value; 
			} 
		}  


		/***
		
		Method: get_class_methods_filter
		Description: similar to get_class_methods, however this allows you to filter the
		             result set using a regular expressions
		Param: $classname - the name of the class. Must be in the class pool
		       $critera (optional) - a regular expression to match against. Leave blank 
		                             to return all results
		Return: the formatted string suitable for use in the SQL statement
		
		***/		
		public static function get_class_methods_filter($classname, $criteria=NULL){
			
			$methods = get_class_methods($classname);
			
			if($criteria == NULL) return $methods;
			
			$result = array();
			
			foreach($methods as $m){
				
				if(preg_match($criteria, $m))
					$result[] = $m;
				
			}
			
			return $result;
			
		}

		/***
		
		Method: cleanArray
		Description: Will strip any slashes from all array values
		Param: &$arr - pointer to an array to operate on. Can be multi-dimensional		
		
		***/		
		public static function cleanArray(&$arr) {
			
			foreach($arr as $k => $v){
				
				if(is_array($v))
					self::cleanArray($arr[$k]);
				else
					$arr[$k] = stripslashes($v);
			}
		}


		/***
		
		Method: generatePassword
		Description: uses random numbers and 2 arrays to create friendly passwords such as
		             4LargeWorms or 11HairyMonkeys
		Return: string
		
		***/			
		public static function generatePassword(){
		
			$words[] = array('Large', 'Small', 'Hot', 'Cold', 'Big', 'Hairy', 'Round', 'Lumpy', 'Coconut', 'Encumbered');
			$words[] = array('Cats', 'Dogs', 'Weasels', 'Birds', 'Worms', 'Bugs', 'Pigs', 'Monkeys', 'Pirates', 'Aardvarks', 'Men', 'Women');
			
			return (rand(2, 15) . $words[0][rand(0, 8)] . $words[1][rand(0, 7)]); 
				
		}

		/***

		Method: sendEmail
		Description: Allows you to send emails. It includes some simple injection attack
		             protection and more comprehensive headers
		Param: $to_email - email of the recipiant
		       $from_email - the from email address. This is usually your email
		       $from_name - The name of the sender
		       $subject - subject of the email
		       $message - contents of the email
		Return: true or false

		***/		
		public static function sendEmail($to_email, $from_email, $from_name, $subject, $message, $additional_headers=array()){	

			##Check for injection attacks (http://securephp.damonkohler.com/index.php/Email_Injection)
			if ((eregi("\r", $from_email) || eregi("\n", $from_email))
				|| (eregi("\r", $from_name) || eregi("\n", $from_name))){
					return false;
		   	}
			####

			$header  = "From: $from_name <$from_email>" . self::CRLF;
			$header .= "Reply-To: $from_name <$from_email>\r\n";		
			$header .= "Message-ID: <".md5(uniqid(time()))."@{$_SERVER['SERVER_NAME']}>" . self::CRLF;
			$header .= "Return-Path: <$from_email>" . self::CRLF;
			$header .= "Importance: normal" . self::CRLF;
			$header .= "Priority: normal" . self::CRLF;
			$header .= "X-Sender: Symphony Email Module <noreply@symphony21.com>" . self::CRLF;
			$header .= "X-Mailer: Symphony Email Module" . self::CRLF;
			$header .= "X-Priority: 3";

			if(is_array($additional_headers) && !empty($additional_headers)){
				$header .= self::CRLF . implode(self::CRLF, $additional_headers);
			}

			if(!mail($to_email, $subject, $message, $header)) return false;

			return true;
		}


		/***
		
		Method: padString
		Description: Pads a string with a specified string until it is of a certain length
		Param: $str - string to operate on
		       $length - desired string length
		       $spacer (optional) - the character you wish to pad with
		       $align (optional) - right, left or middle padding
		Return: padded string
		
		***/
		/*public static function padString($str, $length, $spacer=" ", $align="left"){
			
			switch($align){
				
				case "right":
					return $str . self::repeatStr($spacer, $length - strlen($str));
					break;
					
				case "center":
					return self::repeatStr($spacer, ($length - strlen($str)) * 0.5 ) . $str . self::repeatStr($spacer, ($length - strlen($str)) * 0.5 );
					break;
					
				case "left":
				default:
					return self::repeatStr($spacer, $length - strlen($str)) . $str;
					break;
				
			}
				
		}*/


		/***
		
		Method: repeatStr
		Description: This will repeat a string XX number of times.
		Param: $str - string to repeat
		       $xx - Number of times to repeat the string
		Return: resultant string
		
		***/		
		public static function repeatStr($str, $xx){
			
			$xx = ceil(max(0, $xx));

			return ($xx == 0 ? NULL : str_pad('', strlen($str) * $xx, $str));

				
			/*$result = '';
			
			for($ii = 0; $ii < $xx; $ii++){
				
				$result .= $str;	
				
			}
			
			return $result;*/
				
		}


		/***
		
		Method: strPlural
		Description: Given a number, this will return the singular or plural variable.
		             This is useful when dealing with counts. 1 frog or 2 frogs.
		Param: $num - number to base the result on. 0 - plural, 1 - singular, >1 - plural
		       $singular - string to return if it is a singular
			   $plural - string to return if it is a plural
		Return: resultant string
		
		***/		
		public static function strPlural($num, $singular, $plural){		
			$num = intval($num);			
			return ($num == 1 ? $singular : $plural);				
		}
		
		/***
		
		Method: substrmin
		Description: takes a string and compares it length with val. returns the substr with
	      			 length of the smaller value. IE strlen($str) or $val
		Param: $str - the string to operate on
			   $val - the number to compare lengths with
		Return: the smaller string
		
		***/
		public static function substrmin($str, $val){
			return(substr($str, 0, min(strlen($str), $val)));
		}
		
		/***
		
		Method: substrmax
		Description: takes a string and compares it length with val. returns the substr with
	      			 length of the larger value. IE strlen($str) or $val
		Param: $str - the string to operate on
			   $val - the number to compare lengths with
		Return: the larger string
		
		***/
		public static function substrmax($str, $val){
			return(substr($str, 0, max(strlen($str), $val)));
		}	
		
		/***
		
		Method: right
		Description: creates a string from the right by $num characters
		Param: $str - the string to operate on
			   $num - the number of characters to return
		Return: resultant string portion
		
		***/	
		public static function right($str, $num){
			$str = substr($str, strlen($str)-$num,  $num);
			return $str;
		}
		
		/***
		
		Method: left
		Description: creates a string from the left by $num characters
		Param: $str - the string to operate on
			   $num - the number of characters to return
		Return: resultant string portion
		
		***/	
		public static function left($str, $num){			
			$str = substr($str, 0, $num);
			return $str;	
		}

		/***
		
		Method: realiseDirectory
		Description: Given a path, this public static function will attempt to create all directories
		             within that path until the end folder is reached.
		Param: $path - folder path to create
			   $mode (optional) - the octal permission value to chmod the new folders to
		Return: true or false
		
		***/		
		public static function realiseDirectory($path, $mode=0755){
		
			if(!empty($path)){
				
				if(@file_exists($path) && !@is_dir($path)){
					return false;
					
				}elseif(!@is_dir($path)){
				
					preg_match_all('/([^\/]+)\/?/i', $path, $directories);
		
					$currDir = '';
				
					foreach($directories[0] as $dir){
					
						$currDir = $currDir . $dir;
						
						if(!@file_exists($currDir)){
							if(!mkdir($currDir, intval($mode, 8))){
								return false;
							}
						}	
					}
				}
			}
				
			return true;
		}

		/***
		
		Method: in_array_multi
		Description: looks for a value inside a multi-dimensional array
		Param: $needle - value to look for
			   $haystack - array to search in
		Return: true or false
		
		***/		
		public static function in_array_multi($needle, $haystack){
			
			if($needle == $haystack) return true;
			
			if(is_array($haystack)){
			
				foreach($haystack as $key => $val){
					
					if(is_array($val) && self::in_array_multi($needle, $val)){
						return true;	
					
					}elseif(!strcmp($needle, $key) || !strcmp($needle, $val)){ 
						return true;
													
					}
				}
			}
				
			return false;					
		}
		
		public static function in_array_all($needles, $haystack){
			
			foreach($needles as $n){
				if(!in_array($n, $haystack)) return false;
			}
			
			return true;
		}
		
		
		/***
		
		Method: processFilePostData
		Description: takes a multi-level $_FILES array and processes it, producing a nice
		             indexed array.
		Param: $filedata - raw $_FILE data
		Return: associative array
		
		***/		
		public static function processFilePostData($filedata){
			
			$result = array();
			
			foreach($filedata as $key => $data){
				foreach($data as $handle => $value){
					if(is_array($value)){
						foreach($value as $index => $pair){
							
							if(!is_array($result[$handle][$index])) $result[$handle][$index] = array();
							
							if(!is_array($pair)) $result[$handle][$index][] = $pair;
							else $result[$handle][$index][array_pop(array_keys($pair))][$key] = array_pop(array_values($pair));
						}
					}
					
					else $result[$handle][$key] = $value; 
				}
			}

			return $result;
		}
		
		/***
		
		Method: array_find_available_index
		Description: Looks for the next available index in an array. Works best with numeric keys
		Param: $array - array to fine index for
		Return: available, numeric, index.
		
		***/
		public static function array_find_available_index($array, $seed=NULL){
			
			if($seed) $index = $seed;
			else{
				$keys = array_keys($array);
				sort($keys);
				$index = array_pop($keys);
			}
			
			if(isset($array[$index])){
				do{
					$index++;
				}while(isset($array[$index]));
			}
			
			return $index;		
		}

		/***
		
		Method: array_remove_duplicates
		Description: rebuilds an indexed array to contain no duplicate values
		Param: $array - array to search through
		Return: rebuilt array
		
		***/		
		public static function array_remove_duplicates($array){
		
			/*
			//Flip once to remove duplicates
			$array = array_flip($array);
			
			//Flip back to get desired result
			$array = array_flip($array);
			
			return $array;
			
			*/
			
			if(!is_array($array)) return array($array);
			elseif(empty($array)) return array();
			
			$tmp = array();
			
			foreach($array as $item){
			
				if(!@in_array($item, $tmp))
					$tmp[] = $item;
			}
			
			return $tmp;
				
		}


		/***
		
		Method: str2array
		Description: splits a string by a delimiter
		Param: $string - string to operate on
		       $preserve (optional) - if true, this will retain any empty array values
		       $delim (optional) - a string to split by
		Return: array of string parts
		
		***/		
		public static function str2array($string, $preserve=true, $delim="\\r\\n"){	
				
			if(!$preserve)
				return preg_split('/[\\r\\n]/', $string, -1, PREG_SPLIT_NO_EMPTY);
				
			return preg_split('/[\\r\\n]/', $string);
						
		}


		/***
		
		Method: writeFile
		Description: writes the contents of $data to a file $file.
		Param: $file - file path
		       $data - string to write
		       $perm (optional) - octal permission to apply to the file via CHMOD		
		Return: XHTML code
		
		***/			
		public static function writeFile($file, $data, $perm = 0644){
			
			if(empty($perm)) $perm = 0644;
			
			if(!$handle = @fopen($file, 'w')) {
				return false;
				exit;
			}
			
			if(@fwrite($handle, $data, strlen($data)) === false) {
				return false;
				exit;
			}
			
			@fclose($handle);
	
			@chmod($file, intval($perm, 8));

			return true;
		}


		/***
		
		Method: deleteFile
		Description: deletes a file using the unlink function
		Param: $file - file to delete	
		Return: true or false
		
		***/		
		public static function deleteFile($file){
			if(!@unlink($file)){
				trigger_error('Unable to remove file - ' . $file, E_USER_WARNING);
				return false;
			}
			
			return true;
		}
		
		/***
		
		Method: rmdirr
		Description: Recursively deletes all folders and files from a given start location
		
		***/
		public static function rmdirr($folder){
			
			$folder = rtrim($folder, '/');
			
			if(empty($folder)) return;
			
		    if(is_dir($folder) && !is_link($folder)){
		        foreach(scandir($folder) as $item){
		            if(!strcmp($item, '.') || !strcmp($item, '..')) continue;        
		            self::rmdirr($folder . '/' . $item);
		        }    
		        rmdir($f);
		    }
		
		    else unlink($f);

		}

		/***
		
		Method: getExtension
		Description: finds the file extension of a file
		Param: $file - name of the file to examine
		Return: extension
		
		***/		
		public static function getExtension($file){
		    $parts = explode('.', basename($file));
			return array_pop($parts);
		}

		/***

		Method: listDirStructure
		Description: will index a directory struction from start point $dir
		Param: $dir (optional) - path to start indexing at. must be readable
			   $filters (optional) - either a regular expression or an array of allowable
			                         file types
			   $recurse (optional) - if true, the method will recursively traverse 
			                         the directory stucture
			   $sort (optional) - sort order of indexed files
			   $strip_root (optional) - can remove the $dir portion of the file path for
			                            array keys.
			   $exclude (optional) - ignores file types contained in this array			
		Return: nested array containing the directory structure

		***/	    
		public static function listDirStructure($dir='.', $recurse=true, $sort='asc', $strip_root=NULL, $exclude=array(), $ignore_hidden=true){
			
			if(!is_dir($dir)) return;
			
		    $filter_pattern_match = false;

		    if(isset($filters) && !is_array($filters)) $filter_pattern_match = true;

		    $files = array();

			foreach(scandir($dir) as $file){
				if($file != '.' && $file != '..' && (!$ignore_hidden || ($ignore_hidden && $file{0} != '.'))){

					if(@is_dir("$dir/$file") && !in_array($file, $exclude)){
						$files[] = str_replace($strip_root, '', $dir) ."/$file/";

						if($recurse){
							$files = @array_merge($files, self::listDirStructure("$dir/$file", $recurse, $sort, $strip_root, $exclude, $ignore_hidden));
						}

					}
				}
			}

			return $files;
		}		
	
		/***
		
		Method: listStructure
		Description: will index a directory struction from start point $dir
		Param: $dir (optional) - path to start indexing at. must be readable
			   $filters (optional) - either a regular expression or an array of allowable
			                         file types
			   $recurse (optional) - if true, the method will recursively traverse 
			                         the directory stucture
			   $sort (optional) - sort order of indexed files
			   $strip_root (optional) - can remove the $dir portion of the file path for
			                            array keys.
			   $exclude (optional) - ignores file types contained in this array			
		Return: nested array containing the directory structure
		
		***/	    
	    public static function listStructure($dir=".", $filters=array(), $recurse=true, $sort="asc", $strip_root=NULL, $exclude=array(), $ignore_hidden=true){
		    
			if(!is_dir($dir)) return;
		
		    $filter_pattern_match = false;
		    
		    if(isset($filters) && !is_array($filters)) $filter_pattern_match = true;
		    
		    $files = array();
		    
			foreach(scandir($dir) as $file){
				if($file != '.' && $file != '..' && (!$ignore_hidden || ($ignore_hidden && $file{0} != '.'))){
					
					if(@is_dir("$dir/$file")){
						if($recurse)
							$files[str_replace($strip_root, '', $dir) . "/$file/"] = self::listStructure("$dir/$file", $filters, $recurse, $sort, $strip_root, $exclude, $ignore_hidden);	
						
						$files['dirlist'][] = $file;	
							
					}elseif($filter_pattern_match || (!empty($filters) && is_array($filters))){
					
						if($filter_pattern_match){	
							if(preg_match($filters, $file)){						
								$files['filelist'][] = $file;
								
								if($sort == 'desc') rsort($files['filelist']);
								else sort($files['filelist']);	
							}						
							
						}elseif(in_array(self::getExtension($file), $filters)){
							$files['filelist'][] = $file;
							
							if($sort == 'desc') rsort($files['filelist']);
							else sort($files['filelist']);
						}
						
					}elseif(empty($filters)){
						$files['filelist'][] = $file;
						
						if($sort == 'desc') rsort($files['filelist']);
						else sort($files['filelist']);					
		
					}
				}
			}

			return $files;
		}

		/***

		Method: listStructureFlat
		Description: will index a directory struction from start point $dir, returning 
		             a flat array where each array key is a path to a file
		Param: $dir (optional) - path to start indexing at. must be readable
			   $filters (optional) - either a regular expression or an array of allowable
			                         file types
			   $recurse (optional) - if true, the method will recursively traverse 
			                         the directory stucture
			   $sort (optional) - sort order of indexed files
			   $strip_root (optional) - can remove the $dir portion of the file path for
			                            array keys.
			   $exclude (optional) - ignores file types contained in this array
		Return: flat array containing the directory/file structure

		***/		
		public static function listStructureFlat($dir='.', $filters=array(), $recurse=true, $sort='asc', $strip_root=NULL, $exclude=array(), $ignore_hidden=true, $exclude_dir=array()){

			if(!is_dir($dir)) return;

		    $files = array();

			foreach(scandir($dir) as $file){
				if($file != '.' && $file != '..' && (!$ignore_hidden || ($ignore_hidden && $file{0} != '.'))){	
					if(@is_dir("$dir/$file")){

						if($recurse && !in_array($file, $exclude_dir))
							$files = array_merge($files, self::listStructureFlat("$dir/$file", $filters, $recurse, $sort, $strip_root, $exclude, $ignore_hidden));

					}else{

						$can_include = true;

						if(!empty($filters)){
							$can_include = in_array(self::getExtension($file), $filters);
						}

						if(!empty($exclude)){
							$can_include = !in_array(self::getExtension($file), $exclude);
						}					

						if($can_include){
							$files[] = array('name' => $file, 'path' => $dir);
						}				
					}
				}
			}

			if($sort == 'desc') usort($files, array('General', 'fileSortR'));		
			elseif($sort == 'mtime') usort($files, array('General', 'filemtimeSort'));			
			else usort($files, array('General', 'fileSort'));

			if($strip_root){
				for($ii = 0; $ii < count($files); $ii++)
					$files[$ii]['path'] = str_replace($strip_root, '', $files[$ii]['path']);
			}

			return $files;	

		}
	
		/***
		
		Method: filemtimeSort
		Description: Used by usort. Takes 2 file names and returns -1, 0 or 1. Should 
		             only be called using usort or similar. E.G. 
		             usort($files, array('General', 'filemtimeSort'));
		Param: $f1 - path to first file
		       $f2 - path to second file
		Return: -1, 0 or 1
		
		***/
		public static function filemtimeSort($f1, $f2){
			return @filemtime($f1['path'] . '/' . $f1['name']) - @filemtime($f1['path'] . '/' . $f1['name']);
		}

		/***
		
		Method: fileSort
		Description: Used by usort. Takes 2 file names and returns -1, 0 or 1. Should 
		             only be called using usort or similar. E.G. 
		             usort($files, array('General', 'fileSort'));
		Param: $f1 - path to first file
		       $f2 - path to second file
		Return: -1, 0 or 1
		
		***/
		public static function fileSort($f1, $f2){
			return strcmp($f1['name'], $f2['name']);
		}
		
		/***
		
		Method: fileSortR
		Description: Used by usort. Takes 2 file names and returns -1, 0 or 1. Should 
		             only be called using usort or similar. E.G. 
		             usort($files, array('General', 'fileSortR'));
		Param: $f1 - path to first file
		       $f2 - path to second file
		Return: -1, 0 or 1
		
		***/
		public static function fileSortR($f1, $f2){
			return strcmp($f2['name'], $f1['name']);
		}

		/***
		
		Method: countWords
		Description: counts the number of words in a string
		Param: $string - string to examine
		Return: number of words contained in the string
		
		***/		
		public static function countWords($string){
			
			$string = strip_tags($string);
			$string = preg_replace('/[^\w\s]/i', '', $string);
			
			$words = preg_split('/\s+/', $string, -1, PREG_SPLIT_NO_EMPTY);
			
			return count($words);
		}


		/***
		
		Method: limitWords
		Description: truncates a string so that it contains no more than a certain
		             number of characters, preserving whole words
		Param: $string - string to operate on
		       $maxChars - maximum number of characters
		       $appendHellip (optional) - can optionally append a hellip entity 
		                                  to the string if it is smaller than 
		                                  the input string
		Return: resultant string
		
		***/		
		public static function limitWords($string, $maxChars=200, $appendHellip=false, $truncateToSpace=false) {
			
			if($appendHellip) $maxChars -= 3;

			$string = trim(strip_tags(nl2br($string)));
			$original_length = strlen($string);
			
			if(trim($string) == '') return NULL;
			elseif(strlen($string) < $maxChars) return $string;
			
			$string = substr($string, 0, $maxChars);
			
			if($truncateToSpace && strpos($string, ' ')){
				$string = str_replace(strrchr($string, ' '), '', $string);
			}		
					
			$array  = explode(' ', $string);
			$result =  '';
						
			while(is_array($array) && !empty($array) && strlen(@implode(' ', $array)) > $maxChars){
				array_pop($array);				
			}			
			
			$result = trim(@implode(' ', $array));

			if($appendHellip && strlen($result) < $original_length)
				$result .= '...';
			
			return($result);
		}


		/***
		
		Method: dateDiff
		Description: this will calculate the number of seconds between two dates
		Param: $date1 - start date. Must be a valid unix timestamp
		       $date2 (optional) - end date. Must be a valid unix timestamp. If blank
		                           the current date is used
		Return: number of seconds elapsed
		
		***/		
		public static function dateDiff($date1, $date2=null){
			
			if(!$date2) $date2 = time();
			
			if($date1 < $date2)
				$diff = $date2 - $date1;
			else
				$diff = $date1 - $date2;	
				
			$m1 = (1 / 60);
			$m2 = (1 / 24);	
			
			return floor(((($diff * $m1) * $m1) * $m2));
			
		}


		public static function uploadFile($dest_path, $dest_name, $tmp_name, $perm=0777){
			
			##Upload the file
			if(@is_uploaded_file($tmp_name)) {
				
				$dest_path = rtrim($dest_path, '/') . '/';

				##Try place the file in the correction location	
				if(@move_uploaded_file($tmp_name, $dest_path . $dest_name)){
					@chmod($dest_path . $dest_name, intval($perm, 8));
					return true;					
				}
			}

			##Could not move the file
			return false;	
			
		}
		
		/***
		
		Method: formatFilesize
		Description: giving a filesize in bytes, this will format it for easier reading
		Param: $file_size - file size in bytes
		Return: formatted file size
		
		***/		
		public static function formatFilesize($file_size){
			
			$file_size = intval($file_size);
			
			if($file_size >= (1024 * 1024)) 	$file_size = number_format($file_size * (1 / (1024 * 1024)), 2) . ' mb';
			elseif($file_size >= 1024) 			$file_size = intval($file_size * (1/1024)) . ' kb';
			else 								$file_size = intval($file_size) . ' bytes';
			
			return $file_size;
		}
		
		public static function createXMLDateObject($timestamp, $element='date', $namespace=NULL){
			if(!class_exists('XMLElement')) return false;
			
			$xDate = new XMLElement(($namespace ? $namespace . ':' : '') . $element, 
				DateTimeObj::get('Y-m-d', $timestamp),
				array('time' => DateTimeObj::get('H:i', $timestamp),
				      'weekday' => DateTimeObj::get('N', $timestamp),		
				));

			return $xDate;
			
		}
		
	}
