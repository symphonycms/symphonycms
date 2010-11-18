<?php
	/**
	 * @package toolkit
	 */
	/**
	 * General is a utility class that offers a number miscellaneous of
	 * functions that are used throughout Symphony.
	 */
	Class General{
		/**
		 * @var string The end-of-line constant.
		 * @deprecated This will no longer exist in Symphony 3
		 */
		const CRLF = PHP_EOL;

		/**
		 * Convert any special characters into their entity equivalents.
		 *
		 * @param string $source
		 *	a string to operate on.
		 * @return string
		 *	the encoded version of the string.
		 */
		public static function sanitize($source) {
			$source = htmlspecialchars($source);

			return $source;
		}

		/**
		 * Revert any html entities to their character equivalents.
		 *
		 * @param string $str
		 *	a string to operate on
		 * @return string
		 *	the decoded version of the string
		 */
		public static function reverse_sanitize($str){
		   return htmlspecialchars_decode($str);
		}

		/**
		 * Validate a string against a set of reqular expressions.
		 *
		 * @param array[int]string|string $string
		 *	string to operate on
		 * @param array[int]string|string $rule
		 *	a single rule or array of rules
		 * @return bool
		 *	false if any of the rules in $rule do not match any of the strings in
		 *	$string, return true otherwise.
		 */
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

        /**
		 * Replace the tabs with spaces in the input string.
		 *
		 * @param string $string
		 *	the string in which to replace the tabs with spaces.
		 * @param int $spaces (optional)
		 *	the number of spaces to replace each tab with. This argument is optional
		 *	with a default of 4.
		 * @return string
		 *	the resulting string.
		 */
		public static function tabsToSpaces($string, $spaces=4){
			return str_replace("\t", str_pad(NULL, $spaces), $string);
		}

		/**
		 * Checks an xml document for well-formedness.
		 *
		 * @param string $data
		 *	filename, xml document as a string, or arbitary string
		 * @param pointer &$errors
		 *	pointer to an array which will contain any validation errors
		 * @param bool $isFile (optional)
		 *	if this is true, the method will attempt to read from a file ($data)
		 *	instead.
		 * @param mixed $xsltProcessor (optional)
		 *	if set, the validation will be done using this xslt processor rather
		 *	than the built in XML parser. the default is null.
		 * @param string $encoding (optional)
		 *	if no XML header is expected, than this should be set to match the
		 *	encoding of the XML
		 * @return bool
		 *	true if there are no errors in validating the XML, false otherwise.
		 */
		public static function validateXML($data, &$errors, $isFile=true, $xsltProcessor=NULL, $encoding='UTF-8') {
			$_parser 	= null;
			$_data	 	= null;
			$_vals		= array();
			$_index	= array();

			$_data = ($isFile) ? file_get_contents($data) : $data;

			$_data = preg_replace('/<!DOCTYPE[-.:"\'\/\\w\\s]+>/', NULL, $_data);

			if(strpos($_data, '<?xml') === false){
				$_data = '<?xml version="1.0" encoding="'.$encoding.'"?><rootelement>'.$_data.'</rootelement>';
			}

			if(is_object($xsltProcessor)){

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

				if(!xml_parse($_parser, $_data)) {
					$errors = array('error' => xml_get_error_code($_parser) . ': ' . xml_error_string(xml_get_error_code($_parser)),
									'col' => xml_get_current_column_number($_parser),
									'line' => (xml_get_current_line_number($_parser) - 2));
					return false;
				}

				xml_parser_free($_parser);
			}

			return true;

		}

		/**
		 * Check that a string is a valid URL.
		 *
		 * @param string $url
		 *	string to operate on
		 * @return string
		 *	a blank string or a valid URL
		 */
		public static function validateURL($url = null){
            if(is_null($url) || $url == '') return $url;

            if(!preg_match('#^http[s]?:\/\/#i', $url)){
                $url = 'http://' . $url;
            }

            include(TOOLKIT . '/util.validators.php');
            if(!preg_match($validators['URI'], $url)){
                $url = '';
            }

			return $url;
		}

		/**
		 * Strip any slashes from all array values.
		 *
		 * @param array[] &$arr
		 *	pointer to an array to operate on. Can be multi-dimensional.
		 */
		public static function cleanArray(&$arr) {
			foreach($arr as $k => $v){
				if(is_array($v))
					self::cleanArray($arr[$k]);
				else
					$arr[$k] = stripslashes($v);
			}
		}

		/**
		 * Flatten the input array. Any elements of the input array that are
		 * themselves arrays will be removed and the contents of the removed array
		 * inserted in its place. The keys for the inserted values will be the
		 * concatenation of the keys in the original arrays in which it was embedded.
		 * The elements of the path are separated by periods (.). For example,
		 * given the following nested array structure:
		 * <code>
		 * array(1 =>
		 *			array('key' => 'value'),
		 *		2 =>
		 *			array('key2' => 'value2', 'key3' => 'value3')
		 *		)
		 * </code>
		 * will flatten to:
		 * array('1.key' => 'value', '2.key2' => 'value2', '2.key3' => 'value3')
		 *
		 * @param array[] &$source
		 *	a pointer to the array to flatten.
		 * @param array[] &$output (optional)
		 *	a pointer to the array in which to store the flattened input. if this
		 *	is not provided then a new array will be created.
		 * @param string $path (optional)
		 *	the current prefix of the keys to insert into the output array. this
		 *	defaults to null.
		 */
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

        /**
		 * Flatten the input array. Any elements of the input array that are
		 * themselves arrays will be removed and the contents of the removed array
		 * inserted in its place. The keys for the inserted values will be the
		 * concatenation of the keys in the original arrays in which it was embedded.
		 * The elements of the path are separated by colons (:). For example, given
		 * the following nested array structure:
		 * <code>
		 * array(1 =>
		 *			array('key' => 'value'),
		 *		2 =>
		 *			array('key2' => 'value2', 'key3' => 'value3')
		 *		)
		 * </code>
		 * will flatten to:
		 * array('1:key' => 'value', '2:key2' => 'value2', '2:key3' => 'value3')
		 *
		 * @param array[] &$output
		 *	a pointer to the array in which to store the flattened input.
		 * @param array[] &$source
		 *	a pointer to the array to flatten.
		 * @param string $path
		 *	the current prefix of the keys to insert into the output array.
		 */
		protected static function flattenArraySub(&$output, &$source, $path) {
			foreach ($source as $key => $value) {
				$key = $path . ':' . $key;

				if (is_array($value)) self::flattenArraySub($output, $value, $key);
				else $output[$key] = $value;
			}
		}

		/**
		 * Create friendly passwords such as 4LargeWorms or 11HairyMonkeys. This
		 * uses the rand() function. Thus, if the random number generated is seeded
		 * appropriately, this function will return the same password consistently.
		 *
		 * @return string
		 *	the generated password.
		 */
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

		/**
		 * Send an email. It includes some simple injection attack protection and
		 * more comprehensive headers.
		 *
		 * @param string $to_email
		 *	email of the recipiant
		 * @param string $from_email
		 *	the from email address. This is usually your email.
		 * @param string $from_name
		 *	the name of the sender
		 * @param string $subject
		 *	subject of the email
		 * @param string $message
		 *	contents of the email
		 * @param array[] $additional_headers (optional)
		 *	an array of additional header elements. this defaults to an empty array.
		 * @return bool
		 *	true if the call to mail with the constructed headers returns true,
		 *	false otherwise.
		 */
		public static function sendEmail($to_email, $from_email, $from_name, $subject, $message, array $additional_headers = array()) {
			// Check for injection attacks (http://securephp.damonkohler.com/index.php/Email_Injection)
			if (preg_match("/[\r|\n]/", $from_email) || preg_match("/[\r|\n]/", $from_name)){
                return false;
		   	}

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

			return mail($to_email, $subject, @wordwrap($message, 70), implode(self::CRLF, $headers) . self::CRLF, "-f{$from_email}");

		}

		/**
		 * Encode (parts of) an email header if necessary, according to RFC2047. if
		 * mb_internal_encoding is an available function then this is used to encode
		 * the header, otherwise the encoding is done manually.
		 *
		 * @author Michael Eichelsdoerfer
		 * @param string $input
		 *	the elements of the header to encode.
		 * @param string charset (optional)
		 *	the character set in which to encode the header. this defaults to 'ISO-8859-1'.
		 * @return string
		 *	the resulting encoded email header.
		 */
		public static function encodeHeader($input, $charset='ISO-8859-1') {
		    if(preg_match_all('/(\s?\w*[\x80-\xFF]+\w*\s?)/', $input, $matches)) {
		        if(function_exists('mb_internal_encoding')) {
		            mb_internal_encoding($charset);
		            $input = mb_encode_mimeheader($input, $charset, 'Q');
		        }
		        else {
		            foreach ($matches[1] as $value) {
		                $replacement = preg_replace('/([\x20\x80-\xFF])/e', '"=" . strtoupper(dechex(ord("\1")))', $value);
		                $input = str_replace($value, '=?' . $charset . '?Q?' . $replacement . '?=', $input);
		            }
		        }
		    }

		    return $input;
		}

		/**
		 * Extract the first $val characters of the input string. If $val
		 * is larger than the length of the input string then the original
		 * input string is returned.
		 *
		 * @param string $str
		 *	the string to operate on
		 * @param number $val
		 *	the number to compare lengths with
		 * @return string|bool
		 *	the resulting string or false on failure.
		 */
		public static function substrmin($str, $val){
			return(substr($str, 0, min(strlen($str), $val)));
		}

		/**
		 * Extract the first $val characters of the input string. If
		 * $val is larger than the length of the input string then
		 * the original input string is returned??
		 *
		 * @param string $str
		 *	the string to operate on
		 * @param number $val
		 *	the number to compare lengths with
		 * @return string|bool
		 *	the resulting string or false on failure.
		 */
		public static function substrmax($str, $val){
			return(substr($str, 0, max(strlen($str), $val)));
		}

		/**
		 * Extract the last $num characters from a string.
		 *
		 * @param string $str
		 *	the string to extract the characters from.
		 * @param number $num
		 *	the number of characters to extract.
		 * @return string|bool
		 *	a string containing the last $num characters of the
		 *	input string, or false on failure.
		 */
		public static function right($str, $num){
			$str = substr($str, strlen($str)-$num,  $num);
			return $str;
		}

		/**
		 * Extract the first $num characters from a string.
		 *
		 * @param string $str
		 *	the string to extract the characters from.
		 * @param number $num
		 *	the number of characters to extract.
		 * @return string|bool
		 *	a string containing the last $num characters of the
		 *	input string, or false on failure.
		 */
		public static function left($str, $num){
			$str = substr($str, 0, $num);
			return $str;
		}

		/**
		 * Create all the directories as specified by the input path.
		 *
		 * @param string $path
		 *	the path containing the directories to create.
		 * @param number $mode (optional)
		 *	the permissions (in octal) of the directories to create. this defaults to 0755
		 * @return bool
		 */
		public static function realiseDirectory($path, $mode=0755){
			return mkdir($path, intval($mode, 8), true);
		}

		/**
		 * Search a multi-dimensional array for a value.
		 *
		 * @param mixed $needle
		 *	the value to search for.
		 * @param array[] $haystack
		 *	the multi-dimensional array to search.
		 * @return bool
		 *	true if $needle is found in $haystack.
		 *	true if $needle == $haystack.
		 *	true if $needle is found in any of the arrays contained within $haystack.
		 *	false otherwise.
		 */
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

        /**
		 * Search an array for multiple values.
		 *
		 * @param array[] $needles
		 *	the values to search the $haystack for.
		 * @param array[] $haystack
		 *	the in which to search for the $needles
		 * @return bool
		 *	true if any of the $needles are in $haystack,
		 *	false otherwise.
		 */
		public static function in_array_all($needles, $haystack){

			foreach($needles as $n){
				if(!in_array($n, $haystack)) return false;
			}

			return true;
		}

		/**
		 * Transform a multi-dimensional array to a flat array. The input array
		 * is expected to conform to the structure of the $_FILES variable.
		 *
		 * @param array[] $filedata
		 *	the raw $_FILE data structured array
		 * @return array[]
		 *	the flattened array.
		 */
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

		/**
		 * Merge $_POST with $_FILES to produce a flat array of the contents
		 * of both. If there is no merge_file_post_data function defined then
		 * such a function is created. This is necessary to overcome PHP's ability
		 * to handle forms. This overcomes PHP's convoluted $_FILES structure
		 * to make it simpler to access multi-part/formdata.
		 *
		 * @uses $_POST
		 * @uses $_FILES
		 * @return array[]
		 *	a flat array containing the flattened contents of both $_POST and
		 *	$_FILES.
		 */
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

		/**
		 * Find the next available index in an array. Works best with numeric keys.
		 * The next available index is the minimum integer such that the array does
		 * not have a mapping for that index. Uses the increment operator on the
		 * index type of the input array, whatever that may do.
		 *
		 * @param array[] $array
		 *	the array to find the next index for.
		 * @param mixed $seed (optional)
		 *	the object with which the search for an empty index is initialized. this
		 *	defaults to null.
		 * @return int
		 *	the minimum empty index into the input array.
		 */
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

		/**
		 * Filter the duplicate values from an array into a new array, optionally
		 * ignoring the case of the values (assuming they are strings?). A new array
		 * is returned, the input array is left unchanged.
		 *
		 * @param array[] $array
		 *	the array to filter.
		 * @param bool $ignore_case
		 *	true if the case of the values in the array should be ignored, false otherwise.
		 * @return
		 *	a new array containing only the unique elements of the input array.
		 */
		public static function array_remove_duplicates(array $array, $ignore_case=false){
			return ($ignore_case == true ? self::array_iunique($array) : array_unique($array));
		}

		/**
		 * Test whether a value is in an array based on string comparison, ignoring
		 * the case of the values.
		 *
		 * @param mixed $needle
		 *	the object to search the array for.
		 * @param array[] $haystack
		 *	the array to search for the $needle.
		 * @return bool
		 *	true if the $needle is in the $haystack, false otherwise.
		 */
		public static function in_iarray($needle, array $haystack){
			foreach($haystack as $key => $value){
				if(strcasecmp($value, $needle) == 0) return true;
			}
			return false;
		}

    		/**
		 * Filter the input array for duplicates, treating each element in the array
		 * as a string and comparing them using a case insensitive comparison function.
		 *
		 * @param array[] $array
		 *	the array to filter.
		 * @return
		 *	a new array containing only the unique elements of the input array.
		 */
		public static function array_iunique(array $array){
			$tmp = array();
			foreach($array as $key => $value){
				if(!self::in_iarray($value, $tmp)){
					$tmp[$key] = $value;
				}
			}
			return $tmp;
		}

		/**
		 * Convert an array into an XML fragment amd append it to an existing
		 * XML element. Any arrays contained as elements in the input array will
		 * also be recursively formatted and appended to the input XML fragment.
		 * The input XML element will be modified as a result of calling this.
		 *
         * @uses XMLElement
		 * @param XMLElement $parent
		 *	the XML element to append the formatted array data to.
		 * @param array[] $data
		 *	the array to format and append to the XML fragment.
		 * @param bool $validate
		 *	true if the formatted array data should be validated as it is
		 *	constructed, false otherwise.
		 */
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

				elseif($validate == true && !self::validateXML(self::sanitize($value), $errors, false, new XSLTProcess)){
					return;
				}
				else{
					$child->setValue(self::sanitize($value));
				}

				$parent->appendChild($child);
			}
		}

		/**
		 * Create a file at the input path with the (optional) input permissions
		 * with the input content. This function will ignore errors in opening,
		 * writing, closing and changing the permissions of the resulting file.
		 * If opening or writing the file fail then this will return false.
		 *
		 * @param string $file
		 *	the path of the file to write.
		 * @param mixed $data
		 *	the data to write to the file.
		 * @param int|null $perm (optional)
		 *	the permissions as an octal number to set set on the resulting file.
		 *	this defaults to 0644 (if omitted or set to null)
         * @param string $mode (optional)
         * the mode that the file should be opened with, defaults to 'w'. See modes
         * at http://php.net/manual/en/function.fopen.php
		 * @return bool
		 *	true if the file is successfully opened, written to, closed and has the
		 *	required permissions set. false, otherwise.
		 */
		public static function writeFile($file, $data, $perm = 0644, $mode = 'w'){
			if(is_null($perm)) $perm = 0644;

			if(!$handle = @fopen($file, $mode)) {
				return false;
			}

			if(@fwrite($handle, $data, strlen($data)) === false) {
				return false;
			}

			fclose($handle);
			chmod($file, intval($perm, 8));

			return true;
		}

		/**
		 * Delete a file at a given path, silently ignoring errors depending
		 * on the value of the input variable $silent.
		 *
		 * @param string $file
		 *	the path of the file to delete
		 * @param bool $silent (optional)
		 *	true if an exception should be raised if an error occurs, false
		 *	otherwise. this defaults to true.
		 * @return bool
		 *	true if the file is successfully unlinked, if the unlink fails and
		 *	silent is set to true then an exception is thrown. if the unlink
		 *	fails and silent is set to false then this returns false.
		 */
		public static function deleteFile($file, $slient=true){
			if(!@unlink($file)){
				if($slient == false){
					throw new Exception(__('Unable to remove file - %s', array($file)));
				}

				return false;
			}

			return true;
		}

		/**
		 * Extract the file extension from the input file path.
		 *
		 * @param string $file
		 *	the path of the file to extract the extension of.
		 * @return array[string]string
		 *	an array with a single key 'extension' and a value of the extension
		 *	of the input path.
		 */
		public static function getExtension($file){
			return pathinfo($file, PATHINFO_EXTENSION);
		}

		/**
		 * Construct a multi-dimensional array that reflects the directory
		 * structure of a given path.
		 *
		 * @param string $dir (optional)
		 *	the path of the directory to construct the multi-dimensional array
		 *	for. this defaults to '.'.
		 * @param string $filter (optional)
		 *	A regular expression to filter the directories. This is positive filter, ie.
		 * if the filter matches, the directory is included. Defaults to null.
		 * @param bool $recurse (optional)
		 *	true if sub-directories should be traversed and reflected in the
		 *	resulting array, false otherwise.
		 * @param mixed $strip_root (optional)
		 *	null if the $dir should be stripped from the entries in the array.
		 *	anything else if $dir should be retained. this defaults to null.
		 * @param array $exclude (optional)
		 *	ignore directories listed in this array. this defaults to an empty array.
		 * @param bool $ignore_hidden (optional)
		 *	ignore hidden directory (i.e.directories that begin with a period). this defaults
		 *	to true.
		 * @return null|array[]
		 *	return the array structure reflecting the input directory or null if
         * the input directory is not actually a directory.
		 */
		public static function listDirStructure($dir = '.', $filter = null, $recurse = true, $strip_root = null, $exclude = array(), $ignore_hidden = true) {
			if (!is_dir($dir)) return null;

			$files = array();
			foreach (scandir($dir) as $file) {
				if (
					($file == '.' or $file == '..')
					or ($ignore_hidden and $file{0} == '.')
					or !is_dir("$dir/$file")
					or in_array(array($file, "$dir/$file"), $exclude)
				) continue;

				if(!is_null($filter)) {
					if(!preg_match($filter, $file)) continue;
				}

				$files[] = str_replace($strip_root, '', $dir) ."/$file/";

				if ($recurse) {
					$files = @array_merge($files, self::listDirStructure("$dir/$file", $filter, $recurse, $strip_root, $exclude, $ignore_hidden));
				}
			}

			return $files;
		}

		/**
		 * Construct a multi-dimensional array that reflects the directory
		 * structure of a given path grouped into directory and file keys
         * matching any input constraints.
		 *
		 * @param string $dir (optional)
		 *	the path of the directory to construct the multi-dimensional array
		 *	for. this defaults to '.'.
		 * @param array|string $filters (optional)
		 *	either a regular expression to filter the files by or an array of
		 *	files to include.
		 * @param bool $recurse (optional)
		 *	true if sub-directories should be traversed and reflected in the
		 *	resulting array, false otherwise.
		 * @param string $sort (optional)
		 *	'asc' if the resulting filelist array should be sorted, anything else otherwise.
		 *	this defaults to 'asc'.
		 * @param mixed $strip_root (optional)
		 *	null if the $dir should be stripped from the entries in the array.
		 *	anything else if $dir should be retained. this defaults to null.
		 * @param array $exclude (optional)
		 *	ignore files listed in this array. this defaults to an empty array.
		 * @param bool $ignore_hidden (optional)
		 *	ignore hidden files (i.e. files that begin with a period). this defaults
		 *	to true.
		 * @return null|array[dirlist,filelist]
		 *	return the array structure reflecting the input directory or null if
         * the input directory is not actually a directory.
		 */
	    public static function listStructure($dir=".", $filters=array(), $recurse=true, $sort="asc", $strip_root=NULL, $exclude=array(), $ignore_hidden=true){
			if(!is_dir($dir)) return null;

            // Check to see if $filters is a string containing a regex, or an array of file types
            if(is_array($filters) && !empty($filters)) {
                $filter_type = 'file';
            }
            else if(is_string($filters)) {
                $filter_type = 'regex';
            }
            else {
                $filter_type = null;
            }
		    $files = array();

			foreach(scandir($dir) as $file) {
            	if (
					($file == '.' or $file == '..')
					or ($ignore_hidden and $file{0} == '.')
					or in_array(array($file, "$dir/$file"), $exclude)
				) continue;

                if(is_dir("$dir/$file")) {
                    if($recurse) {
                        $files[str_replace($strip_root, '', $dir) . "/$file/"] = self::listStructure("$dir/$file", $filters, $recurse, $sort, $strip_root, $exclude, $ignore_hidden);
                    }

                    $files['dirlist'][] = $file;
                }
                else if($filter_type == 'regex') {
                    if(preg_match($filters, $file)){
                        $files['filelist'][] = $file;
                    }
                }
                else if($filter_type == 'file') {
                    if(in_array(self::getExtension($file), $filters)) {
                        $files['filelist'][] = $file;
                    }
                }
                else if(is_null($filter_type)){
                    $files['filelist'][] = $file;
                }
            }

            if(is_array($files['filelist'])) {
                ($sort == 'desc') ? rsort($files['filelist']) : sort($files['filelist']);
            }

			return $files;
		}

		/**
		 * Compare two file structures based on their modification time. Should only
		 * be used in the context of a sort function such as usort. For example:
		 * <code>
		 *	usort($files, array('General', 'filemtimeSort'));
		 * </code>
		 *
		 * @param array $f1
		 *	the first file structure array to compare.
		 * @param array $f2
		 *	the second file structure array to compare $f1 to.
		 * @return int
		 *	<1, 0, >1 if $f1 is less than, equal to or greater than $f2.
		 */
		public static function filemtimeSort($f1, $f2){
			return filemtime($f1['path'] . '/' . $f1['name']) - filemtime($f1['path'] . '/' . $f1['name']);
		}

		/**
		 * Compare two file structure arrays based on their name. Names are
		 * compared alphabetically. Should only be used in the context of a
		 * sort function such as usort. For example:
		 * <code>
		 *	usort($files, array('General', 'fileSort'));
		 * </code>
		 *
		 * @param array $f1
		 *	the first file structure array to compare.
		 * @param array $f2
		 *	the second file structure array to compare $f1 to.
		 * @return int
		 *	<1, 0, >1 if $f1 is less than, equal to or greater than $f2.
		 */
		public static function fileSort($f1, $f2){
			return strcmp($f1['name'], $f2['name']);
		}

		/**
		 * Compare two file structure arrays based on their name. Names are compared
		 * alphabetically reversed. For example "z" is less than "a". Should only
		 * be used in the context of a sort function such as usort. For example:
		 * <code>
		 *	usort($files, array('General', 'fileSortR'));
		 * </code>
		 *
		 * @param array $f1
		 *	the first file structure array to compare.
		 * @param array $f2
		 *	the second file structure array to compare $f1 to.
		 * @return int
		 *	<1, 0, >1 if $f2 is less than, equal to or greater than $f1.
		 */
		public static function fileSortR($f1, $f2){
			return strcmp($f2['name'], $f1['name']);
		}

		/**
		 * Count the number of words in a string. Words are delimited by "spaces".
		 * The characters included in the set of "spaces" are:
		 *	'&#x2002;', '&#x2003;', '&#x2004;', '&#x2005;',
		 *	'&#x2006;', '&#x2007;', '&#x2009;', '&#x200a;',
		 *	'&#x200b;', '&#x2002f;', '&#x205f;'
		 * Any html/xml tags are first removed by strip_tags() and any included html
		 * entities are decoded. The resulting string is then split by the above set
		 * of spaces and the resulting size of the resulting array returned.
		 *
		 * @uses strip_tags()
		 * @param string $string
		 *	the string from which to count the contained words.
		 * @return int
		 *	the number of words contained in the input string.
		 */
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

		/**
		 * Truncate a string to a given length. Newlines are replaced with br
		 * html elements and html tags are removed from the string. If the resulting
		 * string contains only spaces then null is returned. If the resulting string
		 * is less than the input length then it is returned. If the option to
		 * truncate the string to a space character is provided then the string is
		 * truncated to the character prior to the last space in the string. Words
		 * (contiguous non-' ' characters) are then removed from the end of the string
		 * until the length of resulting string is within the input bound. Initial
		 * and trailing spaces are removed. Provided the user requested an
		 * ellipsis suffix and the resulting string is shorter than the input string
		 * then the ellipses are appended to the result which is then returned.
		 *
		 * @param string $string
		 *	the string to truncate.
		 * @param int maxChars (optional)
		 *	the maximum length of the string to truncate the input string to. this
		 *	defaults to 200 characters.
		 * @param bool $appendHellip (optional)
		 *	true if the ellipses should be appended to the result in circumstances
		 *	where the result is shorter than the input string. false otherwise. this
		 *	defaults to false.
		 * @param bool $truncateToSpace
		 *	true if the string is to be truncated to the last space prior to removing
		 *	any words necessary to satisfy the input length constraint. false otherwise.
		 *	this defaults to false.
		 * @return null|string
		 *	if the resulting string contains only spaces then null is returned. otherwise
		 *	a string that satisfies the input constraints.
		 */
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

		/**
		 * Move a file from the source path to the destination path and name and
		 * set its permissions to the input permissions. This will ignore errors
		 * in the is_uploaded_file(), move_uploaded_file() and chmod() functions.
		 *
		 * @param string $dest_path
		 *	the file path to which the source file is to be moved.
		 * @param string #dest_name
		 *	the file name within the file path to which the source file is to be moved.
		 * @parm string $tmp_name
		 *	the full path name of the source file to move.
		 * @param number $perm (optional)
		 *	the permissions to apply to the moved file. this defaults to 0777.
		 * @return bool
		 *	true if the file was moved and its permissions set as required. false otherwise.
		 */
		public static function uploadFile($dest_path, $dest_name, $tmp_name, $perm=0777){

			// Upload the file
			if(@is_uploaded_file($tmp_name)) {

				$dest_path = rtrim($dest_path, '/') . '/';

				// Try place the file in the correction location
				if(@move_uploaded_file($tmp_name, $dest_path . $dest_name)){
					chmod($dest_path . $dest_name, intval($perm, 8));
					return true;
				}
			}

			// Could not move the file
			return false;

		}

		/**
		 * Format a number of bytes in human readable format. This will append MB as
		 * appropriate for values greater than 1,024*1,024, KB for values between
		 * 1,024 and 1,024*1,024-1 and bytes for values between 0 and 1,024.
		 *
		 * @param int $file_size
		 *	the number to format.
		 * @return string
		 *	the formatted number.
		 */
		public static function formatFilesize($file_size){

			$file_size = intval($file_size);

			if($file_size >= (1024 * 1024)) 	$file_size = number_format($file_size * (1 / (1024 * 1024)), 2) . ' MB';
			elseif($file_size >= 1024) 			$file_size = intval($file_size * (1/1024)) . ' KB';
			else 								            $file_size = intval($file_size) . ' bytes';

			return $file_size;
		}

		/**
		 * Construct an XML fragment that reflects the structure of the input timestamp.
		 *
		 * @uses DataTimeObj to manipulate the timestamp value.
         * @uses XMLElement
		 * @param number $timestamp
		 *	the timestamp to construct the XML element from.
		 * @param string $element (optional)
		 *	the name of the element to append to the namespace of the constructed XML.
		 *	this defaults to "date".
		 * @param string $namespace (optional)
		 *	the namespace in which the resulting XML enitity will reside. this defaults
		 *	to null.
		 * @return bool|XMLElement
		 *	false if there is no XMLElement class on the system, the constructed XML element
		 *	otherwise.
		 */
		public static function createXMLDateObject($timestamp, $element='date', $namespace=NULL){
			if(!class_exists('XMLElement')) return false;

			$xDate = new XMLElement(
                (!is_null($namespace) ? $namespace . ':' : '') . $element,
				DateTimeObj::get('Y-m-d', $timestamp),
				array(
                        'time' => DateTimeObj::get('H:i', $timestamp),
                        'weekday' => DateTimeObj::get('N', $timestamp)
				)
            );

			return $xDate;

		}

        /**
		 * Construct an XML fragment that describes a pagination structure.
		 *
		 * @param int $total_entries (optional)
		 *	the total number of entries that this structure is paginating. this
		 *	defaults to 0.
		 * @param int $total_pages (optional)
		 *	the total number of pages within the pagination structure. this defaults
		 *	to 0.
		 * @param int $entries_per_page (optional)
		 *	the number of entries per page. this defaults to 1.
		 * @param int $current_page (optional)
		 *	the current page within the total number of pages within this pagination
		 *	structure. this defaults to 1.
		 * @return XMLElement
		 *	the constructed XML fragment.
		 */
		public static function buildPaginationElement($total_entries=0, $total_pages=0, $entries_per_page=1, $current_page=1){

			$pageinfo = new XMLElement('pagination');

			$pageinfo->setAttribute('total-entries', $total_entries);
			$pageinfo->setAttribute('total-pages', $total_pages);
			$pageinfo->setAttribute('entries-per-page', $entries_per_page);
			$pageinfo->setAttribute('current-page', $current_page);

			return $pageinfo;

		}

        /**
         * Uses SHA1 or MD5 to create a hash based on some input
		 * This function is currently very basic, but would allow
		 * future expansion. Salting the hash comes to mind.
         *
         * @param string $input
         * the string to be hashed
         * @param string $algorithm
         * a valid PHP function handle
         * @return string
         * the hashed string
         */
		public static function hash($input, $algorithm='sha1'){
			return call_user_func($algorithm, $input);
		}

	}