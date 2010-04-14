<?php

	Class General{
		
		const CRLF = PHP_EOL;
		
		/***
		
		Method: sanitize
		Description: Will convert any special characters into their entity equivalents
		Param: $str - a string to operate on
		Return: the encoded version of the string
		
		***/
		public static function sanitize($source) {
			$source = @htmlspecialchars($source);
			
			return $source;
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
		Param: $string - filename, xml document as a string, or arbitary string
		       $errors - pointer to an array of libXMLError objects which will contain any validation errors
			   $encoding (optional) - If no XML header is expected, than this should be set to
			 						  match the encoding of the XML
		Return: true or false
		
		***/
		
		public static function validateXML($string, &$errors, $encoding='UTF-8') {
			
			$xsl = '<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
			<xsl:template match="/"></xsl:template>
			</xsl:stylesheet>';
			
			$xml = trim($string);

			if(strpos($string, '<?xml') === false){
				$xml = sprintf('<?xml version="1.0" encoding="%s"?><rootelement>%s</rootelement>', $encoding, $string);
			}
			
			XSLProc::transform($xml, $xsl);
			
			if(XSLProc::hasErrors()){
				$errors = XSLProc::getErrors();
				return false;
			}
			
			return true;
		}
		

		/***
		
		Method: validateURL
		Description: will check that a string is a valid URL
		Param: $string - string to operate on
		Return: a blank string or a valid URL
		
		***/		
		public static function validateURL($url){
			if($url != ''){
				if(!preg_match('#^http[s]?:\/\/#i', $url)){
					$url = 'http://' . $url;
				}
				
				include(TOOLKIT . '/util.validators.php');
				if(!preg_match($validators['URI'], $url)){
					$url = '';
				}
			}
			
			return $url;
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
		
		public static function flattenArray(&$source, &$output = null, $path = null) {
			if (is_null($output)) $output = array();
			
			foreach ($source as $key => $value) {
				if (is_int($key)) $key = (string)($key + 1);
				if (!is_null($path)) $key = $path . '.' . (string)$key;
				
				if (is_array($value)) self::flattenArray($value, $output, $key);
				else $output[$key] = $value;
			}
			
			$source = $output;
		}
		
		protected static function flattenArraySub(&$output, &$source, $path) {
			foreach ($source as $key => $value) {
				$key = $path . ':' . $key;
				
				if (is_array($value)) self::flattenArraySub($output, $value, $key);
				else $output[$key] = $value;
			}
		}
		
		/***
		
		Method: generatePassword
		Description: uses random numbers and 2 arrays to create friendly passwords such as
		             4LargeWorms or 11HairyMonkeys
		Return: string
		
		***/			
		public static function generatePassword(){
		
			$words = array(
				array(
					__('Large'), 
					__('Small'), 
					__('Hot'), 
					__('Cold'), 
					__('Big'), 
					__('Hairy'), 
					__('Round'),
					__('Lumpy'), 
					__('Coconut'), 
					__('Encumbered')
				),
				
				array(
					__('Cats'), 
					__('Dogs'), 
					__('Weasels'), 
					__('Birds'), 
					__('Worms'), 
					__('Bugs'), 
					__('Pigs'), 
					__('Monkeys'),
					__('Pirates'), 
					__('Aardvarks'), 
					__('Men'), 
					__('Women')
				)
			);
			
			return (rand(2, 15) . $words[0][rand(0, count($words[0]) - 1)] . $words[1][rand(0, count($words[1]) - 1)]); 
				
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
		public static function sendEmail($to_email, $from_email, $from_name, $subject, $message, array $additional_headers = array()) {
			## Check for injection attacks (http://securephp.damonkohler.com/index.php/Email_Injection)
			if ((eregi("\r", $from_email) || eregi("\n", $from_email))
				|| (eregi("\r", $from_name) || eregi("\n", $from_name))){
					return false;
		   	}
			####
			
			$subject = self::encodeHeader($subject, 'UTF-8');
			$from_name = self::encodeHeader($from_name, 'UTF-8');
			$headers = array();
			
			$default_headers = array(
				'From'			=> "{$from_name} <{$from_email}>",
		 		'Reply-To'		=> "{$from_name} <{$from_email}>",	
				'Message-ID'	=> sprintf('<%s@%s>', md5(uniqid(time())), $_SERVER['SERVER_NAME']),
				'Return-Path'	=> "<{$from_email}>",
				'Importance'	=> 'normal',
				'Priority'		=> 'normal',
				'X-Sender'		=> 'Symphony Email Module <noreply@symphony-cms.com>',
				'X-Mailer'		=> 'Symphony Email Module',
				'X-Priority'	=> '3',
				'MIME-Version'	=> '1.0',
				'Content-Type'	=> 'text/plain; charset=UTF-8',
			);
			
			if (!empty($additional_headers)) {
				foreach ($additional_headers as $header => $value) {
					$header = preg_replace_callback('/\w+/', create_function('$m', 'if(in_array($m[0], array("MIME", "ID"))) return $m[0]; else return ucfirst($m[0]);'), $header);
					$default_headers[$header] = $value;
				}
			}
			
			foreach ($default_headers as $header => $value) {
				$headers[] = sprintf('%s: %s', $header, $value);
			}
			
			return mail($to_email, $subject, @wordwrap($message, 70), @implode(self::CRLF, $headers) . self::CRLF, "-f{$from_email}");
			
		}


		/***

		Method: encodeHeader
		Description: Encodes (parts of) an email header if necessary, according to RFC2047 if mbstring is available;
		Added by: Michael Eichelsdoerfer

		***/
		public static function encodeHeader($input, $charset='ISO-8859-1')
		{
		    if(preg_match_all('/(\s?\w*[\x80-\xFF]+\w*\s?)/', $input, $matches))
		    {
		        if(function_exists('mb_internal_encoding'))
		        {
		            mb_internal_encoding($charset);
		            $input = mb_encode_mimeheader($input, $charset, 'Q');
		        }
		        else
		        {
		            foreach ($matches[1] as $value)
		            {
		                $replacement = preg_replace('/([\x20\x80-\xFF])/e', '"=" . strtoupper(dechex(ord("\1")))', $value);
		                $input = str_replace($value, '=?' . $charset . '?Q?' . $replacement . '?=', $input);
		            }
		        }
		    }
		    return $input;
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
			return @mkdir($path, intval($mode, 8), true);
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
					
					if(is_array($val)){
						if(self::in_array_multi($needle, $val)) return true;
					}
					
					elseif(!strcmp($needle, $key) || !strcmp($needle, $val)){ 
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
		
		Method: getPostData
		Description: Returns $_POST merged with $_FILES.
		Return: associative array
		
		***/
		public static function getPostData() {
			if (!function_exists('merge_file_post_data')) {
				function merge_file_post_data($type, array $file, &$post) {
					foreach ($file as $key => $value) {
						if (!isset($post[$key])) $post[$key] = array();
						if (is_array($value)) merge_file_post_data($type, $value, $post[$key]);
						else $post[$key][$type] = $value;
					}
				}
			}
			
			$files = array(
				'name'		=> array(),
				'type'		=> array(),
				'tmp_name'	=> array(),
				'error'		=> array(),
				'size'		=> array()
			);
			$post = $_POST;
			
			if(is_array($_FILES) && !empty($_FILES)){
				foreach ($_FILES as $key_a => $data_a) {
					if(!is_array($data_a)) continue;
					foreach ($data_a as $key_b => $data_b) {
						$files[$key_b][$key_a] = $data_b;
					}
				}
			}
			
			foreach ($files as $type => $data) {
				merge_file_post_data($type, $data, $post);
			}
			
			return $post;
		}
		
		/***
		
		Method: array_find_available_index
		Description: Looks for the next available index in an array. Works best with numeric keys
		Param: $array - array to fine index for
		Return: available, numeric, index.
		
		***/
		public static function array_find_available_index($array, $seed=NULL){
			
			if(!is_null($seed)) $index = $seed;
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
		public static function array_remove_duplicates(array $array, $ignore_case=false){
			return ($ignore_case == true ? self::array_iunique($array) : array_unique($array));
		}

		
		public static function in_iarray($needle, array $haystack){
			foreach($haystack as $key => $value){
				if(strcasecmp($value, $needle) == 0) return true;
			}
			return false;
		}

		public static function array_iunique(array $array){
			$tmp = array();
			foreach($array as $key => $value){
				if(!self::in_iarray($value, $tmp)){
					$tmp[$key] = $value;
				}
			}
			return $tmp;
		}
		
		/***
		
		Method: array_to_xml
		Description: Convert an array into an XML element.
		Param: $parent - XML Element to append to
		Param: $data - Array of data to process.
		Return: rebuilt array
		
		***/
		public static function array_to_xml(XMLElement $parent, array $data, $validate=false) {
			foreach ($data as $element_name => $value) {
				if (empty($value)) continue;
				
				if (is_int($element_name)) {
					$child = new XMLElement('item');
					$child->setAttribute('index', $element_name + 1);
				}
				
				else {
					$child = new XMLElement($element_name);
				}
				
				if(is_array($value)){
					self::array_to_xml($child, $value);
				}
				
				elseif($validate == true && !self::validateXML(self::sanitize($value), $errors)){
					return;
				} 
				else{
					$child->setValue(self::sanitize($value));
				}
				
				$parent->appendChild($child);
			}
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
		Return: true on success
		
		***/		
		public static function deleteFile($file, $slient=true){
			if(!@unlink($file)){
				if($slient == false){
					throw new Exception(__('Unable to remove file - %s', array($file)));
				}
				
				return false;
			}
			
			return true;
		}

		/***
		
		Method: getExtension
		Description: finds the file extension of a file
		Param: $file - name of the file to examine
		Return: extension
		
		***/		
		public static function getExtension($file){
			return pathinfo($file, PATHINFO_EXTENSION);
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
		public static function listDirStructure($dir = '.', $recurse = true, $sort = 'asc', $strip_root = null, $exclude = array(), $ignore_hidden = true) {
			if (!is_dir($dir)) return;
			
			$filter_pattern_match = false;
			$files = array();
			
			if (isset($filters) && !is_array($filters)) $filter_pattern_match = true;
			if ($sort == 'asc') $sort = 0; else $sort = 1;
			
			foreach (scandir($dir) as $file) {
				if (
					($file == '.' or $file == '..')
					or ($ignore_hidden and $file{0} == '.')
					or !is_dir("$dir/$file")
					or in_array($file, $exclude)
					or in_array("$dir/$file", $exclude)
				) continue;
				
				$files[] = str_replace($strip_root, '', $dir) ."/$file/";
				
				if ($recurse) {
					$files = @array_merge($files, self::listDirStructure("$dir/$file", $recurse, $sort, $strip_root, $exclude, $ignore_hidden));
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
			
			// Strip spaces:
			$string = html_entity_decode($string, ENT_NOQUOTES, 'UTF-8');
			$spaces = array(
				'&#x2002;', '&#x2003;', '&#x2004;', '&#x2005;',
				'&#x2006;', '&#x2007;', '&#x2009;', '&#x200a;',
				'&#x200b;', '&#x2002f;', '&#x205f;'
			);
			
			foreach ($spaces as &$space) {
				$space = html_entity_decode($space, ENT_NOQUOTES, 'UTF-8');
			}
			
			$string = str_replace($spaces, ' ', $string);
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
			
			if($file_size >= (1024 * 1024)) 	$file_size = number_format($file_size * (1 / (1024 * 1024)), 2) . ' MB';
			elseif($file_size >= 1024) 			$file_size = intval($file_size * (1/1024)) . ' KB';
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

		public static function buildPaginationElement($total_entries=0, $total_pages=0, $entries_per_page=1, $current_page=1){
			
			$pageinfo = new XMLElement('pagination');
			
			$pageinfo->setAttribute('total-entries', $total_entries);
			$pageinfo->setAttribute('total-pages', $total_pages);
			$pageinfo->setAttribute('entries-per-page', $entries_per_page);
			$pageinfo->setAttribute('current-page', $current_page);						

			return $pageinfo;
				
		}

		public static function rmdirr($path){
			$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path), RecursiveIteratorIterator::CHILD_FIRST);
			foreach($iterator as $file){
			    if($file->isDir()) rmdir($file->getPathname());
				else unlink($file->getPathname());
			}
			return rmdir($path);
		}
		
	}
