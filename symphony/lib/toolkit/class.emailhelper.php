<?php

	/**
	 * @package toolkit
	 */

	/**
	 * A helper class for various email functions.
	 */
	Abstract Class EmailHelper{

		/**
		 * Folding an email header field body as required by RFC2822.
		 *
		 * @param string $input header field body string
		 * @param string $max_length defaults to 75
		 * @return string folded output string
		 */
		public static function fold($input, $max_length=75) {
			return @wordwrap($input, $max_length, "\r\n ");
		}

		/**
		 * Q-encoding of a header field 'text' token or 'word' entity
		 * within a 'phrase', according to RFC2047. The output is called
		 * an 'encoded-word'; it must not be longer than 75 characters.
		 *
		 * This might be achieved with PHP's `mbstring` functions, but
		 * `mbstring` is a non-default extension.
		 *
		 * For simplicity reasons this function encodes every character
		 * except upper and lower case letters and decimal digits.
		 *
		 * RFC: 'While there is no limit to the length of a multiple-line
		 * header field, each line of a header field that contains one or
		 * more 'encoded-word's is limited to 76 characters.'
		 * The required 'folding' will not be done here, but in another
		 * helper function.
		 *
		 * This function must be 'multi-byte-sensitive' in a way that it
		 * must never scatter a multi-byte character representation across
		 * multiple encoded-words. So a 'lookahead' has been implemented,
		 * based on the fact that for UTF-8 encoded characters any byte
		 * except the first byte will have a leading '10' bit pattern,
		 * which means an ASCII value >=128 and <=191.
		 *
		 * @param string $input string to encode
		 * @param string $max_length maximum line length (default: 75 chars)
		 * @return string $output encoded string
		 *
		 * @author Elmar Bartel
		 * @author Michael Eichelsdoerfer
		 */
		public static function qEncode($input, $max_length=75) {

			// Don't encode empty strings
			if (empty($input)) return $input;

			$qpHexDigits  = '0123456789ABCDEF';
			$input_length = strlen($input);
			// Substract delimiters, character set and encoding
			$line_limit	  = $max_length - 12;
			$line_length  = 0;

			$output = '=?UTF-8?Q?';

			for ($i=0; $i < $input_length; $i++) {
				$char = $input[$i];
				$ascii = ord($char);

				// No encoding for all 62 alphanumeric characters
				if (   48 <= $ascii && $ascii <= 57
					|| 65 <= $ascii && $ascii <= 90
					|| 97 <= $ascii && $ascii <= 122 )
				{
					$replace_length = 1;
					$replace_char = $char;
				}
				// Encode space as underscore (means better readability
				// for humans)
				else if ($ascii == 32) {
					$replace_length = 1;
					$replace_char = '_';
				}
				// Encode
				else {
					$replace_length = 3;
					// Bit operation is around 10 percent faster
					// than 'strtoupper(dechex($ascii))'
					$replace_char = '='
								  . $qpHexDigits[$ascii >> 4]
								  . $qpHexDigits[$ascii & 0x0f];

					// Account for following bytes of UTF8-multi-byte
					// sequence (max. length is 4 octets, RFC3629)
					$lookahead_limit = min($i+4, $input_length);
					for ($lookahead = $i+1;
						 $lookahead < $lookahead_limit;
						 $lookahead++)
					{
						$ascii_ff = ord($input[$lookahead]);
						if (128 <= $ascii_ff && $ascii_ff <= 191) {
							$replace_char .= '='
										   . $qpHexDigits[$ascii_ff >> 4]
										   . $qpHexDigits[$ascii_ff & 0x0f];
							$replace_length += 3;
							$i++;
						}
						else break;
					}
				}
				// Would the line become too long?
				if ($line_length + $replace_length > $line_limit) {
					$output .= "?= =?UTF-8?Q?";
					$line_length = 0;
				}
				$output .= $replace_char;
				$line_length += $replace_length;
			}
			$output .= '?=';
			return $output;
		}

		/**
		 * Quoted-printable encoding of a message body (part),
		 * according to RFC2045.
		 *
		 * This function handles <CR>, <LF>, <CR><LF> and <LF><CR> sequences
		 * as 'user relevant' line breaks and encodes them as RFC822 line
		 * breaks as required by RFC2045.
		 *
		 * @param string $input string to encode
		 * @param string $max_length maximum line length (default: 76 chars)
		 * @return string $output encoded string
		 *
		 * @author Elmar Bartel
		 * @author Michael Eichelsdoerfer
		 */
		public static function qpContentTransferEncode($input, $max_length=76) {
			$qpHexDigits  = '0123456789ABCDEF';
			$input_length = strlen($input);
			$line_limit	  = $max_length;
			$line_length  = 0;
			$output		  = '';
			$blank		  = false;

			for ($i=0; $i < $input_length; $i++) {
				$char = $input[$i];
				$ascii = ord($char);

				// No encoding for spaces and tabs
				if ($ascii == 9 || $ascii == 32) {
					$blank = true;
					$replace_length = 1;
					$replace_char = $char;
				}
				// CR and LF
				elseif ($ascii == 13 || $ascii == 10) {
					// Use existing offset only.
					if ($i+1 < $input_length) {
						if (   ($ascii == 13 && ord($input[$i+1]) == 10)
							|| ($ascii == 10 && ord($input[$i+1]) == 13) )
						{
							$i++;
						}
					}
					if ($blank) {
						/**
						 * Any tab or space characters on an encoded line MUST
						 * be followed on that line by a printable character.
						 * This character may as well be the soft line break
						 * indicator.
						 *
						 * So if the preceding character is a space or a
						 * tab, we may simply insert a soft line break
						 * here, followed by a literal line break.
						 * Basically this means that we are appending
						 * an empty line (nada).
						 */
						$output .= "=\r\n\r\n";
					}
					else {
						$output .= "\r\n";
					}
					$blank = false;
					$line_length = 0;
					continue;
				}
				// No encoding within ascii range 33 to 126 (exception: 61)
				elseif (32 < $ascii && $ascii < 127 && $char != '=') {
					$replace_length = 1;
					$replace_char = $char;
					$blank = false;
				}
				// Encode
				else {
					$replace_length = 3;
					// bit operation is around 10 percent faster
					// than 'strtoupper(dechex($ascii))'
					$replace_char = '='
								  . $qpHexDigits[$ascii >> 4]
								  . $qpHexDigits[$ascii & 0x0f];
					$blank = false;
				}
				// Would the line become too long?
				if ($line_length + $replace_length > $line_limit - 1) {
					$output .= "=\r\n";
					$line_length = 0;
				}

				$output .= $replace_char;
				$line_length += $replace_length;
			}
			return $output;
		}

		/**
		 * Content-Transfer-Encoding for attachments
		 *
		 * This function will encode attachments according to RFC2045.
		 * Line length must not exceed the default (76 characters).
		 *
		 * @param string $data
		 * @return void
		 * @author Michael Eichelsdoerfer
		 */
		public static function base64ContentTransferEncode($data, $length=76) {
			return chunk_split(base64_encode($data), $length);
		}

		/**
		 * Implode an array to a comma-separated list
		 *
		 * @param string $arr input array
		 * @return string
		 */
		public static function arrayToList(array $arr = array()){
			foreach($arr as $name => $email){
				if(is_numeric($name)){
					$return[] = $email;
				}
				else{
					$return[] = $name . ' <' . $email . '>';
				}
			}
			return implode(', ', $return);
		}

		/**
		 * Gets mime type of a file.
		 *
		 * For email attachments, the mime type is very important.
		 * Uses the php 5.3 function (finfo_open), if this function is not found,
		 * fallback to a fallback function.
		 * Will use application/octet-stream as a fallback when no matches were found.
		 *
		 * @param string $file
		 * @return string MIMEtype
		 * @author Michael Eichelsdoerfer
		 * @author Huib Keemink
		 */
		public function getMimeType($file) {
			if (!empty($file)) {
				// in PHP 5.3 we can use 'finfo'
				if (function_exists('finfo_open')) {
					$finfo = finfo_open(FILEINFO_MIME_TYPE);
					$mime_type = finfo_file($finfo, $file);
					finfo_close($finfo);
				}
				/**
				 * fallback
				 * this may be removed when Symphony requires PHP 5.3
				 */
				else{
					// A few mimetypes to "guess" using the file extension.
					$mimetypes = array(
						'txt'	=> 'text/plain',
						'csv'	=> 'text/csv',
						'pdf'	=> 'application/pdf',
						'doc'	=> 'application/msword',
						'docx'	=> 'application/msword',
						'xls'	=> 'application/vnd.ms-excel',
						'ppt'	=> 'application/vnd.ms-powerpoint',
						'eps'	=> 'application/postscript',
						'zip'	=> 'application/zip',
						'gif'	=> 'image/gif',
						'jpg'	=> 'image/jpeg',
						'jpeg'	=> 'image/jpeg',
						'png'	=> 'image/png',
						'mp3'	=> 'audio/mpeg',
						'mp4a'	=> 'audio/mp4',
						'aac'	=> 'audio/x-aac',
						'aif'	=> 'audio/x-aiff',
						'aiff'	=> 'audio/x-aiff',
						'wav'	=> 'audio/x-wav',
						'wma'	=> 'audio/x-ms-wma',
						'mpeg'	=> 'video/mpeg',
						'mpg'	=> 'video/mpeg',
						'mp4'	=> 'video/mp4',
						'mov'	=> 'video/quicktime',
						'avi'	=> 'video/x-msvideo',
						'wmv'	=> 'video/x-ms-wmv',
					);
					$extension = substr(strrchr($file, '.'), 1);
					if($mimetypes[strtolower($extension)] != null){
						$mime_type = $mimetypes[$extension];
					}
					else{
						$mime_type = 'application/octet-stream';
					}
				}

				return $mime_type;
			}
			return false;
		}

	}
