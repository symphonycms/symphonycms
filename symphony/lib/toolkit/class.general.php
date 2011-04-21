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
		 * The end-of-line constant.
		 * @var string
		 * @deprecated This will be removed in the next version of Symphony
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
		 * Validate a string against a set of regular expressions.
		 *
		 * @param array|string $string
		 *	string to operate on
		 * @param array|string $rule
		 *	a single rule or array of rules
		 * @return boolean
		 *	false if any of the rules in $rule do not match any of the strings in
		 *	`$string`, return true otherwise.
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
		 * @param integer $spaces (optional)
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
		 *	filename, xml document as a string, or arbitrary string
		 * @param pointer &$errors
		 *	pointer to an array which will contain any validation errors
		 * @param boolean $isFile (optional)
		 *	if this is true, the method will attempt to read from a file, `$data`
		 *	instead.
		 * @param mixed $xsltProcessor (optional)
		 *	if set, the validation will be done using this XSLT processor rather
		 *	than the built in XML parser. the default is null.
		 * @param string $encoding (optional)
		 *	if no XML header is expected, than this should be set to match the
		 *	encoding of the XML
		 * @return boolean
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
		 * @param array &$arr
		 *	Pointer to an array to operate on. Can be multi-dimensional.
		 */
		public static function cleanArray(Array &$arr) {
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
		 * `
		 * array(1 =>
		 *			array('key' => 'value'),
		 *		2 =>
		 *			array('key2' => 'value2', 'key3' => 'value3')
		 *		)
		 * `
		 * will flatten to:
		 * `array('1.key' => 'value', '2.key2' => 'value2', '2.key3' => 'value3')`
		 *
		 * @param array &$source
		 *	The array to flatten, passed by reference
		 * @param array &$output (optional)
		 *	The array in which to store the flattened input, passed by reference.
		 *  if this is not provided then a new array will be created.
		 * @param string $path (optional)
		 *	the current prefix of the keys to insert into the output array. this
		 *	defaults to null.
		 */
		public static function flattenArray(Array &$source, &$output = null, $path = null) {
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
		 * `
		 * array(1 =>
		 *			array('key' => 'value'),
		 *		2 =>
		 *			array('key2' => 'value2', 'key3' => 'value3')
		 *		)
		 * `
		 * will flatten to:
		 * `array('1:key' => 'value', '2:key2' => 'value2', '2:key3' => 'value3')`
		 *
		 *
		 * @param array &$output
		 *	The array in which to store the flattened input, passed by reference.
		 * @param array &$source
		 *	The array to flatten, passed by reference
		 * @param string $path
		 *	the current prefix of the keys to insert into the output array.
		 */
		protected static function flattenArraySub(Array &$output, Array &$source, $path) {
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
		 * Allows you to send emails. It initializes the core email class.
		 *
		 * @deprecated Since Symphony 2.2
		 * @param string $to_email
		 *  email of the recipient
		 * @param string $from_email
		 *  the from email address. This is usually your email
		 * @param string $from_name
		 *  the name of the sender
		 * @param string $subject
		 *  subject of the email
		 * @param string $message
		 *  contents of the email
		 * @param array $additional_headers
		 *  an array containing additional email headers. This will NOT work
		 *  for Content-Type header fields which will be added/overwritten by
		 *  the email gateways.)
		 * @return boolean
		 *  true on success
		 */
		public static function sendEmail($to_email, $from_email, $from_name, $subject, $message, array $additional_headers = array()) {

			try{
				$email = Email::create();

				if (!empty($additional_headers)) {
					foreach ($additional_headers as $name => $body) {
						$email->appendHeaderField($name, $body);
					}
				}
				$email->sender_name = $from_name;
				$email->sender_email_address = $from_email;

				$email->recipients = $to_email;
				$email->text_plain = $message;
				$email->subject = $subject;

				return $email->send();
			}
			catch(EmailGatewayException $e){
				throw new SymphonyErrorPage('Error sending email. ' . $e->getMessage());
			}
			catch(EmailException $e){
				throw new SymphonyErrorPage('Error sending email. ' . $e->getMessage());
			}
		}

		/**
		 * Given a string, this will clean it for use as a Symphony handle. Preserves multi-byte characters.
		 *
		 * @since Symphony 2.2.1
		 * @param string $string
		 *	String to be cleaned up
		 * @param int $max_length
		 *	The maximum number of characters in the handle
		 * @param string $delim
		 *	All non-valid characters will be replaced with this
		 * @param boolean $uriencode
		 *	Force the resultant string to be uri encoded making it safe for URLs
		 * @param array $additional_rule_set
		 *	An array of REGEX patterns that should be applied to the `$string`. This
		 *	occurs after the string has been trimmed and joined with the `$delim`
		 * @return string
		 *	Returns resultant handle
		 */
		public static function createHandle($string, $max_length=255, $delim='-', $uriencode=false, $additional_rule_set=NULL) {

			$max_length = intval($max_length);

			// Strip out any tag
			$string = strip_tags($string);

			// Remove punctuation
			$string = preg_replace('/[\\.\'"]+/', NULL, $string);

			// Trim it
			if($max_length != NULL && is_numeric($max_length)) $string = General::limitWords($string, $max_length);

			// Replace spaces (tab, newline etc) with the delimiter
			$string = preg_replace('/[\s]+/', $delim, $string);

			// Find all legal characters
			preg_match_all('/[^<>?@:!-\/\[-`ëí;‘’…]+/u', $string, $matches);

			// Join only legal character with the $delim
			$string = implode($delim, $matches[0]);

			// Allow for custom rules
			if(is_array($additional_rule_set) && !empty($additional_rule_set)) {
				foreach($additional_rule_set as $rule => $replacement) $string = preg_replace($rule, $replacement, $string);
			}

			// Remove leading or trailing delim characters
			$string = trim($string, $delim);

			// Encode it for URI use
			if($uriencode) $string = urlencode($string);

			// Make it lowercase
			$string = strtolower($string);

			return $string;

		}

		/**
		 * Given a string, this will clean it for use as a filename. Preserves multi-byte characters.
		 *
		 * @since Symphony 2.2.1
		 * @param string $string
		 *	String to be cleaned up
		 * @param string $delim
		 *	Replacement for invalid characters
		 * @return string
		 *	Returns created filename
		 */
		public static function createFilename($string, $delim='-') {

			// Strip out any tag
			$string = strip_tags($string);

			// Find all legal characters
			$count = preg_match_all('/[\p{L}\w:;.,+=~]+/u', $string, $matches);
			if($count <= 0 || $count == false) {
				preg_match_all('/[\w:;.,+=~]+/', $string, $matches);
			}

			// Join only legal character with the $delim
			$string = implode($delim, $matches[0]);

			// Remove leading or trailing delim characters
			$string = trim($string, $delim);

			// Make it lowercase
			$string = strtolower($string);

			return $string;

		}

		/**
		 * Extract the first `$val` characters of the input string. If `$val`
		 * is larger than the length of the input string then the original
		 * input string is returned.
		 *
		 * @param string $str
		 *	the string to operate on
		 * @param integer $val
		 *	the number to compare lengths with
		 * @return string|boolean
		 *	the resulting string or false on failure.
		 */
		public static function substrmin($str, $val){
			return(substr($str, 0, min(strlen($str), $val)));
		}

		/**
		 * Extract the first `$val` characters of the input string. If
		 * `$val` is larger than the length of the input string then
		 * the original input string is returned
		 *
		 * @param string $str
		 *	the string to operate on
		 * @param integer $val
		 *	the number to compare lengths with
		 * @return string|boolean
		 *	the resulting string or false on failure.
		 */
		public static function substrmax($str, $val){
			return(substr($str, 0, max(strlen($str), $val)));
		}

		/**
		 * Extract the last `$num` characters from a string.
		 *
		 * @param string $str
		 *	the string to extract the characters from.
		 * @param integer $num
		 *	the number of characters to extract.
		 * @return string|boolean
		 *	a string containing the last `$num` characters of the
		 *	input string, or false on failure.
		 */
		public static function right($str, $num){
			$str = substr($str, strlen($str)-$num,  $num);
			return $str;
		}

		/**
		 * Extract the first `$num` characters from a string.
		 *
		 * @param string $str
		 *	the string to extract the characters from.
		 * @param integer $num
		 *	the number of characters to extract.
		 * @return string|boolean
		 *	a string containing the last `$num` characters of the
		 *	input string, or false on failure.
		 */
		public static function left($str, $num){
			$str = substr($str, 0, $num);
			return $str;
		}

		/**
		 * Create all the directories as specified by the input path. If the current
		 * directory already exists, this function will return true.
		 *
		 * @param string $path
		 *  the path containing the directories to create.
		 * @param integer $mode (optional)
		 *  the permissions (in octal) of the directories to create. Defaults to 0755
		 * @return boolean
		 */
		public static function realiseDirectory($path, $mode=0755){
			if(is_dir($path)) return true;

			return mkdir($path, intval($mode, 8), true);
		}

		/**
		 * Search a multi-dimensional array for a value.
		 *
		 * @param mixed $needle
		 *	the value to search for.
		 * @param array $haystack
		 *	the multi-dimensional array to search.
		 * @return boolean
		 *	true if `$needle` is found in `$haystack`.
		 *	true if `$needle` == `$haystack`.
		 *	true if `$needle` is found in any of the arrays contained within `$haystack`.
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
		 * @param array $needles
		 *	the values to search the `$haystack` for.
		 * @param array $haystack
		 *	the in which to search for the `$needles`
		 * @return boolean
		 *	true if any of the `$needles` are in `$haystack`,
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
		 * is expected to conform to the structure of the `$_FILES` variable.
		 *
		 * @param array $filedata
		 *	the raw `$_FILES` data structured array
		 * @return array
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
		 * Merge `$_POST` with `$_FILES` to produce a flat array of the contents
		 * of both. If there is no merge_file_post_data function defined then
		 * such a function is created. This is necessary to overcome PHP's ability
		 * to handle forms. This overcomes PHP's convoluted `$_FILES` structure
		 * to make it simpler to access multi-part/formdata.
		 *
		 * @return array
		 *	a flat array containing the flattened contents of both `$_POST` and
		 *	`$_FILES`.
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
		 * @param array $array
		 *	the array to find the next index for.
		 * @param mixed $seed (optional)
		 *	the object with which the search for an empty index is initialized. this
		 *	defaults to null.
		 * @return integer
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
		 * @param array $array
		 *	the array to filter.
		 * @param boolean $ignore_case
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
		 * @param array $haystack
		 *	the array to search for the `$needle`.
		 * @return boolean
		 *	true if the `$needle` is in the `$haystack`, false otherwise.
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
		 * @param array $array
		 *	the array to filter.
		 * @return array
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
		 * Function recursively apply a function to an array's values.
		 * This will not touch the keys, just the values.
		 *
		 * @since Symphony 2.2
		 * @param string $function
		 * @param array $array
		 * @return array
		 *  a new array with all the values passed through the given `$function`
		 */
		public static function array_map_recursive($function, array $array) {
			$tmp = array();
			foreach($array as $key => $value) {
				if(is_array($value)) {
					$tmp[$key] = self::array_map_recursive($function, $value);
				}
				else {
					$tmp[$key] = call_user_func($function, $value);
				}
			}
			return $tmp;
		}

		/**
		 * Convert an array into an XML fragment and append it to an existing
		 * XML element. Any arrays contained as elements in the input array will
		 * also be recursively formatted and appended to the input XML fragment.
		 * The input XML element will be modified as a result of calling this.
		 *
		 * @param XMLElement $parent
		 *	the XML element to append the formatted array data to.
		 * @param array $data
		 *	the array to format and append to the XML fragment.
		 * @param boolean $validate
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
					$child = new XMLElement($element_name, null, array(), true);
				}

				if(is_array($value)) {
					self::array_to_xml($child, $value);
					if($child->getNumberOfChildren() == 0) continue;
				}

				else if($validate == true && !self::validateXML(self::sanitize($value), $errors, false, new XSLTProcess)) {
					continue;
				}

				else {
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
		 * @param integer|null $perm (optional)
		 *	the permissions as an octal number to set set on the resulting file.
		 *	this defaults to 0644 (if omitted or set to null)
		 * @param string $mode (optional)
		 * the mode that the file should be opened with, defaults to 'w'. See modes
		 * at http://php.net/manual/en/function.fopen.php
		 * @return boolean
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

			try {
				chmod($file, intval($perm, 8));
			}
			catch(Exception $ex) {
				// If we can't chmod the file, this is probably because our host is
				// running PHP with a different user to that of the file. Although we
				// can delete the file, create a new one and then chmod it, we run the
				// risk of losing the file as we aren't saving it anywhere. For the immediate
				// future, atomic saving isn't needed by Symphony and it's recommended that
				// if your extension require this logic, it uses it's own function rather
				// than this 'General' one.
				return true;
			}

			return true;
		}

		/**
		 * Delete a file at a given path, silently ignoring errors depending
		 * on the value of the input variable $silent.
		 *
		 * @param string $file
		 *	the path of the file to delete
		 * @param boolean $silent (optional)
		 *	true if an exception should be raised if an error occurs, false
		 *	otherwise. this defaults to true.
		 * @return boolean
		 *	true if the file is successfully unlinked, if the unlink fails and
		 *	silent is set to true then an exception is thrown. if the unlink
		 *	fails and silent is set to false then this returns false.
		 */
		public static function deleteFile($file, $slient=true){
			try {
				return unlink($file);
			}
			catch(Exception $ex) {
				if($slient == false){
					throw new Exception(__('Unable to remove file - %s', array($file)));
				}

				return false;
			}
		}

		/**
		 * Extract the file extension from the input file path.
		 *
		 * @param string $file
		 *	the path of the file to extract the extension of.
		 * @return array
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
		 * @param boolean $recurse (optional)
		 *	true if sub-directories should be traversed and reflected in the
		 *	resulting array, false otherwise.
		 * @param mixed $strip_root (optional)
		 *	null if the `$dir` should be stripped from the entries in the array.
		 *	anything else if `$dir` should be retained. this defaults to null.
		 * @param array $exclude (optional)
		 *	ignore directories listed in this array. this defaults to an empty array.
		 * @param boolean $ignore_hidden (optional)
		 *	ignore hidden directory (i.e.directories that begin with a period). this defaults
		 *	to true.
		 * @return null|array
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
					or in_array($file, $exclude)
					or in_array("$dir/$file", $exclude)
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
		 * @param boolean $recurse (optional)
		 *	true if sub-directories should be traversed and reflected in the
		 *	resulting array, false otherwise.
		 * @param string $sort (optional)
		 *	'asc' if the resulting filelist array should be sorted, anything else otherwise.
		 *	this defaults to 'asc'.
		 * @param mixed $strip_root (optional)
		 *	null if the `$dir` should be stripped from the entries in the array.
		 *	anything else if `$dir` should be retained. this defaults to null.
		 * @param array $exclude (optional)
		 *	ignore files listed in this array. this defaults to an empty array.
		 * @param boolean $ignore_hidden (optional)
		 *	ignore hidden files (i.e. files that begin with a period). this defaults
		 *	to true.
		 * @return null|array
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
					or in_array($file, $exclude)
					or in_array("$dir/$file", $exclude)
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
		 * `usort($files, array('General', 'filemtimeSort'));`
		 *
		 * @param array $f1
		 *	the first file structure array to compare.
		 * @param array $f2
		 *	the second file structure array to compare `$f1` to.
		 * @return integer
		 *	<1, 0, >1 if `$f1` is less than, equal to or greater than `$f2`.
		 */
		public static function filemtimeSort($f1, $f2){
			return filemtime($f1['path'] . '/' . $f1['name']) - filemtime($f1['path'] . '/' . $f1['name']);
		}

		/**
		 * Compare two file structure arrays based on their name. Names are
		 * compared alphabetically. Should only be used in the context of a
		 * sort function such as usort. For example:
		 * `usort($files, array('General', 'fileSort'));`
		 *
		 * @param array $f1
		 *	the first file structure array to compare.
		 * @param array $f2
		 *	the second file structure array to compare `$f1` to.
		 * @return integer
		 *	<1, 0, >1 if `$f1` is less than, equal to or greater than `$f2`.
		 */
		public static function fileSort($f1, $f2){
			return strcmp($f1['name'], $f2['name']);
		}

		/**
		 * Compare two file structure arrays based on their name. Names are compared
		 * alphabetically reversed. For example "z" is less than "a". Should only
		 * be used in the context of a sort function such as usort. For example:
		 * `usort($files, array('General', 'fileSortR'));`
		 *
		 * @param array $f1
		 *	the first file structure array to compare.
		 * @param array $f2
		 *	the second file structure array to compare `$f1` to.
		 * @return integer
		 *	<1, 0, >1 if `$f2` is less than, equal to or greater than `$f1`.
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
		 * @param string $string
		 *	the string from which to count the contained words.
		 * @return integer
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

			return str_word_count($string);
		}

		/**
		 * Truncate a string to a given length. Newlines are replaced with `<br />`
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
		 * @param integer maxChars (optional)
		 *	the maximum length of the string to truncate the input string to. this
		 *	defaults to 200 characters.
		 * @param boolean $appendHellip (optional)
		 *	true if the ellipses should be appended to the result in circumstances
		 *	where the result is shorter than the input string. false otherwise. this
		 *	defaults to false.
		 * @return null|string
		 *	if the resulting string contains only spaces then null is returned. otherwise
		 *	a string that satisfies the input constraints.
		 */
		public static function limitWords($string, $maxChars=200, $appendHellip=false) {

			if($appendHellip) $maxChars -= 1;

			$string = trim(strip_tags(nl2br($string)));
			$original_length = strlen($string);

			if($original_length == 0) return null;
			elseif($original_length < $maxChars) return $string;

			$string = trim(substr($string, 0, $maxChars));

			$array = explode(' ', $string);
			$result = '';
			$length = 0;

			while(is_array($array) && !empty($array) && $length > $maxChars){
				$length += strlen(array_pop($array)) + 1;
			}

			$result = implode(' ', $array);

			if($appendHellip && strlen($result) < $original_length)
				$result .= "&#8230;";

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
		 * @param string $tmp_name
		 *	the full path name of the source file to move.
		 * @param integer $perm (optional)
		 *	the permissions to apply to the moved file. this defaults to 0777.
		 * @return boolean
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
		 * @param integer $file_size
		 *	the number to format.
		 * @return string
		 *	the formatted number.
		 */
		public static function formatFilesize($file_size){

			$file_size = intval($file_size);

			if($file_size >= (1024 * 1024)) 	$file_size = number_format($file_size * (1 / (1024 * 1024)), 2) . ' MB';
			elseif($file_size >= 1024) 			$file_size = intval($file_size * (1/1024)) . ' KB';
			else 								$file_size = intval($file_size) . ' bytes';

			return $file_size;
		}

		/**
		 * Construct an XML fragment that reflects the structure of the input timestamp.
		 *
		 * @param integer $timestamp
		 *	the timestamp to construct the XML element from.
		 * @param string $element (optional)
		 *	the name of the element to append to the namespace of the constructed XML.
		 *	this defaults to "date".
		 * @param string $namespace (optional)
		 *	the namespace in which the resulting XML entity will reside. this defaults
		 *	to null.
		 * @return boolean|XMLElement
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
		 * @param integer $total_entries (optional)
		 *	the total number of entries that this structure is paginating. this
		 *	defaults to 0.
		 * @param integer $total_pages (optional)
		 *	the total number of pages within the pagination structure. this defaults
		 *	to 0.
		 * @param integer $entries_per_page (optional)
		 *	the number of entries per page. this defaults to 1.
		 * @param integer $current_page (optional)
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
